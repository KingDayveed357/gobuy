<div class="card mb-4">
    <div class="card-body">
        <h3 class="card-title mb-4">Summary</h3>
        
        <div class="d-flex justify-content-between mb-2">
            <h6 class="text-body fw-semibold mb-0">Subtotal :</h6>
            <h6 class="text-body fw-semibold mb-0">{{ money($order->subtotal) }}</h6>
        </div>
        
        @if ($order->discount_amount->isPositive())
            <div class="d-flex justify-content-between mb-2">
                <h6 class="text-body fw-semibold mb-0">Discount :</h6>
                <h6 class="text-danger fw-semibold mb-0">-{{ money($order->discount_amount) }}</h6>
            </div>
        @endif
        
        <div class="d-flex justify-content-between mb-2">
            <h6 class="text-body fw-semibold mb-0">Delivery :</h6>
            <h6 class="text-body fw-semibold mb-0">{{ money($order->delivery_fee) }}</h6>
        </div>
        
        @if ($order->tax_amount->isPositive())
            <div class="d-flex justify-content-between mb-2">
                <h6 class="text-body fw-semibold mb-0">Tax :</h6>
                <h6 class="text-body fw-semibold mb-0">{{ money($order->tax_amount) }}</h6>
            </div>
        @endif
        
        <div class="d-flex justify-content-between mb-3 border-bottom border-translucent pb-3"></div>
        
        <div class="d-flex justify-content-between mb-2">
            <h5 class="text-body-emphasis fw-bold mb-0">Total :</h5>
            <h5 class="text-body-emphasis fw-bold mb-0">{{ money($order->total) }}</h5>
        </div>
        
        @if ($order->refunded_total?->isPositive())
            <div class="d-flex justify-content-between mb-2">
                <h6 class="text-danger fw-semibold mb-0">Refunded :</h6>
                <h6 class="text-danger fw-semibold mb-0">-{{ money($order->refunded_total) }}</h6>
            </div>
        @endif
        
        <div class="d-flex justify-content-between mt-3 pt-3 border-top border-translucent bg-body-highlight p-3 rounded">
            <h5 class="text-success fw-bold mb-0">Net Paid :</h5>
            <h5 class="text-success fw-bold mb-0">{{ money($order->netPaid()) }}</h5>
        </div>
    </div>
</div>
