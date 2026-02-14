<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Business;
use App\Models\ChildUser;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;

use Illuminate\Support\Facades\Mail;
use App\Mail\TeamInvitation;

class TeamController extends Controller
{
    public function __construct(
        protected \App\Services\NotificationService $notificationService
    ) {
    }

    /**
     * list members
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        if (!$user->business) {
            return response()->json(['message' => 'No business found'], 404);
        }

        // Get child users linked to this business
        // We assume the authenticated user is the 'parent' or owner for now, 
        // OR we check the business_id.
        // The relationship is User -> hasOne Business.

        $members = ChildUser::where('parent_id', $user->id)
            ->with(['child:id,name,email,avatar', 'business']) // removed permissions relation for column
            ->get();

        // We don't need to load 'permissions' relation because it's a column on ChildUser now.

        return response()->json($members);
    }

    /**
     * Invite a member
     */
    public function store(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'role' => 'nullable|string', // Deprecated, but supported for now
            'role_id' => 'nullable|exists:roles,id',
            'permissions' => 'nullable|array',
        ]);

        if (!$request->role && !$request->role_id) {
            return response()->json(['message' => 'Role is required.'], 422);
        }

        $user = Auth::user();
        $business = $user->resolveBusiness();

        if (!$business) {
            return response()->json(['message' => 'No business found'], 404);
        }

        // Resolve Role ID
        $roleId = $request->role_id;
        if (!$roleId && $request->role) {
            // Lookup system role by name
            $systemRole = \App\Models\Role::where('name', $request->role)
                ->where('is_system', true)
                ->first();

            if ($systemRole) {
                $roleId = $systemRole->id;
            } else {
                // Try finding custom role by name for this business? Or just fail?
                // Let's fail if not found as system role
                return response()->json(['message' => 'Invalid role specified.'], 422);
            }
        }

        // Verify role belongs to business or is system
        $role = \App\Models\Role::find($roleId);
        if (!$role || (!$role->is_system && $role->business_id !== $business->id)) {
            return response()->json(['message' => 'Invalid role.'], 422);
        }

        $businessId = $business->id;
        $ownerId = $business->user_id;

        // Check if user already exists
        $newUser = User::where('email', $request->email)->first();

        if ($newUser) {
            // Check if already a member of *this* business
            $existingMember = ChildUser::where('business_id', $businessId)
                ->where('user_id', $newUser->id)
                ->first();

            if ($existingMember) {
                return response()->json(['message' => 'User is already a member of this team.'], 422);
            }
        } else {
            $newUser = User::create([
                'name' => 'Pending User',
                'email' => $request->email,
                'password' => Hash::make(Str::random(32)),
            ]);
        }

        $token = Str::random(60);

        // Link to parent (Business Owner)
        $childUser = ChildUser::create([
            'parent_id' => $ownerId,
            'user_id' => $newUser->id,
            'business_id' => $businessId,
            'role' => $role->name, // Keep for backward compatibility
            'role_id' => $role->id,
            'status' => 'pending',
            'permissions' => $request->permissions,
            'invitation_token' => $token,
            'created_by' => $user->id,
        ]);

        // Send Invitation
        try {
            $baseUrl = config('app.frontend_url', 'http://localhost:5173');
            $inviteUrl = "{$baseUrl}/accept-invite?token={$token}&email=" . urlencode($request->email);

            $this->notificationService->sendTeamInvitation(
                $newUser->email,
                $inviteUrl,
                $business->name,
                $role->label,
                $user->name
            );

            \App\Services\ActivityLogger::log(
                'team.invite',
                "Invited {$newUser->email} as {$role->label}",
                'info',
                ['email' => $newUser->email, 'role' => $role->name, 'role_id' => $role->id],
                $user->id
            );

        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("Failed to send invitation email: " . $e->getMessage());
        }

        return response()->json(['message' => 'Invitation sent successfully', 'data' => $childUser], 201);
    }

    /**
     * Update member role
     */
    public function update(Request $request, $id)
    {
        $childUser = ChildUser::findOrFail($id);
        $user = Auth::user();
        $business = $user->resolveBusiness();

        if (!$business) {
            return response()->json(['message' => 'No business found'], 404);
        }

        // Authorization check: Ensure only parent (owner) can update?
        // Actually, logic says owner is parent.
        if ($childUser->parent_id !== $user->id) {
            // Maybe business admin can also update?
            // For now stick to original check but ensure it matches current user
            if ($childUser->parent_id !== $user->id) {
                return response()->json(['message' => 'Unauthorized'], 403);
            }
        }

        $request->validate([
            'role' => 'sometimes|string',
            'role_id' => 'sometimes|exists:roles,id',
            'permissions' => 'nullable|array',
            'status' => 'sometimes|in:active,pending,deactivated',
        ]);

        $data = $request->only(['status', 'permissions']);

        // Handle Role Update
        if ($request->has('role_id') || $request->has('role')) {
            $roleId = $request->role_id;

            if (!$roleId && $request->role) {
                $systemRole = \App\Models\Role::where('name', $request->role)
                    ->where('is_system', true)
                    ->first();
                if ($systemRole) {
                    $roleId = $systemRole->id;
                } else {
                    // Fail if explicitly trying to set a role that doesn't resolve?
                    // Or keep old role string?
                    // Better to fail validation effectively
                    return response()->json(['message' => 'Invalid role specified.'], 422);
                }
            }

            if ($roleId) {
                $role = \App\Models\Role::find($roleId);
                // Validate role ownership
                if (!$role || (!$role->is_system && $role->business_id !== $business->id)) {
                    return response()->json(['message' => 'Invalid role.'], 422);
                }

                $data['role_id'] = $role->id;
                $data['role'] = $role->name; // Sync legacy column
            }
        }

        $childUser->update($data);

        return response()->json(['message' => 'Member updated', 'data' => $childUser]);
    }

    /**
     * Remove member
     */
    public function destroy($id)
    {
        $member = ChildUser::where('id', $id)
            ->where('business_id', Auth::user()->business->id)
            ->firstOrFail();

        $member->delete();

        return response()->json(['message' => 'Member removed']);
    }

    /**
     * Accept Invite
     */
    public function acceptInvite(Request $request)
    {
        $request->validate([
            'token' => 'required|string',
            'email' => 'required|email',
            'password' => 'required|string|min:8|confirmed',
            'name' => 'required|string|max:255',
        ]);

        // Find the invitation
        // We look for a child user record with this token
        // Also verify the email matches the user linked to that child user
        $childUser = ChildUser::where('invitation_token', $request->token)->first();

        if (!$childUser) {
            return response()->json(['message' => 'Invalid or expired invitation token.'], 404);
        }

        $user = $childUser->child;

        if (!$user || $user->email !== $request->email) {
            return response()->json(['message' => 'Invalid invitation details.'], 403);
        }

        // Update User
        $user->update([
            'name' => $request->name,
            'password' => Hash::make($request->password),
            'email_verified_at' => now(), // Auto verify since they clicked the email link
        ]);

        // Activate Membership
        $childUser->update([
            'status' => 'active',
            'invitation_token' => null, // Invalidate token
        ]);

        // Login User
        $token = $user->createToken('auth_token')->plainTextToken;

        // Notify the inviter (Parent User)
        try {
            $inviter = User::find($childUser->created_by);
            if ($inviter) {
                $inviter->notify(new \App\Notifications\InvitationAccepted($user));
            }

            \App\Services\ActivityLogger::log(
                'team.join',
                "User joined the team: {$user->name}",
                'info',
                ['email' => $user->email],
                $user->id
            );
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("Failed to send in-app notification: " . $e->getMessage());
        }

        return response()->json([
            'message' => 'Invitation accepted successfully.',
            'token' => $token,
            'user' => new \App\Http\Resources\Api\V1\UserResource($user)
        ]);
    }
}
