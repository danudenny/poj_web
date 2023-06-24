<template>
    <div class="container-fluid">
        <Breadcrumbs :main="$route.name"/>
        <div class="col-sm-12">
            <form class="card">
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-8">

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
                    <div class="row">
                        <div class="col-md-12">
                            <table class="table table-striped table-bordered">
                                <thead>
                                <tr>
                                    <th>Level</th>
                                    <th>Unit</th>
                                </tr>
                                </thead>
                                <tbody>
                                <tr v-for="(it, index) in item.child" :key="it">
                                    <td>{{ it.name }}</td>
                                    <td>{{ item.level.desc }}</td>
                                </tr>
                                </tbody>
                            </table>
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

<style scoped>
.table thead th {
    text-align: center;
    font-weight: bold;
    background-color: #0A5640;
    color: #fff;
}

.table tbody td:last-child {
    text-align: center;
}
.table tbody td:first-child {
    text-align: center;
}

.badge {
    font-size: 12px;
    padding: 5px 10px;
    border-radius: 20px;
    font-weight: 500;
    text-transform: uppercase;
    letter-spacing: 1px;
    margin: 0 5px;
}

.badge-success {
    background-color: #28a745;
    color: #fff
}

.badge-danger {
    background-color: #dc3545;
    color: #fff
}

.button-icon {
    width: 28px;
    height: 28px;
    border-radius: 20%;
    border: none;
    margin: 2px;
}

.button-success {
    background-color: #28a745;
    color: #fff
}

.button-success:hover {
    background-color: #218838;
    color: #fff
}

.button-danger {
    background-color: #dc3545;
    color: #fff
}

.button-danger:hover {
    background-color: #c82333;
    color: #fff
}

.button-info {
    background-color: #17a2b8;
    color: #fff
}

.button-info:hover {
    background-color: #138496;
    color: #fff
}
</style>
