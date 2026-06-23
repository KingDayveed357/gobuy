<div>
    @if (empty($lines))
        <div class="card">
            <div class="card-body text-center py-7">
                <span class="fas fa-shopping-cart fs-5 text-body-tertiary mb-3 d-block"></span>
                <p class="text-body-tertiary mb-3">Your cart is empty.</p>
                <a href="{{ route('products.index') }}" class="btn btn-primary">Start shopping</a>
            </div>
        </div>
    @else
        <div class="row g-5" wire:loading.class="opacity-75">
            <div class="col-12 col-lg-8">
                <div class="table-responsive scrollbar mx-n1 px-1">
                    <table class="table fs-9 mb-0 border-top border-translucent">
                        <thead>
                            <tr>
                                <th class="align-middle"></th>
                                <th class="align-middle" style="min-width:250px;">PRODUCTS</th>
                                <th class="align-middle text-end" style="width:160px;">PRICE</th>
                                <th class="align-middle text-center" style="width:200px;">QUANTITY</th>
                                <th class="align-middle text-end" style="width:150px;">TOTAL</th>
                                <th class="align-middle text-end pe-0"></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($lines as $line)
                                @php($item = $line['item'])
                                @php($variant = $item->variant)
                                @php($product = $variant->product)
                                <tr class="cart-table-row" wire:key="cart-item-{{ $item->id }}">
                                    <td class="align-middle white-space-nowrap py-2">
                                        <a class="d-block border border-translucent rounded-2 p-1" href="{{ route('products.show', $product) }}">
                                            <img src="{{ $product->imageUrl() }}" alt="{{ $product->name }}" width="53" height="53" style="object-fit: contain;">
                                        </a>
                                    </td>
                                    <td class="products align-middle">
                                        <a class="fw-semibold mb-0 line-clamp-2 text-body-emphasis text-decoration-none" href="{{ route('products.show', $product) }}">{{ $product->name }}</a>
                                        @unless ($variant->is_default)
                                            <div class="fs-10 text-body-tertiary">{{ $variant->label() }}</div>
                                        @endunless
                                        @if ($line['price']->isWholesale)
                                            <span class="badge badge-phoenix badge-phoenix-primary ms-1">Wholesale</span>
                                        @endif
                                    </td>
                                    <td class="price align-middle fw-semibold text-end">
                                        @if ($line['price']->hasDiscount())
                                            <div class="text-body-tertiary text-decoration-line-through fs-10">{{ money($line['price']->retailPrice) }}</div>
                                            <div class="text-danger">{{ money($line['price']->unitPrice) }}</div>
                                        @else
                                            {{ money($line['price']->unitPrice) }}
                                        @endif
                                    </td>
                                    <td class="quantity align-middle">
                                        {{-- Real PATCH form = no-JS fallback. With JS, the ± buttons (wire:click) and
                                             input change (wire:change) persist instantly; the Update button is hidden. --}}
                                        <form action="{{ route('cart.items.update', $item) }}" method="POST" class="d-flex justify-content-center align-items-center gap-1">
                                            @csrf @method('PATCH')
                                            <button type="button" class="btn btn-sm btn-phoenix-secondary px-2" title="Decrease"
                                                    wire:click="decrement({{ $item->id }})" wire:loading.attr="disabled">&minus;</button>
                                            <input class="form-control form-control-sm text-center" type="number" name="quantity"
                                                   value="{{ $item->quantity }}" min="1" max="{{ $variant->stock }}" style="width:64px;"
                                                   wire:change="setQuantity({{ $item->id }}, $event.target.value)">
                                            <button type="button" class="btn btn-sm btn-phoenix-secondary px-2" title="Increase"
                                                    wire:click="increment({{ $item->id }})" wire:loading.attr="disabled"
                                                    @disabled($item->quantity >= $variant->stock)>+</button>
                                            <button class="btn btn-sm btn-phoenix-secondary" type="submit" title="Update" data-js-hidden><span class="fas fa-rotate-right"></span></button>
                                        </form>
                                    </td>
                                    <td class="total align-middle fw-bold text-body-highlight text-end">{{ money($line['lineTotal']) }}</td>
                                    <td class="align-middle text-end pe-0">
                                        <form action="{{ route('cart.items.destroy', $item) }}" method="POST" wire:submit="remove({{ $item->id }})">
                                            @csrf @method('DELETE')
                                            <button class="btn btn-sm text-body-tertiary text-danger-hover" type="submit" title="Remove"
                                                    wire:loading.attr="disabled" wire:target="remove({{ $item->id }})"><span class="fas fa-trash"></span></button>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="d-flex justify-content-between mt-3">
                    <a href="{{ route('products.index') }}" class="btn btn-link p-0"><span class="fas fa-chevron-left me-1 fs-10"></span>Continue shopping</a>
                    <form action="{{ route('cart.clear') }}" method="POST" wire:submit="clear">
                        @csrf @method('DELETE')
                        <button class="btn btn-link text-danger p-0" type="submit" wire:loading.attr="disabled" wire:target="clear">Clear cart</button>
                    </form>
                </div>
            </div>

            <div class="col-12 col-lg-4">
                <div class="card">
                    <div class="card-body">
                        <h3 class="card-title mb-3">Summary</h3>
                        <div class="d-flex justify-content-between">
                            <p class="text-body fw-semibold">Items</p>
                            <p class="text-body-emphasis fw-semibold">{{ $count }}</p>
                        </div>
                        <div class="d-flex justify-content-between">
                            <p class="text-body fw-semibold">Subtotal</p>
                            <p class="text-body-emphasis fw-semibold">{{ money($subtotal) }}</p>
                        </div>

                        @if ($appliedCoupon)
                            <div class="d-flex justify-content-between align-items-center bg-success-subtle rounded-2 px-3 py-2 mb-2">
                                <div>
                                    <span class="fas fa-tag text-success me-2"></span>
                                    <span class="fw-bold text-success-emphasis">{{ $appliedCoupon->code }}</span>
                                    <span class="d-block fs-9 text-body-tertiary ms-4">Promo applied</span>
                                </div>
                                <form action="{{ route('cart.coupon.remove') }}" method="POST" wire:submit="removeCoupon">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn btn-link btn-sm text-danger p-0" aria-label="Remove promo code">Remove</button>
                                </form>
                            </div>
                            <div class="d-flex justify-content-between">
                                <p class="text-body fw-semibold">Discount</p>
                                <p class="text-success fw-semibold">&minus;{{ money($discount) }}</p>
                            </div>
                        @else
                            <form action="{{ route('cart.coupon.apply') }}" method="POST" wire:submit="applyCoupon" class="mt-3 mb-2">
                                @csrf
                                <label for="promo-code" class="form-label fs-9 fw-semibold text-body-tertiary mb-1">Have a promo code?</label>
                                <div class="input-group input-group-sm">
                                    <input type="text" id="promo-code" name="code" wire:model="couponCode" class="form-control text-uppercase" placeholder="Enter code" autocomplete="off" required>
                                    <button type="submit" class="btn btn-phoenix-primary" wire:loading.attr="disabled" wire:target="applyCoupon">
                                        <span wire:loading.remove wire:target="applyCoupon">Apply</span>
                                        <span wire:loading wire:target="applyCoupon"><span class="fas fa-spinner fa-spin"></span></span>
                                    </button>
                                </div>
                            </form>
                        @endif

                        <div class="d-flex justify-content-between">
                            <p class="text-body fw-semibold">Delivery</p>
                            <p class="text-body-tertiary fw-semibold">Calculated at checkout</p>
                        </div>
                        <div class="d-flex justify-content-between border-y border-dashed py-3 mb-4 mt-3">
                            <h4 class="mb-0">Total</h4>
                            <h4 class="mb-0">{{ money($total) }}</h4>
                        </div>
                        <a href="{{ route('checkout.show') }}" class="btn btn-primary w-100">Proceed to checkout<span class="fas fa-chevron-right ms-1 fs-10"></span></a>
                        @auth
                            @if (auth()->user()->isWholesale())
                                <a href="{{ route('proforma.show') }}" class="btn btn-phoenix-secondary w-100 mt-2"><span class="fas fa-file-invoice me-2"></span>Download proforma invoice</a>
                            @endif
                        @endauth
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
