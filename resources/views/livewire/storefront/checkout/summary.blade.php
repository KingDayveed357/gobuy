<div class="card mt-3 mt-lg-0 relative">
    {{-- A loading overlay to indicate recalculations --}}
    <div wire:loading class="position-absolute w-100 h-100 bg-white" style="opacity: 0.6; z-index: 10;"></div>

    <div class="card-body">
        <h3 class="mb-3">Summary</h3>
        
        <div class="border-bottom border-dashed border-translucent pb-2 mb-2">
            @foreach ($this->totals['lines'] as $line)
                @php($item = $line['item'])
                @php($product = $item->variant->product)
                <div class="row align-items-center mb-2 g-2">
                    <div class="col-8">
                        <div class="d-flex align-items-center">
                            <img class="me-2 border border-translucent rounded-1" src="{{ $product->imageUrl() }}" width="36" height="36" style="object-fit: contain;" alt="">
                            <h6 class="fw-semibold lh-base mb-0 line-clamp-1">{{ $product->name }}</h6>
                        </div>
                    </div>
                    <div class="col-2 text-center"><h6 class="fs-10 mb-0">x{{ $item->quantity }}</h6></div>
                    <div class="col-2 ps-0"><h6 class="mb-0 fw-semibold text-end">{{ money($line['lineTotal']) }}</h6></div>
                </div>
            @endforeach
        </div>
        
        <div class="d-flex justify-content-between">
            <p class="text-body fw-semibold">Subtotal</p>
            <p class="text-body-emphasis fw-semibold">{{ money($this->totals['subtotal']) }}</p>
        </div>
        
        @if ($this->totals['appliedCoupon'])
            <div class="d-flex justify-content-between">
                <p class="text-body fw-semibold"><span class="fas fa-tag text-success me-1"></span>Discount <span class="fs-10 text-body-tertiary">({{ $this->totals['appliedCoupon']->code }})</span></p>
                <p class="text-success fw-semibold">&minus;{{ money($this->totals['discount']) }}</p>
            </div>
        @endif
        
        <div class="d-flex justify-content-between">
            <p class="text-body fw-semibold">Delivery 
                @if($this->totals['deliveryZone'])
                    <span class="fs-10 text-body-tertiary fw-normal">({{ $this->totals['deliveryZone'] }})</span>
                @endif
            </p>
            <p class="text-body-emphasis fw-semibold">{{ money($this->totals['deliveryFee']) }}</p>
        </div>
        
        <div class="d-flex justify-content-between {{ $this->totals['creditAvailable']->isPositive() ? 'pt-3' : 'border-y border-dashed border-translucent py-3 mb-4' }}">
            <h4 class="mb-0">Total</h4>
            <h4 class="mb-0">{{ money($this->totals['total']) }}</h4>
        </div>

        @if ($this->totals['creditAvailable']->isPositive())
            <div class="d-flex justify-content-between align-items-center bg-success-subtle rounded-2 px-3 py-2 my-3">
                <label class="form-check-label fs-9 mb-0 cursor-pointer" for="applyCreditToggle">
                    <span class="fas fa-wallet text-success me-1"></span>Use store credit
                    <span class="d-block text-body-tertiary">{{ money($this->totals['creditAvailable']) }} available</span>
                </label>
                <div class="form-check form-switch mb-0">
                    <input class="form-check-input cursor-pointer" type="checkbox" role="switch" id="applyCreditToggle"
                           wire:model.live="applyCredit">
                </div>
            </div>
            
            @if ($this->totals['creditApplied']->isPositive())
                <div class="d-flex justify-content-between">
                    <p class="text-body fw-semibold">Store credit</p>
                    <p class="text-success fw-semibold">&minus;{{ money($this->totals['creditApplied']) }}</p>
                </div>
            @endif
            
            <div class="d-flex justify-content-between border-y border-dashed border-translucent py-3 mb-4">
                <h4 class="mb-0">Amount due</h4>
                <h4 class="mb-0">{{ money($this->totals['amountDue']) }}</h4>
            </div>
        @endif

        {{-- Note: The outer checkout <form> handles the submit, but we need to show the correct amount here --}}
        <button class="btn btn-primary w-100" type="submit">
            <span>Pay {{ money($this->totals['amountDue']) }}</span><span class="fas fa-chevron-right ms-1 fs-10"></span>
        </button>
        
        @auth
            @if (auth()->user()->isWholesale())
                <a href="{{ route('proforma.show') }}" target="_blank" class="btn btn-phoenix-secondary w-100 mt-2" title="Open proforma invoice in a new tab"><span class="fas fa-file-invoice me-2"></span>Download proforma invoice</a>
            @endif
        @endauth
    </div>
</div>
