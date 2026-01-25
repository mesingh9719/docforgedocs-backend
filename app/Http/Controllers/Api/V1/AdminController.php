<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Document;
use Illuminate\Http\Request;

class AdminController extends Controller
{
    public function stats(Request $request)
    {
        // Date Range (default 6 months for charts)
        $months = 6;
        $labels = [];
        $revenueData = [];
        $userGrowthData = [];

        for ($i = $months - 1; $i >= 0; $i--) {
            $date = now()->subMonths($i);
            $monthName = $date->format('M');
            $labels[] = $monthName;

            // Real User Monthly Count
            $userCount = User::whereYear('created_at', $date->year)
                ->whereMonth('created_at', $date->month)
                ->count();
            $userGrowthData[] = $userCount;

            // Estimated Revenue (Mock: Cumulative Users * $29)
            // This represents MRR based on total active users at that point
            $cumulativeUsers = User::where('created_at', '<=', $date->endOfMonth())->count();
            $revenueData[] = $cumulativeUsers * 29;
        }

        // Recent Activity Log (Users + Documents)
        $newUsers = User::latest()->take(5)->get()->map(function ($u) {
            return [
                'id' => 'u-' . $u->id,
                'type' => 'signup',
                'user' => $u->name,
                'detail' => 'Joined the platform',
                'time' => $u->created_at->diffForHumans(),
                'timestamp' => $u->created_at
            ];
        });

        $newDocs = Document::latest()->take(5)->with('creator')->get()->map(function ($d) {
            return [
                'id' => 'd-' . $d->id,
                'type' => 'document',
                'user' => $d->creator->name ?? 'User',
                'detail' => 'Created document: ' . substr($d->title, 0, 20) . '...',
                'time' => $d->created_at->diffForHumans(),
                'timestamp' => $d->created_at
            ];
        });

        // Merge and Sort
        $activityLog = $newUsers->concat($newDocs)->sortByDesc('timestamp')->take(8)->values();

        // Totals
        $totalUsers = User::count();
        $totalDocs = Document::count();
        $currentMRR = $totalUsers * 29; // Estimated MRR

        // Comparision (vs last month)
        $lastMonthUsers = User::where('created_at', '<=', now()->subMonth())->count();
        $usersChange = $lastMonthUsers > 0 ? round((($totalUsers - $lastMonthUsers) / $lastMonthUsers) * 100, 1) : 100;

        // Unread Inquiries
        $unreadInquiries = \App\Models\ContactInquiry::where('status', 'new')->count();

        return response()->json([
            'metrics' => [
                'total_revenue' => [
                    'value' => '$' . number_format($currentMRR),
                    'change' => '+' . $usersChange . '%', // Correlated to user growth
                    'trend' => 'up'
                ],
                'active_users' => [
                    'value' => number_format($totalUsers),
                    'change' => '+' . $usersChange . '%',
                    'trend' => 'up'
                ],
                'documents' => [
                    'value' => number_format($totalDocs),
                    'change' => '+12%', // Mocked for now or calc similar to users
                    'trend' => 'up'
                ],
                'churn_rate' => [
                    'value' => '0.8%', // Mock
                    'change' => '-0.1%',
                    'trend' => 'down' // Down is good for churn
                ],
                'unread_inquiries' => [
                    'value' => $unreadInquiries,
                    'change' => '0',
                    'trend' => 'neutral'
                ]
            ],
            'charts' => [
                'labels' => $labels,
                'revenue' => $revenueData,
                'users' => $userGrowthData
            ],
            'activity_log' => $activityLog
        ]);
    }
    public function activities(Request $request)
    {
        $query = \App\Models\ActivityLog::with('user');

        if ($request->has('type') && $request->type !== 'all') {
            $query->where('type', $request->type);
        }

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('action', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhereHas('user', function ($u) use ($search) {
                        $u->where('name', 'like', "%{$search}%");
                    });
            });
        }

        $logs = $query->latest()->paginate(20);

        return response()->json($logs);
    }
}
