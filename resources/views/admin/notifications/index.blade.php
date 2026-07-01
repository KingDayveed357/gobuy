@extends('admin.layouts.app')

@section('title', 'Notifications — gobuy admin')
@section('page-title', 'Notifications')

@section('content')
    <div class="d-flex flex-between-center mb-3">
        <h2 class="mb-0">Notifications</h2>
        <form action="{{ route('admin.notifications.read') }}" method="POST">
            @csrf
            <button class="btn btn-sm btn-phoenix-secondary" type="submit">Mark all read</button>
        </form>
    </div>

    <div class="card">
        <div class="card-body p-0">
            @forelse ($notifications as $note)
                @include('admin.notifications._item')
            @empty
                <p class="text-center text-body-tertiary py-5 mb-0">No notifications.</p>
            @endforelse
        </div>
    </div>

    <div class="mt-4">{{ $notifications->links() }}</div>
@endsection
