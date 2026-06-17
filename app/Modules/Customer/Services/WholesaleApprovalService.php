<?php

namespace App\Modules\Customer\Services;

use App\Admin\Models\Admin;
use App\Models\User;
use App\Modules\Customer\Models\WholesaleProfile;
use Illuminate\Support\Facades\Log;

class WholesaleApprovalService
{
    /**
     * Submit (or re-submit) a wholesale application. Moves the user to
     * "pending" — pricing stays retail until an admin approves.
     *
     * @param  array<string, mixed>  $data
     */
    public function apply(User $user, array $data): WholesaleProfile
    {
        $profile = $user->wholesaleProfile()->updateOrCreate([], $data);

        $user->update(['wholesale_status' => User::WHOLESALE_PENDING]);

        Log::info('Wholesale application submitted', ['user_id' => $user->id]);

        return $profile;
    }

    /**
     * Approve an application: flip the customer to wholesale so PriceResolver
     * starts applying wholesale prices.
     */
    public function approve(User $user, Admin $admin): void
    {
        $user->update([
            'customer_type' => User::TYPE_WHOLESALE,
            'wholesale_status' => User::WHOLESALE_APPROVED,
        ]);

        $user->wholesaleProfile?->update([
            'reviewed_at' => now(),
            'reviewed_by' => $admin->id,
        ]);

        Log::info('Wholesale application approved', ['user_id' => $user->id, 'admin_id' => $admin->id]);
    }

    public function reject(User $user, Admin $admin): void
    {
        $user->update([
            'customer_type' => User::TYPE_RETAIL,
            'wholesale_status' => User::WHOLESALE_REJECTED,
        ]);

        $user->wholesaleProfile?->update([
            'reviewed_at' => now(),
            'reviewed_by' => $admin->id,
        ]);

        Log::info('Wholesale application rejected', ['user_id' => $user->id, 'admin_id' => $admin->id]);
    }
}
