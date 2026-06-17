<?php

namespace Tests\Feature\Admin;

use App\Admin\Models\Admin;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\Concerns\InteractsWithAdmin;
use Tests\TestCase;

class AdminAuthTest extends TestCase
{
    use InteractsWithAdmin, LazilyRefreshDatabase;

    public function test_login_page_renders(): void
    {
        $this->get(route('admin.login'))->assertOk()->assertSee('admin console');
    }

    public function test_admin_can_login(): void
    {
        $admin = Admin::factory()->create(['email' => 'boss@gobuy.test', 'password' => Hash::make('secret123')]);

        $this->post(route('admin.login'), ['email' => 'boss@gobuy.test', 'password' => 'secret123'])
            ->assertRedirect(route('admin.dashboard'));

        $this->assertAuthenticatedAs($admin, 'admin');
        $this->assertNotNull($admin->fresh()->last_login_at);
    }

    public function test_login_fails_with_invalid_credentials(): void
    {
        Admin::factory()->create(['email' => 'boss@gobuy.test', 'password' => Hash::make('secret123')]);

        $this->post(route('admin.login'), ['email' => 'boss@gobuy.test', 'password' => 'wrong'])
            ->assertSessionHasErrors('email');

        $this->assertGuest('admin');
    }

    public function test_disabled_admin_cannot_login(): void
    {
        Admin::factory()->inactive()->create(['email' => 'gone@gobuy.test', 'password' => Hash::make('secret123')]);

        $this->post(route('admin.login'), ['email' => 'gone@gobuy.test', 'password' => 'secret123'])
            ->assertSessionHasErrors('email');

        $this->assertGuest('admin');
    }

    public function test_login_is_rate_limited(): void
    {
        Admin::factory()->create(['email' => 'boss@gobuy.test', 'password' => Hash::make('secret123')]);

        foreach (range(1, 5) as $i) {
            $this->post(route('admin.login'), ['email' => 'boss@gobuy.test', 'password' => 'wrong']);
        }

        $response = $this->post(route('admin.login'), ['email' => 'boss@gobuy.test', 'password' => 'wrong']);
        $response->assertSessionHasErrors('email');
        $this->assertStringContainsString('seconds', collect(session('errors')->get('email'))->implode(' '));
    }

    public function test_admin_can_logout(): void
    {
        $this->actingAsAdmin('Super Admin');

        $this->post(route('admin.logout'))->assertRedirect(route('admin.login'));

        $this->assertGuest('admin');
    }
}
