@component('mail::message')
# Good news — it's back!

**{{ $product->name }}**@if($variant->label() && $variant->label() !== $product->name) ({{ $variant->label() }})@endif is back in stock.

Popular items sell out quickly, so grab yours while it lasts.

@component('mail::button', ['url' => route('products.show', $product)])
View product
@endcomponent

Thanks for shopping with Quintessential Mart,<br>
The Quintessential Mart Team
@endcomponent
