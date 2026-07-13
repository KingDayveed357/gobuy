@extends('admin.layouts.app')

@section('title', 'Edit coupon — Quintessential Mart admin')

@section('content')
    <form action="{{ route('admin.coupons.update', $coupon) }}" method="POST" class="mb-6">
        @csrf
        @method('PUT')
        <x-admin.page-header title="Edit coupon: {{ $coupon->code }}" subtitle="Update promotional coupon">
            <x-slot:actions>
                <a href="{{ route('admin.coupons.index') }}" class="btn btn-phoenix-secondary">Discard</a>
                <button type="submit" class="btn btn-primary"><span class="fas fa-check me-2"></span>Save changes</button>
            </x-slot:actions>
        </x-admin.page-header>

        @include('admin.coupons._form', ['coupon' => $coupon])
    </form>
@endsection
