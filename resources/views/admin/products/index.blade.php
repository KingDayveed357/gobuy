@extends('admin.layouts.app')

@section('title', 'Products — gobuy admin')

@php($currentStatus = request('status', 'all'))
@php($tabs = ['all' => 'All', 'active' => 'Active', 'draft' => 'Drafts', 'archived' => 'Archived'])

@section('content')
    <x-admin.page-header title="Products" subtitle="{{ $statusCounts['all'] }} product(s) in your catalog">
        <x-slot:actions>
            <a href="{{ route('admin.products.create') }}" class="btn btn-primary"><span class="fas fa-plus me-2"></span>Add product</a>
        </x-slot:actions>
    </x-admin.page-header>

    {{-- Status count tabs --}}
    <ul class="nav nav-links mb-3 mx-n3">
        @foreach ($tabs as $key => $label)
            <li class="nav-item">
                <a class="nav-link px-3 {{ $currentStatus === $key ? 'active fw-bold' : '' }}"
                   href="{{ route('admin.products.index', array_merge(request()->except(['status', 'page']), $key === 'all' ? [] : ['status' => $key])) }}">
                    {{ $label }} <span class="text-body-tertiary fw-semibold">({{ $statusCounts[$key] }})</span>
                </a>
            </li>
        @endforeach
    </ul>

    {{-- Filters --}}
    <form method="GET" class="admin-toolbar">
        <input type="hidden" name="status" value="{{ $currentStatus === 'all' ? '' : $currentStatus }}">
        <div class="admin-toolbar-grow" style="max-width: 320px;">
            <div class="position-relative">
                <span class="fas fa-search position-absolute text-body-tertiary" style="top:50%;left:.85rem;transform:translateY(-50%);"></span>
                <input class="form-control form-control-sm ps-5" type="search" name="q" value="{{ request('q') }}" placeholder="Search name or SKU">
            </div>
        </div>
        <select name="category" class="form-select form-select-sm w-auto" onchange="this.form.submit()">
            <option value="">All categories</option>
            @foreach ($categoryOptions as $option)
                <option value="{{ $option['id'] }}" @selected((string) request('category') === (string) $option['id'])>
                    {!! str_repeat('&nbsp;&nbsp;', $option['depth']) !!}{{ $option['name'] }}
                </option>
            @endforeach
        </select>
        <button class="btn btn-sm btn-phoenix-secondary" type="submit">Filter</button>
        @if (request()->hasAny(['q', 'category']))
            <a href="{{ route('admin.products.index', $currentStatus === 'all' ? [] : ['status' => $currentStatus]) }}" class="btn btn-sm btn-link text-body-tertiary">Clear</a>
        @endif
    </form>

    <x-admin.table
        :cols="[
            ['label' => ''],
            ['label' => 'Product'],
            ['label' => 'Price', 'align' => 'end'],
            ['label' => 'Stock', 'align' => 'end'],
            ['label' => 'Category'],
            ['label' => 'Status'],
            ['label' => 'SKU'],
            ['label' => '', 'align' => 'end'],
        ]"
        :empty="$products->isEmpty()"
        empty-icon="fa-box"
        empty-text="No products match your filters."
    >
        @foreach ($products as $product)
            @php($variant = $product->primaryVariant())
            <tr>
                <td style="width:56px;"><div class="admin-thumb"><img src="{{ $product->imageUrl() }}" alt=""></div></td>
                <td>
                    <a class="fw-semibold text-body-emphasis text-decoration-none line-clamp-1" href="{{ route('admin.products.edit', $product) }}">{{ $product->name }}</a>
                    @if ($product->hasVariants())<span class="badge badge-phoenix badge-phoenix-info ms-1">{{ $product->variants->count() }} variants</span>@endif
                </td>
                <td class="text-end fw-semibold">{{ money($variant?->retail_price) }}</td>
                <td class="text-end">
                    @if ($product->stock <= 5)
                        <span class="badge badge-phoenix badge-phoenix-{{ $product->stock == 0 ? 'danger' : 'warning' }}">{{ $product->stock }}</span>
                    @else
                        {{ $product->stock }}
                    @endif
                </td>
                <td class="text-body-tertiary">{{ $product->category->name ?? 'Uncategorized' }}</td>
                <td><x-admin.status-badge :value="$product->status" /></td>
                <td class="text-body-tertiary">{{ $variant?->sku }}</td>
                <td class="text-end">
                    <div class="table-actions">
                        <a href="{{ route('admin.products.edit', $product) }}" class="btn btn-sm btn-phoenix-secondary">Edit</a>
                        <x-admin.promote-button
                            :url="route('products.show', $product)"
                            :name="$product->name"
                            :image="$product->imageUrl()"
                            :price="$variant?->retail_price ? money($variant->retail_price) : null" />
                        <button class="btn btn-sm btn-phoenix-danger" type="button"
                                data-bs-toggle="modal" data-bs-target="#deleteModal"
                                data-action="{{ route('admin.products.destroy', $product) }}"
                                data-label="{{ $product->name }}" title="Delete"><span class="fas fa-trash"></span></button>
                    </div>
                </td>
            </tr>
        @endforeach
    </x-admin.table>

    <div class="mt-4">{{ $products->links() }}</div>
@endsection
