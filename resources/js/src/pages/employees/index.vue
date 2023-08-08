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
                                <div class="mb-2">
                                    <button class="btn btn-success" @click="filtering">
                                        <i class="fa fa-filter"></i> Filter
                                    </button>
                                </div>
                                <div class="row" v-if="showFilter">
                                    <div class="col-md-4">
                                        <label>Employee Name</label>
                                        <input type="text" placeholder="Search Employee Name" class="form-control" v-model="filterName" @keyup="filterEmployeeName">
                                    </div>
                                    <div class="col-md-4">
                                        <label>Jobs</label>
                                        <multiselect
                                            v-model="filterJob"
                                            :options="jobs"
                                            label="name"
                                            track-by="id"
                                            placeholder="Select Jobs"
                                            :close-on-select="true"
                                            @select="filterJobName"
                                        ></multiselect>
                                    </div>
                                    <div class="col-md-4">
                                        <label>Departments</label>
                                        <multiselect
                                            v-model="filterDepartment"
                                            :options="departments"
                                            :multiple="false"
                                            label="name"
                                            track-by="id"
                                            placeholder="Select Department"
                                            @select="filterDepartmentName"
                                        ></multiselect>
                                    </div>
                                </div>
                                <div class="row mt-3" v-if="showFilter">
                                    <div class="col-md-4">
                                        <label>Employee Category</label>
                                        <multiselect
                                            v-model="filterEmployeeCategory"
                                            :options="employeeCategories"
                                            :multiple="false"
                                            label="name"
                                            track-by="value"
                                            placeholder="Select Employee Category"
                                            :clear-on-select="true"
                                            :close-on-select="true"
                                            :can-clear="true"
                                            @select="filterEmployeeCategoryName"
                                        ></multiselect>
                                    </div>
                                    <div class="col-md-4">
                                        <label>Employee Category</label>
                                        <multiselect
                                            v-model="filterEmployeeType"
                                            :options="employeeTypes"
                                            :multiple="false"
                                            label="name"
                                            track-by="value"
                                            placeholder="Select Employee Type"
                                            :clear-on-select="true"
                                            :close-on-select="true"
                                            :can-clear="true"
                                            @select="filterEmployeeTypeName"
                                        ></multiselect>
                                    </div>
                                </div>
                                <hr>
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
            pageSize: 20,
            syncLoading: false,
            table: null,
            countdown: 0,
            timerId: null,
            kanwil: [],
            cabang: [],
            outlet: [],
            area: [],
            filterName: '',
            filterDepartment: '',
            filterCorporate: '',
            filterKanwil: '',
            filterArea: '',
            filterCabang: '',
            filterOutlet: '',
            filterJob: '',
            filterEmployeeCategory: '',
            filterEmployeeType: '',
            showFilter: false,
            jobs: [],
            departments: [],
            partners: [],
            employeeCategories: [
                {name: 'Karyawan Tetap', value: 'karyawan_tetap'},
                {name: 'Karyawan Kontrak', value: 'karyawan_kontrak'},
                {name: 'Karyawan Outsourcing', value: 'karyawan_outsourcing'},
            ],
            employeeTypes: [
                {name: 'Internal', value: 'internal'},
                {name: 'Outsourcing', value: 'outsourcing'},
            ]
        }
    },
    async mounted() {
        this.initializeEmployeesTable();
        await this.getJobs();
        await this.getDepartments();
        await this.getPartner();
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
        async getDepartments() {
            await this.$axios
                .get(`/api/v1/admin/department`)
                .then(response => {
                    this.departments = response.data.data;
                })
                .catch(error => {
                    console.error(error);
                });
        },
        async getJobs() {
            await this.$axios
                .get(`/api/v1/admin/job`)
                .then(response => {
                    this.jobs = response.data.data;
                })
                .catch(error => {
                    console.error(error);
                });
        },
        async getPartner() {
            await this.$axios
                .get(`/api/v1/admin/partner`)
                .then(response => {
                    this.partners = response.data.data;
                    console.log(this.partners);
                })
                .catch(error => {
                    console.error(error);
                });
        },
        initializeEmployeesTable() {
            const ls = localStorage.getItem('my_app_token')
            const selectedROle = localStorage.getItem('USER_ROLES')
            this.table = new Tabulator(this.$refs.employeesTable, {
                paginationCounter:"rows",
                ajaxURL: '/api/v1/admin/employee',
                ajaxConfig: {
                    headers: {
                        Authorization: `Bearer ${ls}`,
                        "X-Unit-Relation-ID": this.$store.state.activeAdminUnit?.unit_relation_id ?? '',
                        "X-Selected-Role": JSON.parse(selectedROle)
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
                        if (item.field === 'department_id') this.filterDepartment = item.value.odoo_department_id
                        if (item.field === 'job_id') this.filterJob = item.value.odoo_job_id
                        if (item.field === 'employee_category') this.filterEmployeeCategory = item.value.value
                        if (item.field === 'employee_type') this.filterEmployeeType = item.value.value
                        if (item.field === 'corporate.name') this.filterCorporate = item.value
                        if (item.field === 'kanwil.name') this.filterKanwil = item.value
                        if (item.field === 'area.name') this.filterArea = item.value
                        if (item.field === 'cabang.name') this.filterCabang = item.value
                        if (item.field === 'outlet.name') this.filterOutlet = item.value
                    })
                    return `${url}?page=${params.page}&per_page=${params.size}&name=${this.filterName}&department_id=${this.filterDepartment}&employee_category=${this.filterEmployeeCategory}&employee_type=${this.filterEmployeeType}&kanwil=${this.filterKanwil}&area=${this.filterArea}&cabang=${this.filterCabang}&outlet=${this.filterOutlet}&job_id=${this.filterJob}&corporate=${this.filterCorporate
                    }`
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
                        width: 200,
                        frozen: true,
                        headerHozAlign: 'center',
                        formatter: function (cell, formatterParams, onRendered) {
                            return `<span class="text-danger-emphasis"><b>${cell.getValue()}</b></span>`;
                        },
                        cellClick: (e, cell) => {
                            this.viewData(cell.getRow().getData().id);
                        }
                    },
                    {
                        title: 'Jobs',
                        field: 'job.name',
                        clearable:true,
                        hozAlign: 'center',
                        headerHozAlign: 'center',
                    },
                    {
                        title: 'Department',
                        field: 'department.name',
                        headerHozAlign: 'center',
                        hozAlign: 'center',
                        formatter: function (cell, formatterParams, onRendered) {
                            return cell.getValue() ? cell.getValue() : '<i class="fa fa-times text-danger"></i>'
                        },
                    },
                    {
                        title: 'Current Work',
                        field: 'partner.name',
                        headerFilter:"input",
                        headerHozAlign: 'center',
                        hozAlign: 'center',
                        formatter: function (cell, formatterParams, onRendered) {
                            return cell.getValue() ? cell.getValue() : '<i class="fa fa-times text-danger"></i>'
                        },
                    },
                    {
                        title: 'Employee Category',
                        field: 'employee_category',
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
                        title: 'Employee Type',
                        field: 'employee_type',
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
                        headerFilter: "input",
                        hozAlign: 'center',
                        headerHozAlign: 'center',
                        headerFilterPlaceholder:"Select Kanwil",
                        formatter: function (cell, formatterParams, onRendered) {
                            return cell.getValue() ? cell.getValue() : '<i class="fa fa-times text-danger"></i>'
                        },
                        clearable: true,
                    },
                    {
                        title: 'Area',
                        field: 'area.name',
                        headerFilter: "input",
                        hozAlign: 'center',
                        headerHozAlign: 'center',
                        headerFilterPlaceholder:"Select Area",
                        formatter: function (cell, formatterParams, onRendered) {
                            return cell.getValue() ? cell.getValue() : '<i class="fa fa-times text-danger"></i>'
                        },
                    },
                    {
                        title: 'Cabang',
                        field: 'cabang.name',
                        headerFilter: "input",
                        hozAlign: 'center',
                        headerHozAlign: 'center',
                        headerFilterPlaceholder:"Select Cabang",
                        formatter: function (cell, formatterParams, onRendered) {
                            return cell.getValue() ? cell.getValue() : '<i class="fa fa-times text-danger"></i>'
                        },
                    },
                    {
                        title: 'Outlet',
                        field: 'outlet.name',
                        headerFilter: "input",
                        hozAlign: 'center',
                        headerHozAlign: 'center',
                        headerFilterPlaceholder:"Select Outlet",
                        formatter: function (cell, formatterParams, onRendered) {
                            return cell.getValue() ? cell.getValue() : '<i class="fa fa-times text-danger"></i>'
                        },
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
            this.table.setFilter('name', "=", this.filterName);
            this.loading = false;
        },
        filterEmployeeName() {
            if (this.filterName === "") {
                this.table.clearFilter();
                return;
            }
            this.table.setFilter('name', "=", this.filterName);
        },
        filterJobName() {
            if (this.filterJob === "") {
                this.table.clearFilter();
                return;
            }
            this.table.setFilter('job_id', "=", this.filterJob);
        },
        filterDepartmentName() {
            if (this.filterDepartment === "") {
                this.table.clearFilter();
                return;
            }
            this.table.setFilter('department_id', "=", this.filterDepartment);
        },
        filterEmployeeCategoryName() {
            if (this.filterEmployeeCategory === "") {
                this.table.clearFilter();
                return;
            }
            this.table.setFilter('employee_category', "=", this.filterEmployeeCategory);
        },
        filterEmployeeTypeName() {
            if (this.filterEmployeeType === "") {
                this.table.clearFilter();
                return;
            }
            this.table.setFilter('employee_type', "=", this.filterEmployeeType);
        },
        filtering() {
            this.showFilter = !this.showFilter;
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
