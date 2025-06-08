<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Spatie\Activitylog\Models\Activity;

class ActivityController extends Controller
{
    public function index(Request $request)
    {
        // eager-load the user who caused it, and the subject (model) it happened on
        $activities = Activity::with(['causer', 'subject'])
            ->latest()
            ->paginate(20);

        return view('log.index', compact('activities'));
    }
}
