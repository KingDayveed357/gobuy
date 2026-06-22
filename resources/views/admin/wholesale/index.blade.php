@extends('admin.layouts.app')

@section('title', 'Wholesale applications — gobuy admin')
@section('page-title', 'Wholesale applications')

@section('content')
    <x-admin.page-header title="Wholesale applications" subtitle="{{ $applicants->total() }} pending review" />

    @if ($applicants->isEmpty())
        <x-admin.card>
            <div class="text-center py-6">
                <div class="mb-2"><span class="fas fa-store fs-5 text-body-tertiary"></span></div>
                <h6 class="mb-0">No pending applications.</h6>
            </div>
        </x-admin.card>
    @else
        <div class="row g-3">
            @foreach ($applicants as $user)
                @php($profile = $user->wholesaleProfile)
                <div class="col-12 col-xl-6">
                    <x-admin.card>
                        <div class="d-flex flex-between-center mb-2">
                            <div>
                                <h5 class="mb-0">{{ $profile?->business_name ?? $user->name }}</h5>
                                <p class="fs-9 text-body-tertiary mb-0">{{ $user->name }} · {{ $user->email }}</p>
                            </div>
                            <span class="badge badge-phoenix badge-phoenix-warning">Pending</span>
                        </div>

                        <dl class="row fs-9 mb-2">
                            <dt class="col-4 text-body-tertiary fw-normal">RC number</dt>
                            <dd class="col-8 mb-1">{{ $profile?->rc_number ?: '—' }}</dd>
                            <dt class="col-4 text-body-tertiary fw-normal">Phone</dt>
                            <dd class="col-8 mb-1">{{ $profile?->business_phone ?: '—' }}</dd>
                            <dt class="col-4 text-body-tertiary fw-normal">Industry</dt>
                            <dd class="col-8 mb-1">{{ $profile?->industry ?: '—' }}</dd>
                            <dt class="col-4 text-body-tertiary fw-normal">Address</dt>
                            <dd class="col-8 mb-1">{{ $profile?->business_address ?: '—' }}</dd>
                            <dt class="col-4 text-body-tertiary fw-normal">Intent</dt>
                            <dd class="col-8 mb-0">{{ $profile?->intent ?: '—' }}</dd>
                        </dl>

                        @if ($profile && $profile->documents()->isNotEmpty())
                            <div class="mb-3">
                                <p class="fs-10 fw-semibold text-body-tertiary mb-1">Documents</p>
                                <div class="d-flex flex-wrap gap-2">
                                    @foreach ($profile->documents() as $doc)
                                        <a href="{{ $doc->getUrl() }}" target="_blank" class="btn btn-sm btn-phoenix-secondary"><span class="fas fa-file me-1"></span>{{ \Illuminate\Support\Str::limit($doc->file_name, 20) }}</a>
                                    @endforeach
                                </div>
                            </div>
                        @endif

                        <div class="d-flex flex-wrap gap-2 align-items-end border-top border-translucent pt-3">
                            <form action="{{ route('admin.wholesale.approve', $user) }}" method="POST" class="d-flex gap-2 align-items-end">
                                @csrf
                                <div>
                                    <label class="form-label fs-10 mb-1">Tier</label>
                                    <select name="tier" class="form-select form-select-sm" style="width: 120px;">
                                        @foreach (\App\Modules\Customer\Models\WholesaleProfile::TIERS as $tier)
                                            <option value="{{ $tier }}">{{ ucfirst($tier) }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <button class="btn btn-sm btn-phoenix-success">Approve</button>
                            </form>
                            <form action="{{ route('admin.wholesale.reject', $user) }}" method="POST">
                                @csrf
                                <button class="btn btn-sm btn-phoenix-danger">Reject</button>
                            </form>
                        </div>
                    </x-admin.card>
                </div>
            @endforeach
        </div>

        <div class="mt-4">{{ $applicants->links() }}</div>
    @endif
@endsection
