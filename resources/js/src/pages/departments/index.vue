<template>
    <div class="container-fluid">
        <Breadcrumbs main="Department"/>

        <div class="container-fluid">
            <div class="email-wrap bookmark-wrap">
                <div class="row">
                    <div class="col-md-12">
                        <div class="card card-absolute">
                            <div class="card-header bg-primary">
                                <h5>Department List</h5>
                            </div>
                            <div class="card-body">
                                <div class="d-flex justify-content-end mb-2">
                                    <button class="btn btn-warning" type="button" data-bs-toggle="modal"
                                            data-bs-target="#exampleModalCenter">
                                        <i class="fa fa-recycle" /> &nbsp; Sync From ERP
                                    </button>
                                </div>
                                <div v-if="loading" class="text-center">
                                    <img src="../../assets/loader.gif" alt="loading" width="100">
                                </div>
                                <div ref="departmentTable"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import {TabulatorFull as Tabulator} from 'tabulator-tables';

export default {
    data() {
        return {
            departments: [],
            loading: false,
        }
    },
    async mounted() {
        await this.getDepartments();
        this.initializeDepartmentTable();
    },
    methods: {
        async getDepartments() {
            this.loading = true;
            const unitId = JSON.parse(localStorage.getItem('USER_STORAGE_KEY'));
            await this.$axios.get(`/api/v1/admin/department?company_id=${parseInt(unitId.unit_id)}`)
                .then(response => {
                    this.departments = response.data.data.data;
                    console.log(this.departments)
                })
                .catch(error => {
                    console.error(error);
                });
        },
        initializeDepartmentTable() {
            const table = new Tabulator(this.$refs.departmentTable, {
                data: this.departments,
                layout: 'fitColumns',
                columns: [
                    {
                        title: 'No',
                        field: '',
                        formatter: 'rownum',
                        width: 100
                    },
                    {
                        title: 'Name',
                        field: 'name',
                        headerFilter:"input"
                    },
                    {
                        title: 'Company Name',
                        field: 'unit.name',
                        headerFilter:"input"
                    },
                ],
                pagination: 'local',
                paginationSize: 10,
                paginationSizeSelector: [10, 20, 50, 100],
                headerFilter: true,
                paginationInitialPage:1,
                rowFormatter: (row) => {
                    //
                }
            });
            this.loading = false
        },
    }
}
</script>

<style>
.tabulator .tabulator-header .tabulator-col {
    background-color: #0A5640 !important;
    color: #fff
}
</style>
