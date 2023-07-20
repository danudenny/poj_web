<template>
    <div class="container-fluid">
        <Breadcrumbs main="Employee Management"/>

        <div class="container-fluid">
            <div class="email-wrap bookmark-wrap">
                <div class="row">
                    <div class="col-md-12">
                        <div class="card card-absolute">
                            <div class="card-header bg-primary">
                                <h5>Employee List</h5>
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
                                <div ref="employeesTable"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import axios from "axios";
import {TabulatorFull as Tabulator} from 'tabulator-tables';
import {useToast} from "vue-toastification";

export default {
    data() {
        return {
            employees: [],
            loading: false,
            currentPage: 1,
            pageSize: 10,
            syncLoading: false,
            table: null,
            countdown: 0,
            timerId: null,
            kanwil: [],
            cabang: [],
            outlet: [],
            area: []
        }
    },
    async mounted() {
        this.initializeEmployeesTable();
    },
    methods: {
        startCountdown() {
            this.countdown = 1;
            this.timerId = setInterval(() => {
                this.countdown++;
            }, 1000);
        },
        async getEmployees() {
            this.loading = true;
            const ls = JSON.parse(localStorage.getItem('USER_STORAGE_KEY'));
            await axios
                .get(`/api/v1/admin/employee`)
                .then(response => {
                    this.employees = response.data.data;
                })
                .catch(error => {
                    console.error(error);
                });
        },
        initializeEmployeesTable() {
            const ls = localStorage.getItem('my_app_token')
            this.table = new Tabulator(this.$refs.employeesTable, {
                paginationCounter:"rows",
                ajaxURL: '/api/v1/admin/employee',
                ajaxConfig: {
                    headers: {
                        Authorization: `Bearer ${ls}`,
                    },
                },
                ajaxParams: {
                    page: this.currentPage,
                    size: this.pageSize,
                },
                ajaxResponse: function (url, params, response) {
                    return {
                        data: response.data.data,
                        last_page: response.data.last_page,
                    }
                },
                layout: 'fitDataStretch',
                columns: [
                    {
                        title: 'No',
                        field: '',
                        formatter: 'rownum',
                    },
                    {
                        title: 'Name',
                        field: 'name',
                        headerFilter:"input"
                    },
                    {
                        title: 'Jobs',
                        field: 'job.name',
                        headerFilter:"input"
                    },
                    {
                        title: 'Email',
                        field: 'work_email',
                        headerFilter:"input"
                    },
                    {
                        title: '',
                        formatter: this.viewDetailsFormatter,
                        width: 100,
                        hozAlign: 'center',
                        sortable: false,
                        cellClick: (e, cell) => {
                            this.viewData(cell.getRow().getData().id);
                        }
                    },
                ],
                pagination: true,
                paginationMode: 'remote',
                paginationSize: this.pageSize,
                paginationSizeSelector: [10, 20, 50, 100],
                headerFilter: true,
                paginationInitialPage:1,
                rowFormatter: (row) => {
                    //
                },
                placeholder: 'No Data Available',
            });
            this.loading = false
        },
        viewDetailsFormatter(cell, formatterParams, onRendered) {
            return `<button class="button-icon button-success" data-id="${cell.getRow().getData().id}"><i class="fa fa-eye"></i> </button>`;
        },
        viewData(id) {
            this.$router.push({name: 'employee_detail', params: {id}});
        },
        async syncFromERP() {
            this.syncLoading = true;
            this.loading = true
            this.startCountdown();
            this.table.destroy()

            await axios.create({
                baseURL: import.meta.env.VITE_SYNC_ODOO_URL,
            }).get('/sync-employee')
                .then(async (response) => {
                    if (await response.data.status === 201) {
                        this.syncLoading = false;
                        this.loading = false;
                        await this.getEmployees()
                        this.initializeEmployeesTable();
                        useToast().success(response.data.message);
                    } else {
                        this.syncLoading = false;
                        this.loading = false;
                        await this.getEmployees()
                        this.initializeEmployeesTable();
                        useToast().error(response.data.message);
                    }
                }).catch(async () => {
                    this.syncLoading = false;
                    this.loading = false;
                    await this.getEmployees()
                    this.initializeEmployeesTable();
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
</style>
