<div class="w-100">
    {{-- Real POST form = no-JS fallback (submits the default variant + qty).
         With JS, @click.prevent intercepts and Livewire adds with no reload.
         wire:ignore keeps the page's variant/qty JS-written inputs intact. --}}
    <form action="{{ route('cart.set-quantity') }}" method="POST" wire:ignore x-data="{ adding: false }">
        @csrf
        <input type="hidden" name="product_variant_id" id="pd-variant-id" value="{{ $selectedId }}">
        <input type="hidden" name="quantity" id="pd-hidden-qty" value="{{ max(1, $cartQty) }}">
        <button type="submit"
                id="pd-add"
                class="btn btn-lg btn-warning rounded-pill w-100 fs-9 fs-sm-8"
                @disabled($stock < 1)
                x-bind:disabled="adding"
                @click.prevent="
                    adding = true;
                    await $wire.add(
                        parseInt(document.getElementById('pd-variant-id').value),
                        parseInt(document.getElementById('pd-hidden-qty').value)
                    );
                    adding = false;
                ">
            <span x-show="!adding"><span class="fas fa-shopping-cart me-2"></span><span id="pd-add-text">{{ $cartQty > 0 ? 'Update cart' : 'Add to cart' }}</span></span>
            <span x-show="adding" x-cloak><span class="fas fa-spinner fa-spin me-2"></span>Adding…</span>
        </button>
    </form>
</div>
