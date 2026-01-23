<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Document;
use Illuminate\Http\Request;

class AdminController extends Controller
{
    public function stats()
    {
        return response()->json([
            'total_users' => User::count(),
            'total_documents' => Document::count(),
            // 'active_subscriptions' => Subscription::where('status', 'active')->count(), // Placeholder until Subscription model exists
            'recent_users' => User::latest()->take(5)->get(),
        ]);
    }
}
