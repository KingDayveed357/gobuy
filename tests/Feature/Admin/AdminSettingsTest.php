<?php

namespace Tests\Feature\Admin;

use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\Concerns\InteractsWithAdmin;
use Tests\TestCase;

/**
 * Item #6 — the admin settings page. The tabs are driven by wizard-engine.js
 * (which was never loaded → only the Profile pane worked) and the 2FA toggle
 * must be its own form (it used to be nested inside the password form → it
 * silently posted the wrong endpoint).
 */
class AdminSettingsTest extends TestCase
{
    use InteractsWithAdmin, LazilyRefreshDatabase;

    public function test_the_settings_page_loads_the_wizard_tab_engine(): void
    {
        $this->actingAsAdmin('Admin');

        $this->get(route('admin.settings'))
            ->assertOk()
            ->assertSee('theme/js/wizard-engine.js', false)
            // All three panes are rendered so every tab has content once switchable.
            ->assertSee('pane-admin-profile', false)
            ->assertSee('pane-admin-security', false)
            ->assertSee('pane-admin-notifications', false);
    }

    public function test_the_two_factor_form_is_not_nested_inside_the_password_form(): void
    {
        $this->actingAsAdmin('Admin');

        $html = $this->get(route('admin.settings'))->assertOk()->getContent();

        $securityPos = strpos($html, route('admin.settings.security'));
        $twoFactorPos = strpos($html, route('admin.settings.two-factor'));
        $this->assertNotFalse($securityPos);
        $this->assertNotFalse($twoFactorPos);

        // The password form must close (</form>) before the 2FA form's action
        // appears — proving the two are siblings, not nested.
        $closingBetween = strpos($html, '</form>', $securityPos);
        $this->assertNotFalse($closingBetween);
        $this->assertLessThan($twoFactorPos, $closingBetween, 'The 2FA form is still nested inside the password form.');
    }

    public function test_an_admin_can_change_their_password(): void
    {
        $admin = $this->actingAsAdmin('Admin');
        $admin->forceFill(['password' => Hash::make('old-password-1')])->save();

        $this->post(route('admin.settings.security'), [
            'current_password' => 'old-password-1',
            'password' => 'brand-new-pass-9',
            'password_confirmation' => 'brand-new-pass-9',
        ])->assertRedirect();

        $this->assertTrue(Hash::check('brand-new-pass-9', $admin->fresh()->password));
    }

    public function test_a_wrong_current_password_is_rejected(): void
    {
        $admin = $this->actingAsAdmin('Admin');
        $admin->forceFill(['password' => Hash::make('old-password-1')])->save();

        $this->from(route('admin.settings'))->post(route('admin.settings.security'), [
            'current_password' => 'not-the-password',
            'password' => 'brand-new-pass-9',
            'password_confirmation' => 'brand-new-pass-9',
        ])->assertSessionHasErrors('current_password');

        $this->assertTrue(Hash::check('old-password-1', $admin->fresh()->password));
    }
}
