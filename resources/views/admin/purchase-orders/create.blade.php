@extends('admin.layouts.app')

@section('title', 'New purchase order — Quintessential Mart admin')
@section('page-title', 'New purchase order')

@section('content')
    <x-admin.page-header title="New purchase order" subtitle="Choose a supplier and where the goods will land, then add the items you're ordering.">
        <x-slot:actions>
            <a href="{{ route('admin.purchase-orders.index') }}" class="btn btn-phoenix-secondary btn-sm"><span class="fas fa-arrow-left me-1"></span>All orders</a>
        </x-slot:actions>
    </x-admin.page-header>

    <livewire:admin.purchasing.purchase-order-builder />
@endsection
