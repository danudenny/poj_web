<template>
    <div class="container-fluid">
        <Breadcrumbs :main="$route.name"/>
        <div class="col-sm-12">
            <form class="card" @submit.prevent="updateRole">
                <div class="card-body">
                    <div class="row">
                        <div class="col-sm-6 col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Role Name</label>
                                <input class="form-control" type="text" placeholder="Name" v-model="this.role.name">
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
                                                    <input class="form-check-input checkbox-solid-light" v-model="selectAll" @input="toggleSelectAll()" type="checkbox">
                                                </th>
                                                <th scope="col">Permission</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr v-for="permission in permissions" :key="permission.id">
                                                <td><input type="checkbox" class="form-check-input checkbox-solid-light" v-bind:value="permission"
                                                                      v-model="role.permissions"
                                                            :checked="isChecked(permission)" @click="updateCheckall()" /></td>
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
                    <button class="btn btn-primary m-r-10" type="submit">Update</button>
                    <button class="btn btn-secondary" @click="$router.push('/management/roles')">Cancel</button>
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
        this.getRole();
        this.getPermissions();
    },
    methods: {
        toggleSelectAll: function () {
            this.selectAll = !this.selectAll;
            this.role.permissions = [];
            if (this.selectAll) {
                // this.role.permissions = this.permissions.map(item => item.id);
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
        async getRole() {
            const route = useRoute();
            await axios
                .get(`/api/v1/admin/role/view?id=`+ route.params.id)
                .then(response => {
                    this.role.id = response.data.data.id;
                    this.role.name = response.data.data.name;
                    this.role.permissions = response.data.data.permissions;
                })
                .catch(error => {
                    console.error(error);
                });
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
        async updateRole() {
            let id = this.role.id;
            let name = this.role.name;
            let permission = this.role.permissions.map(value => value.id);
            console.log(permission);

            await axios.post(`/api/v1/admin/role/update`, {
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
        watch: {
            selectedItems() {
                console.log('Selected items:', this.role.permissions);
            }
        },

    },
};
</script>
