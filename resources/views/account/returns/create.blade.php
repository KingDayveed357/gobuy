@extends('layouts.storefront')

@section('title', 'Request a return — gobuy')

@section('content')
    <section class="pt-5 pb-9">
        <div class="container-small">
            <nav class="mb-3" aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('account.dashboard') }}">Account</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('account.orders') }}">Orders</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Request return</li>
                </ol>
            </nav>

            <h2 class="mb-1">Request a return</h2>
            <p class="text-body-tertiary mb-4">
                Order {{ $order->order_number }} ·
                @if ($windowExpiresAt)
                    Return window closes {{ $windowExpiresAt->format('M j, Y') }}
                @endif
            </p>

            @if (session('error'))
                <div class="alert alert-subtle-danger">{{ session('error') }}</div>
            @endif

            <form action="{{ route('account.returns.store', $order) }}" method="POST" enctype="multipart/form-data">
                @csrf
                <input type="hidden" name="idempotency_key" value="{{ old('idempotency_key', \Illuminate\Support\Str::uuid()) }}">

                <div class="card mb-4">
                    <div class="card-body">
                        <h5 class="mb-3">Which items are you returning?</h5>
                        @foreach ($eligibleItems as $item)
                            <div class="d-flex flex-wrap align-items-center gap-3 border-bottom border-translucent py-3">
                                <div class="form-check mb-0">
                                    <input class="form-check-input" type="checkbox" id="sel-{{ $item->id }}" name="items[{{ $item->id }}][selected]" value="1" @checked(old("items.{$item->id}.selected"))>
                                    <input type="hidden" name="items[{{ $item->id }}][order_item_id]" value="{{ $item->id }}">
                                </div>
                                <label class="flex-grow-1 mb-0" for="sel-{{ $item->id }}">
                                    <span class="fw-semibold text-body-emphasis d-block">{{ $item->name }}</span>
                                    <span class="fs-9 text-body-tertiary">Purchased {{ $item->quantity }} · {{ money($item->unit_price) }} each</span>
                                </label>
                                <div style="width: 90px;">
                                    <label class="form-label fs-9 mb-1">Qty</label>
                                    <input type="number" class="form-control form-control-sm" name="items[{{ $item->id }}][quantity]" min="1" max="{{ $item->returnableQuantity() }}" value="{{ old("items.{$item->id}.quantity", 1) }}">
                                </div>
                                <div style="width: 150px;">
                                    <label class="form-label fs-9 mb-1">Condition</label>
                                    <select class="form-select form-select-sm" name="items[{{ $item->id }}][condition_reported]">
                                        <option value="unopened">Unopened</option>
                                        <option value="opened">Opened</option>
                                        <option value="damaged">Damaged</option>
                                    </select>
                                </div>
                            </div>
                        @endforeach

                        @if (! empty($blocked))
                            <p class="fs-9 text-body-tertiary mt-3 mb-0">
                                Some items can't be returned:
                                @foreach ($blocked as $b) <span class="d-block">• {{ $b['item']->name }} — {{ $b['reason'] }}</span> @endforeach
                            </p>
                        @endif
                    </div>
                </div>

                <div class="card mb-4">
                    <div class="card-body">
                        <h5 class="mb-3">Reason & refund</h5>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label" for="reason_code">Reason for return</label>
                                <select class="form-select" id="reason_code" name="reason_code" required>
                                    @foreach ($reasons as $reason)
                                        <option value="{{ $reason->value }}" @selected(old('reason_code') === $reason->value)>{{ $reason->label() }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="refund_destination">Refund to</label>
                                <select class="form-select" id="refund_destination" name="refund_destination" required>
                                    @foreach ($destinations as $destination)
                                        <option value="{{ $destination->value }}" @selected(old('refund_destination', 'store_credit') === $destination->value)>{{ $destination->label() }}</option>
                                    @endforeach
                                </select>
                                <div class="form-text">Store credit is instant. Refunds to your bank take 3–7 business days.</div>
                            </div>
                            <div class="col-12" x-data="imageUploader()">
                                <label class="form-label" for="photos">Photos <span class="text-body-tertiary fw-normal">(required for damaged / faulty / wrong items)</span></label>
                                <div 
                                    class="border border-dashed rounded-3 p-4 text-center position-relative"
                                    :class="{ 'border-primary bg-primary-subtle': isDropping, 'border-300': !isDropping, 'border-danger': error }"
                                    @dragover.prevent="isDropping = true"
                                    @dragleave.prevent="isDropping = false"
                                    @drop.prevent="handleDrop($event)"
                                >
                                    <input 
                                        type="file" 
                                        id="photos" 
                                        name="photos[]" 
                                        accept="image/*" 
                                        multiple
                                        class="position-absolute w-100 h-100 top-0 start-0 opacity-0"
                                        style="cursor: pointer;"
                                        @change="handleFiles($event)"
                                        x-ref="fileInput"
                                    >
                                    <span class="fas fa-cloud-arrow-up fs-3 text-body-tertiary mb-2"></span>
                                    <h6 class="mb-1">Drag and drop photos here</h6>
                                    <p class="fs-9 text-body-tertiary mb-0">or click to browse (up to 5 images, 1MB each)</p>
                                    <p class="fs-10 text-body-tertiary mt-2 mb-0"><span class="fas fa-info-circle me-1 text-warning"></span>Please do not upload blurry images, screenshots, or files larger than 1MB each.</p>
                                </div>
                                
                                <div x-show="error" x-text="error" class="text-danger fs-9 mt-1 d-none" :class="{ 'd-block': error }"></div>
                                @error('photos')<div class="text-danger fs-9 mt-1">{{ $message }}</div>@enderror
                                @error('photos.*')<div class="text-danger fs-9 mt-1">{{ $message }}</div>@enderror

                                <div class="d-flex flex-wrap gap-2 mt-3" x-show="images.length > 0" style="display: none;">
                                    <template x-for="(image, index) in images" :key="index">
                                        <div class="position-relative border rounded-2 overflow-hidden shadow-sm" style="width: 84px; height: 84px;">
                                            <img :src="image.url" class="w-100 h-100 object-fit-cover" alt="Preview">
                                            <button 
                                                type="button" 
                                                class="btn btn-phoenix-danger p-1 btn-sm position-absolute top-0 bg-white end-0 m-1 "
                                                style="opacity: 0.9;"
                                                @click.prevent="removeImage(index)"
                                            >
                                                <span class="fas fa-trash  text-danger"></span>
                                            </button>
                                            
                                        </div>
                                    </template>
                                </div>
                                <script>
                                    function imageUploader() {
                                        return {
                                            isDropping: false,
                                            error: '',
                                            images: [],
                                            handleDrop(e) {
                                                this.isDropping = false;
                                                this.processFiles(e.dataTransfer.files);
                                            },
                                            handleFiles(e) {
                                                this.processFiles(e.target.files);
                                            },
                                            processFiles(files) {
                                                this.error = '';
                                                let newFiles = Array.from(files);
                                                
                                                if (this.images.length + newFiles.length > 5) {
                                                    this.error = 'You can only upload a maximum of 5 images.';
                                                    newFiles = newFiles.slice(0, 5 - this.images.length);
                                                }
                                                
                                                newFiles.forEach(file => {
                                                    if (!file.type.startsWith('image/')) {
                                                        this.error = 'Only image files are allowed.';
                                                        return;
                                                    }
                                                    if (file.size > 1 * 1024 * 1024) {
                                                        this.error = 'Each image must be less than 1MB.';
                                                        return;
                                                    }
                                                    
                                                    const reader = new FileReader();
                                                    reader.onload = (e) => {
                                                        this.images.push({
                                                            file: file,
                                                            url: e.target.result
                                                        });
                                                        this.syncInput();
                                                    };
                                                    reader.readAsDataURL(file);
                                                });
                                            },
                                            removeImage(index) {
                                                this.images.splice(index, 1);
                                                this.syncInput();
                                            },
                                            syncInput() {
                                                // Create a new DataTransfer object to sync files back to the input
                                                const dt = new DataTransfer();
                                                this.images.forEach(img => dt.items.add(img.file));
                                                this.$refs.fileInput.files = dt.files;
                                            }
                                        }
                                    }
                                </script>
                            </div>
                            <div class="col-12">
                                <label class="form-label" for="customer_note">Anything else? <span class="text-body-tertiary fw-normal">(optional)</span></label>
                                <textarea class="form-control" id="customer_note" name="customer_note" rows="3" maxlength="1000">{{ old('customer_note') }}</textarea>
                            </div>
                        </div>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary px-4"><span class="fas fa-rotate-left me-2"></span>Submit return request</button>
                <a href="{{ route('account.orders') }}" class="btn btn-phoenix-secondary ms-2">Cancel</a>
            </form>
        </div>
    </section>
@endsection
