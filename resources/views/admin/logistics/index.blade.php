@extends('admin.layouts.app')

@section('title', 'Logistics — Quintessential Mart admin')
@section('page-title', 'Logistics')

@section('content')
    <x-admin.page-header title="Logistics & Fulfilment" subtitle="Manage shipments, delivery pricing, and physical locations.">
        <x-slot:actions>
            <a href="{{ route('admin.shipments.index') }}" class="btn btn-primary"><span class="fas fa-truck me-2"></span>Dispatch Console</a>
        </x-slot:actions>
    </x-admin.page-header>

    <div class="row g-4">
        <!-- Shipments & Dispatch -->
        <div class="col-12 col-md-6 col-xl-4">
            <a href="{{ route('admin.shipments.index') }}" class="card h-100 text-decoration-none hover-shadow transition-base">
                <div class="card-body d-flex flex-column text-center py-5">
                    <div class="icon-circle bg-primary-subtle text-primary mx-auto mb-3" style="width: 64px; height: 64px; border-radius: 50%; display: flex; align-items: center; justify-content: center;">
                        <span class="fas fa-truck-fast fs-3"></span>
                    </div>
                    <h4 class="mb-2 text-body-emphasis">Shipments & Dispatch</h4>
                    <p class="text-body-tertiary fs-9 mb-0">View all orders awaiting shipment, generate manifests, and update delivery statuses.</p>
                </div>
            </a>
        </div>

        <!-- Physical Locations -->
        <div class="col-12 col-md-6 col-xl-4">
            <a href="{{ route('admin.locations.index') }}" class="card h-100 text-decoration-none hover-shadow transition-base">
                <div class="card-body d-flex flex-column text-center py-5">
                    <div class="icon-circle bg-info-subtle text-info mx-auto mb-3" style="width: 64px; height: 64px; border-radius: 50%; display: flex; align-items: center; justify-content: center;">
                        <span class="fas fa-location-dot fs-3"></span>
                    </div>
                    <h4 class="mb-2 text-body-emphasis">Locations</h4>
                    <p class="text-body-tertiary fs-9 mb-0">Manage physical Quintessential Mart locations, configured as pickup points or return centres.</p>
                </div>
            </a>
        </div>

        <!-- Delivery Zones -->
        <div class="col-12 col-md-6 col-xl-4">
            <a href="{{ route('admin.delivery-zones.index') }}" class="card h-100 text-decoration-none hover-shadow transition-base">
                <div class="card-body d-flex flex-column text-center py-5">
                    <div class="icon-circle bg-warning-subtle text-warning mx-auto mb-3" style="width: 64px; height: 64px; border-radius: 50%; display: flex; align-items: center; justify-content: center;">
                        <span class="fas fa-map-location-dot fs-3"></span>
                    </div>
                    <h4 class="mb-2 text-body-emphasis">Delivery Zones</h4>
                    <p class="text-body-tertiary fs-9 mb-0">Configure shipping fees, map Nigerian states to zones, and set free delivery thresholds.</p>
                </div>
            </a>
        </div>
    </div>
@endsection
