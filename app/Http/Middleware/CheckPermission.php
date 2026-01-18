<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Services\PermissionService;
use Illuminate\Support\Facades\Auth;

class CheckPermission
{
    protected $permissionService;

    public function __construct(PermissionService $permissionService)
    {
        $this->permissionService = $permissionService;
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string $permission): Response
    {
        if (!Auth::check()) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $user = Auth::user();

        // Check permission
        // Note: For multi-business users we would need to determine context from route/header.
        // For now, assuming current context associated with user or default.
        // If the request targets a specific business resource, validation might need that ID.
        // Assuming single business context for now for simplicity of this SaaS stage.
        $businessId = $user->business ? $user->business->id : null;

        // If the user is an owner/parent, businessId logic inside service handles it safely.

        if (!$this->permissionService->hasPermission($user, $permission, $businessId)) {
            return response()->json(['message' => 'Forbidden. You do not have the required permission: ' . $permission], 403);
        }

        return $next($request);
    }
}
