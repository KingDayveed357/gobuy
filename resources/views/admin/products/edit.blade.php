@extends('admin.layouts.app')

@section('title', 'Edit product — gobuy admin')

@section('content')
    <form action="{{ route('admin.products.update', $product) }}" method="POST" class="mb-6">
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

    <form action="{{ route('admin.products.destroy', $product) }}" method="POST"
          onsubmit="return confirm('Delete this product?');">
        @csrf
        @method('DELETE')
        <button type="submit" class="btn btn-link text-danger p-0"><span class="fas fa-trash me-2"></span>Delete product</button>
    </form>

    @include('admin.categories._create-modal', ['options' => $categoryOptions])
@endsection
