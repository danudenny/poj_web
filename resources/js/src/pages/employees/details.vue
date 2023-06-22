<template>
    <div class="container-fluid">
        <Breadcrumbs :main="$route.name"/>
        <div class="col-sm-12">
            <form class="card">
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-5">
                            <div class="mb-3">
                                <label class="form-label">Employee ID</label>
                                <input class="form-control" type="text" v-model="item.odoo_employee_id" disabled>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Name</label>
                                <input class="form-control" type="text" v-model="item.name" disabled>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Work Email</label>
                                <input class="form-control" type="text" v-model="item.work_email" disabled>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Status</label>
                                <input class="form-control" type="text" v-model="item.status" disabled>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Gender</label>
                                <input class="form-control" type="text" v-model="item.gender" disabled>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Martial Status</label>
                                <input class="form-control" type="text" v-model="item.marital" disabled>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Certificate</label>
                                <input class="form-control" type="text" v-model="item.certificate" disabled>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">First Contract Date</label>
                                <input class="form-control" type="text" v-model="item.first_contract_date" disabled>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card-footer text-end">
                    <button class="btn btn-secondary" @click="$router.push('/management/employees')">Back</button>
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
        this.getEmployee();
    },
    methods: {
        getEmployee() {
            const route = useRoute();
            axios
                .get(`/api/v1/admin/employee/view/`+ route.params.id)
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
