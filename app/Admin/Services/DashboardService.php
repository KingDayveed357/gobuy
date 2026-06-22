<?php

namespace App\Admin\Services;

use App\Models\User;
use App\Modules\Catalog\Models\Product;
use App\Modules\Order\Enums\PaymentStatus;
use App\Modules\Order\Models\Order;
use Illuminate\Support\Collection;

class DashboardService
{
    /**
     * Headline KPIs for the dashboard.
     *
     * @return array<string, int|float>
     */
    public function metrics(): array
    {
        $paid = Order::where('payment_status', PaymentStatus::Paid->value);

        return [
            'revenue' => (float) (clone $paid)->sum('total'),
            'paid_orders' => (clone $paid)->count(),
            'pending_orders' => Order::where('payment_status', PaymentStatus::Unpaid->value)->count(),
            'customers' => User::where('role', User::ROLE_CUSTOMER)->count(),
            'products' => Product::count(),
            'low_stock' => Product::whereHas('variants', fn ($q) => $q->where('stock', '<=', 5))->count(),
        ];
    }

    /**
     * @return Collection<int, Order>
     */
    public function recentOrders(int $limit = 8)
    {
        return Order::with('items')->latest()->take($limit)->get();
    }

    /**
     * @return Collection<int, Product>
     */
    public function lowStockProducts(int $limit = 5)
    {
        return Product::with(['category', 'variants'])
            ->whereHas('variants', fn ($q) => $q->where('stock', '<=', 5))
            ->take($limit)
            ->get();
    }
}
