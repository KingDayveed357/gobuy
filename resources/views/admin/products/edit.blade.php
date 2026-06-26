@extends('admin.layouts.app')

@section('title', 'Edit product — gobuy admin')

@section('content')
    <form action="{{ route('admin.products.update', $product) }}" method="POST" class="mb-6" enctype="multipart/form-data">
        @csrf
        @method('PUT')
        <x-admin.page-header title="Edit product" subtitle="{{ $product->name }}">
            <x-slot:actions>
                <a href="{{ route('admin.products.index') }}" class="btn btn-phoenix-secondary">Discard</a>
                <button type="submit" class="btn btn-primary"><span class="fas fa-check me-2"></span>Save changes</button>
            </x-slot:actions>
        </x-admin.page-header>

        @include('admin.products._form')
    </form>

    <button type="button" class="btn btn-link text-danger p-0" data-bs-toggle="modal" data-bs-target="#deleteModal" data-action="{{ route('admin.products.destroy', $product) }}">
        <span class="fas fa-trash me-2"></span>Delete product
    </button>

    @include('admin.categories._create-modal', ['options' => $categoryOptions])
    @include('admin.products._create-brand-modal')
@endsection