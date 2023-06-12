<template>
    <div class="container-fluid">
        <Breadcrumbs :main="$route.name"/>
        <div class="col-sm-12">
            <form class="card">
                <div class="card-body">
                    <div class="row">
                        <div class="col-sm-6 col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Permission Name</label>
                                <input class="form-control" type="text" placeholder="Name" v-model="item.name" disabled>
                            </div>
                        </div>

                    </div>
                </div>

                <div class="card-footer text-end">
                    <button class="btn btn-secondary" @click="$router.push('/management/permissions')">Back</button>
                </div>
            </form>
        </div>
    </div>
</template>

<script>
import axios from 'axios';
import { useRoute } from 'vue-router';

export default {
    data() {
        return {
            item: [],
        }
    },
    mounted() {
        this.getPermission();
    },
    methods: {
        getPermission() {
            const route = useRoute();
            axios
                .get(`/api/v1/admin/permission/view?id=`+ route.params.id)
                .then(response => {
                    this.item = response.data.data;
                })
                .catch(error => {
                    console.error(error);
                });
        },
    },
};
</script>
