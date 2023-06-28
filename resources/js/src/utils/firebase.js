import { initializeApp } from 'firebase/app';
import { getMessaging } from 'firebase/messaging';

const firebaseConfig = {
    apiKey: "AIzaSyBtRjaGFT0fEzFYT8rj4G1MyP1l-IrJNXo",
    authDomain: "fcm-poj.firebaseapp.com",
    projectId: "fcm-poj",
    storageBucket: "fcm-poj.appspot.com",
    messagingSenderId: "447593872327",
    appId: "1:447593872327:web:c926c0d9b79614aa9737e2"
};

const firebaseApp = initializeApp(firebaseConfig);
const messaging = getMessaging(firebaseApp);

export { messaging };
