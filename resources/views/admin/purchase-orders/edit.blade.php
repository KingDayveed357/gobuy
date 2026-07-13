@extends('admin.layouts.app')

@section('title', 'Edit purchase order — gobuy admin')
@section('page-title', 'Edit purchase order')

@section('content')
    <x-admin.page-header title="Edit purchase order" subtitle="Update the supplier, location or items in this draft order.">
        <x-slot:actions>
            <a href="{{ route('admin.purchase-orders.index') }}" class="btn btn-phoenix-secondary btn-sm"><span class="fas fa-arrow-left me-1"></span>All orders</a>
        </x-slot:actions>
    </x-admin.page-header>

    <livewire:admin.purchasing.purchase-order-builder :order="$order" />
@endsection
