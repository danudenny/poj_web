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
            area: [],
            filterName: '',
            filterEmail: '',
            filterCorporate: '',
            filterKanwil: '',
            filterArea: '',
            filterCabang: '',
            filterOutlet: '',
            filterJob: '',
        }
    },
    async mounted() {
        await this.getKanwil()
        await this.getArea()
        await this.getCabang()
        await this.getOutlet()
        this.initializeEmployeesTable();
    },
    computed() {
        this.initializeEmployeesTable()
    },
    methods: {
        startCountdown() {
            this.countdown = 1;
            this.timerId = setInterval(() => {
                this.countdown++;
            }, 1000);
        },
        async getKanwil() {
            this.loading = true;
            await axios
                .get(`/api/v1/admin/unit?unit_level=4`)
                .then(response => {
                    this.kanwil = response.data.data;
                })
                .catch(error => {
                    console.error(error);
                });
        },
        async getArea() {
            await axios
                .get(`/api/v1/admin/unit?unit_level=5`)
                .then(response => {
                    this.area = response.data.data;
                })
                .catch(error => {
                    console.error(error);
                });
        },
        async getCabang() {
            await axios
                .get(`/api/v1/admin/unit?unit_level=6`)
                .then(response => {
                    this.cabang = response.data.data;
                })
                .catch(error => {
                    console.error(error);
                });
        },
        async getOutlet() {
            await axios
                .get(`/api/v1/admin/unit?unit_level=7`)
                .then(response => {
                    this.outlet = response.data.data;
                })
                .catch(error => {
                    console.error(error);
                });
        },
        async getEmployees() {
            this.loading = true;
            const ls = JSON.parse(localStorage.getItem('USER_STORAGE_KEY'));
            await axios
                .get(`/api/v1/admin/unit?unit_level=6`)
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
                        "X-Unit-Relation-ID": this.$store.state.activeAdminUnit?.unit_relation_id ?? ''
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
                ajaxURLGenerator: (url, config, params) => {
                    params.filter.map((item) => {
                        if (item.field === 'name') this.filterName = item.value
                        if (item.field === 'work_email') this.filterEmail = item.value
                        if (item.field === 'job.name') this.filterJob = item.value
                        if (item.field === 'corporate.name') this.filterCorporate = item.value
                        if (item.field === 'kanwil.name') this.filterKanwil = item.value
                        if (item.field === 'area.name') this.filterArea = item.value
                        if (item.field === 'cabang.name') this.filterCabang = item.value
                        if (item.field === 'outlet.name') this.filterOutlet = item.value
                    })
                    return `${url}?page=${params.page}&size=${params.size}&name=${this.filterName}&email=${this.filterEmail}&kanwil=${this.filterKanwil}&area=${this.filterArea}&cabang=${this.filterCabang}&outlet=${this.filterOutlet}&job=${this.filterJob}&corporate=${this.filterCorporate}`
                },
                layout: 'fitData',
                renderHorizontal:"virtual",
                height: '100%',
                frozenColumn:2,
                columns: [
                    {
                        title: 'No',
                        field: '',
                        formatter: 'rownum',
                        hozAlign: 'center',
                        headerHozAlign: 'center',
                        frozen: true,
                    },
                    {
                        title: 'Name',
                        field: 'name',
                        headerFilter:"input",
                        width: 200,
                        frozen: true,
                        headerHozAlign: 'center',
                    },
                    {
                        title: 'Jobs',
                        field: 'job.name',
                        headerFilter: "input",
                        clearable:true,
                        hozAlign: 'center',
                        headerHozAlign: 'center',
                    },
                    {
                        title: 'Email',
                        field: 'work_email',
                        headerFilter:"input",
                        headerHozAlign: 'center',
                    },
                    {
                        title: 'Employee Type',
                        field: 'employee_category',
                        headerFilter:"input",
                        headerHozAlign: 'center',
                        hozAlign: 'center',
                        formatter: function (cell, formatterParams, onRendered) {
                            const arr =  cell.getValue().split("_");

                            for (let i = 0; i < arr.length; i++) {
                                arr[i] = arr[i].charAt(0).toUpperCase() + arr[i].slice(1);
                            }
                            return cell.getValue() ? arr.join(" ") : '<i class="fa fa-times text-danger"></i>'
                        },
                    },
                    {
                        title: 'Corporate',
                        field: 'corporate.name',
                        headerFilter: "input",
                        hozAlign: 'center',
                        headerHozAlign: 'center',
                        headerFilterPlaceholder:"Select Corporate",
                        formatter: function (cell, formatterParams, onRendered) {
                            return cell.getValue() ? cell.getValue() : '<i class="fa fa-times text-danger"></i>'
                        },
                    },
                    {
                        title: 'Kantor Wilayah',
                        field: 'kanwil.name',
                        headerFilter: "list",
                        hozAlign: 'center',
                        headerHozAlign: 'center',
                        headerFilterPlaceholder:"Select Kanwil",
                        formatter: function (cell, formatterParams, onRendered) {
                            return cell.getValue() ? cell.getValue() : '<i class="fa fa-times text-danger"></i>'
                        },
                        headerFilterParams: {
                            values: this.kanwil.map((item) => {
                                return item.name
                            }),
                            clearable:true,
                            freetext:true
                        },
                    },
                    {
                        title: 'Area',
                        field: 'area.name',
                        headerFilter: "list",
                        hozAlign: 'center',
                        headerHozAlign: 'center',
                        headerFilterPlaceholder:"Select Area",
                        formatter: function (cell, formatterParams, onRendered) {
                            return cell.getValue() ? cell.getValue() : '<i class="fa fa-times text-danger"></i>'
                        },
                        headerFilterParams: {
                            values: this.area.map((item) => {
                                return item.name
                            }),
                            clearable:true,
                            freetext:true
                        },
                    },
                    {
                        title: 'Cabang',
                        field: 'cabang.name',
                        headerFilter: "list",
                        hozAlign: 'center',
                        headerHozAlign: 'center',
                        headerFilterPlaceholder:"Select Cabang",
                        formatter: function (cell, formatterParams, onRendered) {
                            return cell.getValue() ? cell.getValue() : '<i class="fa fa-times text-danger"></i>'
                        },
                        headerFilterParams: {
                            values: this.cabang.map((item) => {
                                return item.name
                            }),
                            clearable:true,
                            freetext:true
                        },
                    },
                    {
                        title: 'Outlet',
                        field: 'outlet.name',
                        headerFilter: "list",
                        hozAlign: 'center',
                        headerHozAlign: 'center',
                        headerFilterPlaceholder:"Select Outlet",
                        formatter: function (cell, formatterParams, onRendered) {
                            return cell.getValue() ? cell.getValue() : '<i class="fa fa-times text-danger"></i>'
                        },
                        headerFilterParams: {
                            values: this.outlet.map((item) => {
                                return item.name
                            }),
                            clearable:true,
                            freetext:true
                        },
                    },
                    {
                        title: '',
                        formatter: this.viewDetailsFormatter,
                        width: 100,
                        hozAlign: 'center',
                        cellClick: (e, cell) => {
                            this.viewData(cell.getRow().getData().id);
                        }
                    },
                ],
                pagination: true,
                paginationMode: 'remote',
                filterMode:"remote",
                paginationSize: this.pageSize,
                paginationSizeSelector: [10, 20, 50, 100],
                headerFilter: true,
                paginationInitialPage:1,
                placeholder: 'No Data Available',
            });
            this.loading = false;
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
