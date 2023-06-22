<template>
    <div class="container-fluid">
        <Breadcrumbs :main="$route.name"/>
        <div class="col-sm-12">
            <form class="card">
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-5">

                            <div class="mb-3">
                                <label class="form-label">Name</label>
                                <input class="form-control" type="text" v-model="item.name" disabled>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Create Date</label>
                                <input class="form-control" type="text" v-model="item.create_date" disabled>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Write Date</label>
                                <input class="form-control" type="text" v-model="item.write_date" disabled>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card-footer text-end">
                    <button class="btn btn-secondary" @click="$router.push('/corporates')">Back</button>
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
        this.getUnit();
    },
    methods: {
        getUnit() {
            const route = useRoute();
            axios
                .get(`/api/v1/admin/unit/view?id=`+ route.params.id)
                .then(response => {
                    this.item = response.data.data;
                })
                .catch(error => {
                    console.error(error);
                });
        }
    }
};
</script>
