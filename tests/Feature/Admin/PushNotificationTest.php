<?php

namespace Tests\Feature\Admin;

use App\Admin\Notifications\AdminAlertNotification;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use NotificationChannels\WebPush\WebPushChannel;
use Tests\Concerns\InteractsWithAdmin;
use Tests\TestCase;

class PushNotificationTest extends TestCase
{
    use InteractsWithAdmin, LazilyRefreshDatabase;

    private array $subscription = [
        'endpoint' => 'https://fcm.googleapis.com/fcm/send/abc123',
        'keys' => ['p256dh' => 'BPk-fakepublickey', 'auth' => 'fakeauthtoken'],
    ];

    public function test_admin_can_store_a_browser_push_subscription(): void
    {
        $admin = $this->actingAsAdmin('Super Admin');

        $this->postJson(route('admin.push-subscriptions.store'), $this->subscription)->assertOk();

        $this->assertDatabaseHas('push_subscriptions', [
            'subscribable_type' => $admin->getMorphClass(),
            'subscribable_id' => $admin->id,
            'endpoint' => $this->subscription['endpoint'],
        ]);
    }

    public function test_admin_can_remove_a_push_subscription(): void
    {
        $admin = $this->actingAsAdmin('Super Admin');
        $admin->updatePushSubscription($this->subscription['endpoint'], 'key', 'auth');

        $this->deleteJson(route('admin.push-subscriptions.destroy'), [
            'endpoint' => $this->subscription['endpoint'],
        ])->assertOk();

        $this->assertDatabaseMissing('push_subscriptions', ['endpoint' => $this->subscription['endpoint']]);
    }

    public function test_a_guest_cannot_store_a_subscription(): void
    {
        // The admin guard redirects unauthenticated requests to login rather
        // than storing anything.
        $this->post(route('admin.push-subscriptions.store'), $this->subscription)
            ->assertRedirect(route('admin.login'));

        $this->assertDatabaseCount('push_subscriptions', 0);
    }

    public function test_admin_alerts_add_the_web_push_channel_and_build_a_payload(): void
    {
        $admin = $this->actingAsAdmin('Super Admin');

        $alert = new AdminAlertNotification(
            'Payment mismatch',
            'A payment did not match the expected amount.',
            'critical',
            route('admin.notifications.index'),
        );

        // Push is added on top of database/mail for a push-capable recipient.
        $this->assertContains(WebPushChannel::class, $alert->via($admin));

        $payload = $alert->toWebPush($admin, $alert)->toArray();
        $this->assertSame('Payment mismatch', $payload['title']);
        $this->assertSame('A payment did not match the expected amount.', $payload['body']);
        $this->assertSame(route('admin.notifications.index'), $payload['data']['url']);
    }
}
