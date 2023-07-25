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
                                    <button class="btn btn-warning"  :disabled="syncLoading" type="button" @click="syncFromERP">
                                        <span v-if="syncLoading">
                                            <i  class="fa fa-spinner fa-spin"></i> Processing ... ({{ countdown }}s)
                                        </span>
                                        <span v-else>
                                            <i class="fa fa-recycle"></i> &nbsp; Sync From ERP
                                        </span>
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
import axios from "axios";
import {useToast} from "vue-toastification";

export default {
    data() {
        return {
            departments: [],
            loading: false,
            syncLoading: false,
            table: null,
            countdown: 0,
            timerId: null
        }
    },
    async mounted() {
        await this.getDepartments();
        this.initializeDepartmentTable();
    },
    methods: {
        startCountdown() {
            this.countdown = 1;
            this.timerId = setInterval(() => {
                this.countdown++;
            }, 1000);
        },
        async getDepartments() {
            this.loading = true;
            await this.$axios.get(`/api/v1/admin/department`)
                .then(response => {
                    this.departments = response.data.data;
                })
                .catch(error => {
                    console.error(error);
                });
        },
        initializeDepartmentTable() {
            this.table = new Tabulator(this.$refs.departmentTable, {
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
                        headerFilter:"input",
                        headerHozAlign: 'center',
                    },
                    {
                        title: 'Company Name',
                        field: 'unit.name',
                        headerFilter:"input",
                        hozAlign: 'center',
                        headerHozAlign: 'center',
                    },
                ],
                pagination: 'local',
                paginationSize: 10,
                paginationSizeSelector: [10, 20, 50, 100],
                headerFilter: true,
                paginationInitialPage:1,
                rowFormatter: (row) => {
                    //
                },
                placeholder:"No Data Available",
            });
            this.loading = false
        },
        async syncFromERP() {
            this.syncLoading = true;
            this.loading = true
            this.startCountdown();
            this.table.destroy()

            await axios.create({
                baseURL: import.meta.env.VITE_SYNC_ODOO_URL,
            }).get('/sync-department')
                .then(async (response) => {
                    if (await response.data.status === 201) {
                        this.syncLoading = false;
                        this.loading = false;
                        await this.getDepartments()
                        this.initializeDepartmentTable();
                        useToast().success(response.data.message);
                    } else {
                        this.syncLoading = false;
                        this.loading = false;
                        await this.getDepartments()
                        this.initializeDepartmentTable();
                        useToast().error(response.data.message);
                    }
                }).catch(async () => {
                    this.syncLoading = false;
                    this.loading = false;
                    await this.getDepartments()
                    this.initializeDepartmentTable();
                    useToast().error("Failed to Sync Data! Check connection.");
                }).finally(() => {
                    this.syncLoading = false;
                    clearInterval(this.timerId);
                });
        }
    }
}
</script>

<style>
.tabulator .tabulator-header .tabulator-col {
    background-color: #0A5640 !important;
    color: #fff
}
</style>
