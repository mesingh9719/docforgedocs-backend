<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Document;
use App\Models\User;
use App\Models\ChildUser;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function stats(Request $request)
    {
        $user = Auth::user();
        $business = $user->resolveBusiness();

        $userIds = [$user->id];

        // If user is a business owner, include all team members
        if ($business && $business->user_id === $user->id) {
            // Get all child users associated with this business
            $childIds = ChildUser::where('business_id', $business->id)->pluck('user_id')->toArray();
            $userIds = array_merge($userIds, $childIds);
        }

        // Determine Date Range
        $range = $request->get('range', '6m');
        $startDate = match ($range) {
            '7d' => Carbon::now()->subDays(7),
            '30d' => Carbon::now()->subDays(30),
            '90d' => Carbon::now()->subDays(90),
            '1y' => Carbon::now()->subYear(),
            default => Carbon::now()->subMonths(6),
        };

        $dateQuery = function ($q) use ($startDate) {
            $q->where('created_at', '>=', $startDate);
        };

        // Queries
        $totalDocuments = Document::whereIn('created_by', $userIds)->where($dateQuery)->count();
        $teamSize = $business ? ChildUser::where('business_id', $business->id)->count() : 0;

        $breakdown = [
            'draft' => Document::whereIn('created_by', $userIds)->where('status', 'draft')->where($dateQuery)->count(),
            'sent' => Document::whereIn('created_by', $userIds)->where('status', 'sent')->where($dateQuery)->count(),
            'completed' => Document::whereIn('created_by', $userIds)->where('status', 'completed')->where($dateQuery)->count(),
        ];

        return response()->json([
            'total_documents' => $totalDocuments,
            'team_size' => $teamSize,
            'storage_used' => '450 MB', // Placeholder
            'breakdown' => $breakdown
        ]);
    }

    public function analytics(Request $request)
    {
        $user = Auth::user();
        $business = $user->resolveBusiness();

        $userIds = [$user->id];
        if ($business && $business->user_id === $user->id) {
            $childIds = ChildUser::where('business_id', $business->id)->pluck('user_id')->toArray();
            $userIds = array_merge($userIds, $childIds);
        }

        $range = $request->get('range', '6m');
        $labels = collect([]);
        $data = collect([]);

        // Calculate grouping based on range
        if (in_array($range, ['7d', '30d'])) {
            // Group by Day
            $days = $range === '7d' ? 6 : 29;
            for ($i = $days; $i >= 0; $i--) {
                $date = Carbon::now()->subDays($i);
                $labels->push($date->format('M d'));
                $count = Document::whereIn('created_by', $userIds)
                    ->whereDate('created_at', $date)
                    ->count();
                $data->push($count);
            }
            $startDate = Carbon::now()->subDays($days + 1); // Approx start
        } else {
            // Group by Month
            $months = match ($range) {
                '90d' => 2,
                '1y' => 11,
                default => 5
            };

            for ($i = $months; $i >= 0; $i--) {
                $date = Carbon::now()->subMonths($i);
                $labels->push($date->format('M Y'));
                $count = Document::whereIn('created_by', $userIds)
                    ->whereYear('created_at', $date->year)
                    ->whereMonth('created_at', $date->month)
                    ->count();
                $data->push($count);
            }
            $startDate = Carbon::now()->subMonths($months + 1);
        }

        // Explicit Start Date for Breakdown (matching range exactly)
        $startDate = match ($range) {
            '7d' => Carbon::now()->subDays(7),
            '30d' => Carbon::now()->subDays(30),
            '90d' => Carbon::now()->subDays(90),
            '1y' => Carbon::now()->subYear(),
            default => Carbon::now()->subMonths(6),
        };

        return response()->json([
            'labels' => $labels,
            'data' => $data,
            'breakdown' => [
                'draft' => Document::whereIn('created_by', $userIds)->where('created_at', '>=', $startDate)->where('status', 'draft')->count(),
                'sent' => Document::whereIn('created_by', $userIds)->where('created_at', '>=', $startDate)->where('status', 'sent')->count(),
                'completed' => Document::whereIn('created_by', $userIds)->where('created_at', '>=', $startDate)->where('status', 'completed')->count(),
            ]
        ]);
    }
    public function activity(Request $request)
    {
        $user = Auth::user();
        $business = $user->resolveBusiness();

        $userIds = [$user->id];
        if ($business && $business->user_id === $user->id) {
            $childIds = ChildUser::where('business_id', $business->id)->pluck('user_id')->toArray();
            $userIds = array_merge($userIds, $childIds);
        }

        // Combine recent documents (created or updated)
        // For simplicity, we just look at documents created or updated by the user recently.
        $recentDocs = Document::whereIn('created_by', $userIds)
            ->latest('updated_at')
            ->take(10)
            ->with('creator')
            ->get()
            ->map(function ($doc) {
                // Determine if it was created or updated recently
                // A simple heuristic: if created_at and updated_at are close, it's "Created", else "Updated"
                $action = $doc->created_at->diffInMinutes($doc->updated_at) < 5 ? 'created' : 'updated';

                return [
                    'id' => $doc->id,
                    'user' => $doc->creator->name ?? 'Unknown',
                    'initials' => substr($doc->creator->name ?? 'U', 0, 2),
                    'action' => $action,
                    'project' => $doc->title,
                    'time' => $doc->updated_at->diffForHumans()
                ];
            });

        return response()->json($recentDocs);
    }
}
