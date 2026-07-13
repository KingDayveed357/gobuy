@extends('layouts.storefront')

@section('title', 'Your cart — Quintessential Mart')

@section('content')
    <section class="pt-5 pb-9">
        <div class="container-small cart">
            <nav class="mb-3" aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('home') }}">Home</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Cart</li>
                </ol>
            </nav>
            <h2 class="mb-6">Cart</h2>

            <livewire:cart.cart-manager />
        </div>
    </section>
@endsection
