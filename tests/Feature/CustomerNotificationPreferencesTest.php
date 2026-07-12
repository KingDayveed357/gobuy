<?php

namespace Tests\Feature;

use App\Models\User;
use App\Modules\Catalog\Mail\BackInStockMail;
use App\Modules\Catalog\Models\Product;
use App\Modules\Catalog\Services\BackInStockService;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * Item #7 — customer account settings gains functional notification preferences.
 */
class CustomerNotificationPreferencesTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_defaults_apply_when_preferences_are_unset(): void
    {
        $user = User::factory()->create(['notification_preferences' => null]);

        $this->assertTrue($user->wantsNotification('order_updates'));
        $this->assertTrue($user->wantsNotification('promotions'));
        $this->assertFalse($user->wantsNotification('newsletter'));
        $this->assertFalse($user->wantsNotification('unknown_key'));
    }

    public function test_customer_can_save_notification_preferences(): void
    {
        $user = User::factory()->create();

        // Only order updates + promotions checked; the rest are toggled off.
        $this->actingAs($user)->post(route('account.settings.notifications'), [
            'notifications' => ['order_updates' => '1', 'promotions' => '1'],
        ])->assertRedirect();

        $user->refresh();
        $this->assertTrue($user->wantsNotification('order_updates'));
        $this->assertTrue($user->wantsNotification('promotions'));
        $this->assertFalse($user->wantsNotification('back_in_stock'));
        $this->assertFalse($user->wantsNotification('newsletter'));
    }

    public function test_preferences_persist_as_a_structured_array(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('account.settings.notifications'), [
            'notifications' => ['newsletter' => '1'],
        ]);

        $prefs = $user->fresh()->notification_preferences;
        $this->assertIsArray($prefs);
        $this->assertTrue($prefs['newsletter']);
        $this->assertFalse($prefs['order_updates']);
    }

    public function test_back_in_stock_alerts_respect_the_opted_out_preference(): void
    {
        Mail::fake();

        $optedOut = User::factory()->create(['notification_preferences' => ['back_in_stock' => false]]);
        $optedIn = User::factory()->create(['notification_preferences' => ['back_in_stock' => true]]);

        $variant = Product::factory()->outOfStock()->create()->primaryVariant();

        $service = app(BackInStockService::class);
        $service->register($variant, $optedOut->email, $optedOut->id);
        $service->register($variant, $optedIn->email, $optedIn->id);

        $variant->update(['stock' => 5]);
        $service->flush($variant->refresh());

        Mail::assertQueued(BackInStockMail::class, 1);
        Mail::assertNotQueued(BackInStockMail::class, fn ($mail) => $mail->hasTo($optedOut->email));
    }

    public function test_the_settings_page_renders_the_notifications_tab(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('account.settings'))
            ->assertOk()
            ->assertSee('Notifications')
            ->assertSee('Order updates')
            ->assertSee('notifications[promotions]', false);
    }
}
