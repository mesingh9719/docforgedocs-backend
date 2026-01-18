<?php

namespace App\Services;

use App\Models\User;
use App\Models\ChildUser;

class PermissionService
{
    /**
     * Check if a user has a specific permission within a business context.
     */
    public function hasPermission(User $user, string $permission, $businessId = null): bool
    {
        // If system super admin logic existed it would go here.

        // Get the role of the user in the business context
        // If businessId is null, use the user's current business context if configured, 
        // or the business linked to the authenticated user.

        if (!$businessId && $user->business) {
            // If user owns the business (direct owner logic if separate from child_users)
            // For this app architecture, owner is also often a "parent" but let's assume owners have full access.
            return true;
        }

        // Logic for Child Users (Team Members)
        // Find the ChildUser record linking this user to the business
        // We need the business ID effectively.

        // Simpler approach given current ChildUser structure:
        // We look for the ChildUser record where user_id = $user->id
        // If multiple businesses, we need the context. Assuming single context for now or provided businessId.

        $query = ChildUser::where('user_id', $user->id);

        if ($businessId) {
            $query->where('business_id', $businessId);
        }

        $membership = $query->first();

        // If no membership found, check if they are the OWNER (parent)
        if (!$membership) {
            // Check if they own the business with this ID
            if ($user->business && $user->business->id == $businessId) {
                return true; // Owners have all permissions
            }
            return false;
        }

        if ($membership->status !== 'active') {
            return false;
        }

        // 1. Check if specific permissions are assigned to this user (Override)
        if (!is_null($membership->permissions)) {
            return in_array($permission, $membership->permissions);
        }

        // 2. Fallback to Role-based permissions
        $role = $membership->role;
        return $this->roleHasPermission($role, $permission);
    }

    /**
     * Check if a role string has a permission based on config.
     */
    public function roleHasPermission(string $role, string $permission): bool
    {
        $config = config('permissions.permissions.' . $permission);

        if (!$config) {
            return false;
        }

        return in_array($role, $config['roles']);
    }

    /**
     * Get the full matrix for UI display.
     */
    public function getMatrix()
    {
        $permissions = config('permissions.permissions');
        $roles = config('permissions.roles');

        // Transform for easier frontend consumption
        $matrix = [];
        foreach ($permissions as $key => $details) {
            $matrix[] = [
                'key' => $key,
                'label' => $details['label'],
                'roles' => $details['roles']
            ];
        }

        return [
            'roles' => $roles,
            'permissions' => $matrix
        ];
    }
}
