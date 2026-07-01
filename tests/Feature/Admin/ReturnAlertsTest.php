<?php

namespace Tests\Feature\Admin;

use App\Admin\Notifications\AdminAlertNotification;
use App\Models\User;
use App\Modules\Catalog\Models\Product;
use App\Modules\Order\Enums\OrderStatus;
use App\Modules\Order\Models\Order;
use App\Modules\Order\Models\OrderItem;
use App\Modules\Returns\Services\ReturnRequestService;
use App\Support\Money;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\Concerns\InteractsWithAdmin;
use Tests\TestCase;

class ReturnAlertsTest extends TestCase
{
    use InteractsWithAdmin, LazilyRefreshDatabase;

    private function deliveredItem(User $user): OrderItem
    {
        $product = Product::factory()->stock(5)->create();
        $order = Order::factory()->create([
            'user_id' => $user->id,
            'status' => OrderStatus::Delivered,
            'delivered_at' => now()->subDay(),
            'total' => Money::fromNaira(2000),
        ]);

        return $order->items()->create([
            'product_variant_id' => $product->primaryVariant()->id,
            'name' => $product->name,
            'sku' => $product->primaryVariant()->sku,
            'unit_price' => Money::fromNaira(2000),
            'quantity' => 1,
            'line_total' => Money::fromNaira(2000),
        ]);
    }

    public function test_a_new_return_request_alerts_returns_admins(): void
    {
        Notification::fake();
        $admin = $this->actingAsAdmin('Super Admin');

        $user = User::factory()->create(['created_at' => now()->subYear()]);
        $item = $this->deliveredItem($user);

        app(ReturnRequestService::class)->create(
            $item->order, $user,
            [['order_item_id' => $item->id, 'quantity' => 1]],
            'changed_mind', 'store_credit', null, 'idem-'.uniqid(),
        );

        Notification::assertSentTo(
            $admin,
            AdminAlertNotification::class,
            fn (AdminAlertNotification $n) => $n->title === 'New return request',
        );
    }
}
