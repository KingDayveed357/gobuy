<?php

namespace App\Modules\Order\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CheckoutRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'customer_name' => ['required', 'string', 'max:255'],
            'customer_email' => ['required', 'email', 'max:255'],
            'customer_phone' => ['required', 'string', 'max:30'],

            'delivery_method' => ['required', 'in:home_delivery,pickup'],
            'pickup_location_id' => ['required_if:delivery_method,pickup', 'nullable', 'exists:locations,id'],

            'payment_method' => ['nullable', 'in:paystack,bank_transfer,pod'],
            'checkout_token' => ['nullable', 'string', 'max:255'],

            // A delivery address is only required when shipping to the customer.
            'address_line' => ['required_if:delivery_method,home_delivery', 'nullable', 'string', 'max:255'],
            'city' => ['required_if:delivery_method,home_delivery', 'nullable', 'string', 'max:120'],
            'state' => ['required_if:delivery_method,home_delivery', 'nullable', 'string', 'max:120'],
        ];
    }
}
