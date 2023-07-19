<template>
    <router-view/>
</template>

<script setup>
import { initializeApp } from "firebase/app";
import { getMessaging, getToken, onMessage } from "firebase/messaging";
import {useToast} from "vue-toastification";

const firebaseConfig = {
    apiKey: import.meta.env.VITE_FCM_API_KEY,
    authDomain: import.meta.env.VITE_FCM_AUTH_DOMAIN,
    projectId: import.meta.env.VITE_FCM_PROJECT_ID,
    storageBucket: import.meta.env.VITE_FCM_STORAGE_BUCKET,
    messagingSenderId: import.meta.env.VITE_FCM_MESSAGING_SENDER_ID,
    appId: import.meta.env.VITE_FCM_APP_ID,
};

const app = initializeApp(firebaseConfig);

const messaging = getMessaging();
onMessage(messaging, (payload) => {
    if (payload.notification) {
        useToast().info(payload.notification.title, {position: "top-right",});
    } else {
        useToast().info(payload.data.title, {position: "top-right",});
    }
});

getToken(messaging, { vapidKey: import.meta.env.VITE_VAPID_KEY }).then((currentToken) => {
    if (currentToken) {
        const token = localStorage.getItem('my_app_token')
        const baseUrl = import.meta.env.VITE_API_URL
        const requestOptions = {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
                'Authorization': 'Bearer ' + token,
            },
            body: JSON.stringify({ fcm_token: currentToken })
        };
        const ls = JSON.parse(localStorage.getItem('USER_STORAGE_KEY'))
        fetch(`${baseUrl}/api/v1/admin/user/update-token/${ls.id}`, requestOptions)
            .then(response => response.json())
    } else {
        console.log('No registration token available. Request permission to generate one.');
    }
}).catch((err) => {
    console.log('An error occurred while retrieving token. ', err);
});
</script>
