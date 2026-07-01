@extends('admin.layouts.app')

@section('title', 'Edit role — gobuy admin')

@section('content')
    <form action="{{ route('admin.roles.update', $role) }}" method="POST" class="mb-6">
        @csrf
        @method('PUT')
        <x-admin.page-header :title="'Edit ' . $role->name" subtitle="Update what this role can access. Changes apply to everyone assigned it.">
            <x-slot:actions>
                <a href="{{ route('admin.roles.index') }}" class="btn btn-phoenix-secondary">Discard</a>
                <button type="submit" class="btn btn-primary"><span class="fas fa-check me-2"></span>Save changes</button>
            </x-slot:actions>
        </x-admin.page-header>

        @include('admin.roles._form')
    </form>
@endsection
