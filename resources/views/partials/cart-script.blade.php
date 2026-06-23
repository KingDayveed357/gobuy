<script>
    // One delegated handler for every [data-add-to-cart] form on the page — no
    // per-card Livewire component. Posts to cart.store (JSON), toasts, and tells
    // the Livewire nav badge to refresh via the global 'cart-updated' event.
    (function () {
        var csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

        document.addEventListener('submit', async function (e) {
            var form = e.target.closest('form[data-add-to-cart]');
            if (! form) return;
            e.preventDefault();

            var btn = form.querySelector('button[type="submit"]');
            if (btn) btn.disabled = true;

            try {
                var res = await fetch(form.action, {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                    body: new FormData(form),
                });
                var data = await res.json().catch(function () { return {}; });

                if (res.ok) {
                    if (window.Toast) Toast.success(data.message || 'Added to cart.');
                    if (window.Livewire) Livewire.dispatch('cart-updated');
                } else {
                    if (window.Toast) Toast.error(data.message || 'Could not add to cart.');
                }
            } catch (err) {
                if (window.Toast) Toast.error('Network error — please try again.');
            } finally {
                if (btn) btn.disabled = false;
            }
        });
    })();
</script>
