import firebase from 'firebase/app';
import 'firebase/firebase-messaging';

const firebaseConfig = {
    apiKey: 'AIzaSyBtRjaGFT0fEzFYT8rj4G1MyP1l-IrJNXo',
    authDomain: "fcm-poj.firebaseapp.com",
    projectId: "fcm-poj",
    appId: "1:447593872327:web:c926c0d9b79614aa9737e2"
};

firebase.initializeApp(firebaseConfig);

export const messaging = firebase.messaging();

