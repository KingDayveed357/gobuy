@extends('admin.layouts.app')

@section('title', 'Stock counts — Quintessential Mart admin')
@section('page-title', 'Stock counts')

@section('content')
    <x-admin.page-header title="Stock counts &amp; damage" subtitle="Count what's physically on the shelf and let the books catch up — and write off anything damaged or lost." />

    <ul class="nav nav-underline mb-4" role="tablist">
        <li class="nav-item"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tab-count" type="button" role="tab"><span class="fas fa-clipboard-check me-2"></span>Stock count</button></li>
        <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-damage" type="button" role="tab"><span class="fas fa-trash-can me-2"></span>Damage / write-off</button></li>
    </ul>

    <div class="tab-content">
        <div class="tab-pane fade show active" id="tab-count" role="tabpanel">
            <livewire:admin.stock-counts.record-stock-count />
        </div>
        <div class="tab-pane fade" id="tab-damage" role="tabpanel">
            <livewire:admin.stock-counts.write-off-damage />
        </div>
    </div>
@endsection
