/*
 * GoBuy service worker — Web Push receiver.
 *
 * Payload shape matches NotificationChannels\WebPush\WebPushMessage::toArray():
 * { title, body, icon, badge, tag, data: { url } }.
 */
self.addEventListener('push', function (event) {
    var payload = {};
    try {
        payload = event.data ? event.data.json() : {};
    } catch (e) {
        payload = { body: event.data ? event.data.text() : '' };
    }

    var title = payload.title || 'GoBuy';
    var options = {
        body: payload.body || '',
        icon: payload.icon || '/theme/img/favicons/apple-touch-icon.png',
        badge: payload.badge || '/theme/img/favicons/favicon-32x32.png',
        tag: payload.tag || undefined,
        renotify: !!payload.tag,
        data: payload.data || {},
    };

    event.waitUntil(self.registration.showNotification(title, options));
});

self.addEventListener('notificationclick', function (event) {
    event.notification.close();
    var url = (event.notification.data && event.notification.data.url) || '/';

    event.waitUntil(
        self.clients.matchAll({ type: 'window', includeUncontrolled: true }).then(function (clientList) {
            for (var i = 0; i < clientList.length; i++) {
                var client = clientList[i];
                if (client.url === url && 'focus' in client) {
                    return client.focus();
                }
            }
            return self.clients.openWindow ? self.clients.openWindow(url) : null;
        })
    );
});
