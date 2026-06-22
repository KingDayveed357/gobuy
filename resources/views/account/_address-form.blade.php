@props(['address' => null, 'action', 'method' => 'POST'])

<form action="{{ $action }}" method="POST">
    @csrf
    @if ($method !== 'POST')@method($method)@endif
    <div class="row g-3">
        <div class="col-md-6">
            <label class="form-label">Label <span class="text-body-tertiary">(optional)</span></label>
            <input class="form-control" type="text" name="label" value="{{ old('label', $address?->label) }}" placeholder="Home, Office…">
        </div>
        <div class="col-md-6">
            <label class="form-label">Recipient name</label>
            <input class="form-control" type="text" name="recipient_name" value="{{ old('recipient_name', $address?->recipient_name) }}" required>
        </div>
        <div class="col-md-6">
            <label class="form-label">Phone</label>
            <input class="form-control" type="text" name="phone" value="{{ old('phone', $address?->phone) }}" required>
        </div>
        <div class="col-md-6">
            <label class="form-label">Address line 1</label>
            <input class="form-control" type="text" name="line1" value="{{ old('line1', $address?->line1) }}" required>
        </div>
        <div class="col-md-6">
            <label class="form-label">Address line 2 <span class="text-body-tertiary">(optional)</span></label>
            <input class="form-control" type="text" name="line2" value="{{ old('line2', $address?->line2) }}">
        </div>
        <div class="col-md-3">
            <label class="form-label">City</label>
            <input class="form-control" type="text" name="city" value="{{ old('city', $address?->city) }}" required>
        </div>
        <div class="col-md-3">
            <label class="form-label">State</label>
            <input class="form-control" type="text" name="state" value="{{ old('state', $address?->state) }}" required>
        </div>
        <div class="col-md-6">
            <label class="form-label">Country</label>
            <input class="form-control" type="text" name="country" value="{{ old('country', $address?->country ?? 'Nigeria') }}">
        </div>
        <div class="col-md-6">
            <label class="form-label">Postal code <span class="text-body-tertiary">(optional)</span></label>
            <input class="form-control" type="text" name="postal_code" value="{{ old('postal_code', $address?->postal_code) }}">
        </div>
        <div class="col-12 d-flex flex-wrap gap-3">
            <div class="form-check">
                <input type="hidden" name="is_default_shipping" value="0">
                <input class="form-check-input" type="checkbox" name="is_default_shipping" value="1" id="ship-{{ $address?->id ?? 'new' }}" @checked(old('is_default_shipping', $address?->is_default_shipping))>
                <label class="form-check-label" for="ship-{{ $address?->id ?? 'new' }}">Default for shipping</label>
            </div>
            <div class="form-check">
                <input type="hidden" name="is_default_billing" value="0">
                <input class="form-check-input" type="checkbox" name="is_default_billing" value="1" id="bill-{{ $address?->id ?? 'new' }}" @checked(old('is_default_billing', $address?->is_default_billing))>
                <label class="form-check-label" for="bill-{{ $address?->id ?? 'new' }}">Default for billing</label>
            </div>
        </div>
    </div>
    <button class="btn btn-primary mt-4" type="submit">{{ $address ? 'Save changes' : 'Add address' }}</button>
</form>
