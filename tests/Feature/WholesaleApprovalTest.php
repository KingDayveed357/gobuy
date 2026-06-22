<?php

namespace Tests\Feature;

use App\Models\User;
use App\Modules\Catalog\Models\Product;
use App\Modules\Pricing\Services\PriceResolver;
use App\Support\Money;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\Concerns\InteractsWithAdmin;
use Tests\TestCase;

class WholesaleApprovalTest extends TestCase
{
    use InteractsWithAdmin, LazilyRefreshDatabase;

    /**
     * @return array<string, string>
     */
    private function application(): array
    {
        return [
            'business_name' => 'Obi Stores Ltd',
            'rc_number' => 'RC123456',
            'business_phone' => '08030000000',
            'business_address' => '5 Trade Fair Complex, Lagos',
            'intent' => 'We buy safety equipment in bulk for construction sites.',
        ];
    }

    public function test_user_can_submit_wholesale_application_and_becomes_pending(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('account.wholesale'), $this->application())
            ->assertRedirect(route('account.dashboard'));

        $this->assertDatabaseHas('wholesale_profiles', ['user_id' => $user->id, 'business_name' => 'Obi Stores Ltd']);
        $this->assertSame(User::WHOLESALE_PENDING, $user->fresh()->wholesale_status);
        // Still retail until approved.
        $this->assertFalse($user->fresh()->isWholesale());
    }

    public function test_admin_approval_unlocks_wholesale_pricing(): void
    {
        $this->actingAsAdmin('Admin');
        $user = User::factory()->pendingWholesale()->create();
        $user->wholesaleProfile()->create($this->application());

        $product = Product::factory()->priced(retail: 1000, wholesale: 800)->create();
        $resolver = app(PriceResolver::class);

        // Before approval: retail price even at qualifying quantity.
        $this->assertSame(Money::fromNaira(1000)->kobo, $resolver->for($product, $user->fresh(), 10)->unitPrice->kobo);

        $this->post(route('admin.wholesale.approve', $user))->assertRedirect();

        $user->refresh();
        $this->assertTrue($user->isWholesale());
        $this->assertSame(User::WHOLESALE_APPROVED, $user->wholesale_status);

        // After approval: wholesale price applies at qualifying quantity.
        $this->assertSame(Money::fromNaira(800)->kobo, $resolver->for($product, $user, 10)->unitPrice->kobo);
    }

    public function test_admin_can_reject_application(): void
    {
        $this->actingAsAdmin('Admin');
        $user = User::factory()->pendingWholesale()->create();
        $user->wholesaleProfile()->create($this->application());

        $this->post(route('admin.wholesale.reject', $user))->assertRedirect();

        $user->refresh();
        $this->assertSame(User::WHOLESALE_REJECTED, $user->wholesale_status);
        $this->assertFalse($user->isWholesale());
    }

    public function test_guest_cannot_apply_for_wholesale(): void
    {
        $this->post(route('account.wholesale'), $this->application())->assertRedirect(route('login'));
    }
}
