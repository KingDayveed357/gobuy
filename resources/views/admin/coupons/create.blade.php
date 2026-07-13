@extends('admin.layouts.app')

@section('title', 'Add a coupon — Quintessential Mart admin')

@section('content')
    <form action="{{ route('admin.coupons.store') }}" method="POST" class="mb-6">
        @csrf
        <x-admin.page-header title="Add a coupon" subtitle="Create a new promotional coupon">
            <x-slot:actions>
                <a href="{{ route('admin.coupons.index') }}" class="btn btn-phoenix-secondary">Discard</a>
                <button type="submit" class="btn btn-primary"><span class="fas fa-check me-2"></span>Save coupon</button>
            </x-slot:actions>
        </x-admin.page-header>

        @include('admin.coupons._form', ['coupon' => new \App\Modules\Pricing\Models\Coupon])
    </form>
@endsection
