@extends('layouts.storefront')

@section('title', 'Return '.$return->reference.' — Quintessential Mart')

@section('content')
    <section class="pt-5 pb-9">
        <div class="container-small">
            <nav class="mb-3" aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('account.dashboard') }}">Account</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('account.returns.index') }}">Returns</a></li>
                    <li class="breadcrumb-item active" aria-current="page">{{ $return->reference }}</li>
                </ol>
            </nav>

            <div class="d-flex flex-wrap justify-content-between align-items-center mb-4">
                <div>
                    <h2 class="mb-1">Return {{ $return->reference }}</h2>
                    <p class="text-body-tertiary mb-0">Order {{ $return->order->order_number }} · Refund to {{ $return->refund_destination->label() }}</p>
                </div>
                <span class="badge badge-phoenix {{ $return->status->isSettled() ? 'badge-phoenix-success' : ($return->status->isOpen() ? 'badge-phoenix-warning' : 'badge-phoenix-secondary') }} fs-9">{{ $return->status->label() }}</span>
            </div>

            <div class="row g-4">
                <div class="col-lg-7">
                    <div class="card mb-4"><div class="card-body">
                        <h5 class="mb-3">Items</h5>
                        <table class="table table-sm fs-9 mb-0">
                            <thead><tr><th>Item</th><th class="text-center">Qty</th><th class="text-end">Paid</th></tr></thead>
                            <tbody>
                                @foreach ($return->items as $item)
                                    <tr>
                                        <td class="text-body-emphasis">{{ $item->orderItem->name ?? 'Item' }}</td>
                                        <td class="text-center">{{ $item->quantity }}</td>
                                        <td class="text-end">{{ money($item->unit_price_snapshot) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div></div>

                    @if ($return->status->value === 'info_requested')
                        @php($infoEvent = $return->events->firstWhere('action', 'info_requested'))
                        <div class="card mb-4 border-warning"><div class="card-body">
                            <h6 class="mb-2"><span class="fas fa-circle-question me-2"></span>We need a bit more info</h6>
                            @if ($infoEvent && data_get($infoEvent->meta, 'message'))
                                <p class="fs-9 mb-3">“{{ data_get($infoEvent->meta, 'message') }}”</p>
                            @endif
                            <form action="{{ route('account.returns.reply', $return) }}" method="POST" enctype="multipart/form-data">
                                @csrf
                                <textarea name="message" class="form-control form-control-sm mb-2" rows="3" placeholder="Your reply…" maxlength="1000" required></textarea>
                                <div x-data="imageUploader()" class="mb-2">
                                    <div 
                                        class="border border-dashed rounded-3 p-3 text-center position-relative"
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
                                        <span class="fas fa-image fs-5 text-body-tertiary mb-1 d-block"></span>
                                        <p class="fs-9 text-body-tertiary mb-0">Drag & drop or click to add photos (up to 5 images, 1MB each)</p>
                                        <p class="fs-10 text-body-tertiary mt-2 mb-0"><span class="fas fa-info-circle me-1 text-warning"></span>Please do not upload blurry images, screenshots, or files larger than 1MB each.</p>
                                    </div>
                                    <div x-show="error" x-text="error" class="text-danger fs-9 mt-1 d-none" :class="{ 'd-block': error }"></div>
                                    <div class="d-flex flex-wrap gap-2 mt-2" x-show="images.length > 0" style="display: none;">
                                        <template x-for="(image, index) in images" :key="index">
                                            <div class="position-relative border rounded-2 overflow-hidden shadow-sm" style="width: 60px; height: 60px;">
                                                <img :src="image.url" class="w-100 h-100 object-fit-cover" alt="Preview">
                                                <button 
                                                    type="button" 
                                                    class="btn btn-phoenix-danger btn-sm p-1 position-absolute top-0 end-0 m-1 bg-white shadow-sm"
                                                    style="opacity: 0.9;"
                                                    @click.prevent="removeImage(index)"
                                                >
                                                    <span class="fas fa-trash-alt fs-10 text-danger"></span>
                                                </button>
                                            </div>
                                        </template>
                                    </div>
                                    <script>
                                        if (typeof imageUploader === 'undefined') {
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
                                                        const dt = new DataTransfer();
                                                        this.images.forEach(img => dt.items.add(img.file));
                                                        this.$refs.fileInput.files = dt.files;
                                                    }
                                                }
                                            }
                                        }
                                    </script>
                                </div>
                                <button class="btn btn-primary btn-sm"><span class="fas fa-paper-plane me-2"></span>Send reply</button>
                            </form>
                        </div></div>
                    @endif

                    @if (in_array($return->status->value, ['awaiting_shipment', 'in_transit'], true) && $return->returnShipment)
                        <div class="card mb-4 border-primary"><div class="card-body">
                            <h6 class="mb-2"><span class="fas fa-truck-fast me-2"></span>Ship your item back</h6>
                            <p class="fs-9 mb-1">Tracking: <span class="fw-semibold">{{ $return->returnShipment->tracking_reference }}</span>
                                <span class="badge {{ $return->returnShipment->isMerchantPaid() ? 'text-bg-success' : 'text-bg-secondary' }} ms-1">{{ $return->returnShipment->isMerchantPaid() ? 'Prepaid' : 'You pay shipping' }}</span>
                            </p>
                            <div class="d-flex flex-wrap gap-2 mt-3">
                                <a href="{{ route('account.returns.label', $return) }}" class="btn btn-phoenix-primary btn-sm"><span class="fas fa-tag me-2"></span>View / print label</a>
                                @if ($return->status->value === 'awaiting_shipment')
                                    <form action="{{ route('account.returns.shipped', $return) }}" method="POST">
                                        @csrf
                                        <button type="submit" class="btn btn-primary btn-sm"><span class="fas fa-check me-2"></span>I've shipped it</button>
                                    </form>
                                @else
                                    <span class="badge badge-phoenix badge-phoenix-info align-self-center">On its way back</span>
                                @endif
                            </div>
                        </div></div>
                    @endif

                    @if ($return->status->canTransitionTo(\App\Modules\Returns\Enums\ReturnStatus::Cancelled))
                        <div x-data="{ showCancelModal: false }">
                            <button @click="showCancelModal = true" type="button" class="btn btn-phoenix-danger btn-sm">Cancel return</button>
                            
                            <!-- Cancel Modal -->
                            <div class="modal fade" :class="{ 'show d-block': showCancelModal }" tabindex="-1" style="background: rgba(0,0,0,0.5);" x-show="showCancelModal" style="display: none;">
                                <div class="modal-dialog modal-dialog-centered">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title">Cancel Return Request</h5>
                                            <button type="button" class="btn-close" @click="showCancelModal = false" aria-label="Close"></button>
                                        </div>
                                        <div class="modal-body">
                                            <p class="fs-9 text-body-secondary mb-0">Are you sure you want to cancel this return? This action is irreversible, and you may not be able to open a new return if the return window has expired.</p>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary btn-sm" @click="showCancelModal = false">Nevermind</button>
                                            <form action="{{ route('account.returns.cancel', $return) }}" method="POST" class="d-inline">
                                                @csrf
                                                <button type="submit" class="btn btn-danger btn-sm">Confirm Cancellation</button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>

                <div class="col-lg-5">
                    <div class="card"><div class="card-body">
                        <h5 class="mb-3">Timeline</h5>
                        <ul class="list-unstyled mb-0">
                            @foreach ($return->events as $event)
                                <li class="d-flex gap-3 pb-3">
                                    <span class="fas fa-circle text-primary fs-11 mt-1"></span>
                                    <div>
                                        <p class="mb-0 fw-semibold text-body-emphasis fs-9">{{ ucfirst(str_replace('_', ' ', $event->action)) }}@if ($event->to_status) → {{ \App\Modules\Returns\Enums\ReturnStatus::from($event->to_status)->label() }}@endif</p>
                                        <span class="fs-10 text-body-tertiary">{{ $event->created_at->format('M j, Y g:i A') }}</span>
                                    </div>
                                </li>
                            @endforeach
                        </ul>
                    </div></div>
                </div>
            </div>
        </div>
    </section>
@endsection
