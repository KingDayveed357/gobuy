<?php

namespace App\Modules\Order\DTOs;

/**
 * Validated checkout input. DTOs are used deliberately for the
 * checkout/order/payment flow where the shape of data matters.
 */
final class CheckoutData
{
    public function __construct(
        public readonly string $customerName,
        public readonly string $customerEmail,
        public readonly string $customerPhone,
        public readonly string $addressLine,
        public readonly string $city,
        public readonly string $state,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            customerName: $data['customer_name'],
            customerEmail: $data['customer_email'],
            customerPhone: $data['customer_phone'],
            addressLine: $data['address_line'],
            city: $data['city'],
            state: $data['state'],
        );
    }

    /**
     * @return array<string, string>
     */
    public function toOrderAttributes(): array
    {
        return [
            'customer_name' => $this->customerName,
            'customer_email' => $this->customerEmail,
            'customer_phone' => $this->customerPhone,
            'address_line' => $this->addressLine,
            'city' => $this->city,
            'state' => $this->state,
        ];
    }
}
