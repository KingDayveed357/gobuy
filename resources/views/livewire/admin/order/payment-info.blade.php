<div class="card mb-4" x-data="{ loading: false }" x-on:livewire:commit.window="loading = false">
    <div class="card-body">
        <h3 class="card-title mb-4">Payment</h3>
        <div class="d-flex flex-between-center mb-2">
            <span class="fs-9 text-body-tertiary">Method</span>
            <span class="badge badge-phoenix badge-phoenix-info">{{ $order->payment_method?->label() ?? 'Paystack' }}</span>
        </div>
        <div class="d-flex flex-between-center mt-2">
            <span class="fs-9 text-body-tertiary">Status</span>
            @php
                $paymentColor = match($order->payment_status->value) {
                    'paid' => 'success',
                    'partially_refunded' => 'info',
                    'refunded' => 'secondary',
                    default => 'warning'
                };
            @endphp
            <span class="badge badge-phoenix badge-phoenix-{{ $paymentColor }}">
                {{ $order->payment_status->label() }}
            </span>
        </div>

        @if (auth('admin')->user()?->can('manage_payments')
            && $order->payment_method === \App\Modules\Order\Enums\PaymentMethod::PayOnDelivery
            && ! $order->isPaid())
            <div class="mt-3">
                <button type="button" 
                        wire:click="markCashCollected" 
                        @click="loading = true"
                        wire:loading.attr="disabled"
                        :disabled="loading"
                        class="btn btn-sm btn-phoenix-success w-100">
                    <span wire:loading.remove><span class="fas fa-hand-holding-dollar me-2"></span>Mark cash collected</span>
                    <span wire:loading><span class="spinner-border spinner-border-sm me-2" role="status"></span>Processing...</span>
                </button>
            </div>
            
            @if (session()->has('payment_success'))
                <div class="alert alert-subtle-success fs-10 mt-3 mb-0 py-2">{{ session('payment_success') }}</div>
            @endif
        @endif
    </div>
</div>
