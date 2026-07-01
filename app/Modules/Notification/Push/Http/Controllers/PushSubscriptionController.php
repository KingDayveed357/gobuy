<?php

namespace App\Modules\Notification\Push\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Notification\Push\Http\Requests\StorePushSubscriptionRequest;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Stores/removes a browser's Web Push subscription for the signed-in admin.
 * Push is an admin-only, operational feature (customers are served by email +
 * SMS, not push). The polymorphic push_subscriptions row is handled by the
 * package's HasPushSubscriptions trait, so this controller stays thin.
 */
class PushSubscriptionController extends Controller
{
    public function store(StorePushSubscriptionRequest $request): JsonResponse
    {
        $this->subscriber($request)->updatePushSubscription(
            $request->input('endpoint'),
            $request->input('keys.p256dh'),
            $request->input('keys.auth'),
            $request->input('contentEncoding'),
        );

        return response()->json(['ok' => true]);
    }

    public function destroy(Request $request): JsonResponse
    {
        if ($endpoint = $request->input('endpoint')) {
            $this->subscriber($request)->deletePushSubscription($endpoint);
        }

        return response()->json(['ok' => true]);
    }

    private function subscriber(Request $request): Authenticatable
    {
        $subscriber = $request->user('admin');

        abort_if($subscriber === null, 403);

        return $subscriber;
    }
}
