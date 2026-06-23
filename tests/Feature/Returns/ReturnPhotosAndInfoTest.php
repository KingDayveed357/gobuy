<?php

namespace Tests\Feature\Returns;

use App\Models\User;
use App\Modules\Catalog\Models\Product;
use App\Modules\Order\Enums\OrderStatus;
use App\Modules\Order\Models\Order;
use App\Modules\Order\Models\OrderItem;
use App\Modules\Returns\Enums\ReturnStatus;
use App\Modules\Returns\Models\ReturnRequest;
use App\Support\Money;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\InteractsWithAdmin;
use Tests\TestCase;

class ReturnPhotosAndInfoTest extends TestCase
{
    use InteractsWithAdmin;
    use LazilyRefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['gobuy.returns.auto_approve.enabled' => false]);
    }

    private function deliveredItem(User $user): OrderItem
    {
        $product = Product::factory()->stock(5)->create();
        $order = Order::factory()->create([
            'user_id' => $user->id, 'status' => OrderStatus::Delivered, 'delivered_at' => now()->subDay(),
            'customer_phone' => '08030001122',
        ]);

        return $order->items()->create([
            'product_variant_id' => $product->primaryVariant()->id,
            'name' => $product->name, 'sku' => $product->primaryVariant()->sku,
            'unit_price' => Money::fromNaira(2000), 'quantity' => 1, 'line_total' => Money::fromNaira(2000),
        ]);
    }

    private function payload(OrderItem $item, string $reason): array
    {
        return [
            'reason_code' => $reason, 'refund_destination' => 'store_credit', 'idempotency_key' => uniqid(),
            'items' => [$item->id => ['order_item_id' => $item->id, 'selected' => '1', 'quantity' => 1]],
        ];
    }

    public function test_a_damaged_return_requires_a_photo(): void
    {
        $user = User::factory()->create();
        $item = $this->deliveredItem($user);

        $this->actingAs($user)
            ->post(route('account.returns.store', $item->order), $this->payload($item, 'damaged'))
            ->assertSessionHasErrors('photos');

        $this->assertSame(0, ReturnRequest::count());
    }

    public function test_a_damaged_return_with_a_photo_is_accepted_and_stores_the_image(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();
        $item = $this->deliveredItem($user);

        $this->actingAs($user)->post(
            route('account.returns.store', $item->order),
            $this->payload($item, 'damaged') + ['photos' => [UploadedFile::fake()->image('damage.jpg')]],
        )->assertRedirect();

        $return = ReturnRequest::first();
        $this->assertNotNull($return);
        $this->assertSame(1, $return->getMedia(ReturnRequest::MEDIA_PHOTOS)->count());
    }

    public function test_admin_can_request_info_and_customer_can_reply(): void
    {
        $user = User::factory()->create();
        $order = Order::factory()->create(['user_id' => $user->id, 'customer_phone' => '08030001122']);
        $return = ReturnRequest::factory()->create([
            'order_id' => $order->id, 'user_id' => $user->id, 'status' => ReturnStatus::Requested,
        ]);

        $this->actingAsAdmin('Super Admin');
        $this->post(route('admin.returns.request-info', $return), ['message' => 'Please share a photo of the label.'])
            ->assertRedirect();
        $this->assertSame('info_requested', $return->fresh()->status->value);

        $this->actingAs($user)
            ->post(route('account.returns.reply', $return), ['message' => 'Here is the info you asked for.'])
            ->assertRedirect();

        $return->refresh();
        $this->assertSame('requested', $return->status->value);
        $this->assertDatabaseHas('return_events', ['return_request_id' => $return->id, 'action' => 'customer_reply']);
    }
}
