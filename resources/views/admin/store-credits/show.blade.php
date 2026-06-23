@extends('admin.layouts.app')

@section('title', 'Store credit — '.$customer->name)
@section('page-title', 'Store credit')

@section('content')
    <x-admin.page-header :title="$customer->name" :subtitle="$customer->email">
        <x-slot:actions>
            <span class="badge badge-phoenix badge-phoenix-primary fs-9">Balance: {{ money($balance) }}</span>
            <a href="{{ route('admin.store-credits.index') }}" class="btn btn-phoenix-secondary btn-sm">Back</a>
        </x-slot:actions>
    </x-admin.page-header>

    <!-- @if (session('status'))<div class="alert alert-subtle-success">{{ session('status') }}</div>@endif -->

    <x-admin.table
        :cols="[['label' => 'Date'], ['label' => 'Type'], ['label' => 'Reason'], ['label' => 'By'], ['label' => 'Amount', 'align' => 'end']]"
        :empty="$entries->isEmpty()"
        empty-icon="fa-receipt"
        empty-text="No ledger entries."
    >
        @foreach ($entries as $entry)
            <tr>
                <td>{{ $entry->created_at->format('M j, Y g:i A') }}</td>
                <td>{{ ucfirst(str_replace('_', ' ', $entry->type)) }}</td>
                <td>{{ $entry->reason ?? '—' }}</td>
                <td>{{ $entry->admin?->name ?? 'System' }}</td>
                <td class="text-end fw-semibold {{ $entry->amount->kobo >= 0 ? 'text-success' : 'text-danger' }}">
                    {{ $entry->amount->kobo >= 0 ? '+' : '−' }}{{ money(\App\Support\Money::fromKobo(abs($entry->amount->kobo))) }}
                </td>
            </tr>
        @endforeach
    </x-admin.table>
    <div class="mt-4">{{ $entries->links() }}</div>
@endsection
