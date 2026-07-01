<?php

namespace Tests\Feature\Admin;

use App\Admin\Mail\AdminTwoFactorCodeMail;
use App\Admin\Models\Admin;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Tests\Concerns\InteractsWithAdmin;
use Tests\TestCase;

/**
 * Phase F — opt-in email-OTP 2FA at admin login.
 */
class AdminTwoFactorTest extends TestCase
{
    use InteractsWithAdmin, LazilyRefreshDatabase;

    public function test_an_admin_can_toggle_two_factor_in_settings(): void
    {
        $admin = $this->actingAsAdmin('Admin');

        $this->post(route('admin.settings.two-factor'))->assertRedirect();
        $this->assertTrue($admin->fresh()->two_factor_enabled);

        $this->post(route('admin.settings.two-factor'));
        $this->assertFalse($admin->fresh()->two_factor_enabled);
    }

    public function test_login_without_two_factor_goes_straight_in(): void
    {
        $admin = Admin::factory()->create(['email' => 'plain@gobuy.test', 'password' => Hash::make('secret123')]);

        $this->post(route('admin.login'), ['email' => 'plain@gobuy.test', 'password' => 'secret123'])
            ->assertRedirect(route('admin.dashboard'));
        $this->assertAuthenticatedAs($admin, 'admin');
    }

    public function test_login_with_two_factor_requires_an_emailed_code(): void
    {
        Mail::fake();
        $admin = Admin::factory()->create([
            'email' => 'safe@gobuy.test', 'password' => Hash::make('secret123'), 'two_factor_enabled' => true,
        ]);

        // Step 1: password is accepted but the session is NOT authenticated yet.
        $this->post(route('admin.login'), ['email' => 'safe@gobuy.test', 'password' => 'secret123'])
            ->assertRedirect(route('admin.2fa.challenge'));
        $this->assertGuest('admin');

        $code = null;
        Mail::assertQueued(AdminTwoFactorCodeMail::class, function ($mail) use (&$code, $admin) {
            $code = $mail->code;

            return $mail->hasTo($admin->email);
        });

        // Wrong code is rejected.
        $this->post(route('admin.2fa.verify'), ['code' => 'not-the-code'])->assertSessionHasErrors('code');
        $this->assertGuest('admin');

        // Correct code completes sign-in.
        $this->post(route('admin.2fa.verify'), ['code' => $code])->assertRedirect(route('admin.dashboard'));
        $this->assertAuthenticatedAs($admin->fresh(), 'admin');
    }
}
