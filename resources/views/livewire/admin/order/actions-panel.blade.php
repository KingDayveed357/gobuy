<div class="card mb-4">
    <div class="card-body">
        <h3 class="card-title mb-4">Order Status</h3>
        
        @if(empty($allowedTransitions))
            <p class="text-body-tertiary fs-9 mb-0">This order is in a final state ({{ $order->status->label() }}).</p>
        @else
            <form wire:submit="updateStatus">
                <div class="mb-2">
                    <select wire:model="nextStatus" class="form-select form-select-sm" required wire:loading.attr="disabled">
                        <option value="">Select next status...</option>
                        @foreach ($allowedTransitions as $transition)
                            <option value="{{ $transition->value }}">{{ $transition->label() }}</option>
                        @endforeach
                    </select>
                    @error('nextStatus') <span class="text-danger fs-10">{{ $message }}</span> @enderror
                </div>
                
                <input wire:model="note" class="form-control form-control-sm mb-3" type="text" placeholder="Note (optional)" wire:loading.attr="disabled">
                
                <button type="submit" class="btn btn-sm btn-primary w-100" wire:loading.attr="disabled">
                    <span wire:loading.remove wire:target="updateStatus">Update Status</span>
                    <span wire:loading wire:target="updateStatus"><span class="spinner-border spinner-border-sm me-2" role="status"></span>Updating...</span>
                </button>

                @if (session()->has('success'))
                    <div class="alert alert-subtle-success fs-10 mt-3 mb-0 py-2">{{ session('success') }}</div>
                @endif
            </form>
        @endif
    </div>
</div>
