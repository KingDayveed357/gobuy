@extends('admin.layouts.app')

@section('title', 'Store settings — GoBuy Admin')
@section('page-title', 'Store settings')

@section('content')
    <div class="mb-4">
        <h2 class="mb-1">Store settings</h2>
        <p class="text-body-tertiary mb-0">Storefront identity, contact details and social links. These appear across the customer-facing site.</p>
    </div>

    {{-- Commerce Operations modules: optional retail/omnichannel capabilities.
         Off by default — a plain e-commerce store never sees them. --}}
    <div class="card mb-4" style="max-width: 760px;">
        <div class="card-body">
            <h5 class="mb-1">Commerce modules</h5>
            <p class="text-body-tertiary fs-9 mb-3">Switch on advanced retail operations — in-store sales, multi-location inventory, purchasing and more. Leave them off to keep the admin focused on your online store.</p>
            @php($available = $modules->available())
            @if (empty($available))
                <div class="alert alert-subtle-info mb-3">Advanced retail operations are on the way. When available you'll enable them here — until then your store stays a clean online shop.</div>
                <h6 class="text-body-tertiary text-uppercase fs-10 mb-2">Coming soon</h6>
                <ul class="list-unstyled mb-0">
                    @foreach ($modules->definitions() as $key => $def)
                        <li class="d-flex align-items-start gap-2 py-1">
                            <span class="fas fa-clock text-body-tertiary mt-1"></span>
                            <span><span class="fw-semibold">{{ $def['label'] }}</span><br><span class="fs-10 text-body-tertiary">{{ $def['description'] }}</span></span>
                        </li>
                    @endforeach
                </ul>
            @else
                <form action="{{ route('admin.settings.modules.update') }}" method="POST">
                    @csrf
                    @foreach ($available as $key => $def)
                        <div class="form-check form-switch mb-3">
                            <input type="checkbox" class="form-check-input" role="switch" id="mod-{{ $key }}" name="modules[]" value="{{ $key }}" @checked($modules->enabled($key))>
                            <label class="form-check-label" for="mod-{{ $key }}">
                                <span class="fw-semibold">{{ $def['label'] }}</span>
                                @if ($modules->dependencies($key))<span class="badge badge-phoenix badge-phoenix-secondary fs-10 ms-1">needs {{ implode(', ', array_map(fn ($d) => $modules->definition($d)['label'] ?? $d, $modules->dependencies($key))) }}</span>@endif
                                <br><span class="fs-10 text-body-tertiary">{{ $def['description'] }}</span>
                            </label>
                        </div>
                    @endforeach
                    <button type="submit" class="btn btn-phoenix-primary btn-sm"><span class="fas fa-toggle-on me-1"></span>Save modules</button>
                </form>
            @endif
        </div>
    </div>

    <form action="{{ route('admin.settings.store.update') }}" method="POST" style="max-width: 760px;">
        @csrf
        <div class="card mb-4">
            <div class="card-body">
                <h5 class="mb-3">Identity & contact</h5>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label" for="store_name">Store name</label>
                        <input type="text" class="form-control" id="store_name" name="store_name" value="{{ old('store_name', $settings['store_name'] ?? $defaults['store_name']) }}" maxlength="120">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="store_email">Contact email</label>
                        <input type="email" class="form-control" id="store_email" name="store_email" value="{{ old('store_email', $settings['store_email'] ?? '') }}" maxlength="160">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="store_phone">Contact phone</label>
                        <input type="text" class="form-control" id="store_phone" name="store_phone" value="{{ old('store_phone', $settings['store_phone'] ?? '') }}" maxlength="40">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="whatsapp_number">WhatsApp number</label>
                        <input type="text" class="form-control" id="whatsapp_number" name="whatsapp_number" value="{{ old('whatsapp_number', $settings['whatsapp_number'] ?? '') }}" placeholder="2348012345678" maxlength="40">
                    </div>
                    <div class="col-12">
                        <label class="form-label" for="free_delivery_note">Free-delivery note <span class="text-body-tertiary fw-normal">(shown on storefront)</span></label>
                        <input type="text" class="form-control" id="free_delivery_note" name="free_delivery_note" value="{{ old('free_delivery_note', $settings['free_delivery_note'] ?? '') }}" placeholder="e.g. Free delivery on orders over ₦50,000" maxlength="160">
                    </div>
                </div>
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-body">
                <h5 class="mb-3">Social links</h5>
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label" for="instagram_url"><span class="fab fa-instagram me-1"></span>Instagram</label>
                        <input type="url" class="form-control" id="instagram_url" name="instagram_url" value="{{ old('instagram_url', $settings['instagram_url'] ?? '') }}" placeholder="https://instagram.com/…" maxlength="255">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="facebook_url"><span class="fab fa-facebook me-1"></span>Facebook</label>
                        <input type="url" class="form-control" id="facebook_url" name="facebook_url" value="{{ old('facebook_url', $settings['facebook_url'] ?? '') }}" placeholder="https://facebook.com/…" maxlength="255">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="x_url"><span class="fab fa-x-twitter me-1"></span>X (Twitter)</label>
                        <input type="url" class="form-control" id="x_url" name="x_url" value="{{ old('x_url', $settings['x_url'] ?? '') }}" placeholder="https://x.com/…" maxlength="255">
                    </div>
                </div>
            </div>
        </div>

        <button type="submit" class="btn btn-primary px-4"><span class="fas fa-save me-2"></span>Save store settings</button>
    </form>
@endsection
