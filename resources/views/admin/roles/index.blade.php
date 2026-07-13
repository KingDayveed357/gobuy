@extends('admin.layouts.app')

@section('title', 'Roles — Quintessential Mart admin')
@section('page-title', 'Roles')

@section('content')
    <x-admin.page-header title="Roles" subtitle="Set up a role once, then assign it to as many staff as you like.">
        <x-slot:actions>
            <a href="{{ route('admin.roles.create') }}" class="btn btn-primary"><span class="fas fa-plus me-2"></span>New role</a>
        </x-slot:actions>
    </x-admin.page-header>

    <x-admin.table
        :cols="[
            ['label' => 'Role'],
            ['label' => 'People', 'align' => 'center'],
            ['label' => 'Access'],
            ['label' => 'Actions', 'align' => 'end'],
        ]"
        :empty="$roles->isEmpty()"
        empty-icon="fa-shield-halved"
        empty-text="No roles yet."
    >
        @foreach ($roles as $role)
            @php($isOwner = $role->name === $superAdminRole)
            <tr>
                <td class="text-body-emphasis fw-semibold">
                    {{ $role->name }}
                    @if ($isOwner)
                        <span class="badge badge-phoenix badge-phoenix-warning ms-1"><span class="fas fa-crown me-1"></span>Owner</span>
                    @elseif ($role->is_system)
                        <span class="badge badge-phoenix badge-phoenix-secondary ms-1">Default</span>
                    @endif
                </td>
                <td class="text-center">{{ $peopleCounts[$role->id] ?? 0 }}</td>
                <td class="text-body-tertiary fs-9">
                    @if ($isOwner)
                        Full, unrestricted access
                    @else
                        {{ $role->permissions_count }} {{ $role->permissions_count === 1 ? 'area' : 'areas' }}
                    @endif
                </td>
                <td class="text-end">
                    @if ($isOwner)
                        <span class="text-body-tertiary fs-9"><span class="fas fa-lock me-1"></span>Protected</span>
                    @else
                        <div class="d-inline-flex gap-2">
                            <a href="{{ route('admin.roles.edit', $role) }}" class="btn btn-sm btn-phoenix-secondary">Edit</a>
                            <form action="{{ route('admin.roles.clone', $role) }}" method="POST" class="d-inline">
                                @csrf
                                <button class="btn btn-sm btn-phoenix-secondary" title="Duplicate this role"><span class="fas fa-copy"></span></button>
                            </form>
                            <button type="button" class="btn btn-sm btn-phoenix-danger"
                                data-bs-toggle="modal" data-bs-target="#actionModal"
                                data-action="{{ route('admin.roles.destroy', $role) }}" data-method="DELETE"
                                data-title="Delete role"
                                data-message="Delete the “{{ $role->name }}” role? This only works when no staff are assigned to it."
                                data-confirm-text="Delete role" data-variant="danger">Delete</button>
                        </div>
                    @endif
                </td>
            </tr>
        @endforeach
    </x-admin.table>
@endsection
