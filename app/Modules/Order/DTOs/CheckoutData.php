<?php

namespace App\Modules\Order\DTOs;

use App\Modules\Logistics\Models\Shipment;
use App\Modules\Order\Enums\PaymentMethod;

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
        public readonly string $deliveryMethod = Shipment::METHOD_HOME,
        public readonly ?int $pickupLocationId = null,
        public readonly string $paymentMethod = PaymentMethod::Paystack->value,
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
            deliveryMethod: $data['delivery_method'] ?? Shipment::METHOD_HOME,
            pickupLocationId: isset($data['pickup_location_id']) ? (int) $data['pickup_location_id'] : null,
            paymentMethod: $data['payment_method'] ?? PaymentMethod::Paystack->value,
        );
    }

    public function isPickup(): bool
    {
        return $this->deliveryMethod === Shipment::METHOD_PICKUP;
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
            'payment_method' => $this->paymentMethod,
        ];
    }
}
