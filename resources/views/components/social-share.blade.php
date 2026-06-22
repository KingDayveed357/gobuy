@props(['url', 'title' => '', 'price' => null])

@php
    $message = trim($title.($price ? " — {$price}" : '').' '.$url);
    $waHref = 'https://wa.me/?text='.rawurlencode($message);
    $fbHref = 'https://www.facebook.com/sharer/sharer.php?u='.rawurlencode($url);
    $xHref = 'https://twitter.com/intent/tweet?text='.rawurlencode($title).'&url='.rawurlencode($url);
@endphp

<div {{ $attributes->merge(['class' => 'd-flex align-items-center gap-2 flex-wrap']) }}>
    <span class="fs-9 text-body-tertiary me-1">Share:</span>
    <a href="{{ $waHref }}" target="_blank" rel="noopener" class="btn btn-sm btn-phoenix-success" aria-label="Share on WhatsApp"><span class="fab fa-whatsapp"></span></a>
    <a href="{{ $fbHref }}" target="_blank" rel="noopener" class="btn btn-sm btn-phoenix-primary" aria-label="Share on Facebook"><span class="fab fa-facebook-f"></span></a>
    <a href="{{ $xHref }}" target="_blank" rel="noopener" class="btn btn-sm btn-phoenix-secondary" aria-label="Share on X"><span class="fab fa-x-twitter"></span></a>
    <button type="button" class="btn btn-sm btn-phoenix-secondary js-copy-link" data-url="{{ $url }}" data-title="{{ $title }}" aria-label="Copy link"><span class="fas fa-link"></span></button>

    @once
        @push('scripts')
            <script>
                document.addEventListener('click', function (e) {
                    var btn = e.target.closest('.js-copy-link');
                    if (!btn) { return; }
                    var url = btn.dataset.url;
                    var title = btn.dataset.title || document.title;
                    
                    var done = function () {
                        var icon = btn.querySelector('span');
                        var prev = icon.className;
                        icon.className = 'fas fa-check';
                        setTimeout(function () { icon.className = prev; }, 1500);
                    };

                    if (navigator.share) {
                        navigator.share({
                            title: title,
                            url: url
                        }).catch(function(err) {
                            if (err.name !== 'AbortError') {
                                if (navigator.clipboard) navigator.clipboard.writeText(url).then(done).catch(done);
                            }
                        });
                    } else if (navigator.clipboard) {
                        navigator.clipboard.writeText(url).then(done).catch(done);
                    } else {
                        done();
                    }
                });
            </script>
        @endpush
    @endonce
</div>
