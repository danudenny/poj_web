<template>
    <li class="onhover-dropdown">
        <div class="notification-box">
            <vue-feather type="copy" @click="notification_open()"></vue-feather>
        </div>
        <div class="onhover-show-div notification-dropdown">
            <h6 class="f-18 mb-0 dropdown-title bg-primary">Switch Roles</h6>
            <div>
                <p v-for="item in roles" class="roles">{{ item.toUpperCase() }}</p>
            </div>
        </div>
    </li>
</template>

<script>
export default {
    data() {
        return {
            notification: false,
            roles: []
        };
    },
    mounted() {
        this.getProfile();
    },
    methods: {
        getProfile() {
            return (this.roles = JSON.parse(
                localStorage.getItem("USER_ROLES")
            ));
        },
        notification_open() {
            this.notification = !this.notification;
        },
    },
};
</script>

<style scoped>
.roles {
    cursor: pointer;
    margin: 10px;
    padding: 10px;
}
.roles:hover {
    background-color: #e6e6e6;
    font-weight: bold;
}
</style>
