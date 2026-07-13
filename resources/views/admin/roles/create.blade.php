@extends('admin.layouts.app')

@section('title', 'New role — Quintessential Mart admin')

@section('content')
    <form action="{{ route('admin.roles.store') }}" method="POST" class="mb-6">
        @csrf
        <x-admin.page-header title="New role" subtitle="Create a reusable role and choose what it can access.">
            <x-slot:actions>
                <a href="{{ route('admin.roles.index') }}" class="btn btn-phoenix-secondary">Discard</a>
                <button type="submit" class="btn btn-primary"><span class="fas fa-check me-2"></span>Create role</button>
            </x-slot:actions>
        </x-admin.page-header>

        @include('admin.roles._form')
    </form>
@endsection
