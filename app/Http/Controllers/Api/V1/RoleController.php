<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Role;
use App\Models\Permission;

class RoleController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $business = $user->resolveBusiness();

        if (!$business) {
            return response()->json(['message' => 'User does not belong to a business'], 403);
        }

        $roles = Role::where('business_id', $business->id)
            ->orWhere('is_system', true)
            ->with('permissions')
            ->get();

        return response()->json($roles);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'label' => 'required|string|max:255',
            'permissions' => 'array',
            'permissions.*' => 'exists:permissions,id',
        ]);

        $user = $request->user();
        $business = $user->resolveBusiness();

        if (!$business) {
            return response()->json(['message' => 'User does not belong to a business'], 403);
        }

        // Create slug from name if not provided or ensure uniqueness
// Since name is visible in UI as slug-like, let's just use it but scope to business
// Actually, name should be slug-like (e.g. junior-editor), label is human readable

        $role = Role::create([
            'business_id' => $business->id,
            'name' => \Illuminate\Support\Str::slug($request->name),
            'label' => $request->label,
            'description' => $request->description,
            'is_system' => false,
        ]);

        if ($request->has('permissions')) {
            $role->permissions()->sync($request->permissions);
        }

        return response()->json($role->load('permissions'), 201);
    }

    public function show(Role $role)
    {
        // Check access
        $user = request()->user();
        $business = $user->resolveBusiness();

        if ($role->business_id && $role->business_id !== $business->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        return response()->json($role->load('permissions'));
    }

    public function update(Request $request, Role $role)
    {
        $user = $request->user();
        $business = $user->resolveBusiness();

        if ($role->is_system) {
            return response()->json(['message' => 'Cannot edit system roles'], 403);
        }

        if ($role->business_id !== $business->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $request->validate([
            'label' => 'required|string|max:255',
            'permissions' => 'array',
            'permissions.*' => 'exists:permissions,id',
        ]);

        $role->update([
            'label' => $request->label,
            'description' => $request->description,
        ]);

        if ($request->has('permissions')) {
            $role->permissions()->sync($request->permissions);
        }

        return response()->json($role->load('permissions'));
    }

    public function destroy(Role $role)
    {
        $user = request()->user();
        $business = $user->resolveBusiness();

        if ($role->is_system) {
            return response()->json(['message' => 'Cannot delete system roles'], 403);
        }

        if ($role->business_id !== $business->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // Check if any users are assigned to this role? Maybe optional but good practice.
// For now, let's allow deletion, users will have null role due to onDelete('set null')

        $role->delete();

        return response()->json(['message' => 'Role deleted successfully']);
    }
}