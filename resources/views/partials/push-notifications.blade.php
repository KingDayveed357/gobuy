@php
    $pushPublicKey = config('webpush.vapid.public_key');
    // The including layout must pass explicit subscription endpoints (admin panel).
    $pushStoreUrl = $pushStoreUrl ?? null;
    $pushDeleteUrl = $pushDeleteUrl ?? null;
@endphp

{{-- Only emit the push client when VAPID is configured and endpoints were provided. --}}
@if ($pushPublicKey && $pushStoreUrl && $pushDeleteUrl)
    <script>
        window.GoBuyPush = {
            publicKey: @json($pushPublicKey),
            storeUrl: @json($pushStoreUrl),
            deleteUrl: @json($pushDeleteUrl),
            csrf: '{{ csrf_token() }}',

            supported: function () {
                return 'serviceWorker' in navigator && 'PushManager' in window && 'Notification' in window;
            },

            _urlB64ToUint8Array: function (base64String) {
                var padding = '='.repeat((4 - (base64String.length % 4)) % 4);
                var base64 = (base64String + padding).replace(/-/g, '+').replace(/_/g, '/');
                var raw = window.atob(base64);
                var output = new Uint8Array(raw.length);
                for (var i = 0; i < raw.length; ++i) { output[i] = raw.charCodeAt(i); }
                return output;
            },

            register: function () {
                if (!this.supported()) { return Promise.reject('unsupported'); }
                return navigator.serviceWorker.register('/sw.js');
            },

            // Reflect current permission/subscription state onto any [data-push-toggle] controls.
            refresh: function () {
                var self = this;
                if (!this.supported()) { self._paint('unsupported'); return; }
                if (Notification.permission === 'denied') { self._paint('denied'); return; }
                navigator.serviceWorker.ready.then(function (reg) {
                    return reg.pushManager.getSubscription();
                }).then(function (sub) {
                    self._paint(sub ? 'on' : 'off');
                }).catch(function () { self._paint('off'); });
            },

            _paint: function (state) {
                document.querySelectorAll('[data-push-toggle]').forEach(function (el) {
                    el.dataset.pushState = state;
                    var on = state === 'on';
                    if (el.querySelector('[data-push-label]')) {
                        el.querySelector('[data-push-label]').textContent =
                            state === 'unsupported' ? 'Push unavailable'
                            : state === 'denied' ? 'Notifications blocked'
                            : on ? 'Alerts on' : 'Enable alerts';
                    }
                    el.disabled = (state === 'unsupported' || state === 'denied');
                });
            },

            toggle: function () {
                var self = this;
                return navigator.serviceWorker.ready.then(function (reg) {
                    return reg.pushManager.getSubscription().then(function (sub) {
                        return sub ? self._unsubscribe(sub) : self._subscribe(reg);
                    });
                });
            },

            _subscribe: function (reg) {
                var self = this;
                return Notification.requestPermission().then(function (perm) {
                    if (perm !== 'granted') { self._paint(perm === 'denied' ? 'denied' : 'off'); return; }
                    return reg.pushManager.subscribe({
                        userVisibleOnly: true,
                        applicationServerKey: self._urlB64ToUint8Array(self.publicKey),
                    }).then(function (sub) {
                        return self._send(self.storeUrl, 'POST', sub.toJSON()).then(function () {
                            self._paint('on');
                            if (window.Toast && Toast.success) { Toast.success('Push alerts enabled.'); }
                        });
                    });
                });
            },

            _unsubscribe: function (sub) {
                var self = this;
                var endpoint = sub.endpoint;
                return sub.unsubscribe().then(function () {
                    return self._send(self.deleteUrl, 'DELETE', { endpoint: endpoint });
                }).then(function () {
                    self._paint('off');
                    if (window.Toast && Toast.success) { Toast.success('Push alerts disabled.'); }
                });
            },

            _send: function (url, method, body) {
                return fetch(url, {
                    method: method,
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': this.csrf, 'Accept': 'application/json' },
                    credentials: 'same-origin',
                    body: JSON.stringify(body),
                });
            },
        };

        document.addEventListener('DOMContentLoaded', function () {
            if (!window.GoBuyPush.supported()) { window.GoBuyPush._paint('unsupported'); return; }
            window.GoBuyPush.register().then(function () {
                window.GoBuyPush.refresh();
            }).catch(function () {});

            document.querySelectorAll('[data-push-toggle]').forEach(function (el) {
                el.addEventListener('click', function (e) {
                    e.preventDefault();
                    window.GoBuyPush.toggle();
                });
            });
        });
    </script>
@endif
