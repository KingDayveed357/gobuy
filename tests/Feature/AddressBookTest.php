<?php

namespace Tests\Feature;

use App\Models\User;
use App\Modules\Customer\Models\Address;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class AddressBookTest extends TestCase
{
    use LazilyRefreshDatabase;

    /**
     * @return array<string, mixed>
     */
    private function payload(array $overrides = []): array
    {
        return array_merge([
            'label' => 'Home',
            'recipient_name' => 'Ada Obi',
            'phone' => '08030000000',
            'line1' => '12 Marina Road',
            'city' => 'Lagos',
            'state' => 'Lagos',
            'country' => 'Nigeria',
        ], $overrides);
    }

    public function test_first_address_becomes_default_for_shipping_and_billing(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('account.addresses.store'), $this->payload())
            ->assertRedirect(route('account.addresses.index'));

        $address = $user->addresses()->first();
        $this->assertTrue($address->is_default_shipping);
        $this->assertTrue($address->is_default_billing);
    }

    public function test_setting_a_new_default_shipping_unsets_the_previous(): void
    {
        $user = User::factory()->create();
        $first = Address::factory()->for($user)->defaultShipping()->defaultBilling()->create();
        $second = Address::factory()->for($user)->create();

        $this->actingAs($user)
            ->post(route('account.addresses.default', $second), ['purpose' => 'shipping'])
            ->assertRedirect();

        $this->assertFalse($first->fresh()->is_default_shipping);
        $this->assertTrue($second->fresh()->is_default_shipping);
    }

    public function test_deleting_a_default_promotes_another_address(): void
    {
        $user = User::factory()->create();
        $default = Address::factory()->for($user)->defaultShipping()->defaultBilling()->create();
        $other = Address::factory()->for($user)->create();

        $this->actingAs($user)->delete(route('account.addresses.destroy', $default))->assertRedirect();

        $this->assertDatabaseMissing('addresses', ['id' => $default->id]);
        $this->assertTrue($other->fresh()->is_default_shipping);
    }

    public function test_a_user_cannot_modify_another_users_address(): void
    {
        $owner = User::factory()->create();
        $address = Address::factory()->for($owner)->create();

        $this->actingAs(User::factory()->create())
            ->put(route('account.addresses.update', $address), $this->payload())
            ->assertForbidden();
    }

    public function test_address_json_endpoint_returns_the_users_addresses(): void
    {
        $user = User::factory()->create();
        Address::factory()->for($user)->count(2)->create();

        $this->actingAs($user)->getJson(route('account.addresses.json'))
            ->assertOk()
            ->assertJsonCount(2, 'addresses');
    }

    public function test_validation_rejects_missing_required_fields(): void
    {
        $this->actingAs(User::factory()->create())
            ->post(route('account.addresses.store'), ['label' => 'Home'])
            ->assertSessionHasErrors(['recipient_name', 'phone', 'line1', 'city', 'state']);
    }
}
