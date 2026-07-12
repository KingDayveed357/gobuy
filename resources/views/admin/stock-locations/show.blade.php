@extends('admin.layouts.app')

@section('title', $location->name.' — stock')
@section('page-title', 'Location stock')

@section('content')
    <x-admin.page-header :title="$location->name" :subtitle="'Stock currently held at this location'.($location->type ? ' ('.$location->type.')' : '').'.'">
        <x-slot:actions>
            <a href="{{ route('admin.stock-locations.index') }}" class="btn btn-phoenix-secondary btn-sm"><span class="fas fa-arrow-left me-1"></span>All locations</a>
        </x-slot:actions>
    </x-admin.page-header>

    <x-admin.card flush>
        <div class="table-responsive">
            <table class="table admin-table mb-0">
                <thead><tr><th>Product</th><th>SKU</th><th class="text-end">On hand here</th></tr></thead>
                <tbody>
                    @forelse ($levels as $level)
                        <tr>
                            <td class="fw-semibold fs-9">{{ $level->variant?->product?->name ?? '—' }}@if ($level->variant && ! $level->variant->is_default) — {{ $level->variant->label() }}@endif</td>
                            <td class="fs-10 text-body-tertiary">{{ $level->variant?->sku }}</td>
                            <td class="text-end fw-bold">{{ number_format($level->on_hand) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="3"><x-admin.empty-state icon="fa-box-open" text="No stock held at this location yet." /></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-admin.card>
@endsection
