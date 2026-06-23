@extends('layouts.storefront')

@section('title', 'Checkout — gobuy')

@section('content')
    <section class="pt-5 pb-9">
        <div class="container-small">
            <nav class="mb-3" aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('cart.index') }}">Cart</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Checkout</li>
                </ol>
            </nav>
            <h2 class="mb-4">Check out</h2>

            <div class="d-flex align-items-center gap-2 mb-5 fs-9 flex-wrap">
                <span class="badge badge-phoenix badge-phoenix-primary"><span class="fas fa-1 me-1"></span>Details</span>
                <span class="text-body-tertiary"><span class="fas fa-chevron-right fs-10"></span></span>
                <span class="badge badge-phoenix badge-phoenix-primary"><span class="fas fa-2 me-1"></span>Delivery</span>
                <span class="text-body-tertiary"><span class="fas fa-chevron-right fs-10"></span></span>
                <span class="badge badge-phoenix badge-phoenix-secondary"><span class="fas fa-3 me-1"></span>Payment</span>
            </div>

            @if (session('error'))
                <div class="alert alert-subtle-danger">{{ session('error') }}</div>
            @endif

            <form action="{{ route('checkout.store') }}" method="POST">
                @csrf
                <input type="hidden" name="delivery_method" id="deliveryMethod" value="home_delivery">
                <div class="row justify-content-between">
                    <div class="col-lg-7">
                        @if ($errors->any())
                            <div class="alert alert-subtle-danger">
                                <ul class="mb-0">
                                    @foreach ($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        <h3 class="mb-4">Delivery details</h3>

                        @if ($addresses->isNotEmpty())
                            <div class="mb-4">
                                <label class="form-label fw-semibold">Use a saved address</label>
                                <select class="form-select" id="savedAddressPicker">
                                    @foreach ($addresses as $address)
                                        <option value="{{ $address->id }}" @selected($defaultAddress && $address->id === $defaultAddress->id)>
                                            {{ $address->label ? $address->label.' — ' : '' }}{{ $address->recipient_name }}, {{ $address->formatted() }}
                                        </option>
                                    @endforeach
                                    <option value="">Enter a new address…</option>
                                </select>
                            </div>
                            @php
                                $addressData = $addresses->map(fn ($a) => [
                                    "id" => $a->id,
                                    "recipient_name" => $a->recipient_name,
                                    "phone" => $a->phone,
                                    "city" => $a->city,
                                    "state" => $a->state,
                                    "address_line" => trim($a->line1.($a->line2 ? ", ".$a->line2 : "")),
                                ]);
                            @endphp
                            <div id="addressData" data-addresses='@json($addressData)'></div>
                        @endif

                        @php($pf = $defaultAddress)
                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <label class="form-label">Full name</label>
                                <input class="form-control" type="text" name="customer_name" id="cd-name"
                                       value="{{ old('customer_name', $pf?->recipient_name ?? auth()->user()?->name) }}" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Email</label>
                                <input class="form-control" type="email" name="customer_email"
                                       value="{{ old('customer_email', auth()->user()?->email) }}" required>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Phone</label>
                                <input class="form-control" type="text" name="customer_phone" id="cd-phone"
                                       value="{{ old('customer_phone', $pf?->phone) }}" required>
                            </div>
                        </div>

                        <label class="form-label fw-semibold">Delivery method</label>
                        <div class="row g-2 mb-4">
                            <div class="col-sm-6">
                                <label class="d-flex align-items-center border border-translucent rounded-3 p-3 h-100 cursor-pointer">
                                    <input class="form-check-input mt-0 me-2 delivery-method-radio" type="radio" name="delivery_method_choice" value="home_delivery" checked>
                                    <span><span class="fas fa-truck me-2 text-primary"></span>Home delivery</span>
                                </label>
                            </div>
                            <div class="col-sm-6">
                                <label class="d-flex align-items-center border border-translucent rounded-3 p-3 h-100 cursor-pointer">
                                    <input class="form-check-input mt-0 me-2 delivery-method-radio" type="radio" name="delivery_method_choice" value="pickup">
                                    <span><span class="fas fa-store me-2 text-primary"></span>Pick up in store</span>
                                </label>
                            </div>
                        </div>

                        <div id="homeFields" class="row g-3 mb-5">
                            <div class="col-md-6">
                                <label class="form-label">State</label>
                                <input class="form-control" type="text" name="state" id="cd-state" value="{{ old('state', $pf?->state) }}" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">City</label>
                                <input class="form-control" type="text" name="city" id="cd-city" value="{{ old('city', $pf?->city) }}" required>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Address</label>
                                <input class="form-control" type="text" name="address_line" id="cd-address"
                                       value="{{ old('address_line', $pf ? trim($pf->line1.($pf->line2 ? ', '.$pf->line2 : '')) : '') }}" required>
                            </div>
                        </div>

                        <div id="pickupFields" class="mb-5 d-none">
                            <label class="form-label">Choose a pickup location</label>
                            <select class="form-select" name="pickup_location_id" id="pickupSelect">
                                @foreach ($pickupLocations as $loc)
                                    <option value="{{ $loc->id }}" data-state="{{ $loc->state }}">{{ $loc->name }} — {{ $loc->formatted() }}@if ($loc->opening_hours) ({{ $loc->opening_hours }})@endif</option>
                                @endforeach
                            </select>
                            <p class="fs-9 text-body-tertiary mt-2 mb-0">Pickup is free. Bring your order number and a valid ID.</p>
                        </div>

                        @push('scripts')
                            <script>
                                (function () {
                                    var radios = document.querySelectorAll('.delivery-method-radio');
                                    var hidden = document.getElementById('deliveryMethod');
                                    var homeFields = document.getElementById('homeFields');
                                    var pickupFields = document.getElementById('pickupFields');
                                    var pickupSelect = document.getElementById('pickupSelect');
                                    var stateInput = document.getElementById('cd-state');
                                    var addressInputs = ['cd-state', 'cd-city', 'cd-address'].map(function (id) { return document.getElementById(id); });
                                    var subtotalKobo = parseInt(document.querySelector('[data-subtotal-kobo]').dataset.subtotalKobo) || 0;

                                    function fmt(kobo) { return '₦' + (kobo / 100).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }); }

                                    function currentMethod() {
                                        var checked = document.querySelector('.delivery-method-radio:checked');
                                        return checked ? checked.value : 'home_delivery';
                                    }

                                    function refreshQuote() {
                                        var method = currentMethod();
                                        var state = method === 'pickup'
                                            ? (pickupSelect.options[pickupSelect.selectedIndex]?.dataset.state || '')
                                            : (stateInput.value || '');

                                        fetch('{{ route('checkout.delivery-quote') }}', {
                                            method: 'POST',
                                            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' },
                                            body: JSON.stringify({ delivery_method: method, state: state })
                                        }).then(function (r) { return r.json(); }).then(function (d) {
                                            document.getElementById('sum-delivery').textContent = d.fee_formatted;
                                            document.getElementById('sum-total').textContent = d.total_formatted;
                                            document.getElementById('sum-pay-label').textContent = 'Pay ' + d.total_formatted;
                                            document.getElementById('sum-zone').textContent = d.zone ? '(' + d.zone + ')' : '';
                                        }).catch(function () {});
                                    }

                                    function applyMethod() {
                                        var pickup = currentMethod() === 'pickup';
                                        hidden.value = pickup ? 'pickup' : 'home_delivery';
                                        homeFields.classList.toggle('d-none', pickup);
                                        pickupFields.classList.toggle('d-none', !pickup);
                                        addressInputs.forEach(function (el) { if (el) { el.required = !pickup; } });
                                        refreshQuote();
                                    }

                                    radios.forEach(function (r) { r.addEventListener('change', applyMethod); });
                                    if (pickupSelect) { pickupSelect.addEventListener('change', refreshQuote); }
                                    if (stateInput) { stateInput.addEventListener('change', refreshQuote); }
                                    applyMethod();
                                })();
                            </script>
                        @endpush

                        @if ($addresses->isNotEmpty())
                            @push('scripts')
                                <script>
                                    (function () {
                                        var picker = document.getElementById('savedAddressPicker');
                                        var store = document.getElementById('addressData');
                                        if (!picker || !store) return;
                                        var addresses = JSON.parse(store.dataset.addresses || '[]');
                                        var byId = {};
                                        addresses.forEach(function (a) { byId[a.id] = a; });

                                        picker.addEventListener('change', function () {
                                            var a = byId[this.value];
                                            if (!a) return; // "Enter a new address" — leave fields as-is
                                            document.getElementById('cd-name').value = a.recipient_name;
                                            document.getElementById('cd-phone').value = a.phone;
                                            document.getElementById('cd-state').value = a.state;
                                            document.getElementById('cd-city').value = a.city;
                                            document.getElementById('cd-address').value = a.address_line;
                                        });
                                    })();
                                </script>
                            @endpush
                        @endif

                        <h3 class="mb-3">Payment</h3>
                        <div class="d-flex flex-column gap-2 mb-4">
                            <label class="d-flex align-items-start border border-translucent rounded-3 p-3 cursor-pointer">
                                <input class="form-check-input mt-1 me-3" type="radio" name="payment_method" value="paystack" checked>
                                <span>
                                    <span class="fw-semibold d-block"><span class="fas fa-shield-halved text-success me-2"></span>Pay now — Card, transfer or USSD</span>
                                    <span class="fs-9 text-body-tertiary">Secure payment via Paystack. You'll be redirected to complete it.</span>
                                </span>
                            </label>
                            <label class="d-flex align-items-start border border-translucent rounded-3 p-3 cursor-pointer">
                                <input class="form-check-input mt-1 me-3" type="radio" name="payment_method" value="bank_transfer">
                                <span>
                                    <span class="fw-semibold d-block"><span class="fas fa-building-columns text-primary me-2"></span>Bank transfer</span>
                                    <span class="fs-9 text-body-tertiary">Transfer manually and upload your proof of payment. Ships once confirmed.</span>
                                </span>
                            </label>
                            @if ($podEligible)
                                <label class="d-flex align-items-start border border-translucent rounded-3 p-3 cursor-pointer">
                                    <input class="form-check-input mt-1 me-3" type="radio" name="payment_method" value="pod">
                                    <span>
                                        <span class="fw-semibold d-block"><span class="fas fa-hand-holding-dollar text-warning me-2"></span>Pay on delivery</span>
                                        <span class="fs-9 text-body-tertiary">Pay with cash when your order arrives.</span>
                                    </span>
                                </label>
                            @endif
                        </div>
                    </div>

                    <div class="col-lg-5 col-xl-4">
                        <div class="card mt-3 mt-lg-0">
                            <div class="card-body">
                                <h3 class="mb-3">Summary</h3>
                                <div class="border-bottom border-dashed border-translucent pb-2 mb-2">
                                    @foreach ($lines as $line)
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
                                    <p class="text-body-emphasis fw-semibold">{{ money($subtotal) }}</p>
                                </div>
                                @if ($appliedCoupon)
                                    <div class="d-flex justify-content-between">
                                        <p class="text-body fw-semibold"><span class="fas fa-tag text-success me-1"></span>Discount <span class="fs-10 text-body-tertiary">({{ $appliedCoupon->code }})</span></p>
                                        <p class="text-success fw-semibold">&minus;{{ money($discount) }}</p>
                                    </div>
                                @endif
                                <div class="d-flex justify-content-between" data-subtotal-kobo="{{ $subtotal->minus($discount)->kobo }}">
                                    <p class="text-body fw-semibold">Delivery <span id="sum-zone" class="fs-10 text-body-tertiary fw-normal"></span></p>
                                    <p class="text-body-emphasis fw-semibold" id="sum-delivery">{{ money($deliveryFee) }}</p>
                                </div>
                                <div class="d-flex justify-content-between {{ ($creditAvailable ?? \App\Support\Money::zero())->isPositive() ? 'pt-3' : 'border-y border-dashed border-translucent py-3 mb-4' }}">
                                    <h4 class="mb-0">Total</h4>
                                    <h4 class="mb-0" id="sum-total">{{ money($total) }}</h4>
                                </div>

                                @if (($creditAvailable ?? \App\Support\Money::zero())->isPositive())
                                    <div class="d-flex justify-content-between align-items-center bg-success-subtle rounded-2 px-3 py-2 my-3">
                                        <label class="form-check-label fs-9 mb-0" for="applyCreditToggle">
                                            <span class="fas fa-wallet text-success me-1"></span>Use store credit
                                            <span class="d-block text-body-tertiary">{{ money($creditAvailable) }} available</span>
                                        </label>
                                        <div class="form-check form-switch mb-0">
                                            <input class="form-check-input" type="checkbox" role="switch" id="applyCreditToggle"
                                                   @checked($applyCredit ?? false)
                                                   onchange="document.getElementById('creditToggleForm').querySelector('[name=apply]').value = this.checked ? 1 : 0; document.getElementById('creditToggleForm').submit();">
                                        </div>
                                    </div>
                                    @if (($creditApplied ?? \App\Support\Money::zero())->isPositive())
                                        <div class="d-flex justify-content-between">
                                            <p class="text-body fw-semibold">Store credit</p>
                                            <p class="text-success fw-semibold">&minus;{{ money($creditApplied) }}</p>
                                        </div>
                                    @endif
                                    <div class="d-flex justify-content-between border-y border-dashed border-translucent py-3 mb-4">
                                        <h4 class="mb-0">Amount due</h4>
                                        <h4 class="mb-0">{{ money($amountDue ?? $total) }}</h4>
                                    </div>
                                @endif

                                <button class="btn btn-primary w-100" type="submit">
                                    <span id="sum-pay-label">Pay {{ money($amountDue ?? $total) }}</span><span class="fas fa-chevron-right ms-1 fs-10"></span>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </form>

            @auth
                <form id="creditToggleForm" action="{{ route('checkout.store-credit') }}" method="POST" class="d-none">
                    @csrf
                    <input type="hidden" name="apply" value="{{ ($applyCredit ?? false) ? 1 : 0 }}">
                </form>
            @endauth
        </div>
    </section>
@endsection
