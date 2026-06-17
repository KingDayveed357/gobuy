@extends('layouts.storefront')

@section('title', 'Your cart — gobuy')

@section('content')
    <section class="pt-5 pb-9">
        <div class="container-small cart">
            <nav class="mb-3" aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('home') }}">Home</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Cart</li>
                </ol>
            </nav>
            <h2 class="mb-6">Cart</h2>

            @if (empty($lines))
                <div class="card">
                    <div class="card-body text-center py-7">
                        <span class="fas fa-shopping-cart fs-5 text-body-tertiary mb-3 d-block"></span>
                        <p class="text-body-tertiary mb-3">Your cart is empty.</p>
                        <a href="{{ route('products.index') }}" class="btn btn-primary">Start shopping</a>
                    </div>
                </div>
            @else
                <div class="row g-5">
                    <div class="col-12 col-lg-8">
                        <div class="table-responsive scrollbar mx-n1 px-1">
                            <table class="table fs-9 mb-0 border-top border-translucent">
                                <thead>
                                    <tr>
                                        <th class="align-middle" scope="col"></th>
                                        <th class="align-middle" scope="col" style="min-width:250px;">PRODUCTS</th>
                                        <th class="align-middle text-end" scope="col" style="width:160px;">PRICE</th>
                                        <th class="align-middle text-center" scope="col" style="width:220px;">QUANTITY</th>
                                        <th class="align-middle text-end" scope="col" style="width:160px;">TOTAL</th>
                                        <th class="align-middle text-end pe-0" scope="col"></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($lines as $line)
                                        @php($item = $line['item'])
                                        <tr class="cart-table-row">
                                            <td class="align-middle white-space-nowrap py-2">
                                                <a class="d-block border border-translucent rounded-2 p-1" href="{{ route('products.show', $item->product) }}">
                                                    <img src="{{ $item->product->imageUrl() }}" alt="{{ $item->product->name }}" width="53" height="53" style="object-fit: contain;">
                                                </a>
                                            </td>
                                            <td class="products align-middle">
                                                <a class="fw-semibold mb-0 line-clamp-2 text-body-emphasis text-decoration-none" href="{{ route('products.show', $item->product) }}">
                                                    {{ $item->product->name }}
                                                </a>
                                                @if ($line['price']->isWholesale)
                                                    <span class="badge badge-phoenix badge-phoenix-success ms-1">Wholesale</span>
                                                @endif
                                            </td>
                                            <td class="price align-middle text-body fw-semibold text-end">₦{{ number_format($line['price']->unitPrice, 2) }}</td>
                                            <td class="quantity align-middle">
                                                <form action="{{ route('cart.items.update', $item) }}" method="POST" class="d-flex justify-content-center gap-1">
                                                    @csrf
                                                    @method('PATCH')
                                                    <input class="form-control form-control-sm text-center" type="number" name="quantity"
                                                           value="{{ $item->quantity }}" min="0" max="{{ $item->product->stock }}" style="width:70px;">
                                                    <button class="btn btn-sm btn-phoenix-secondary" type="submit" title="Update">
                                                        <span class="fas fa-rotate-right"></span>
                                                    </button>
                                                </form>
                                            </td>
                                            <td class="total align-middle fw-bold text-body-highlight text-end">₦{{ number_format($line['lineTotal'], 2) }}</td>
                                            <td class="align-middle text-end pe-0">
                                                <form action="{{ route('cart.items.destroy', $item) }}" method="POST">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button class="btn btn-sm text-body-tertiary text-danger-hover" type="submit" title="Remove">
                                                        <span class="fas fa-trash"></span>
                                                    </button>
                                                </form>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <div class="d-flex justify-content-between mt-3">
                            <a href="{{ route('products.index') }}" class="btn btn-link p-0"><span class="fas fa-chevron-left me-1 fs-10"></span>Continue shopping</a>
                            <form action="{{ route('cart.clear') }}" method="POST">
                                @csrf
                                @method('DELETE')
                                <button class="btn btn-link text-danger p-0" type="submit">Clear cart</button>
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
                                    <p class="text-body fw-semibold">Items subtotal</p>
                                    <p class="text-body-emphasis fw-semibold">₦{{ number_format($subtotal, 2) }}</p>
                                </div>
                                <div class="d-flex justify-content-between">
                                    <p class="text-body fw-semibold">Delivery</p>
                                    <p class="text-body-tertiary fw-semibold">Calculated at checkout</p>
                                </div>
                                <div class="d-flex justify-content-between border-y border-dashed py-3 mb-4">
                                    <h4 class="mb-0">Subtotal</h4>
                                    <h4 class="mb-0">₦{{ number_format($subtotal, 2) }}</h4>
                                </div>
                                <a href="{{ route('checkout.show') }}" class="btn btn-primary w-100">Proceed to checkout<span class="fas fa-chevron-right ms-1 fs-10"></span></a>
                            </div>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </section>
@endsection
