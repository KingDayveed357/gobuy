<script>
    // Ticks every [data-countdown] element on the page (banners + flash-sale
    // sections). Value is an ISO-8601 target; renders "Nd HH:MM:SS".
    (function () {
        function pad(n) { return n < 10 ? '0' + n : '' + n; }
        function tick() {
            document.querySelectorAll('.gb-countdown[data-countdown]').forEach(function (el) {
                var target = new Date(el.getAttribute('data-countdown')).getTime();
                var diff = Math.max(0, target - Date.now());
                var d = Math.floor(diff / 86400000);
                var h = Math.floor((diff % 86400000) / 3600000);
                var m = Math.floor((diff % 3600000) / 60000);
                var s = Math.floor((diff % 60000) / 1000);
                el.innerHTML = '<span class="fas fa-clock me-1"></span><span class="fw-bold">'
                    + (d > 0 ? d + 'd ' : '') + pad(h) + ':' + pad(m) + ':' + pad(s) + '</span>';
                if (diff <= 0) { el.removeAttribute('data-countdown'); }
            });
        }
        tick();
        setInterval(tick, 1000);
    })();
</script>
