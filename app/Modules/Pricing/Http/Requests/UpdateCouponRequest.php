<?php

namespace App\Modules\Pricing\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCouponRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user('admin') !== null;
    }

    public function rules(): array
    {
        $couponId = $this->route('coupon')->id;

        return [
            'code' => ['required', 'string', 'max:50', Rule::unique('coupons', 'code')->ignore($couponId)],
            'type' => ['required', 'string', Rule::in(['fixed', 'percentage', 'free_shipping'])],
            'value' => ['required', 'numeric', 'min:0'],
            'min_cart_value' => ['nullable', 'numeric', 'min:0'],
            'is_active' => ['boolean'],
            'eligibility' => ['required', 'string', Rule::in(['retail', 'wholesale', 'both'])],
            'starts_at' => ['nullable', 'date'],
            'expires_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'usage_limit_total' => ['nullable', 'integer', 'min:1'],
            'usage_limit_per_user' => ['nullable', 'integer', 'min:1'],
        ];
    }
}
