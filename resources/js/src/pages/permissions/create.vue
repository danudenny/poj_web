<template>
    <div class="container-fluid">
        <Breadcrumbs :main="$route.name"/>
        <div class="col-sm-12">
            <form class="card" @submit.prevent="addPermission">
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
                    <button class="btn btn-primary m-r-10" type="submit">Save</button>
                    <button class="btn btn-secondary" @click="$router.push('/management/permissions')">Cancel</button>
                </div>
            </form>
        </div>
    </div>
</template>


<script>
import axios from "axios";
import {useToast} from "vue-toastification";

export default {
    data() {
        return {
            permission: {
                name: null,
            },
        }
    },
    mounted() {

    },
    methods: {
        async addPermission() {
            let name = this.permission.name;

            await this.$axios.post(`/api/v1/admin/permission/save`, {
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
