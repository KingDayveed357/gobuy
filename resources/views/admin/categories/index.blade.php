@extends('admin.layouts.app')

@section('title', 'Categories — Quintessential Mart admin')

@php($byParent = $categories->groupBy(fn ($c) => $c->parent_id ?? 0))

@section('content')
    <x-admin.page-header title="Categories" subtitle="Organize your catalog into a parent → child hierarchy" />

    <div class="row g-4">
        <div class="col-12 col-lg-4">
            <x-admin.card title="Add category">
                <form action="{{ route('admin.categories.store') }}" method="POST">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label">Name</label>
                        <input class="form-control" type="text" name="name" value="{{ old('name') }}" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Parent category</label>
                        <x-admin.category-select :options="$options" name="parent_id" :selected="old('parent_id')" include-none />
                        <p class="fs-10 text-body-tertiary mt-1 mb-0">Leave as “top level” to create a root category.</p>
                    </div>
                    <div class="form-check form-switch mb-4">
                        <input type="hidden" name="is_active" value="0">
                        <input class="form-check-input" type="checkbox" role="switch" name="is_active" value="1" id="newCatActive" checked>
                        <label class="form-check-label" for="newCatActive">Active</label>
                    </div>
                    <button class="btn btn-primary w-100" type="submit"><span class="fas fa-plus me-2"></span>Create category</button>
                </form>
            </x-admin.card>
        </div>

        <div class="col-12 col-lg-8">
            <x-admin.table
                :cols="[['label' => 'Category'], ['label' => 'Products', 'align' => 'center'], ['label' => 'Status'], ['label' => '', 'align' => 'end']]"
                :empty="$categories->isEmpty()"
                empty-icon="fa-tag"
                empty-text="No categories yet — create one on the left."
            >
                @foreach ($byParent->get(0, collect()) as $root)
                    @include('admin.categories._row', ['category' => $root, 'depth' => 0, 'byParent' => $byParent, 'options' => $options])
                @endforeach
            </x-admin.table>
        </div>
    </div>
@endsection
