<?php

namespace App\Modules\Customer\Services;

use App\Admin\Models\Admin;
use App\Models\User;
use App\Modules\Customer\Models\WholesaleProfile;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;

class WholesaleApprovalService
{
    /**
     * Submit (or re-submit) a wholesale application. Moves the user to
     * "pending" — pricing stays retail until an admin approves.
     *
     * @param  array<string, mixed>  $data
     * @param  array<int, UploadedFile>  $documents
     */
    public function apply(User $user, array $data, array $documents = []): WholesaleProfile
    {
        $profile = $user->wholesaleProfile()->updateOrCreate([], [
            'business_name' => $data['business_name'],
            'rc_number' => $data['rc_number'] ?? null,
            'business_phone' => $data['business_phone'],
            'business_address' => $data['business_address'],
            'industry' => $data['industry'] ?? null,
            'intent' => $data['intent'] ?? null,
            'status' => WholesaleProfile::STATUS_PENDING,
            'reviewed_at' => null,
            'reviewed_by' => null,
        ]);

        foreach ($documents as $document) {
            $profile->addMedia($document)->toMediaCollection(WholesaleProfile::MEDIA_DOCUMENTS);
        }

        $user->update(['wholesale_status' => User::WHOLESALE_PENDING]);

        Log::info('Wholesale application submitted', ['user_id' => $user->id]);

        return $profile;
    }

    /**
     * Approve an application: flip the customer to wholesale so PriceResolver
     * starts applying wholesale prices, and assign a pricing tier.
     */
    public function approve(User $user, Admin $admin, ?string $tier = null): void
    {
        $user->update([
            'customer_type' => User::TYPE_WHOLESALE,
            'wholesale_status' => User::WHOLESALE_APPROVED,
        ]);

        $user->wholesaleProfile?->update([
            'status' => WholesaleProfile::STATUS_APPROVED,
            'tier' => in_array($tier, WholesaleProfile::TIERS, true) ? $tier : 'bronze',
            'reviewed_at' => now(),
            'reviewed_by' => $admin->id,
        ]);

        Log::info('Wholesale application approved', ['user_id' => $user->id, 'admin_id' => $admin->id, 'tier' => $tier]);
    }

    public function reject(User $user, Admin $admin): void
    {
        $user->update([
            'customer_type' => User::TYPE_RETAIL,
            'wholesale_status' => User::WHOLESALE_REJECTED,
        ]);

        $user->wholesaleProfile?->update([
            'status' => WholesaleProfile::STATUS_REJECTED,
            'reviewed_at' => now(),
            'reviewed_by' => $admin->id,
        ]);

        Log::info('Wholesale application rejected', ['user_id' => $user->id, 'admin_id' => $admin->id]);
    }
}
