<?php

namespace App\Http\Controllers\Api\V1\Drive;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\Node;
use App\Services\DocumentDriveService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class NodeController extends Controller
{
    protected $driveService;

    public function __construct(DocumentDriveService $driveService)
    {
        $this->driveService = $driveService;
    }

    /**
     * List files and folders.
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $business = $user->resolveBusiness(); // Helper method on User model

        if (!$business) {
            return response()->json(['message' => 'No business found'], 403);
        }

        $parentId = $request->query('parent_id');

        $nodes = Node::where('business_id', $business->id)
            ->where('parent_id', $parentId)
            ->with('latestVersion') // Eager load version info
            ->orderByRaw("type = 'folder' DESC") // Folders first
            ->orderBy('name', 'asc')
            ->get();

        // Breadcrumbs logic
        $breadcrumbs = [];
        if ($parentId) {
            $current = Node::find($parentId);
            while ($current) {
                array_unshift($breadcrumbs, ['id' => $current->id, 'name' => $current->name]);
                $current = $current->parent;
            }
        }
        array_unshift($breadcrumbs, ['id' => null, 'name' => 'Home']);

        return response()->json([
            'nodes' => $nodes,
            'breadcrumbs' => $breadcrumbs
        ]);
    }

    /**
     * Create a new folder or upload a file.
     */
    public function store(Request $request)
    {
        $request->validate([
            'parent_id' => 'nullable|exists:dms_nodes,id',
            // If type is not provided, try to infer or validate both
            // Simplified for now: expect type
            'type' => 'required|in:folder,file',
            'name' => 'required_if:type,folder|string|max:255',
            'file' => 'required_if:type,file|file|max:50000', // 50MB max
        ]);

        $user = $request->user();
        $business = $user->resolveBusiness();

        if ($request->type === 'folder') {
            try {
                $folder = $this->driveService->createFolder(
                    $business,
                    $user,
                    $request->name,
                    $request->parent_id
                );
                return response()->json(['message' => 'Folder created', 'node' => $folder], 201);
            } catch (\Exception $e) {
                return response()->json(['message' => $e->getMessage()], 409);
            }
        }

        if ($request->type === 'file') {
            try {
                // Ensure file is present
                if (!$request->hasFile('file')) {
                    return response()->json(['message' => 'No file uploaded'], 400);
                }

                $fileNode = $this->driveService->uploadFile(
                    $business,
                    $user,
                    $request->file('file'),
                    $request->parent_id
                );
                return response()->json(['message' => 'File uploaded', 'node' => $fileNode], 201);
            } catch (\Exception $e) {
                return response()->json(['message' => 'Upload failed: ' . $e->getMessage()], 500);
            }
        }
    }

    /**
     * Delete a file or folder (Soft Delete).
     */
    public function destroy(Request $request, $id)
    {
        $user = $request->user();
        $business = $user->resolveBusiness();

        $node = Node::where('business_id', $business->id)->where('id', $id)->firstOrFail();

        // Permission check could go here (e.g. only owner or admin)

        $this->driveService->deleteNode($node);

        return response()->json(['message' => 'Item moved to trash']);
    }

    /**
     * List trashed items.
     */
    public function trash(Request $request)
    {
        $user = $request->user();
        $business = $user->resolveBusiness();

        if (!$business)
            return response()->json(['message' => 'No business found'], 403);

        $nodes = Node::onlyTrashed()
            ->where('business_id', $business->id)
            ->orderBy('deleted_at', 'desc')
            ->get();

        return response()->json(['nodes' => $nodes]);
    }

    /**
     * Restore a trashed item.
     */
    public function restore(Request $request, $id)
    {
        $user = $request->user();
        $business = $user->resolveBusiness();

        $node = Node::onlyTrashed()
            ->where('business_id', $business->id)
            ->where('id', $id)
            ->firstOrFail();

        $this->driveService->restoreNode($node);

        return response()->json(['message' => 'Item restored', 'node' => $node->fresh()]);
    }

    /**
     * Permanently delete an item.
     */
    public function forceDelete(Request $request, $id)
    {
        $user = $request->user();
        $business = $user->resolveBusiness();

        $node = Node::withTrashed()
            ->where('business_id', $business->id)
            ->where('id', $id)
            ->firstOrFail();

        // Strict permission check: Only Owner/Admin should force delete?
        // For now, allow business members.

        $this->driveService->forceDeleteNode($node);

        return response()->json(['message' => 'Item permanently deleted']);
    }

    /**
     * Preview/download a file.
     */
    public function preview(Request $request, $id)
    {
        $user = $request->user();
        $business = $user->resolveBusiness();

        $node = Node::where('business_id', $business->id)
            ->where('id', $id)
            ->where('type', 'file')
            ->firstOrFail();

        // Get latest version
        $version = $node->latestVersion;
        if (!$version) {
            return response()->json(['message' => 'No file version found'], 404);
        }

        // For local storage, stream the file
        $path = $version->storage_path;
        if (!Storage::disk('local')->exists($path)) {
            return response()->json(['message' => 'File not found'], 404);
        }

        return response()->file(Storage::disk('local')->path($path), [
            'Content-Type' => $node->mime_type ?? 'application/octet-stream',
            'Content-Disposition' => 'inline; filename="' . $node->name . '"'
        ]);
    }

    /**
     * Rename a node.
     */
    public function rename(Request $request, $id)
    {
        $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $user = $request->user();
        $business = $user->resolveBusiness();

        $node = Node::where('business_id', $business->id)
            ->where('id', $id)
            ->firstOrFail();

        $node->update(['name' => $request->name]);

        return response()->json(['message' => 'Item renamed', 'node' => $node]);
    }

    /**
     * Move a node to a different parent folder.
     */
    public function move(Request $request, $id)
    {
        $request->validate([
            'parent_id' => 'nullable|exists:dms_nodes,id',
        ]);

        $user = $request->user();
        $business = $user->resolveBusiness();

        $node = Node::where('business_id', $business->id)
            ->where('id', $id)
            ->firstOrFail();

        // Validate that parent is a folder (if provided)
        if ($request->parent_id) {
            $parent = Node::where('business_id', $business->id)
                ->where('id', $request->parent_id)
                ->where('type', 'folder')
                ->firstOrFail();
        }

        $node->update(['parent_id' => $request->parent_id]);

        return response()->json(['message' => 'Item moved', 'node' => $node]);
    }

    /**
     * Copy a node (duplicate it to a new location).
     */
    public function copy(Request $request, $id)
    {
        $request->validate([
            'parent_id' => 'nullable|exists:dms_nodes,id',
        ]);

        $user = $request->user();
        $business = $user->resolveBusiness();

        $sourceNode = Node::where('business_id', $business->id)
            ->where('id', $id)
            ->firstOrFail();

        // Validate that parent is a folder (if provided)
        if ($request->parent_id) {
            $parent = Node::where('business_id', $business->id)
                ->where('id', $request->parent_id)
                ->where('type', 'folder')
                ->firstOrFail();
        }

        return DB::transaction(function () use ($sourceNode, $request, $business, $user) {
            // Create a copy of the node
            $copyName = $this->generateCopyName($sourceNode->name, $request->parent_id, $business->id);

            $newNode = new Node();
            $newNode->uuid = (string) \Illuminate\Support\Str::uuid();
            $newNode->business_id = $sourceNode->business_id;
            $newNode->parent_id = $request->parent_id;
            $newNode->name = $copyName;
            $newNode->type = $sourceNode->type;
            $newNode->size = $sourceNode->size;
            $newNode->mime_type = $sourceNode->mime_type;
            $newNode->created_by = $user->id;
            $newNode->save();

            // If it's a file, copy the file versions
            if ($sourceNode->type === 'file') {
                foreach ($sourceNode->versions as $version) {
                    // Copy the physical file
                    $oldPath = $version->storage_path;
                    $extension = pathinfo($sourceNode->name, PATHINFO_EXTENSION);
                    $newPath = 'uploads/' . $business->id . '/' . uniqid() . '_' . time() . '.' . $extension;

                    if (Storage::disk('local')->exists($oldPath)) {
                        Storage::disk('local')->copy($oldPath, $newPath);
                    }

                    // Create new version
                    $newNode->versions()->create([
                        'version_number' => $version->version_number,
                        'size' => $version->size,
                        'mime_type' => $version->mime_type,
                        'storage_path' => $newPath,
                        'uploaded_by' => $version->uploaded_by,
                    ]);
                }
            }

            return response()->json(['message' => 'Item copied', 'node' => $newNode]);
        });
    }

    /**
     * Generate a unique name for copied items.
     */
    private function generateCopyName($originalName, $parentId, $businessId)
    {
        $baseName = $originalName;
        $extension = '';

        // Extract extension if it's a file
        if (strpos($originalName, '.') !== false) {
            $parts = explode('.', $originalName);
            $extension = '.' . array_pop($parts);
            $baseName = implode('.', $parts);
        }

        $copyNumber = 1;
        $newName = $baseName . ' (Copy)' . $extension;

        // Check if name exists and increment
        while (
            Node::where('business_id', $businessId)
                ->where('parent_id', $parentId)
                ->where('name', $newName)
                ->exists()
        ) {
            $copyNumber++;
            $newName = $baseName . ' (Copy ' . $copyNumber . ')' . $extension;
        }

        return $newName;
    }
}
