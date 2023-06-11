<template>
    <div class="container-fluid">
        <Breadcrumbs :main="$route.name"/>
        <div class="col-sm-12">
            <form class="card" @submit.prevent="addUser">

                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Name</label>
                                <input class="form-control" type="text" placeholder="Name" v-model="user.name">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Username</label>
                                <input class="form-control" type="text" placeholder="Username" v-model="user.username">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Email address</label>
                                <input class="form-control" type="email" placeholder="Email" v-model="user.email">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Password</label>
                                <input class="form-control" type="password" placeholder="Password" v-model="user.password">
                            </div>
                            <div class="mb-3">
                                <div class="mb-2">
                                    <label class="col-form-label">Roles</label>
                                    <multiselect v-model="user.roles" tag-placeholder="Add this as new tag" placeholder="Select Roles"
                                                 label="name" track-by="name" :options="roles" :multiple="true" :taggable="true" @tag="addTag">
                                    </multiselect>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card-footer text-start">
                    <button class="btn btn-primary m-r-10" type="submit">Save</button>
                    <button class="btn btn-secondary" @click="$router.push('/management/users')">Cancel</button>
                </div>
            </form>
        </div>
    </div>
</template>

<script>
import {useRoute} from "vue-router";
import axios from "axios";

export default {
    data() {
        return {
            user: {
                id: null,
                name: null,
                email: null,
                username: null,
                password: null,
                employee_id: null,
                roles: []
            },
            roles: [],
        }
    },
    mounted() {
        this.getRoles();
    },
    methods: {
        async getRoles() {
            await axios.get(`/api/v1/admin/user/roles`)
                .then(res => {
                    this.roles = res.data.data;
                })
                .catch(e => {
                    console.log(e);
                });
        },
        async addUser() {
            let id = this.user.id;
            let name = this.user.name;
            let username = this.user.username;
            let email = this.user.email;
            let password = this.user.password;
            let roles = this.user.roles.map(value => value.id);
            let employee_id = this.user.employee_id;

            await axios.post(`/api/v1/admin/user/save`, {
                id: id,
                name: name,
                username: username,
                email: email,
                password: password,
                roles: roles,
                employee_id: employee_id
            })
                .then(res => {
                    this.$router.push('/management/users');
                    console.log(res);
                })
                .catch(e => {
                    console.log(e);
                });
        },

    },
};
</script>
