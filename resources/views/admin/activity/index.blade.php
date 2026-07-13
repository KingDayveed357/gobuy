@extends('admin.layouts.app')

@section('title', 'Activity log — Quintessential Mart admin')
@section('page-title', 'Activity log')

@php($tones = ['primary', 'info', 'success', 'warning'])

@section('content')
    <x-admin.page-header title="Activity log" subtitle="An immutable record of what your team did, and when." />

    <x-admin.table
        :cols="[
            ['label' => 'Who'],
            ['label' => 'Did what'],
            ['label' => 'When'],
            ['label' => 'IP', 'align' => 'end'],
        ]"
        :empty="$activities->isEmpty()"
        empty-icon="fa-clock-rotate-left"
        empty-text="No activity yet. Actions your team takes will appear here."
    >
        <x-slot:toolbar>
            <form method="GET" class="admin-toolbar mb-0 w-100">
                <div class="admin-toolbar-grow" style="max-width: 260px;">
                    <input class="form-control form-control-sm" type="search" name="q" value="{{ request('q') }}" placeholder="Search actions">
                </div>
                <select name="actor" class="form-select form-select-sm w-auto" onchange="this.form.submit()">
                    <option value="">Anyone</option>
                    @foreach ($actors as $actor)
                        <option value="{{ $actor->id }}" @selected(request('actor') == $actor->id)>{{ $actor->name }}</option>
                    @endforeach
                </select>
                <select name="view" class="form-select form-select-sm w-auto" onchange="this.form.submit()">
                    <option value="">All activity</option>
                    <option value="logins" @selected($view === 'logins')>Logins only</option>
                </select>
                <button class="btn btn-sm btn-phoenix-secondary" type="submit">Filter</button>
            </form>
        </x-slot:toolbar>

        @foreach ($activities as $activity)
            @php($who = $activity->admin?->name ?? ($activity->properties['email'] ?? 'Unknown'))
            <tr>
                <td>
                    <div class="d-flex align-items-center gap-2">
                        <span class="admin-avatar bg-{{ $tones[($activity->admin_id ?? 0) % 4] }}-subtle text-{{ $tones[($activity->admin_id ?? 0) % 4] }}" style="width:1.85rem;height:1.85rem;font-size:.7rem;">
                            {{ strtoupper(mb_substr($who, 0, 1)) }}
                        </span>
                        <span class="fs-9 fw-semibold text-body-emphasis">{{ $who }}</span>
                    </div>
                </td>
                <td>
                    <span class="fs-9">{{ $activity->summary() }}</span>
                    @if ($activity->isAuthEvent())
                        <span class="badge badge-phoenix badge-phoenix-info ms-1">sign-in</span>
                    @endif
                </td>
                <td class="fs-9 text-body-tertiary text-nowrap" title="{{ $activity->created_at }}">{{ $activity->created_at->diffForHumans() }}</td>
                <td class="text-end fs-10 text-body-tertiary">{{ $activity->ip_address ?? '—' }}</td>
            </tr>
        @endforeach
    </x-admin.table>

    <div class="mt-4">{{ $activities->links() }}</div>
@endsection
