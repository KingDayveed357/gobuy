<?php

namespace App\Modules\Pricing\Services;

use App\Modules\Pricing\Models\Coupon;

class PricingService
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function createCoupon(array $data): Coupon
    {
        return Coupon::create($data);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function updateCoupon(Coupon $coupon, array $data): Coupon
    {
        $coupon->update($data);

        return $coupon;
    }

    public function deleteCoupon(Coupon $coupon): void
    {
        $coupon->delete();
    }
}
