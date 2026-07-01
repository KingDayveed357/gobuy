@extends('admin.layouts.app')

@section('title', 'Staff — gobuy admin')
@section('page-title', 'Staff')

@php
    $badges = ['active' => 'success', 'invited' => 'info', 'suspended' => 'warning', 'archived' => 'secondary'];
    $avatarTones = ['primary', 'info', 'success', 'warning'];
@endphp

@section('content')
    <x-admin.page-header title="Staff" subtitle="Everyone with access to your store — invite, suspend, replace or offboard in a click.">
        <x-slot:actions>
            <button type="button" class="btn btn-primary" data-bs-toggle="offcanvas" data-bs-target="#inviteDrawer">
                <span class="fas fa-user-plus me-2"></span>Invite staff
            </button>
        </x-slot:actions>
    </x-admin.page-header>

    <ul class="nav nav-pills gap-2 mb-4">
        @foreach (['active' => 'Active', 'invited' => 'Invited', 'suspended' => 'Suspended', 'archived' => 'Archived'] as $key => $label)
            <li class="nav-item">
                <a class="nav-link {{ $status === $key ? 'active' : 'bg-body-secondary text-body' }} py-1 px-3"
                   href="{{ route('admin.staff.index', ['status' => $key]) }}">
                    {{ $label }} <span class="badge {{ $status === $key ? 'bg-white text-primary' : 'bg-body-tertiary text-body-tertiary' }} ms-1">{{ $counts[$key] }}</span>
                </a>
            </li>
        @endforeach
    </ul>

    <x-admin.table
        :cols="[
            ['label' => 'Name'],
            ['label' => 'Role'],
            ['label' => 'Status'],
            ['label' => 'Last active'],
            ['label' => '', 'align' => 'end'],
        ]"
        :empty="$staff->isEmpty()"
        empty-icon="fa-user-group"
        empty-text="No {{ $status }} staff yet."
    >
        @foreach ($staff as $member)
            <tr>
                <td>
                    <div class="d-flex align-items-center gap-2">
                        <span class="admin-avatar bg-{{ $avatarTones[$member->id % 4] }}-subtle text-{{ $avatarTones[$member->id % 4] }}">{{ $member->initials() }}</span>
                        <span>
                            <span class="fw-semibold text-body-emphasis d-block lh-1 mb-1">{{ $member->name }}</span>
                            <span class="fs-10 text-body-tertiary">{{ $member->email }}</span>
                        </span>
                    </div>
                </td>
                <td class="fs-9">{{ $member->roles->first()?->name ?? '—' }}</td>
                <td><span class="badge badge-phoenix badge-phoenix-{{ $badges[$member->status()] ?? 'secondary' }}">{{ ucfirst($member->status()) }}</span></td>
                <td class="fs-9 text-body-tertiary">{{ $member->last_login_at?->diffForHumans() ?? 'Never' }}</td>
                <td class="text-end">
                    @if ($status === 'archived')
                        <span class="text-body-tertiary fs-9">Archived {{ $member->deleted_at?->diffForHumans() }}</span>
                    @else
                        <a href="{{ route('admin.staff.show', $member) }}" class="btn btn-sm btn-phoenix-secondary">Manage</a>
                    @endif
                </td>
            </tr>
        @endforeach
    </x-admin.table>

    {{-- Invite slide-over: a 3-field action that keeps you in the directory. --}}
    <div class="offcanvas offcanvas-end" tabindex="-1" id="inviteDrawer" aria-labelledby="inviteDrawerLabel" style="width: min(440px, 100%);">
        <div class="offcanvas-header border-bottom border-translucent">
            <h5 class="offcanvas-title mb-0" id="inviteDrawerLabel">Invite a team member</h5>
            <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
        </div>
        <div class="offcanvas-body">
            <form action="{{ route('admin.staff.store') }}" method="POST">
                @csrf
                <p class="fs-9 text-body-tertiary mb-4">They'll get an email to set a password and join. Their role decides what they can access.</p>

                <div class="mb-3">
                    <label class="form-label">Full name</label>
                    <input class="form-control @error('name') is-invalid @enderror" name="name" value="{{ old('name') }}" required>
                    @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="mb-3">
                    <label class="form-label">Email address</label>
                    <input class="form-control @error('email') is-invalid @enderror" type="email" name="email" value="{{ old('email') }}" required>
                    @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="mb-4">
                    <label class="form-label">Role</label>
                    <select class="form-select @error('role') is-invalid @enderror" name="role" required>
                        <option value="" disabled @selected(! old('role', $invitePrefill))>Choose a role…</option>
                        @foreach ($roles as $role)
                            <option value="{{ $role->name }}" @selected(old('role', $invitePrefill) === $role->name)>{{ $role->name }}</option>
                        @endforeach
                    </select>
                    @error('role')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    <p class="fs-9 text-body-tertiary mt-2 mb-0">Need a different role? <a href="{{ route('admin.roles.index') }}">Manage roles</a>.</p>
                </div>

                <button type="submit" class="btn btn-primary w-100"><span class="fas fa-paper-plane me-2"></span>Send invitation</button>
            </form>
        </div>
    </div>

    @if ($errors->any() || $invitePrefill)
        @push('scripts')
            <script>
                document.addEventListener('DOMContentLoaded', function () {
                    var el = document.getElementById('inviteDrawer');
                    if (el && window.bootstrap) { bootstrap.Offcanvas.getOrCreateInstance(el).show(); }
                });
            </script>
        @endpush
    @endif
@endsection
