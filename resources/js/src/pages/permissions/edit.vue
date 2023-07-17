<template>
    <div class="container-fluid">
        <Breadcrumbs :main="$route.name"/>
        <div class="col-sm-12">
            <form class="card" @submit.prevent="updatePermission">
                <div class="card-body">
                    <div class="row">
                        <div class="col-sm-6 col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Permission Name</label>
                                <input class="form-control" type="text" placeholder="Name" v-model="permission.name">
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card-footer text-start">
                    <button class="btn btn-primary m-r-10" type="submit">Update</button>
                    <button class="btn btn-secondary" @click="$router.push('/management/permissions')">Cancel</button>
                </div>
            </form>
        </div>
    </div>
</template>


<script>
import axios from "axios";
import {useRoute} from "vue-router";
import {useToast} from "vue-toastification";

export default {
    data() {
        return {
            permission: {
                id: null,
                name: null,
            },
        }
    },
    mounted() {
        this.getPermission()
    },
    methods: {
        async getPermission() {
            const route = useRoute();
            await axios
                .get(`/api/v1/admin/permission/view?id=`+ route.params.id)
                .then(response => {
                    this.permission.id = response.data.data.id;
                    this.permission.name = response.data.data.name;
                })
                .catch(error => {
                    console.error(error);
                });
        },
        async updatePermission() {
            let id = this.permission.id;
            let name = this.permission.name;

            await this.$axios.post(`/api/v1/admin/permission/update`, {
                id: id,
                name: name,
            })
                .then(res => {
                    useToast().success(res.data.message , { position: 'bottom-right' });
                    this.$router.push('/management/permissions');
                })
                .catch(e => {
                    useToast().error(e.response.data.message , { position: 'bottom-right' });
                });
        },
    },
};
</script>
