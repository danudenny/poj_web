<template>
    <router-view/>
</template>

<script>
import {getMessaging, getToken, onMessage} from "firebase/messaging";
export default {
    name: 'App',
    mounted() {
        console.log('Firebase cloud messaging object', this.$messaging)
        const messaging = getMessaging();
        getToken(messaging, {vapidKey: import.meta.env.VITE_VAPID_KEY}).then((currentToken) => {
            if (currentToken) {
                const currentUser = JSON.parse(localStorage.getItem('USER_STORAGE_KEY'));
                this.$axios.put(`/api/v1/admin/user/update-token/${currentUser.id}`, {fcm_token: currentToken})
            } else {
                console.log('No registration token available. Request permission to generate one.');
            }
        }).catch((err) => {
            console.log('An error occurred while retrieving token. ', err);
        });
    }
}
</script>
