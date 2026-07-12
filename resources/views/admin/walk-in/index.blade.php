@extends('admin.layouts.app')

@section('title', 'New sale — gobuy admin')
@section('page-title', 'New sale')

@section('content')
    <x-admin.page-header title="New sale" subtitle="Record an in-store, phone or social sale — it flows through the same books as the website." />

    <livewire:admin.walk-in.walk-in-sale />
@endsection
