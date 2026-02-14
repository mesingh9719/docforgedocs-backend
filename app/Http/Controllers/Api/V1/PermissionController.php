<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Permission;

class PermissionController extends Controller
{
    public function index()
    {
        // 1. Fetch all permissions with their roles
        $permissions = Permission::with('roles')->get();

        // 2. Fetch system roles for the matrix header
        $roles = \App\Models\Role::where('is_system', true)->pluck('label', 'name');

        // 3. Format permissions
        $formattedPermissions = $permissions->map(function ($perm) {
            return [
                'id' => $perm->id,
                'key' => $perm->name,
                'label' => $perm->label,
                'description' => $perm->description, // Added for UI tooltips
                'group' => $perm->group,
                'roles' => $perm->roles->pluck('name'),
            ];
        });

        return response()->json([
            'roles' => $roles,
            'permissions' => $formattedPermissions,
        ]);
    }
}