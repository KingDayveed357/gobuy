/* =============================================================================
   gobuy admin — Product image uploader
   -----------------------------------------------------------------------------
   Upgrades the product gallery dropzone into a real async uploader: each dropped
   file uploads immediately (with a live progress bar), can be cancelled mid-flight,
   and retried on failure. Successful uploads are staged server-side and represented
   by a hidden `uploaded_tokens[]` input the product form submits on save.

   Falls back gracefully: with JS off, the plain <input name="images[]"> still posts
   the files with the form.
   ============================================================================= */
(function () {
    'use strict';

    function init(cfg) {
        var dz = cfg.dropzone;
        var input = cfg.input;
        var grid = cfg.grid;
        var tokens = cfg.tokens;
        if (!dz || !input || !grid || !tokens) { return; }

        var maxFiles = cfg.maxFiles || 8;
        var maxBytes = cfg.maxBytes || 8 * 1024 * 1024;
        var existing = cfg.existingCount || 0;
        var active = 0; // uploader-created tiles not removed

        function toast(type, msg) {
            if (window.Toast && typeof window.Toast[type] === 'function') { window.Toast[type](msg); }
        }

        function total() { return existing + active; }

        function addFiles(files) {
            Array.prototype.slice.call(files).forEach(function (file) {
                if (!/^image\//.test(file.type)) { toast('error', file.name + ' is not an image.'); return; }
                if (file.size > maxBytes) { toast('error', file.name + ' is larger than 8MB.'); return; }
                if (total() >= maxFiles) { toast('error', 'You can add up to ' + maxFiles + ' images.'); return; }
                createTile(file);
            });
        }

        function createTile(file) {
            active++;
            grid.classList.remove('d-none');

            var tile = document.createElement('div');
            tile.className = 'admin-gallery-item gb-up-item';
            tile._file = file;

            var img = document.createElement('img');
            img.src = URL.createObjectURL(file);
            img.onload = function () { URL.revokeObjectURL(img.src); };
            tile.appendChild(img);

            var overlay = document.createElement('div');
            overlay.className = 'gb-up-overlay';
            overlay.innerHTML =
                '<div class="gb-up-progress"><div class="gb-up-bar"></div></div>' +
                '<button type="button" class="gb-up-btn gb-up-cancel" title="Cancel" aria-label="Cancel upload">&times;</button>' +
                '<button type="button" class="gb-up-btn gb-up-retry" title="Retry" aria-label="Retry upload"><span class="fas fa-rotate-right"></span></button>';
            tile.appendChild(overlay);

            var remove = document.createElement('button');
            remove.type = 'button';
            remove.className = 'admin-gallery-remove gb-up-remove';
            remove.title = 'Remove image';
            remove.innerHTML = '<span class="fas fa-trash"></span>';
            tile.appendChild(remove);

            var badge = document.createElement('span');
            badge.className = 'admin-gallery-badge admin-gallery-badge--new';
            badge.textContent = 'New';
            tile.appendChild(badge);

            grid.appendChild(tile);

            overlay.querySelector('.gb-up-cancel').addEventListener('click', function () { cancel(tile); });
            overlay.querySelector('.gb-up-retry').addEventListener('click', function () { upload(tile); });
            remove.addEventListener('click', function () { discard(tile); });

            upload(tile);
        }

        function setState(tile, state) { tile.setAttribute('data-state', state); }

        function upload(tile) {
            setState(tile, 'uploading');
            var bar = tile.querySelector('.gb-up-bar');
            bar.style.width = '0%';

            var form = new FormData();
            form.append('file', tile._file);

            var xhr = new XMLHttpRequest();
            tile._xhr = xhr;
            xhr.open('POST', cfg.uploadUrl, true);
            xhr.setRequestHeader('X-CSRF-TOKEN', cfg.csrf);
            xhr.setRequestHeader('Accept', 'application/json');

            xhr.upload.onprogress = function (e) {
                if (e.lengthComputable) { bar.style.width = Math.round((e.loaded / e.total) * 100) + '%'; }
            };
            xhr.onload = function () {
                tile._xhr = null;
                if (xhr.status >= 200 && xhr.status < 300) {
                    var res = {};
                    try { res = JSON.parse(xhr.responseText); } catch (e) { /* noop */ }
                    if (res.token) { attachToken(tile, res.token); setState(tile, 'done'); return; }
                }
                setState(tile, 'error');
                toast('error', 'Upload failed for ' + tile._file.name + '.');
            };
            xhr.onerror = function () { tile._xhr = null; setState(tile, 'error'); };
            xhr.send(form);
        }

        function attachToken(tile, token) {
            var hidden = tile._input || document.createElement('input');
            hidden.type = 'hidden';
            hidden.name = 'uploaded_tokens[]';
            hidden.value = token;
            tile._input = hidden;
            tile._token = token;
            tokens.appendChild(hidden);
        }

        function cancel(tile) {
            if (tile._xhr) { tile._xhr.abort(); tile._xhr = null; }
            removeTile(tile);
        }

        function discard(tile) {
            // A completed upload leaves a staged file — ask the server to drop it.
            if (tile._token) {
                var xhr = new XMLHttpRequest();
                xhr.open('DELETE', cfg.deleteUrl, true);
                xhr.setRequestHeader('X-CSRF-TOKEN', cfg.csrf);
                xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
                xhr.send('token=' + encodeURIComponent(tile._token));
            }
            removeTile(tile);
        }

        function removeTile(tile) {
            if (tile._input && tile._input.parentNode) { tile._input.parentNode.removeChild(tile._input); }
            if (tile.parentNode) { tile.parentNode.removeChild(tile); }
            active = Math.max(0, active - 1);
            if (!grid.children.length) { grid.classList.add('d-none'); }
        }

        // ── Wire the dropzone ──────────────────────────────────────────────
        input.addEventListener('change', function () {
            addFiles(input.files);
            input.value = ''; // don't also submit these via the images[] fallback
        });
        ['dragover', 'dragenter'].forEach(function (evt) {
            dz.addEventListener(evt, function (e) { e.preventDefault(); dz.classList.add('is-dragging'); });
        });
        ['dragleave', 'drop'].forEach(function (evt) {
            dz.addEventListener(evt, function (e) { e.preventDefault(); dz.classList.remove('is-dragging'); });
        });
        dz.addEventListener('drop', function (e) {
            if (e.dataTransfer && e.dataTransfer.files.length) { addFiles(e.dataTransfer.files); }
        });

        // Keep the existing-image removal toggle working.
        document.querySelectorAll('.admin-gallery-remove input[name="remove_media[]"]').forEach(function (cb) {
            var label = cb.closest('.admin-gallery-remove');
            if (!label) { return; }
            label.addEventListener('click', function () {
                setTimeout(function () {
                    var item = label.closest('.admin-gallery-item');
                    if (item) {
                        item.classList.toggle('is-removed', cb.checked);
                        existing += cb.checked ? -1 : 1;
                    }
                }, 0);
            });
        });
    }

    window.GbProductUploader = { init: init };
})();
