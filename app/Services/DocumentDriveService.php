<?php

namespace App\Services;

use App\Models\Node;
use App\Models\FileVersion;
use App\Models\Business;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use App\Models\User;

class DocumentDriveService
{
    /**
     * Create a new folder.
     */
    public function createFolder(Business $business, User $user, string $name, ?int $parentId = null): Node
    {
        // Check uniqueness in parent
        $exists = Node::where('business_id', $business->id)
            ->where('parent_id', $parentId)
            ->where('name', $name)
            ->where('type', 'folder')
            ->exists();

        if ($exists) {
            // Auto-rename (e.g., "Folder (1)") could be implemented here
            // For now, throw exception
            throw new \Exception("A folder with this name already exists in this location.");
        }

        return Node::create([
            'uuid' => Str::uuid(),
            'business_id' => $business->id,
            'parent_id' => $parentId,
            'name' => $name,
            'type' => 'folder',
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);
    }

    /**
     * Upload a new file (or version).
     */
    public function uploadFile(Business $business, User $user, UploadedFile $file, ?int $parentId = null): Node
    {
        return DB::transaction(function () use ($business, $user, $file, $parentId) {
            $name = $file->getClientOriginalName();
            $mime = $file->getMimeType();
            $size = $file->getSize();
            // Generate checksum
            $checksum = hash_file('sha256', $file->getRealPath());

            // Check if file already exists in this folder
            $existingNode = Node::where('business_id', $business->id)
                ->where('parent_id', $parentId)
                ->where('name', $name)
                ->where('type', 'file')
                ->first();

            $storagePath = $file->store('dms/' . $business->id, 'local'); // Use 's3' in production

            if ($existingNode) {
                // Determine next version number
                $latestVersion = $existingNode->latestVersion;
                $nextVersion = $latestVersion ? $latestVersion->version_number + 1 : 1;

                // Create new version
                FileVersion::create([
                    'node_id' => $existingNode->id,
                    'storage_path' => $storagePath,
                    'version_number' => $nextVersion,
                    'checksum' => $checksum,
                    'size' => $size,
                    'created_by' => $user->id,
                ]);

                // Update Node metadata
                $existingNode->update([
                    'mime_type' => $mime,
                    'size' => $size,
                    'updated_by' => $user->id,
                ]);

                return $existingNode;
            } else {
                // Create new Node
                $node = Node::create([
                    'uuid' => Str::uuid(),
                    'business_id' => $business->id,
                    'parent_id' => $parentId,
                    'name' => $name,
                    'type' => 'file',
                    'mime_type' => $mime,
                    'size' => $size,
                    'created_by' => $user->id,
                    'updated_by' => $user->id,
                ]);

                // Create Version 1
                FileVersion::create([
                    'node_id' => $node->id,
                    'storage_path' => $storagePath,
                    'version_number' => 1,
                    'checksum' => $checksum,
                    'size' => $size,
                    'created_by' => $user->id,
                ]);

                return $node;
            }
        });
    }

    /**
     * Soft delete a node (and its children recursively).
     */
    public function deleteNode(Node $node): bool
    {
        // Soft deletes will handle simple hiding.
        // For folders, we need to delete children.
        // Eloquent 'cascade' deletes usually handle hard deletes DB-side.
        // For soft deletes, we might need to iterate if we want to "empty trash" selectively.

        // For now, basic soft delete of the target node.
        // Assuming the UI hides children if parent is trashed is risky.
        // Better: recursive soft delete.

        DB::transaction(function () use ($node) {
            $this->recursiveDelete($node);
        });

        return true;
    }

    /**
     * Restore a soft-deleted node.
     */
    public function restoreNode(Node $node): bool
    {
        // Check if parent still exists (not deleted)
        if ($node->parent_id && Node::where('id', $node->parent_id)->doesntExist()) {
            // If parent is gone, move to root
            $node->update(['parent_id' => null]);
        }

        // Restore children recursively if it's a folder?
        // Typically, if you restore a folder, you might want its contents back.
        // But Laravel soft deletes don't automatically cascade restores.
        // Let's keep it simple: restore target node. 
        // User can manually restore children if needed, or we implement recursive restore later.

        return $node->restore();
    }

    /**
     * Permanently delete a node and its files.
     */
    public function forceDeleteNode(Node $node): bool
    {
        return DB::transaction(function () use ($node) {
            // delete physical files for all versions if it's a file
            if ($node->type === 'file') {
                foreach ($node->versions as $version) {
                    Storage::disk('local')->delete($version->storage_path);
                    $version->delete(); // Hard delete from DB
                }
            }

            // If folder, recursively force delete children
            // We need to fetch children INCLUDING soft deleted ones
            $children = $node->children()->withTrashed()->get();
            foreach ($children as $child) {
                $this->forceDeleteNode($child);
            }

            return $node->forceDelete();
        });
    }

    private function recursiveDelete(Node $node)
    {
        $node->children()->each(function (Node $child) {
            $this->recursiveDelete($child);
        });
        $node->delete();
    }
}
