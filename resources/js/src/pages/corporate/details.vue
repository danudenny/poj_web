<template>
    <div class="container-fluid">
        <Breadcrumbs :title="$route.name"/>
        <div class="col-sm-12">
            <form class="card">
                <div class="card-body">
                    <div>
                        <button type="button" v-if="editing" class="btn btn-warning" @click="editData">
                            <i class="fa fa-pencil-square"></i> Edit Data
                        </button>
                        <div v-else class="d-flex justify-content-end mb-4 column-gap-2">
                            <button type="button" class="btn btn-success" @click="saveData">
                                <i class="fa fa-save"></i> Save Data
                            </button>
                            <button type="button" class="btn btn-danger" @click="closeEdit">
                                <i class="fa fa-times-circle"></i> Cancel Edit
                            </button>
                        </div>
                    </div>
                    <hr>
                    <div class="row">
                        <div class="col-md-12">
                            <div class="mb-3">
                                <label class="form-label">Name</label>
                                <input class="form-control" type="text" v-model="item.name" disabled>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Latitude</label>
                                <input class="form-control" type="text" v-model="item.lat" :disabled="editing">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Early Tolerance (minutes)</label>
                                <input class="form-control" type="text" v-model="item.early_buffer" :disabled="editing">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Radius Buffer (meters)</label>
                                <input class="form-control" type="text" v-model="item.radius" :disabled="editing">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Longitude</label>
                                <input class="form-control" type="text" v-model="item.long" :disabled="editing">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Late Tolerance (minutes)</label>
                                <input class="form-control" type="text" v-model="item.late_buffer" :disabled="editing">
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <table class="table table-striped table-bordered">
                                <thead>
                                <tr>
                                    <th>
                                        Unit Kantor Wilayah
                                        <input class="form-control mt-2" type="text" v-model="search" @input="handleInputChange" placeholder="Search">
                                    </th>
                                </tr>
                                </thead>
                                <tbody>
                                <tr v-for="(it, index) in item.child" :key="it" v-if="item.child && item.child.length > 0">
                                    <td>{{ it.name }}</td>
                                </tr>
                                <tr v-else>
                                    <td><span>No Data Available</span></td>
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
import {useToast} from 'vue-toastification';

export default {
    data() {
        return {
            item: [],
            search: null,
            editing: true
        }
    },
    mounted() {
        this.getUnit();
    },
    methods: {
        closeEdit() {
            this.editing = true;
            this.getUnit();
        },
        editData() {
            this.editing = false;
        },
        saveData() {
            axios
                .put(`/api/v1/admin/unit/update/${this.$route.params.id}`, {
                    lat: this.item.lat,
                    long: this.item.long,
                    early_buffer: this.item.early_buffer,
                    late_buffer: this.item.late_buffer,
                    radius: this.item.radius
                })
                .then(response => {
                    this.editing = true;
                    this.getUnit();
                    useToast().success('Data successfully updated');
                })
                .catch(error => {
                    console.error(error);
                    useToast().success('Data failed to update');
                });
        },
        getUnit() {
            axios
                .get(`/api/v1/admin/unit/view/${this.$route.params.id}?unit_level=3`)
                .then(response => {
                    this.item = response.data.data[0];
                })
                .catch(error => {
                    console.error(error);
                });
        },
        handleInputChange() {
            if (this.search) {
                this.item.child = this.item.child.filter((item) => {
                    return item.name.toLowerCase().includes(this.search.toLowerCase());
                });
            } else {
                this.getUnit();
            }
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
