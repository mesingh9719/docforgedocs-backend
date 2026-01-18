<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function stats()
    {
        $user = auth()->user();
        if (!$user->business) {
            // Check parent's business
            $business = $user->resolveBusiness();
            if (!$business)
                return response()->json(['message' => 'No business found'], 404);
        } else {
            $business = $user->business;
        }

        // Stats
        $totalDocuments = \App\Models\Document::where('user_id', $user->id)
            ->orWhereHas('business', function ($q) use ($business) {
                $q->where('id', $business->id);
            })->count();

        // Active members in business
        $activeMembers = \App\Models\ChildUser::where('business_id', $business->id)
            ->where('status', 'active')
            ->count() + 1; // +1 for owner

        $pendingDocs = \App\Models\Document::where('business_id', $business->id)
            ->where('status', 'Sent') // Assuming 'Sent' means pending signature
            ->count();

        return response()->json([
            'total_documents' => $totalDocuments,
            'active_members' => $activeMembers,
            'pending_documents' => $pendingDocs,
            // 'revenue' could be calculated from Invoices in future
        ]);
    }

    public function activity()
    {
        $user = auth()->user();
        $business = $user->resolveBusiness();
        if (!$business)
            return response()->json([]);

        // Fetch latest documents created/updated
        $activities = \App\Models\Document::where('business_id', $business->id)
            ->with('user')
            ->latest()
            ->take(5)
            ->get()
            ->map(function ($doc) {
                return [
                    'id' => $doc->id,
                    'user' => $doc->user->name,
                    'action' => 'updated document', // Simplified action log
                    'project' => $doc->name,
                    'time' => $doc->updated_at->diffForHumans(),
                    'initials' => substr($doc->user->name, 0, 2),
                ];
            });

        return response()->json($activities);
    }
}
