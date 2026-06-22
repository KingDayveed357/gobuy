<?php

namespace App\Modules\Customer\Services;

use App\Models\User;
use App\Modules\Customer\Models\Address;
use Illuminate\Support\Facades\DB;

/**
 * Owns the address book lifecycle, keeping the "single default per purpose"
 * invariant: a user has at most one default shipping and one default billing
 * address at any time.
 */
class AddressService
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function create(User $user, array $data): Address
    {
        return DB::transaction(function () use ($user, $data): Address {
            $first = $user->addresses()->doesntExist();

            // The first address is always the default for both purposes.
            $data['is_default_shipping'] = $first || ! empty($data['is_default_shipping']);
            $data['is_default_billing'] = $first || ! empty($data['is_default_billing']);

            $address = $user->addresses()->create($data);

            $this->normaliseDefaults($user, $address);

            return $address;
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Address $address, array $data): Address
    {
        return DB::transaction(function () use ($address, $data): Address {
            $address->update($data);
            $this->normaliseDefaults($address->user, $address);

            return $address;
        });
    }

    public function delete(Address $address): void
    {
        DB::transaction(function () use ($address): void {
            $user = $address->user;
            $wasShipping = $address->is_default_shipping;
            $wasBilling = $address->is_default_billing;

            $address->delete();

            // Promote another address to default if we removed a default one.
            $fallback = $user->addresses()->first();
            if ($fallback) {
                if ($wasShipping) {
                    $fallback->update(['is_default_shipping' => true]);
                }
                if ($wasBilling) {
                    $fallback->update(['is_default_billing' => true]);
                }
            }
        });
    }

    public function setDefault(Address $address, string $purpose): void
    {
        $column = $purpose === 'billing' ? 'is_default_billing' : 'is_default_shipping';

        DB::transaction(function () use ($address, $column): void {
            $address->user->addresses()->where('id', '!=', $address->id)->update([$column => false]);
            $address->update([$column => true]);
        });
    }

    /**
     * Ensure only the given address keeps any default flag it was just granted.
     */
    private function normaliseDefaults(User $user, Address $address): void
    {
        if ($address->is_default_shipping) {
            $user->addresses()->where('id', '!=', $address->id)->update(['is_default_shipping' => false]);
        }

        if ($address->is_default_billing) {
            $user->addresses()->where('id', '!=', $address->id)->update(['is_default_billing' => false]);
        }
    }
}
