<!-- <div class="row g-5">
    <div class="col-12 col-xl-8">
        <h4 class="mb-3">Coupon Details</h4>

        <div class="row g-3">
            <div class="col-sm-6">
                <input class="form-control form-control-lg" name="code" label="Coupon Code" value="{{ old('code', $coupon->code) }}" required />
            </div>
            <div class="col-sm-6">
                <label class="form-label" for="type">Discount Type <span class="text-danger">*</span></label>
                <select class="form-select @error('type') is-invalid @enderror" name="type" id="type" required>
                    <option value="percentage" @selected(old('type', $coupon->type) === 'percentage')>Percentage</option>
                    <option value="fixed" @selected(old('type', $coupon->type) === 'fixed')>Fixed Amount</option>
                    <option value="free_shipping" @selected(old('type', $coupon->type) === 'free_shipping')>Free Shipping</option>
                </select>
                @error('type')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
            <div class="col-sm-6">
                <input class="form-control form-control-lg" name="value" label="Discount Value" value="{{ old('value', $coupon->value) }}" type="number" step="0.01" min="0" required />
            </div>
            <div class="col-sm-6">
                <input class="form-control form-control-lg" name="min_cart_value" label="Minimum Cart Value (Optional)" value="{{ old('min_cart_value', $coupon->min_cart_value) }}" type="number" step="0.01" min="0" />
            </div>
            <div class="col-sm-6">
                <input class="form-control form-control-lg" name="starts_at" label="Starts At (Optional)" value="{{ old('starts_at', $coupon->starts_at?->format('Y-m-d\TH:i')) }}" type="datetime-local" />
            </div>
            <div class="col-sm-6">
                <input class="form-control form-control-lg" name="expires_at" label="Expires At (Optional)" value="{{ old('expires_at', $coupon->expires_at?->format('Y-m-d\TH:i')) }}" type="datetime-local" />
            </div>
            <div class="col-sm-6">
                <input class="form-control form-control-lg" name="usage_limit_total" label="Total Usage Limit (Optional)" value="{{ old('usage_limit_total', $coupon->usage_limit_total) }}" type="number" step="1" min="1" />
            </div>
            <div class="col-sm-6">
                <input class="form-control form-control-lg" name="usage_limit_per_user" label="Usage Limit Per User (Optional)" value="{{ old('usage_limit_per_user', $coupon->usage_limit_per_user) }}" type="number" step="1" min="1" />
            </div>
        </div>
    </div>
    
    <div class="col-12 col-xl-4">
        <h4 class="mb-3">Settings</h4>
        
        <div class="card mb-4">
            <div class="card-body">
                <h5 class="card-title mb-3">Status & Eligibility</h5>
                
                <div class="form-check form-switch mb-4">
                    <input class="form-check-input" type="checkbox" role="switch" name="is_active" id="is_active" value="1" @checked(old('is_active', $coupon->is_active))>
                    <label class="form-check-label" for="is_active">Active</label>
                </div>
                
                <label class="form-label" for="eligibility">Customer Eligibility</label>
                <select class="form-select @error('eligibility') is-invalid @enderror" name="eligibility" id="eligibility">
                    <option value="both" @selected(old('eligibility', $coupon->eligibility) === 'both')>All Customers</option>
                    <option value="retail" @selected(old('eligibility', $coupon->eligibility) === 'retail')>Retail Only</option>
                    <option value="wholesale" @selected(old('eligibility', $coupon->eligibility) === 'wholesale')>Wholesale Only</option>
                </select>
                @error('eligibility')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
        </div>
    </div>
</div> -->
<div class="row g-5">
    <div class="col-12 col-xl-8">
        <h4 class="mb-3">Coupon Details</h4>

        <div class="row g-3">
            <div class="col-sm-6">
                <label class="form-label" for="code">Coupon Code <span class="text-danger">*</span></label>
                <input id="code" class="form-control form-control-lg @error('code') is-invalid @enderror"
                       name="code"
                       value="{{ old('code', $coupon->code) }}"
                       required />
                @error('code')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="col-sm-6">
                <label class="form-label" for="type">Discount Type <span class="text-danger">*</span></label>
                <select class="form-select @error('type') is-invalid @enderror" name="type" id="type" required>
                    <option value="percentage" @selected(old('type', $coupon->type) === 'percentage')>Percentage</option>
                    <option value="fixed" @selected(old('type', $coupon->type) === 'fixed')>Fixed Amount</option>
                    <option value="free_shipping" @selected(old('type', $coupon->type) === 'free_shipping')>Free Shipping</option>
                </select>
                @error('type')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="col-sm-6">
                <label class="form-label" for="value">Discount Value <span class="text-danger">*</span></label>
                <input id="value" class="form-control form-control-lg @error('value') is-invalid @enderror"
                       name="value"
                       type="number"
                       step="0.01"
                       min="0"
                       value="{{ old('value', $coupon->value) }}"
                       required />
                @error('value')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="col-sm-6">
                <label class="form-label" for="min_cart_value">Minimum Cart Value (Optional)</label>
                <input id="min_cart_value" class="form-control form-control-lg @error('min_cart_value') is-invalid @enderror"
                       name="min_cart_value"
                       type="number"
                       step="0.01"
                       min="0"
                       value="{{ old('min_cart_value', $coupon->min_cart_value) }}" />
                @error('min_cart_value')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="col-sm-6">
                <label class="form-label" for="starts_at">Starts At (Optional)</label>
                <input id="starts_at" class="form-control form-control-lg @error('starts_at') is-invalid @enderror"
                       name="starts_at"
                       type="datetime-local"
                       value="{{ old('starts_at', $coupon->starts_at?->format('Y-m-d\TH:i')) }}" />
                @error('starts_at')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="col-sm-6">
                <label class="form-label" for="expires_at">Expires At (Optional)</label>
                <input id="expires_at" class="form-control form-control-lg @error('expires_at') is-invalid @enderror"
                       name="expires_at"
                       type="datetime-local"
                       value="{{ old('expires_at', $coupon->expires_at?->format('Y-m-d\TH:i')) }}" />
                @error('expires_at')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="col-sm-6">
                <label class="form-label" for="usage_limit_total">Total Usage Limit (Optional)</label>
                <input id="usage_limit_total" class="form-control form-control-lg @error('usage_limit_total') is-invalid @enderror"
                       name="usage_limit_total"
                       type="number"
                       step="1"
                       min="1"
                       value="{{ old('usage_limit_total', $coupon->usage_limit_total) }}" />
                @error('usage_limit_total')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="col-sm-6">
                <label class="form-label" for="usage_limit_per_user">Usage Limit Per User (Optional)</label>
                <input id="usage_limit_per_user" class="form-control form-control-lg @error('usage_limit_per_user') is-invalid @enderror"
                       name="usage_limit_per_user"
                       type="number"
                       step="1"
                       min="1"
                       value="{{ old('usage_limit_per_user', $coupon->usage_limit_per_user) }}" />
                @error('usage_limit_per_user')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
        </div>
    </div>

     <div class="col-12 col-xl-4">
        <h4 class="mb-3">Settings</h4>
        
        <div class="card mb-4">
            <div class="card-body">
                <h5 class="card-title mb-3">Status & Eligibility</h5>
                
                <div class="form-check form-switch mb-4">
                    <input class="form-check-input" type="checkbox" role="switch" name="is_active" id="is_active" value="1" @checked(old('is_active', $coupon->is_active))>
                    <label class="form-check-label" for="is_active">Active</label>
                </div>
                
                <label class="form-label" for="eligibility">Customer Eligibility</label>
                <select class="form-select @error('eligibility') is-invalid @enderror" name="eligibility" id="eligibility">
                    <option value="both" @selected(old('eligibility', $coupon->eligibility) === 'both')>All Customers</option>
                    <option value="retail" @selected(old('eligibility', $coupon->eligibility) === 'retail')>Retail Only</option>
                    <option value="wholesale" @selected(old('eligibility', $coupon->eligibility) === 'wholesale')>Wholesale Only</option>
                </select>
                @error('eligibility')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
        </div>
    </div>
</div>