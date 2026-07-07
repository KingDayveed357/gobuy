<script>
    // Merchandising telemetry: records an impression the first time each section
    // scrolls into view, and a click when a link/button inside it is used.
    // Batched and flushed with fetch keepalive so it survives navigation and
    // never blocks the page. No-ops gracefully when IntersectionObserver is absent.
    (function () {
        var url = @json(route('storefront.track-block'));
        var csrf = document.querySelector('meta[name="csrf-token"]');
        csrf = csrf ? csrf.getAttribute('content') : null;
        var blocks = document.querySelectorAll('[data-track-section]');
        if (!url || !csrf || !blocks.length) { return; }

        var queue = [];
        var flushTimer = null;

        function flush() {
            if (!queue.length) { return; }
            var batch = queue.splice(0, 50);
            try {
                fetch(url, {
                    method: 'POST',
                    keepalive: true,
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
                    body: JSON.stringify({ events: batch }),
                });
            } catch (e) { /* telemetry must never break the page */ }
        }

        function enqueue(id, type) {
            queue.push({ id: id, type: type });
            if (flushTimer) { clearTimeout(flushTimer); }
            flushTimer = setTimeout(flush, 1200);
        }

        // Impressions — fire once per section per page view.
        if ('IntersectionObserver' in window) {
            var io = new IntersectionObserver(function (entries) {
                entries.forEach(function (entry) {
                    if (entry.isIntersecting) {
                        enqueue(parseInt(entry.target.getAttribute('data-track-section'), 10), 'impression');
                        io.unobserve(entry.target);
                    }
                });
            }, { threshold: 0.5 });
            blocks.forEach(function (el) { io.observe(el); });
        }

        // Clicks — any actionable element inside a tracked section.
        document.addEventListener('click', function (e) {
            var actionable = e.target.closest('a, button');
            if (!actionable) { return; }
            var block = actionable.closest('[data-track-section]');
            if (!block) { return; }
            enqueue(parseInt(block.getAttribute('data-track-section'), 10), 'click');
        }, true);

        // Make sure queued events leave before the tab is hidden/closed.
        document.addEventListener('visibilitychange', function () {
            if (document.visibilityState === 'hidden') { flush(); }
        });
    })();
</script>
