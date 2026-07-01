<?php

namespace Tests\Feature\Catalog;

use App\Admin\Notifications\AdminAlertNotification;
use App\Modules\Catalog\Models\BulkQuantityRequest;
use App\Modules\Catalog\Models\Product;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\Concerns\InteractsWithAdmin;
use Tests\TestCase;

class BulkQuantityRequestTest extends TestCase
{
    use InteractsWithAdmin, LazilyRefreshDatabase;

    public function test_a_shopper_can_submit_a_bulk_request_and_customer_admins_are_alerted(): void
    {
        Notification::fake();
        $this->seedAdminAccess();
        $admin = $this->adminWithRole('Support'); // has manage_customers

        $product = Product::factory()->stock(2)->create();

        $this->post(route('bulk-requests.store'), [
            'product_id' => $product->id,
            'product_variant_id' => $product->primaryVariant()->id,
            'name' => 'Acme Corp',
            'email' => 'buyer@acme.test',
            'quantity' => 50,
        ])->assertRedirect();

        $this->assertDatabaseHas('bulk_quantity_requests', [
            'product_id' => $product->id,
            'quantity' => 50,
            'status' => 'new',
        ]);

        Notification::assertSentTo(
            $admin,
            AdminAlertNotification::class,
            fn (AdminAlertNotification $n) => $n->title === 'Bulk quantity request',
        );
    }

    public function test_a_bulk_request_requires_a_quantity(): void
    {
        $product = Product::factory()->stock(2)->create();

        $this->post(route('bulk-requests.store'), [
            'product_id' => $product->id,
            'name' => 'Acme Corp',
            'email' => 'buyer@acme.test',
        ])->assertSessionHasErrors('quantity');

        $this->assertSame(0, BulkQuantityRequest::count());
    }

    public function test_an_admin_can_list_and_update_a_bulk_request(): void
    {
        $this->actingAsAdmin('Support');
        $product = Product::factory()->stock(2)->create();
        $request = BulkQuantityRequest::create([
            'product_id' => $product->id,
            'name' => 'Acme Corp',
            'email' => 'buyer@acme.test',
            'quantity' => 40,
            'status' => 'new',
        ]);

        $this->get(route('admin.bulk-requests.index'))->assertOk()->assertSee('Acme Corp');

        $this->post(route('admin.bulk-requests.status', $request), ['status' => 'contacted'])
            ->assertRedirect();

        $this->assertSame('contacted', $request->fresh()->status);
    }
}
