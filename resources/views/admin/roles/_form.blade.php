@php
    $current = old('permissions', $assigned);

    // Per-module presentation (icon + tone) for fast visual scanning.
    $meta = [
        'Catalog'   => ['icon' => 'fa-box',          'tone' => 'primary'],
        'Orders'    => ['icon' => 'fa-cart-shopping', 'tone' => 'info'],
        'Customers' => ['icon' => 'fa-users',         'tone' => 'info'],
        'Returns'   => ['icon' => 'fa-rotate-left',   'tone' => 'warning'],
        'Finance'   => ['icon' => 'fa-building-columns', 'tone' => 'warning'],
        'Insights'  => ['icon' => 'fa-chart-simple',  'tone' => 'success'],
    ];
@endphp

<div class="row justify-content-center">
    <div class="col-12 col-xl-9 col-xxl-8">
        <x-admin.card class="h-auto">
            <label class="form-label fw-semibold" for="role-name">Role name</label>
            <input class="form-control form-control-lg @error('name') is-invalid @enderror" id="role-name"
                   name="name" value="{{ old('name', $role->name) }}" placeholder="e.g. Inventory Manager" maxlength="50" required>
            @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
            <p class="fs-9 text-body-tertiary mt-2 mb-0">Everyone with this role shares the access you choose below.</p>

            <hr class="border-translucent my-4">

            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-1">
                <h5 class="mb-0">What can this role do?</h5>
                <span class="fs-9 text-body-tertiary">Turn on only what's needed</span>
            </div>
            <p class="fs-9 text-body-tertiary mb-4">Least privilege keeps your store safer. You can change any of this later.</p>

            @foreach ($modules as $module => $permissions)
                @php($m = $meta[$module] ?? ['icon' => 'fa-circle', 'tone' => 'secondary'])
                <div class="py-3 {{ ! $loop->last ? 'border-bottom border-translucent' : '' }}">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <span class="admin-stat-icon bg-{{ $m['tone'] }}-subtle text-{{ $m['tone'] }}" style="width:1.75rem;height:1.75rem;">
                            <span class="fas {{ $m['icon'] }} fs-10"></span>
                        </span>
                        <h6 class="mb-0">{{ $module }}</h6>
                        @if ($module === 'Finance')
                            <span class="badge badge-phoenix badge-phoenix-warning ms-1" title="Handles money — grant with care"><span class="fas fa-triangle-exclamation me-1"></span>Sensitive</span>
                        @endif
                    </div>

                    @foreach ($permissions as $permission => $label)
                        <label class="d-flex align-items-center justify-content-between gap-3 py-2 px-2 rounded-2 cursor-pointer admin-perm-row" for="perm-{{ $permission }}">
                            <span class="fs-9 text-body-emphasis">{{ $label }}</span>
                            <span class="form-check form-switch mb-0 flex-shrink-0">
                                <input class="form-check-input" type="checkbox" role="switch"
                                       name="permissions[]" value="{{ $permission }}" id="perm-{{ $permission }}"
                                       @checked(in_array($permission, $current, true))>
                            </span>
                        </label>
                    @endforeach
                </div>
            @endforeach
        </x-admin.card>

        <p class="fs-9 text-body-tertiary mt-3 mb-0">
            <span class="fas fa-circle-info me-1"></span>
            Money-out actions (refunds, wallet funding) and store settings always stay with you, the owner — they're never part of a role.
        </p>
    </div>
</div>
