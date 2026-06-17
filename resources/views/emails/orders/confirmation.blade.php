@component('mail::message')
# Thank you for your order!

Hi {{ $order->customer_name }}, we've received your payment and your order is confirmed.

**Order:** {{ $order->order_number }}
**Placed:** {{ $order->placed_at?->format('M j, Y g:i A') }}

@component('mail::table')
| Item | Qty | Total |
|:-----|:---:|------:|
@foreach ($order->items as $item)
| {{ $item->name }} | {{ $item->quantity }} | ₦{{ number_format($item->line_total, 2) }} |
@endforeach
@endcomponent

**Subtotal:** ₦{{ number_format($order->subtotal, 2) }}
**Delivery:** ₦{{ number_format($order->delivery_fee, 2) }}
**Total:** ₦{{ number_format($order->total, 2) }}

**Delivery to:**
{{ $order->customer_name }}
{{ $order->address_line }}, {{ $order->city }}, {{ $order->state }}

@component('mail::button', ['url' => route('orders.success', $order)])
View your order
@endcomponent

Thanks for shopping with gobuy,
The gobuy team
@endcomponent
