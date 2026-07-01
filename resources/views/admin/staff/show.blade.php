@extends('admin.layouts.app')

@section('title', $staff->name.' — gobuy admin')

@php
    $badges = ['active' => 'success', 'invited' => 'info', 'suspended' => 'warning', 'archived' => 'secondary'];
    $tone = ['primary', 'info', 'success', 'warning'][$staff->id % 4];
@endphp

@section('content')
    <x-admin.page-header :title="$staff->name" :subtitle="$staff->email">
        <x-slot:actions>
            <a href="{{ route('admin.staff.index') }}" class="btn btn-phoenix-secondary"><span class="fas fa-arrow-left me-2"></span>Staff</a>
        </x-slot:actions>
    </x-admin.page-header>

    @if ($isInvited)
        <x-admin.card class="mb-4">
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
                <div class="d-flex align-items-center gap-2">
                    <span class="fas fa-paper-plane text-info"></span>
                    <span class="fs-9"><span class="fw-semibold">Invitation pending</span> — {{ $staff->name }} hasn't set up their account yet.</span>
                </div>
                @if ($canManage)
                    <form action="{{ route('admin.staff.resend', $staff) }}" method="POST">
                        @csrf
                        <button class="btn btn-sm btn-phoenix-info"><span class="fas fa-rotate-right me-1"></span>Resend invite</button>
                    </form>
                @endif
            </div>
            @if ($localActivationUrl)
                <div class="alert alert-subtle-secondary mt-3 mb-0">
                    <p class="fs-10 fw-semibold text-uppercase text-body-tertiary mb-1"><span class="fas fa-laptop-code me-1"></span>Local dev only — activation link</p>
                    <div class="input-group input-group-sm">
                        <input class="form-control fs-10" id="activationLink" value="{{ $localActivationUrl }}" readonly>
                        <button class="btn btn-phoenix-secondary" type="button" onclick="navigator.clipboard.writeText(document.getElementById('activationLink').value); this.innerHTML='&lt;span class=&quot;fas fa-check&quot;&gt;&lt;/span&gt; Copied'">Copy</button>
                    </div>
                </div>
            @endif
        </x-admin.card>
    @endif

    <div class="row g-4">
        <div class="col-12 col-lg-7">
            <x-admin.card class="h-auto">
                <div class="d-flex align-items-center gap-3 mb-4">
                    <span class="admin-avatar bg-{{ $tone }}-subtle text-{{ $tone }}" style="width:3rem;height:3rem;font-size:1rem;">{{ $staff->initials() }}</span>
                    <div>
                        <h5 class="mb-0">{{ $staff->name }}</h5>
                        <span class="badge badge-phoenix badge-phoenix-{{ $badges[$staff->status()] ?? 'secondary' }}">{{ ucfirst($staff->status()) }}</span>
                    </div>
                </div>
                <dl class="row mb-0 fs-9">
                    <dt class="col-4 col-sm-3 text-body-tertiary fw-normal">Role</dt>
                    <dd class="col-8 col-sm-9 fw-semibold">{{ $staff->roles->first()?->name ?? '—' }}</dd>
                    <dt class="col-4 col-sm-3 text-body-tertiary fw-normal">Last active</dt>
                    <dd class="col-8 col-sm-9">{{ $staff->last_login_at?->format('M j, Y g:i A') ?? 'Never signed in' }}</dd>
                    @if ($staff->invitedBy)
                        <dt class="col-4 col-sm-3 text-body-tertiary fw-normal">Invited by</dt>
                        <dd class="col-8 col-sm-9">{{ $staff->invitedBy->name }}</dd>
                    @endif
                </dl>

                @if ($canManage)
                    <form action="{{ route('admin.staff.role', $staff) }}" method="POST" class="border-top border-translucent mt-3 pt-3">
                        @csrf @method('PUT')
                        <div class="row align-items-end g-2">
                            <div class="col">
                                <label class="form-label fw-semibold fs-10 text-uppercase mb-1">Change Role</label>
                                <select name="role" class="form-select form-select-sm">
                                    @foreach ($roles as $role)
                                        <option value="{{ $role->name }}" @selected($staff->hasRole($role->name))>{{ $role->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-auto">
                                <button class="btn btn-sm btn-phoenix-primary text-nowrap"><span class="fas fa-user-shield me-1"></span>Update</button>
                            </div>
                        </div>
                        <p class="fs-10 text-body-tertiary mt-2 mb-0">The new role's access applies immediately.</p>
                    </form>
                @endif
            </x-admin.card>

            <x-admin.card title="Recent activity" class="mt-4 h-auto">
                <div class="timeline-container ps-2 pt-2">
                    @forelse ($activity as $entry)
                        <div class="d-flex align-items-start position-relative pb-4">
                            @unless ($loop->last)
                                <div class="position-absolute border-start border-translucent h-100" style="left: 0.85rem; top: 1.75rem; width: 0; border-width: 2px !important;"></div>
                            @endunless
                            
                            <div class="d-flex flex-center rounded-circle bg-body-highlight border border-translucent z-1" style="width: 1.75rem; height: 1.75rem; flex-shrink: 0;">
                                <span class="fas {{ $entry->isAuthEvent() ? 'fa-right-to-bracket text-info' : 'fa-circle-dot text-body-quaternary' }} fs-10"></span>
                            </div>
                            
                            <div class="ms-3 flex-grow-1">
                                <div class="d-flex justify-content-between align-items-center gap-3">
                                    <span class="fs-9 text-body-emphasis fw-semibold">{{ $entry->summary() }}</span>
                                    <span class="fs-10 text-body-tertiary text-nowrap" title="{{ $entry->created_at }}">{{ $entry->created_at->diffForHumans() }}</span>
                                </div>
                            </div>
                        </div>
                    @empty
                        <p class="fs-9 text-body-tertiary mb-0">No activity recorded yet.</p>
                    @endforelse
                </div>
            </x-admin.card>
        </div>

        <div class="col-12 col-lg-5">
            <x-admin.card title="Access" class="h-auto">
                @if (! $canManage)
                    <p class="fs-9 text-body-tertiary mb-0"><span class="fas fa-shield-halved me-1"></span>This account is protected and can't be changed here.</p>
                @else
                    <div class="d-grid gap-2">
                        @if ($staff->is_active)
                            <button type="button" class="btn btn-phoenix-warning"
                                data-bs-toggle="modal" data-bs-target="#actionModal"
                                data-action="{{ route('admin.staff.suspend', $staff) }}" data-method="POST"
                                data-title="Suspend access" data-message="Suspend {{ $staff->name }}? They'll be signed out and unable to log in until you restore them. Nothing is deleted."
                                data-confirm-text="Suspend access" data-variant="warning">
                                <span class="fas fa-ban me-2"></span>Suspend access
                            </button>
                            <button type="button" class="btn btn-phoenix-secondary"
                                data-bs-toggle="modal" data-bs-target="#actionModal"
                                data-action="{{ route('admin.staff.replace', $staff) }}" data-method="POST"
                                data-title="Replace this person" data-message="Suspend {{ $staff->name }} and start inviting their replacement with the same role?"
                                data-confirm-text="Suspend &amp; replace" data-variant="info">
                                <span class="fas fa-people-arrows me-2"></span>Replace
                            </button>
                        @else
                            <form action="{{ route('admin.staff.reactivate', $staff) }}" method="POST">
                                @csrf
                                <button class="btn btn-phoenix-success w-100"><span class="fas fa-rotate-left me-2"></span>Restore access</button>
                            </form>
                        @endif

                        <button type="button" class="btn btn-phoenix-danger"
                            data-bs-toggle="modal" data-bs-target="#actionModal"
                            data-action="{{ route('admin.staff.archive', $staff) }}" data-method="DELETE"
                            data-title="Archive staff member" data-message="Archive {{ $staff->name }}? They lose all access immediately. This is reversible by inviting them again later."
                            data-confirm-text="Archive" data-variant="danger">
                            <span class="fas fa-box-archive me-2"></span>Archive
                        </button>
                    </div>
                    <div class="border-top border-translucent mt-3 pt-3">
                        <div class="d-flex gap-2 text-body-tertiary">
                            <span class="fas fa-info-circle mt-1 fs-9"></span>
                            <p class="fs-10 mb-0">Suspended staff keep their history and can be restored anytime. Archiving removes them from the team.</p>
                        </div>
                    </div>
                @endif
            </x-admin.card>
        </div>
    </div>
@endsection
