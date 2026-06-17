<?php

namespace App\Modules\Order\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Order\Http\Requests\TrackOrderRequest;
use App\Modules\Order\Models\Order;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class OrderController extends Controller
{
    public function success(Order $order): View
    {
        $order->load(['items', 'statusHistories', 'payment']);

        return view('storefront.orders.success', ['order' => $order]);
    }

    public function trackForm(): View
    {
        return view('storefront.orders.track');
    }

    public function track(TrackOrderRequest $request): View|RedirectResponse
    {
        $order = Order::with(['items', 'statusHistories'])
            ->where('order_number', $request->validated('order_number'))
            ->where('customer_email', $request->validated('email'))
            ->first();

        if (! $order) {
            return back()
                ->withInput()
                ->with('error', 'No order found for that order number and email.');
        }

        return view('storefront.orders.tracking', ['order' => $order]);
    }
}
