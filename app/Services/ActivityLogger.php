<?php

namespace App\Services;

use App\Models\ActivityLog;
use Illuminate\Support\Facades\Auth;

class ActivityLogger
{
    /**
     * Log an activity.
     *
     * @param string $activityType
     * @param string $description
     * @return void
     */
    public static function log($activityType, $description)
    {
        ActivityLog::create([
            'user_id' => Auth::id(),
            'activity_type' => $activityType,
            'description' => $description,
        ]);
    }
}
