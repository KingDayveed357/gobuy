@extends('layouts.storefront')

@section('title', 'My wishlist — gobuy')

@section('content')
    <section class="pt-5 pb-9">
        <div class="container-small">
            <nav class="mb-3" aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('account.dashboard') }}">Account</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Wishlist</li>
                </ol>
            </nav>

            <h2 class="mb-5">Wishlist<span class="text-body-tertiary fw-normal ms-2">({{ $items->total() }})</span></h2>

            @if ($items->isEmpty())
                <div class="border-y border-translucent text-center py-6">
                    <div class="mb-2"><span class="far fa-heart fs-5 text-body-tertiary"></span></div>
                    <h5 class="mb-1">Your wishlist is empty</h5>
                    <p class="text-body-tertiary mb-3">Tap the heart on any product to save it here.</p>
                    <a href="{{ route('products.index') }}" class="btn btn-primary">Browse products</a>
                </div>
            @else
                <div class="border-y border-translucent">
                    <div class="table-responsive scrollbar">
                        <table class="table fs-9 mb-0">
                            <thead>
                                <tr>
                                    <th class="align-middle" scope="col" style="width:7%;"></th>
                                    <th class="align-middle" scope="col" style="width:38%; min-width:250px;">PRODUCTS</th>
                                    <th class="align-middle" scope="col" style="width:20%;">VARIANT</th>
                                    <th class="align-middle text-end" scope="col" style="width:13%;">PRICE</th>
                                    <th class="align-middle text-end pe-0" scope="col" style="width:22%;"> </th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($items as $item)
                                    @php($product = $item->product)
                                    @php($variant = $product->primaryVariant())
                                    <tr class="position-static">
                                        <td class="align-middle white-space-nowrap ps-0 py-2">
                                            <a class="border border-translucent rounded-2 d-inline-block p-1" href="{{ route('products.show', $product) }}">
                                                <img src="{{ $product->imageUrl() }}" alt="{{ $product->name }}" width="53" height="53" style="object-fit:contain;">
                                            </a>
                                        </td>
                                        <td class="align-middle pe-4">
                                            <a class="fw-semibold mb-0 line-clamp-2 text-body-emphasis text-decoration-none" href="{{ route('products.show', $product) }}">{{ $product->name }}</a>
                                            <span class="d-block fs-10 text-body-tertiary">{{ $product->category?->name }}</span>
                                        </td>
                                        <td class="align-middle white-space-nowrap fs-9 text-body">{{ $product->hasVariants() ? 'Multiple options' : ($variant?->label() ?? '—') }}</td>
                                        <td class="align-middle fs-9 fw-semibold text-end"><x-price-tag :product="$product" /></td>
                                        <td class="align-middle text-end text-nowrap pe-0">
                                            <form action="{{ route('wishlist.toggle', $product) }}" method="POST" class="d-inline">
                                                @csrf
                                                <button class="btn btn-sm text-body-quaternary text-danger-hover me-2" title="Remove"><span class="fas fa-trash"></span></button>
                                            </form>
                                            @if ($product->hasVariants())
                                                <a href="{{ route('products.show', $product) }}" class="btn btn-sm btn-phoenix-primary fs-10"><span class="fas fa-sliders me-1"></span>Choose options</a>
                                            @else
                                                <form action="{{ route('wishlist.to-cart', $product) }}" method="POST" class="d-inline">
                                                    @csrf
                                                    <button class="btn btn-primary btn-sm fs-10" @disabled(! $product->isInStock())>
                                                        <span class="fas fa-shopping-cart me-1 fs-10"></span>{{ $product->isInStock() ? 'Add to cart' : 'Sold out' }}
                                                    </button>
                                                </form>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>

                @if ($items->hasPages())
                    <div class="d-flex justify-content-center mt-4">{{ $items->onEachSide(1)->links() }}</div>
                @endif
            @endif
        </div>
    </section>
@endsection
