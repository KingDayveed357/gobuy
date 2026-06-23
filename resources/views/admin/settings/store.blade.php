@extends('admin.layouts.app')

@section('title', 'Store settings — GoBuy Admin')
@section('page-title', 'Store settings')

@section('content')
    <div class="mb-4">
        <h2 class="mb-1">Store settings</h2>
        <p class="text-body-tertiary mb-0">Storefront identity, contact details and social links. These appear across the customer-facing site.</p>
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
