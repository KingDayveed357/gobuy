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
            <table class="table table-sm fs-9 mb-0">
                <tbody>
                    @forelse ($notifications as $note)
                        <tr class="{{ $note->read_at ? '' : 'fw-semibold' }}">
                            <td>
                                <span class="fas fa-receipt me-2 text-primary"></span>
                                New paid order
                                <a href="{{ route('admin.orders.show', $note->data['order_number']) }}">{{ $note->data['order_number'] }}</a>
                                from {{ $note->data['customer'] }}
                            </td>
                            <td class="text-end">₦{{ number_format($note->data['total'], 2) }}</td>
                            <td class="text-end text-body-tertiary">{{ $note->created_at->diffForHumans() }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="3" class="text-center text-body-tertiary py-4">No notifications.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-4">{{ $notifications->links() }}</div>
@endsection
