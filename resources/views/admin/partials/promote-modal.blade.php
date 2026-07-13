{{-- Shared "Promote" modal — populated from the clicked <x-admin.promote-button>.
     Builds UTM-tagged links + per-channel share intents entirely client-side. --}}
<div class="modal fade" id="promoteModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="promoteTitle">Promote</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="d-flex gap-3 mb-3">
                    <img id="promoteImg" src="" alt="" class="rounded-3 object-fit-cover border border-translucent" style="width:84px;height:84px;">
                    <div class="flex-1">
                        <p class="fs-9 text-body-tertiary mb-1">Share this to social — every link is tagged so you can measure clicks in analytics.</p>
                        <a id="promoteDownload" href="#" download class="btn btn-sm btn-phoenix-secondary"><span class="fas fa-download me-1"></span>Download image</a>
                    </div>
                </div>

                <label class="form-label fs-9">Pre-written caption</label>
                <div class="input-group input-group-sm mb-3">
                    <textarea id="promoteCopy" class="form-control" rows="3"></textarea>
                    <button type="button" class="btn btn-phoenix-secondary js-copy" data-copy-target="promoteCopy"><span class="fas fa-copy"></span></button>
                </div>

                <label class="form-label fs-9">Tracked link</label>
                <div class="input-group input-group-sm mb-3">
                    <input id="promoteLink" type="text" class="form-control" readonly>
                    <button type="button" class="btn btn-phoenix-secondary js-copy" data-copy-target="promoteLink"><span class="fas fa-copy"></span></button>
                </div>

                <label class="form-label fs-9">Share to</label>
                <div class="d-flex flex-wrap gap-2">
                    <a id="promoteWa" href="#" target="_blank" rel="noopener" class="btn btn-sm btn-phoenix-success"><span class="fab fa-whatsapp me-1"></span>WhatsApp</a>
                    <a id="promoteTg" href="#" target="_blank" rel="noopener" class="btn btn-sm btn-phoenix-info"><span class="fab fa-telegram me-1"></span>Telegram</a>
                    <a id="promoteFb" href="#" target="_blank" rel="noopener" class="btn btn-sm btn-phoenix-primary"><span class="fab fa-facebook-f me-1"></span>Facebook</a>
                    <a id="promoteX" href="#" target="_blank" rel="noopener" class="btn btn-sm btn-phoenix-secondary"><span class="fab fa-x-twitter me-1"></span>X</a>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
(function () {
    var modal = document.getElementById('promoteModal');
    if (!modal) { return; }

    function tagged(url, source, campaign) {
        try {
            var u = new URL(url, window.location.origin);
            u.searchParams.set('utm_source', source);
            u.searchParams.set('utm_medium', 'social');
            u.searchParams.set('utm_campaign', campaign);
            return u.toString();
        } catch (e) { return url; }
    }

    modal.addEventListener('show.bs.modal', function (e) {
        var b = e.relatedTarget;
        if (!b) { return; }
        var url = b.getAttribute('data-promote-url');
        var name = b.getAttribute('data-promote-name') || 'This product';
        var image = b.getAttribute('data-promote-image');
        var price = b.getAttribute('data-promote-price');
        var campaign = b.getAttribute('data-promote-campaign') || 'promo';

        document.getElementById('promoteTitle').textContent = 'Promote: ' + name;
        var img = document.getElementById('promoteImg');
        var dl = document.getElementById('promoteDownload');
        img.style.display = image ? '' : 'none';
        dl.style.display = image ? '' : 'none';
        if (image) { img.src = image; dl.href = image; }

        var caption = name + (price ? ' — now ' + price : '') + ' on Quintessential Mart. Shop now:';
        document.getElementById('promoteLink').value = tagged(url, 'link', campaign);
        document.getElementById('promoteCopy').value = caption + ' ' + tagged(url, 'link', campaign);

        document.getElementById('promoteWa').href = 'https://wa.me/?text=' + encodeURIComponent(caption + ' ' + tagged(url, 'whatsapp', campaign));
        document.getElementById('promoteTg').href = 'https://t.me/share/url?url=' + encodeURIComponent(tagged(url, 'telegram', campaign)) + '&text=' + encodeURIComponent(caption);
        document.getElementById('promoteFb').href = 'https://www.facebook.com/sharer/sharer.php?u=' + encodeURIComponent(tagged(url, 'facebook', campaign));
        document.getElementById('promoteX').href = 'https://twitter.com/intent/tweet?text=' + encodeURIComponent(caption) + '&url=' + encodeURIComponent(tagged(url, 'twitter', campaign));
    });

    modal.addEventListener('click', function (e) {
        var btn = e.target.closest('.js-copy');
        if (!btn) { return; }
        var field = document.getElementById(btn.getAttribute('data-copy-target'));
        if (!field) { return; }
        field.select();
        var done = function () {
            var icon = btn.querySelector('span');
            var prev = icon.className;
            icon.className = 'fas fa-check';
            setTimeout(function () { icon.className = prev; }, 1200);
        };
        if (navigator.clipboard) { navigator.clipboard.writeText(field.value).then(done).catch(done); }
        else { document.execCommand('copy'); done(); }
    });
})();
</script>
