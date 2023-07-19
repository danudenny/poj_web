self.addEventListener('push', (event) => {
    if (event.data) {
        const payload = event.data.json();
        const title = payload.notification.title;
        const options = {
            body: payload.notification.body,
        };

        event.waitUntil(self.registration.showNotification(title, options));
    }
});

self.addEventListener('notificationclick', (event) => {
    event.notification.close();
    event.waitUntil(
        clients.openWindow('/')
    );
});
