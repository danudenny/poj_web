<template>
    <select class="form-control" v-model="selectedRoles" @change="switchRoles(selectedRoles)" :disabled="roles.length <= 1">
        <option v-for="item in roles" :value="item" >{{ item.toUpperCase() }}</option>
    </select>
</template>

<script>
import { useToast } from "vue-toastification";
export default {
    data() {
        return {
            notification: false,
            roles: [],
            selectedRoles: ""
        };
    },
    mounted() {
        this.getProfile();
    },
    methods: {
        async switchRoles(role) {
            const setRole = localStorage.setItem("USER_ROLES", JSON.stringify(role));
            await this.getUserProfile(setRole);
            this.getProfile();
            window.location.reload();
            useToast().success("Role Changed Successfully");
        },
        async getUserProfile(role) {
            await this.$axios
                .get("/api/v1/admin/user/profile", {
                    headers: {
                        'X-Selected-Role': role
                    }
                })
                .then(async (response) => {
                    localStorage.setItem(
                        "AVAILABLE_USER_ROLES",
                        JSON.stringify(response.data.data.availableRole)
                    );
                    localStorage.setItem(
                        "USER_ROLES",
                        JSON.stringify(response.data.data.roles)
                    );
                    localStorage.setItem(
                        "USER_PERMISSIONS",
                        JSON.stringify(response.data.data.permissions)
                    );
                })
                .catch((error) => {
                    console.log(error);
                });
        },
        getProfile() {
            this.selectedRoles = JSON.parse(
                localStorage.getItem("USER_ROLES")
            );
            this.roles = JSON.parse(
                localStorage.getItem("AVAILABLE_USER_ROLES")
            )
            return this.roles;
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
