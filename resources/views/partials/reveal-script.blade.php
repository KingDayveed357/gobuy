<script>
    // Scroll-reveal (M7 motion): eases each [.gb-reveal] block in as it enters the
    // viewport. CSS hides them only under html.js, so this must reveal them; if
    // IntersectionObserver is unavailable we simply show everything at once.
    (function () {
        var blocks = document.querySelectorAll('.gb-reveal');
        if (!blocks.length) { return; }

        function revealAll() { blocks.forEach(function (el) { el.classList.add('gb-reveal--in'); }); }

        if (!('IntersectionObserver' in window)) { revealAll(); return; }

        var io = new IntersectionObserver(function (entries) {
            entries.forEach(function (entry) {
                if (entry.isIntersecting) {
                    entry.target.classList.add('gb-reveal--in');
                    io.unobserve(entry.target);
                }
            });
        }, { rootMargin: '0px 0px -8% 0px', threshold: 0.08 });

        blocks.forEach(function (el) { io.observe(el); });
    })();
</script>
