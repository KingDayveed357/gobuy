<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Modules\Customer\Exceptions\SocialAuthException;
use App\Modules\Customer\Models\SocialAccount;
use App\Modules\Customer\Services\SocialAuthService;
use App\Modules\Order\Models\Order;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;
use Tests\TestCase;

class SocialAuthTest extends TestCase
{
    use LazilyRefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['social.providers.google.enabled' => true]);
    }

    private function service(): SocialAuthService
    {
        return app(SocialAuthService::class);
    }

    private function oauthUser(string $id, ?string $email, string $name = 'Jane Doe', array $raw = []): SocialiteUser
    {
        $user = new SocialiteUser;
        $user->map(['id' => $id, 'name' => $name, 'email' => $email, 'avatar' => 'https://cdn/a.png']);
        $user->setRaw($raw);
        $user->token = 'access-token';
        $user->refreshToken = 'refresh-token';

        return $user;
    }

    // ── Identity resolution ────────────────────────────────────────────────

    public function test_a_new_google_user_is_created_verified_and_passwordless(): void
    {
        $result = $this->service()->resolve('google', $this->oauthUser('g-1', 'new@example.com'));

        $this->assertTrue($result['is_new']);
        $user = $result['user'];
        $this->assertSame('new@example.com', $user->email);
        $this->assertTrue($user->hasVerifiedEmail());        // Google vouches → no OTP wall
        $this->assertFalse($user->hasPassword());            // social-only
        $this->assertDatabaseHas('social_accounts', ['provider' => 'google', 'provider_id' => 'g-1', 'user_id' => $user->id]);
    }

    public function test_a_returning_social_user_signs_in_without_a_duplicate(): void
    {
        $first = $this->service()->resolve('google', $this->oauthUser('g-2', 'repeat@example.com'))['user'];

        $again = $this->service()->resolve('google', $this->oauthUser('g-2', 'repeat@example.com'));

        $this->assertFalse($again['is_new']);
        $this->assertTrue($first->is($again['user']));
        $this->assertSame(1, User::count());
        $this->assertSame(1, SocialAccount::count());
    }

    public function test_a_verified_email_links_to_an_existing_password_account(): void
    {
        $existing = User::factory()->create(['email' => 'link@example.com', 'password' => Hash::make('secret123')]);

        $result = $this->service()->resolve('google', $this->oauthUser('g-3', 'link@example.com'));

        $this->assertFalse($result['is_new']);
        $this->assertTrue($existing->is($result['user']));   // same account — no duplicate
        $this->assertSame(1, User::count());
        $this->assertDatabaseHas('social_accounts', ['user_id' => $existing->id, 'provider' => 'google']);
        // The password login still works after linking.
        $this->assertTrue(Hash::check('secret123', $existing->fresh()->password));
    }

    public function test_linking_verifies_a_previously_unverified_account(): void
    {
        $existing = User::factory()->unverified()->create(['email' => 'pending@example.com']);
        $this->assertFalse($existing->hasVerifiedEmail());

        $this->service()->resolve('google', $this->oauthUser('g-4', 'pending@example.com'));

        $this->assertTrue($existing->fresh()->hasVerifiedEmail());
    }

    // ── Facebook / unverified-email edge cases ─────────────────────────────

    public function test_an_unverified_facebook_email_matching_an_existing_user_is_rejected(): void
    {
        User::factory()->create(['email' => 'victim@example.com']);

        $this->expectException(SocialAuthException::class);
        $this->service()->resolve('facebook', $this->oauthUser('f-1', 'victim@example.com', raw: []));
    }

    public function test_an_unverified_facebook_email_creates_an_account_that_still_needs_otp(): void
    {
        $result = $this->service()->resolve('facebook', $this->oauthUser('f-2', 'fresh@example.com', raw: []));

        $this->assertTrue($result['is_new']);
        $this->assertFalse($result['user']->hasVerifiedEmail()); // falls back to OTP
    }

    public function test_a_verified_facebook_email_bypasses_otp(): void
    {
        $result = $this->service()->resolve('facebook', $this->oauthUser('f-3', 'ok@example.com', raw: ['email_verified' => true]));

        $this->assertTrue($result['user']->hasVerifiedEmail());
    }

    public function test_a_provider_returning_no_email_is_rejected(): void
    {
        $this->expectException(SocialAuthException::class);
        $this->service()->resolve('facebook', $this->oauthUser('f-4', null));
    }

    // ── Guest history merge ────────────────────────────────────────────────

    public function test_guest_orders_are_claimed_on_verified_social_signup(): void
    {
        $guestOrder = Order::factory()->create(['user_id' => null, 'customer_email' => 'buyer@example.com']);

        $result = $this->service()->resolve('google', $this->oauthUser('g-5', 'buyer@example.com'));

        $this->assertSame($result['user']->id, $guestOrder->fresh()->user_id);
    }

    // ── Controller / routing ───────────────────────────────────────────────

    public function test_the_callback_signs_the_user_in_and_redirects(): void
    {
        Socialite::shouldReceive('driver')->with('google')->andReturnSelf();
        Socialite::shouldReceive('user')->andReturn($this->oauthUser('g-6', 'flow@example.com'));

        $this->get(route('social.callback', 'google'))->assertRedirect(route('account.dashboard'));

        $this->assertAuthenticated();
    }

    public function test_enabled_providers_render_a_button_on_the_login_page(): void
    {
        $this->get(route('login'))->assertOk()->assertSee('Continue with Google');
    }

    public function test_disabled_providers_render_no_button(): void
    {
        config(['social.providers.google.enabled' => false]);

        $this->get(route('login'))->assertOk()->assertDontSee('Continue with Google');
    }

    public function test_a_disabled_provider_returns_404(): void
    {
        config(['social.providers.google.enabled' => false]);

        $this->get(route('social.redirect', 'google'))->assertNotFound();
    }

    public function test_an_unknown_provider_returns_404(): void
    {
        $this->get(route('social.redirect', 'myspace'))->assertNotFound();
    }
}
