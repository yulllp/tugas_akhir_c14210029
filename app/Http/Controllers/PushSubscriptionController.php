<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PushSubscriptionController extends Controller
{
    public function subscribe(Request $request)
    {
        /** @var \App\Models\User|null $user */
        $user = Auth::user();
        if (! $user) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $endpoint        = $request->input('endpoint');
        $p256dhKey       = $request->input('keys.p256dh');
        $authToken       = $request->input('keys.auth');
        $contentEncoding = $request->input('contentEncoding', 'aesgcm');

        $user->updatePushSubscription(
            $endpoint,
            $p256dhKey,
            $authToken,
            $contentEncoding
        );

        return response()->json(['success' => true]);
    }

    /**
     * Optional: allow users to unsubscribe manually.
     */
    public function unsubscribe(Request $request)
    {
        /** @var \App\Models\User|null $user */
        $user = Auth::user();
        if (! $user) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $endpoint = $request->input('endpoint');
        $user->deletePushSubscription($endpoint);

        return response()->json(['success' => true]);
    }
}
