@extends('admin.layouts.app')

@section('title', $campaign->name.' — campaign')
@section('page-title', 'Campaign')

@section('content')
    <livewire:admin.campaign.editor :campaign="$campaign" />
@endsection
