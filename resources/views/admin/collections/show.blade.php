@extends('admin.layouts.app')

@section('title', $collection->name.' — Collection')
@section('page-title', 'Collection')

@section('content')
    <x-admin.page-header :title="$collection->name" subtitle="Add, remove and re-order the products in this collection.">
        <x-slot:actions>
            <a href="{{ route('admin.collections.index') }}" class="btn btn-phoenix-secondary btn-sm"><span class="fas fa-chevron-left me-1"></span>All collections</a>
        </x-slot:actions>
    </x-admin.page-header>

    <div class="row g-4">
        <div class="col-12 col-lg-8">
            <x-admin.card title="Products in this collection" subtitle="Drag the order values and save. Storefront shows them in this order." flush>
                @if ($collection->products->isEmpty())
                    <x-admin.empty-state icon="fa-box-open" text="No products yet — add some from the panel on the right." />
                @else
                    <form action="{{ route('admin.collections.reorder', $collection) }}" method="POST">
                        @csrf
                        <div class="table-responsive">
                            <table class="table admin-table mb-0">
                                <thead><tr><th style="width:60px;">Order</th><th>Product</th><th class="text-end">Remove</th></tr></thead>
                                <tbody>
                                    @foreach ($collection->products as $i => $product)
                                        <tr>
                                            <td><input type="number" class="form-control form-control-sm" name="product_ids[{{ $i }}]" value="{{ $product->id }}" hidden>
                                                <span class="fw-bold text-body-tertiary">{{ $i + 1 }}</span></td>
                                            <td class="d-flex align-items-center gap-2">
                                                <img src="{{ $product->imageUrl() }}" alt="" width="36" height="36" class="rounded-2 object-fit-cover">
                                                <span class="fw-semibold fs-9">{{ $product->name }}</span>
                                            </td>
                                            <td class="text-end">
                                                <button formaction="{{ route('admin.collections.detach', [$collection, $product]) }}" formmethod="POST" class="btn btn-sm btn-phoenix-danger" name="_method" value="DELETE"><span class="fas fa-times"></span></button>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        <div class="p-3 border-top border-translucent">
                            <p class="fs-9 text-body-tertiary mb-2">Rows post in the order shown; use the arrows in your browser or re-add to change order.</p>
                            <button class="btn btn-sm btn-primary" type="submit">Save order</button>
                        </div>
                    </form>
                @endif
            </x-admin.card>
        </div>

        <div class="col-12 col-lg-4">
            <x-admin.card title="Add a product">
                @if ($available->isEmpty())
                    <p class="text-body-tertiary fs-9 mb-0">All active products are already in this collection (showing up to 200).</p>
                @else
                    <form action="{{ route('admin.collections.attach', $collection) }}" method="POST">
                        @csrf
                        <div class="mb-3">
                            <select class="form-select" name="product_id" required>
                                <option value="">Choose a product…</option>
                                @foreach ($available as $product)
                                    <option value="{{ $product->id }}">{{ $product->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <button class="btn btn-primary w-100" type="submit"><span class="fas fa-plus me-1"></span>Add to collection</button>
                    </form>
                @endif
            </x-admin.card>
        </div>
    </div>
@endsection
