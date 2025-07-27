<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use NotificationChannels\WebPush\PushSubscription;

class WebPushController extends Controller
{
    /**
     * Subscribe to web push notifications.
     */
    public function subscribe(Request $request): JsonResponse
    {
        $request->validate([
            'subscription' => 'required|array',
            'subscription.endpoint' => 'required|string',
            'subscription.keys' => 'required|array',
            'subscription.keys.p256dh' => 'required|string',
            'subscription.keys.auth' => 'required|string',
        ]);

        $user = Auth::user();
        $subscriptionData = $request->input('subscription');

        try {
            // Delete existing subscription for this user
            PushSubscription::where('subscribable_id', $user->id)
                ->where('subscribable_type', get_class($user))
                ->delete();

            // Create new subscription
            PushSubscription::create([
                'subscribable_id' => $user->id,
                'subscribable_type' => get_class($user),
                'endpoint' => $subscriptionData['endpoint'],
                'public_key' => $subscriptionData['keys']['p256dh'],
                'auth_token' => $subscriptionData['keys']['auth'],
                'content_encoding' => 'aesgcm',
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Successfully subscribed to push notifications.',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to subscribe to push notifications.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Unsubscribe from web push notifications.
     */
    public function unsubscribe(Request $request): JsonResponse
    {
        $request->validate([
            'subscription' => 'required|array',
            'subscription.endpoint' => 'required|string',
        ]);

        $user = Auth::user();
        $endpoint = $request->input('subscription.endpoint');

        try {
            $deleted = PushSubscription::where('subscribable_id', $user->id)
                ->where('subscribable_type', get_class($user))
                ->where('endpoint', $endpoint)
                ->delete();

            if ($deleted) {
                return response()->json([
                    'success' => true,
                    'message' => 'Successfully unsubscribed from push notifications.',
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Subscription not found.',
                ], 404);
            }
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to unsubscribe from push notifications.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get subscription status.
     */
    public function status(): JsonResponse
    {
        $user = Auth::user();

        $hasSubscription = PushSubscription::where('subscribable_id', $user->id)
            ->where('subscribable_type', get_class($user))
            ->exists();

        return response()->json([
            'success' => true,
            'subscribed' => $hasSubscription,
        ]);
    }
}
