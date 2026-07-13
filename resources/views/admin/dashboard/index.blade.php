@extends('admin.layouts.app')

@section('title', 'Dashboard — Quintessential Mart admin')
@section('page-title', 'Dashboard')

@section('content')
    <x-admin.page-header title="Dashboard" subtitle="Overview of store performance">
        <x-slot:actions>
            @if (auth('admin')->user()?->can('view_analytics'))
                <a href="{{ route('admin.analytics') }}" class="btn btn-sm btn-phoenix-secondary"><span class="fas fa-chart-line me-2"></span>Analytics</a>
            @endif
        </x-slot:actions>
    </x-admin.page-header>

    <livewire:admin.dashboard.overview lazy />
@endsection
