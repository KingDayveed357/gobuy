<?php

namespace Tests\Feature\Admin;

use App\Admin\Notifications\AdminAlertNotification;
use App\Models\User;
use App\Modules\Catalog\Models\Product;
use App\Modules\Catalog\Services\CatalogService;
use App\Modules\Review\Services\ReviewService;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\Concerns\InteractsWithAdmin;
use Tests\TestCase;

class OperationalAlertsTest extends TestCase
{
    use InteractsWithAdmin, LazilyRefreshDatabase;

    public function test_selling_the_last_unit_alerts_product_admins(): void
    {
        Notification::fake();
        $admin = $this->actingAsAdmin('Super Admin');

        $product = Product::factory()->stock(1)->create();
        app(CatalogService::class)->decrementStock($product->primaryVariant(), 1);

        Notification::assertSentTo(
            $admin,
            AdminAlertNotification::class,
            fn (AdminAlertNotification $n) => $n->title === 'Out of stock',
        );
    }

    public function test_a_submitted_review_alerts_product_admins_for_moderation(): void
    {
        Notification::fake();
        $admin = $this->actingAsAdmin('Super Admin');

        $user = User::factory()->create();
        $product = Product::factory()->stock(5)->create();

        app(ReviewService::class)->submit($user, $product, ['rating' => 4, 'body' => 'Great product']);

        Notification::assertSentTo(
            $admin,
            AdminAlertNotification::class,
            fn (AdminAlertNotification $n) => $n->title === 'Review awaiting moderation',
        );
    }

    public function test_a_new_registration_alerts_customer_admins(): void
    {
        Notification::fake();
        // Create the admin but stay a guest — the register route is guest-only.
        $this->seedAdminAccess();
        $admin = $this->adminWithRole('Super Admin');

        $this->post(route('register'), [
            'name' => 'Ada Lovelace',
            'email' => 'ada@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        Notification::assertSentTo(
            $admin,
            AdminAlertNotification::class,
            fn (AdminAlertNotification $n) => $n->title === 'New customer registered',
        );
    }
}
