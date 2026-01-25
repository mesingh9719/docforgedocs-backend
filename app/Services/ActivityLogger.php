<?php

namespace App\Services;

use App\Models\ActivityLog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

class ActivityLogger
{
    /**
     * Log an activity.
     *
     * @param string $action The action name (e.g., 'user.login')
     * @param string $description Human readable description
     * @param string $type info, warning, error, job
     * @param array $metadata Additional data (e.g., request input, error trace)
     * @param int|null $userId Optional user ID (defaults to Auth::id())
     */
    public static function log(string $action, string $description, string $type = 'info', array $metadata = [], ?int $userId = null)
    {
        try {
            ActivityLog::create([
                'user_id' => $userId ?? Auth::id(),
                'action' => $action,
                'description' => $description,
                'type' => $type,
                'metadata' => $metadata,
                'ip_address' => Request::ip()
            ]);
        } catch (\Exception $e) {
            // Failsafe: Don't crash the app if logging fails
            // In a real production app, we might write to a fallback file log
        }
    }

    public static function info(string $action, string $description, array $metadata = [])
    {
        self::log($action, $description, 'info', $metadata);
    }

    public static function error(string $action, string $description, array $metadata = [])
    {
        self::log($action, $description, 'error', $metadata);
    }
}
