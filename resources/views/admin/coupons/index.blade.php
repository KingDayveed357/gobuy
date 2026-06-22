@extends('admin.layouts.app')

@section('title', 'Coupons — gobuy admin')
@section('page-title', 'Coupons')

@section('content')
    <x-admin.page-header title="Coupons" subtitle="{{ $coupons->total() }} coupon(s)">
        <x-slot:actions>
            <a href="{{ route('admin.coupons.create') }}" class="btn btn-primary"><span class="fas fa-plus me-2"></span>New coupon</a>
        </x-slot:actions>
    </x-admin.page-header>

    <x-admin.table
        :cols="[
            ['label' => 'Code'],
            ['label' => 'Type'],
            ['label' => 'Value', 'align' => 'end'],
            ['label' => 'Status'],
            ['label' => 'Eligibility'],
            ['label' => '', 'align' => 'end'],
        ]"
        :empty="$coupons->isEmpty()"
        empty-icon="fa-tag"
        empty-text="No coupons found."
    >
        <x-slot:toolbar>
            <form method="GET" class="admin-toolbar mb-0 w-100">
                <div class="admin-toolbar-grow" style="max-width: 340px;">
                    <div class="position-relative">
                        <span class="fas fa-search position-absolute text-body-tertiary" style="top: 50%; left: 0.85rem; transform: translateY(-50%);"></span>
                        <input class="form-control form-control-sm ps-5" type="search" name="q" value="{{ request('q') }}" placeholder="Search coupon code">
                    </div>
                </div>
                <button class="btn btn-sm btn-phoenix-secondary" type="submit">Search</button>
            </form>
        </x-slot:toolbar>

        @foreach ($coupons as $coupon)
            <tr>
                <td class="fw-semibold text-body-emphasis">{{ $coupon->code }}</td>
                <td>{{ ucfirst(str_replace('_', ' ', $coupon->type)) }}</td>
                <td class="text-end">
                    @if($coupon->type === 'percentage')
                        {{ $coupon->value }}%
                    @elseif($coupon->type === 'fixed')
                        ₦{{ number_format($coupon->value, 2) }}
                    @else
                        -
                    @endif
                </td>
                <td>
                    <span class="badge badge-phoenix badge-phoenix-{{ $coupon->is_active ? 'success' : 'secondary' }}">
                        {{ $coupon->is_active ? 'Active' : 'Inactive' }}
                    </span>
                </td>
                <td>{{ ucfirst($coupon->eligibility) }}</td>
                <td class="text-end">
                    <div class="table-actions">
                        <a href="{{ route('admin.coupons.edit', $coupon) }}" class="btn btn-sm btn-phoenix-secondary">Edit</a>
                        <button type="button" class="btn btn-sm btn-phoenix-danger" title="Delete" data-bs-toggle="modal" data-bs-target="#actionModal" data-action="{{ route('admin.coupons.destroy', $coupon) }}" data-method="DELETE" data-title="Delete Coupon" data-message="Are you sure you want to delete this coupon?" data-confirm-text="Yes, delete it" data-variant="danger">
                            <span class="fas fa-trash"></span>
                        </button>
                    </div>
                </td>
            </tr>
        @endforeach
    </x-admin.table>

    <div class="mt-4">{{ $coupons->links() }}</div>
@endsection
