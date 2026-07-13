@extends('admin.layouts.app')

@section('title', 'Stock transfers — Quintessential Mart admin')
@section('page-title', 'Stock transfers')

@section('content')
    <x-admin.page-header title="Stock transfers" subtitle="Move stock between your locations — restock the shop from home storage, and keep a record of every move." />

    <livewire:admin.transfers.transfer-stock />
@endsection
