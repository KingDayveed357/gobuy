<?php

namespace App\Modules\Returns\Services;

use App\Admin\Models\Admin;
use App\Models\User;
use App\Modules\Returns\Exceptions\InsufficientStoreCredit;
use App\Modules\Returns\Models\StoreCredit;
use App\Modules\Returns\Models\StoreCreditEntry;
use App\Support\Money;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

/**
 * The store-credit wallet. The ledger ({@see StoreCreditEntry}) is the source of
 * truth; the wallet's cached `balance` is kept in step inside the same
 * transaction. Writes are idempotent on a supplied key.
 */
class StoreCreditService
{
    public function walletFor(User $user): StoreCredit
    {
        return StoreCredit::firstOrCreate(['user_id' => $user->id]);
    }

    public function balanceFor(User $user): Money
    {
        return StoreCredit::where('user_id', $user->id)->first()?->balance ?? Money::zero();
    }

    /**
     * How much credit can be applied against a given amount due (never more than
     * the wallet holds, never more than the bill).
     */
    public function redeemableFor(User $user, Money $cap): Money
    {
        return $this->balanceFor($user)->min($cap);
    }

    /**
     * Spend credit from a wallet (a negative ledger entry). Idempotent on the
     * key; guards against spending more than the balance.
     */
    public function spend(
        User $user,
        Money $amount,
        ?Model $source = null,
        ?string $idempotencyKey = null,
        ?string $reason = null,
    ): ?StoreCreditEntry {
        if (! $amount->isPositive()) {
            return null;
        }

        if ($idempotencyKey !== null) {
            $existing = StoreCreditEntry::where('idempotency_key', $idempotencyKey)->first();
            if ($existing) {
                return $existing; // replay — already spent
            }
        }

        return DB::transaction(function () use ($user, $amount, $source, $idempotencyKey, $reason): StoreCreditEntry {
            $wallet = StoreCredit::where('user_id', $user->id)->lockForUpdate()->first();

            if ($wallet === null || $wallet->balance->lessThan($amount)) {
                throw new InsufficientStoreCredit('Insufficient store credit.');
            }

            $entry = $wallet->entries()->create([
                'amount' => Money::fromKobo(-$amount->kobo), // negative = spend
                'type' => StoreCreditEntry::TYPE_SPEND,
                'source_type' => $source?->getMorphClass(),
                'source_id' => $source?->getKey(),
                'reason' => $reason,
                'idempotency_key' => $idempotencyKey,
            ]);

            StoreCredit::whereKey($wallet->id)->update(['balance' => DB::raw('balance - '.$amount->kobo)]);

            return $entry;
        });
    }

    /**
     * Credit a customer's wallet. Idempotent: replaying the same key returns the
     * original entry without double-crediting.
     */
    public function issue(
        User $user,
        Money $amount,
        ?Model $source = null,
        ?string $idempotencyKey = null,
        ?string $reason = null,
        ?Admin $admin = null,
    ): StoreCreditEntry {
        if ($idempotencyKey !== null) {
            $existing = StoreCreditEntry::where('idempotency_key', $idempotencyKey)->first();
            if ($existing) {
                return $existing;
            }
        }

        return DB::transaction(function () use ($user, $amount, $source, $idempotencyKey, $reason, $admin): StoreCreditEntry {
            $wallet = $this->walletFor($user);

            $entry = $wallet->entries()->create([
                'amount' => $amount,
                'type' => StoreCreditEntry::TYPE_REFUND_CREDIT,
                'source_type' => $source?->getMorphClass(),
                'source_id' => $source?->getKey(),
                'reason' => $reason,
                'expires_at' => now()->addMonths((int) config('gobuy.returns.store_credit_expiry_months')),
                'idempotency_key' => $idempotencyKey,
                'admin_id' => $admin?->id,
            ]);

            // Atomic, cast-free balance bump (Money cast forbids ->increment()).
            StoreCredit::whereKey($wallet->id)->update(['balance' => DB::raw('balance + '.$amount->kobo)]);

            return $entry;
        });
    }

    /**
     * Expire an unspent credit grant whose window has passed. Posts an offsetting
     * `expiry` entry for the still-available portion (capped at the live balance,
     * so already-spent credit isn't clawed back twice) and is idempotent per grant.
     */
    public function expireEntry(StoreCreditEntry $grant): void
    {
        if ($grant->amount->kobo <= 0) {
            return;
        }

        $key = "expiry:{$grant->id}";
        if (StoreCreditEntry::where('idempotency_key', $key)->exists()) {
            return; // already processed
        }

        DB::transaction(function () use ($grant, $key): void {
            $wallet = StoreCredit::whereKey($grant->store_credit_id)->lockForUpdate()->first();
            if ($wallet === null) {
                return;
            }

            $expire = min($grant->amount->kobo, $wallet->balance->kobo);

            $wallet->entries()->create([
                'amount' => Money::fromKobo(-$expire),
                'type' => StoreCreditEntry::TYPE_EXPIRY,
                'reason' => 'Store credit expired',
                'idempotency_key' => $key,
            ]);

            if ($expire > 0) {
                StoreCredit::whereKey($wallet->id)->update(['balance' => DB::raw('balance - '.$expire)]);
            }
        });
    }
}
