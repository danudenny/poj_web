<template>
    <div class="container-fluid">
        <Breadcrumbs :main="$route.name"/>
        <div class="col-sm-12">
            <form class="card" @submit.prevent="addRole">
                <div class="card-body">
                    <div class="row">
                        <div class="col-sm-6 col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Role Name</label>
                                <input class="form-control" type="text" placeholder="Name" v-model="role.name">
                            </div>
                        </div>
                    </div>
                    <dv class="row">
                        <div class="col-sm-6">
                            <div class="card-block row">
                                <div class="col-sm-12 col-lg-12 col-xl-12">
                                    <div class="table-responsive">
                                        <table class="table">
                                            <thead class="table bg-primary">
                                            <tr>
                                                <th scope="col">
                                                    <input class="form-check-input checkbox-solid-light" v-model="selectAll" @click="toggleSelectAll()" type="checkbox">
                                                </th>
                                                <th scope="col">Permission</th>
                                            </tr>
                                            </thead>
                                            <tbody>
                                            <tr v-for="permission in permissions" :key="permission.id">
                                                <td><input type="checkbox" class="form-check-input checkbox-solid-light" v-bind:value="permission"
                                                           v-model="role.permissions" @change="updateCheckall()" /></td>
                                                <td>{{ permission.name }}</td>
                                            </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </dv>
                </div>

                <div class="card-footer text-start">
                    <button class="btn btn-primary m-r-10" type="submit">Save</button>
                    <button class="btn btn-secondary" @click="$router.push('/management/roles')">Cancel</button>
                </div>
            </form>
        </div>
    </div>
</template>

<script>
import axios from "axios";

export default {
    data() {
        return {
            role: {
                id: null,
                name: null,
                permissions: []
            },
            permissions: [],
            selectAll: false,
        }
    },
    mounted() {
        this.getPermissions();
    },
    methods: {
        toggleSelectAll: function () {
            this.selectAll = !this.selectAll;
            this.role.permissions = [];
            if (this.selectAll) {
                for (var key in this.permissions) {
                    this.role.permissions.push(this.permissions[key]);
                }
            }
        },
        updateCheckall: function(){
            if(this.role.permissions.length == this.permissions.length) {
                this.selectAll = true;
            } else {
                this.selectAll = false;
            }
        },
        async getPermissions() {
            await axios.get(`/api/v1/admin/role/permissions`)
                .then(res => {
                    this.permissions = res.data.data;
                })
                .catch(e => {
                    console.log(e);
                });
        },
        async addRole() {
            let id = this.role.id;
            let name = this.role.name;
            let permission = this.role.permissions.map(value => value.id);

            await axios.post(`/api/v1/admin/role/save`, {
                id: id,
                name: name,
                permission: permission
            })
                .then(res => {
                    this.$router.push('/management/roles');
                    console.log(res);
                })
                .catch(e => {
                    console.log(e);
                });
        },
        async isChecked(permission) {
            let check = await this.role.permissions.map(val => val.id === permission.id) !== false;
            return check;
        },
    },
};
</script>
