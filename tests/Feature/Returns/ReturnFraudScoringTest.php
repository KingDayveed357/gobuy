<?php

namespace Tests\Feature\Returns;

use App\Models\User;
use App\Modules\Catalog\Models\Product;
use App\Modules\Order\Enums\OrderStatus;
use App\Modules\Order\Models\Order;
use App\Modules\Order\Models\OrderItem;
use App\Modules\Returns\Models\ReturnRequest;
use App\Modules\Returns\Services\ReturnRequestService;
use App\Support\Money;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class ReturnFraudScoringTest extends TestCase
{
    use LazilyRefreshDatabase;

    private function deliveredItem(User $user, int $unitNaira = 2000, int $qty = 1, ?Order $order = null): OrderItem
    {
        $product = Product::factory()->stock(5)->create();
        $order ??= Order::factory()->create([
            'user_id' => $user->id,
            'status' => OrderStatus::Delivered,
            'delivered_at' => now()->subDay(),
            'total' => Money::fromNaira($unitNaira * $qty),
        ]);

        return $order->items()->create([
            'product_variant_id' => $product->primaryVariant()->id,
            'name' => $product->name,
            'sku' => $product->primaryVariant()->sku,
            'unit_price' => Money::fromNaira($unitNaira),
            'quantity' => $qty,
            'line_total' => Money::fromNaira($unitNaira * $qty),
        ]);
    }

    private function request(User $user, OrderItem $item, string $reason = 'changed_mind', int $qty = 1): ReturnRequest
    {
        return app(ReturnRequestService::class)->create(
            $item->order, $user,
            [['order_item_id' => $item->id, 'quantity' => $qty]],
            $reason, 'store_credit', null, 'idem-'.uniqid(),
        );
    }

    public function test_a_low_risk_soft_return_is_auto_approved(): void
    {
        // Established account, small value, changed-mind → auto-approve.
        $user = User::factory()->create(['created_at' => now()->subYear()]);
        $item = $this->deliveredItem($user, unitNaira: 2000);

        $return = $this->request($user, $item);

        $this->assertTrue($return->auto_approved);
        $this->assertSame('awaiting_shipment', $return->status->value);
        $this->assertLessThanOrEqual(40, $return->risk_score);
        $this->assertNotNull($return->returnShipment);
    }

    public function test_a_high_value_return_is_not_auto_approved(): void
    {
        // Above the ₦100k high-value threshold (and the ₦50k auto cap) → manual review.
        $user = User::factory()->create(['created_at' => now()->subYear()]);
        $item = $this->deliveredItem($user, unitNaira: 120000);

        $return = $this->request($user, $item);

        $this->assertFalse($return->auto_approved);
        $this->assertSame('requested', $return->status->value);
        $this->assertContains('high_value', $return->risk_flags);
    }

    public function test_a_hard_to_verify_reason_routes_to_manual_review(): void
    {
        $user = User::factory()->create(['created_at' => now()->subYear()]);
        $item = $this->deliveredItem($user, unitNaira: 2000);

        $return = $this->request($user, $item, reason: 'damaged');

        // "damaged" is not in the auto-approve reason allowlist.
        $this->assertFalse($return->auto_approved);
        $this->assertSame('requested', $return->status->value);
    }

    public function test_a_new_account_is_flagged(): void
    {
        $user = User::factory()->create(['created_at' => now()->subDay()]);
        $item = $this->deliveredItem($user, unitNaira: 2000);

        $return = $this->request($user, $item);

        $this->assertContains('new_account', $return->risk_flags);
    }

    public function test_a_serial_returner_scores_high_and_is_not_auto_approved(): void
    {
        $user = User::factory()->create(['created_at' => now()->subYear()]);

        // Seed five prior returns in the lookback window.
        ReturnRequest::factory()->count(5)->create(['user_id' => $user->id, 'created_at' => now()->subDays(10)]);

        $item = $this->deliveredItem($user, unitNaira: 2000);
        $return = $this->request($user, $item);

        $this->assertContains('frequent_returner', $return->risk_flags);
        $this->assertGreaterThanOrEqual(35, $return->risk_score);
        $this->assertFalse($return->auto_approved); // score exceeds the 40 cap with other signals, or frequent flag
    }
}
