@extends('admin.layouts.app')

@section('title', 'Register — Quintessential Mart admin')
@section('page-title', 'Register')

@section('content')
    <x-admin.page-header title="Cash register" subtitle="Open the day, and close it by counting the drawer against the day's sales." />

    <livewire:admin.register.register />
@endsection
