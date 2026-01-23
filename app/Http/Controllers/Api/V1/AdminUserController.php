<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;

class AdminUserController extends Controller
{
    public function index(Request $request)
    {
        $users = User::query()
            ->when($request->search, function ($query, $search) {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            })
            ->with(['business', 'parentUser.parent']) // Eager load business info
            ->withCount(['documents', 'childUsers'])
            ->latest()
            ->paginate(10);

        return \App\Http\Resources\Api\V1\UserResource::collection($users);
    }

    public function show(User $user)
    {
        $user->load(['business', 'documents', 'children']);
        return new \App\Http\Resources\Api\V1\UserResource($user);
    }

    // Add logic for banning/deleting users if needed
    public function destroy(User $user)
    {
        // Don't delete self
        if ($user->id === auth()->id()) {
            return response()->json(['message' => 'Cannot delete yourself'], 403);
        }

        $user->delete();
        return response()->json(['message' => 'User deleted successfully']);
    }
}
