@extends('admin.layouts.app')

@section('title', 'Packaging units — gobuy admin')
@section('page-title', 'Packaging units')

@section('content')
    <x-admin.page-header title="Packaging units" subtitle="Sell the same product as a bottle, pack, carton or crate. Stock is always counted in base units — packaging is just how you sell it." />

    <livewire:admin.packaging.manage-packaging />
@endsection
