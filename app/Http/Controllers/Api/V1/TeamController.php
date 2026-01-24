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
            'role' => 'required|in:admin,editor,member,viewer',
            'permissions' => 'nullable|array',
        ]);

        $user = Auth::user();
        $business = $user->resolveBusiness();

        if (!$business) {
            return response()->json(['message' => 'No business found'], 404);
        }

        $businessId = $business->id;

        // Identify the Business Owner (Parent)
        // If current user owns the business, they are the parent.
        // If current user is a sub-user, the business owner is the parent.
        $ownerId = $business->user_id; // Assuming Business has user_id

        // We will create a user with empty password (or unusable) and set status pending.
        $newUser = User::create([
            'name' => 'Pending User', // Placeholder
            'email' => $request->email,
            'password' => Hash::make(Str::random(32)), // Random secure password
        ]);

        $token = Str::random(60);

        // Link to parent (Business Owner) because the team belongs to the Business
        $childUser = ChildUser::create([
            'parent_id' => $ownerId,
            'user_id' => $newUser->id,
            'business_id' => $businessId,
            'role' => $request->role,
            'status' => 'pending',
            'permissions' => $request->permissions, // Store permissions
            'invitation_token' => $token,
            'created_by' => $user->id, // The actual user who performed the invite
        ]);

        // Send Invitation Email
        try {
            // Use config or env for frontend URL
            $baseUrl = config('app.frontend_url', 'http://localhost:5173');
            $inviteUrl = "{$baseUrl}/accept-invite?token={$token}&email=" . urlencode($request->email);

            $businessName = $business->name;

            // Send Email using NotificationService
            $this->notificationService->sendTeamInvitation(
                $newUser->email,
                $inviteUrl,
                $businessName,
                $request->role,
                $user->name
            );

        } catch (\Exception $e) {
            // Log email failure but don't fail the request completely if possible, or do.
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

        // Authorization check: Ensure only parent/admin can update
        if ($childUser->parent_id !== Auth::id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $request->validate([
            'role' => 'sometimes|in:admin,editor,member,viewer',
            'permissions' => 'nullable|array',
        ]);

        $childUser->update($request->only(['role', 'status', 'permissions']));

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

        return response()->json([
            'message' => 'Invitation accepted successfully.',
            'token' => $token,
            'user' => new \App\Http\Resources\Api\V1\UserResource($user)
        ]);
    }
}
