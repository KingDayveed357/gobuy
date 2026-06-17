@extends('admin.layouts.app')

@section('title', 'Wholesale applications — gobuy admin')
@section('page-title', 'Wholesale applications')

@section('content')
    <x-admin.page-header title="Wholesale applications" subtitle="Pending review" />

    <x-admin.table
        :cols="[
            ['label' => 'Applicant'],
            ['label' => 'Business'],
            ['label' => 'RC No.'],
            ['label' => 'Phone'],
            ['label' => 'Action', 'align' => 'end'],
        ]"
        :empty="$applicants->isEmpty()"
        empty-icon="fa-store"
        empty-text="No pending applications."
    >
        @foreach ($applicants as $user)
            <tr>
                <td>
                    <span class="fw-semibold text-body-emphasis">{{ $user->name }}</span><br>
                    <span class="fs-10 text-body-tertiary">{{ $user->email }}</span>
                </td>
                <td>{{ $user->wholesaleProfile?->business_name ?? '—' }}</td>
                <td>{{ $user->wholesaleProfile?->rc_number ?? '—' }}</td>
                <td>{{ $user->wholesaleProfile?->business_phone ?? '—' }}</td>
                <td class="text-end">
                    <form action="{{ route('admin.wholesale.approve', $user) }}" method="POST" class="d-inline">
                        @csrf
                        <button class="btn btn-sm btn-phoenix-success">Approve</button>
                    </form>
                    <form action="{{ route('admin.wholesale.reject', $user) }}" method="POST" class="d-inline">
                        @csrf
                        <button class="btn btn-sm btn-phoenix-danger">Reject</button>
                    </form>
                </td>
            </tr>
        @endforeach
    </x-admin.table>

    <div class="mt-4">{{ $applicants->links() }}</div>
@endsection
