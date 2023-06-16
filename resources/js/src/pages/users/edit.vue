<template>
    <div class="container-fluid">
        <Breadcrumbs :main="$route.name"/>
        <div class="col-sm-12">
            <form class="card" @submit.prevent="updateUser">

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
                    <button class="btn btn-primary m-r-10" type="submit">Update</button>
                    <button class="btn btn-secondary" @click="$router.push('/management/users')">Cancel</button>
                </div>
            </form>
        </div>
    </div>
</template>

<script>
import {useRoute} from "vue-router";
import axios from "axios";
import {useToast} from "vue-toastification";

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
        this.getUser();
        this.getRoles();
    },
    methods: {
        async getUser() {
            const route = useRoute();
            await axios
                .get(`/api/v1/admin/user/view?id=`+ route.params.id)
                .then(response => {
                    this.user.id = response.data.data.id;
                    this.user.name = response.data.data.name;
                    this.user.username = response.data.data.username;
                    this.user.employee_id = response.data.data.employee_id;
                    this.user.email = response.data.data.email;
                    this.user.roles = response.data.data.roles;
                })
                .catch(error => {
                    console.error(error);
                });
        },
        async getRoles() {
            await axios.get(`/api/v1/admin/user/roles`)
                .then(res => {
                    this.roles = res.data.data;
                })
                .catch(e => {
                    console.log(e);
                });
        },
        async updateUser() {
            let id = this.user.id;
            let name = this.user.name;
            let username = this.user.username;
            let email = this.user.email;
            let password = this.user.password;
            let roles = this.user.roles.map(value => value.id);
            let employee_id = this.user.employee_id;

            await axios.post(`/api/v1/admin/user/update`, {
                id: id,
                name: name,
                username: username,
                email: email,
                password: password,
                roles: roles,
                employee_id: employee_id
            })
                .then(res => {
                    useToast().success(res.data.message , { position: 'bottom-right' });
                    this.$router.push('/management/users');
                    console.log(res);
                })
                .catch(e => {
                    useToast().error(e.response.data.message , { position: 'bottom-right' });
                    console.log(e);
                });
        },

    },
};
</script>
