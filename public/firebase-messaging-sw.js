importScripts('https://www.gstatic.com/firebasejs/8.10.1/firebase-app.js');
importScripts('https://www.gstatic.com/firebasejs/8.10.1/firebase-messaging.js');

firebase.initializeApp({
    apiKey: "AIzaSyBtRjaGFT0fEzFYT8rj4G1MyP1l-IrJNXo",
    authDomain: "fcm-poj.firebaseapp.com",
    projectId: "fcm-poj",
    storageBucket: "fcm-poj.appspot.com",
    messagingSenderId: "447593872327",
    appId: "1:447593872327:web:c926c0d9b79614aa9737e2"
});

const messaging = firebase.messaging();

messaging.onBackgroundMessage((payload) => {
    console.log(
        '[firebase-messaging-sw.js] Received background message ',
        payload
    );
    // Customize notification here
    const notificationTitle = payload.notification.title;
    const notificationOptions = {
        body: payload.notification.body,
        icon: 'https://jobindo.com/mobile/thumbnails/27416289.jpg'
    };

    self.registration.showNotification(notificationTitle, notificationOptions);
});
