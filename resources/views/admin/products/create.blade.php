@extends('admin.layouts.app')

@section('title', 'Add a product — gobuy admin')

@section('content')
    <form action="{{ route('admin.products.store') }}" method="POST" class="mb-6">
        @csrf
        <x-admin.page-header title="Add a product" subtitle="Create a new catalog product">
            <x-slot:actions>
                <a href="{{ route('admin.products.index') }}" class="btn btn-phoenix-secondary">Discard</a>
                <button type="submit" class="btn btn-primary"><span class="fas fa-check me-2"></span>Publish product</button>
            </x-slot:actions>
        </x-admin.page-header>

        @include('admin.products._form')
    </form>

    @include('admin.categories._create-modal', ['options' => $categoryOptions])
@endsection
