@extends('admin.layouts.app')

@section('title', 'Products — gobuy admin')
@section('page-title', 'Products')

@section('content')
    <x-admin.page-header title="Products" subtitle="{{ $products->total() }} product(s)">
        <x-slot:actions>
            <a href="{{ route('admin.products.create') }}" class="btn btn-primary"><span class="fas fa-plus me-2"></span>New product</a>
        </x-slot:actions>
    </x-admin.page-header>

    <x-admin.table
        :cols="[
            ['label' => 'Name'],
            ['label' => 'SKU'],
            ['label' => 'Category'],
            ['label' => 'Retail', 'align' => 'end'],
            ['label' => 'Stock', 'align' => 'end'],
            ['label' => 'Status'],
            ['label' => '', 'align' => 'end'],
        ]"
        :empty="$products->isEmpty()"
        empty-icon="fa-box"
        empty-text="No products match your search."
    >
        <x-slot:toolbar>
            <form method="GET" class="admin-toolbar mb-0 w-100">
                <div class="admin-toolbar-grow" style="max-width: 340px;">
                    <div class="position-relative">
                        <span class="fas fa-search position-absolute text-body-tertiary" style="top: 50%; left: 0.85rem; transform: translateY(-50%);"></span>
                        <input class="form-control form-control-sm ps-5" type="search" name="q" value="{{ request('q') }}" placeholder="Search name or SKU">
                    </div>
                </div>
                <button class="btn btn-sm btn-phoenix-secondary" type="submit">Search</button>
            </form>
        </x-slot:toolbar>

        @foreach ($products as $product)
            <tr>
                <td>
                    <div class="d-flex align-items-center">
                        <div class="admin-thumb me-2"><img src="{{ $product->imageUrl() }}" alt=""></div>
                        <span class="fw-semibold text-body-emphasis">{{ $product->name }}</span>
                    </div>
                </td>
                <td class="text-body-tertiary">{{ $product->sku }}</td>
                <td>{{ $product->category->name }}</td>
                <td class="text-end">₦{{ number_format($product->retail_price, 2) }}</td>
                <td class="text-end">{{ $product->stock }}</td>
                <td><x-admin.status-badge :value="$product->status" /></td>
                <td class="text-end">
                    <div class="table-actions">
                        <a href="{{ route('admin.products.edit', $product) }}" class="btn btn-sm btn-phoenix-secondary">Edit</a>
                        <form action="{{ route('admin.products.destroy', $product) }}" method="POST"
                              onsubmit="return confirm('Delete this product?');">
                            @csrf
                            @method('DELETE')
                            <button class="btn btn-sm btn-phoenix-danger" title="Delete"><span class="fas fa-trash"></span></button>
                        </form>
                    </div>
                </td>
            </tr>
        @endforeach
    </x-admin.table>

    <div class="mt-4">{{ $products->links() }}</div>
@endsection
