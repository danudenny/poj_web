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
                                <div class="mb-2 d-flex justify-content-start column-gap-2">
                                    <button class="btn btn-primary" @click="filtering">
                                        <i class="fa fa-filter"></i> &nbsp;
                                        <span v-if="showFilter">Hide Filter</span>
                                        <span v-else>Show Filter</span>
                                    </button>
                                    <button class="btn btn-outline-danger" v-if="showFilter" @click="onResetFilter">
                                        <i class="fa fa-rotate-left" ></i>&nbsp; Reset Filter
                                    </button>
                                    <button class="btn btn-outline-success" @click="exportExcel">
                                        <i class="fa fa-file-excel-o"></i>&nbsp; Export to Excel
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
                                            @select="filterJobName"
                                            @search-change="onJobSearchName"
                                        ></multiselect>
                                    </div>
                                    <div class="col-md-4">
                                        <label>Department</label>
                                        <multiselect
                                            v-model="filterDepartment"
                                            :options="departments"
                                            :multiple="false"
                                            label="name"
                                            track-by="id"
                                            placeholder="Select Team"
                                            @select="filterDepartmentName"
                                        ></multiselect>
                                    </div>
                                </div>
                                <div class="row mt-3" v-if="showFilter">
                                    <div class="col-md-4">
                                        <label>Teams</label>
                                        <multiselect
                                            v-model="filterTeam"
                                            :options="teams"
                                            :multiple="false"
                                            label="name"
                                            track-by="id"
                                            placeholder="Select Team"
                                            @select="filterTeamName"
                                        ></multiselect>
                                    </div>
                                    <div class="col-md-4">
                                        <label>Employee Category</label>
                                        <multiselect
                                            v-model="filterEmployeeCategory"
                                            placeholder="Select Employee Category"
                                            label="name"
                                            track-by="value"
                                            :options="employeeCategories"
                                            :multiple="false"
                                            :required="true"
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
import {TabulatorFull as Tabulator} from 'tabulator-tables';

export default {
    data() {
        return {
            employees: [],
            loading: false,
            isOnSearch: true,
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
            filterDepartment: null,
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
            jobPagination: {
                name: '',
                onSearch: false
            },
            employeeCategories: [
                {name: 'Karyawan Tetap', value: 'karyawan_tetap'},
                {name: 'Karyawan Kontrak', value: 'karyawan_kontrak'},
                {name: 'Karyawan Outsourcing', value: 'karyawan_outsourcing'},
            ],
            employeeTypes: [
                {name: 'Internal', value: 'internal'},
                {name: 'Outsourcing', value: 'outsourcing'},
            ],
            teams: [],
            filterTeam: '',
        }
    },
    async mounted() {
        this.initializeEmployeesTable();
        await this.getJobs();
        await this.getDepartments();
        await this.getPartner();
        await this.getTeam();
    },
    computed() {
        this.initializeEmployeesTable()
    },
    methods: {
        exportExcel() {
            this.table.download("xlsx", "employees.xlsx", {
                sheetName: "Employees",
                columnGroups: false,
                columnCalcs: false,
            });
        },
        onResetFilter() {
            this.filterName = '';
            this.filterDepartment = '';
            this.filterCorporate = '';
            this.filterKanwil = '';
            this.filterArea = '';
            this.filterCabang = '';
            this.filterOutlet = '';
            this.filterJob = '';
            this.filterEmployeeCategory = '';
            this.filterEmployeeType = '';
            this.filterTeam = '';
            this.table.clearFilter();
        },
        filterTeamName() {
            if (this.filterTeam === "") {
                this.table.clearFilter();
                return;
            }
            this.table.setFilter('team.name', '=', this.filterTeam?.id);
        },
        async getTeam() {
            await this.$axios
                .get(`/api/v1/admin/team?per_page=50`)
                .then(response => {
                    this.teams = response.data.data.data;
                })
                .catch(error => {
                    console.error(error);
                });
        },
        async getDepartments() {
            await this.$axios
                .get(`/api/v1/admin/department/all`)
                .then(response => {
                    this.departments = response.data.data;
                })
                .catch(error => {
                    console.error(error);
                });
        },
        async getJobs() {
            await this.$axios
                .get(`/api/v1/admin/job?name=${this.jobPagination.name}`)
                .then(response => {
                    this.jobs = response.data.data.data;
                    this.jobPagination.onSearch = false
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
                ajaxURL: '/api/v1/admin/employee/paginated',
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
                ajaxResponse: (url, params, response) => {
                    this.isOnSearch = false

                    return {
                        data: response.data.data,
                        last_page: response.data.last_page,
                    }
                },
                ajaxURLGenerator: (url, config, params) => {
                    let localFilter = {
                        employee_category: this.filterEmployeeCategory?.value ?? '',
                        employee_type: this.filterEmployeeType?.value ?? '',
                        department_id: this.filterDepartment?.odoo_department_id ?? '',
                        job_id: this.filterJob?.odoo_job_id ?? '',
                        employeeName: this.filterName,
                        team_id: this.filterTeam?.id ?? '',
                        kanwilName: '',
                        areaName: '',
                        cabangName: '',
                        outletName: '',
                        customerName: ''
                    }


                    params.filter.map((item) => {
                        if (item.field === 'corporate.name') this.filterCorporate = item.value
                        if (item.field === 'kanwil.name') localFilter.kanwilName = item.value
                        if (item.field === 'area.name') localFilter.areaName = item.value
                        if (item.field === 'cabang.name') localFilter.cabangName = item.value
                        if (item.field === 'outlet.name') localFilter.outletName = item.value
                        if (item.field === 'partner.name') localFilter.customerName = item.value
                        if (item.field === 'department_id') localFilter.department_id = item.value
                        if (item.field === 'team.name') localFilter.team_id = item.value
                    })

                    return `${url}?page=${params.page}&per_page=${params.size}&customer_name=${localFilter.customerName}&name=${localFilter.employeeName}&odoo_department_id=${localFilter.department_id}&team_id=${localFilter.team_id}&employee_category=${localFilter.employee_category}&employee_type=${localFilter.employee_type}&kanwil_name=${localFilter.kanwilName}&area_name=${localFilter.areaName}&cabang_name=${localFilter.cabangName}&outlet_name=${localFilter.outletName}&odoo_job_id=${localFilter.job_id}&corporate=${this.filterCorporate
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
                        formatter: function (cell) {
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
                        formatter: function (cell) {
                            return cell.getValue() ? cell.getValue() : '<i class="fa fa-times text-danger"></i>'
                        },
                    },
                    {
                        title: 'Team',
                        field: 'team.name',
                        headerHozAlign: 'center',
                        hozAlign: 'center',
                        formatter: function (cell) {
                            return cell.getValue() ? cell.getValue() : '<i class="fa fa-times text-danger"></i>'
                        },
                    },
                    {
                        title: 'Current Work',
                        field: 'partner.name',
                        headerFilter:"input",
                        headerHozAlign: 'center',
                        hozAlign: 'center',
                        formatter: function (cell) {
                            return cell.getValue() ? cell.getValue() : '<i class="fa fa-times text-danger"></i>'
                        },
                    },
                    {
                        title: 'Employee Category',
                        field: 'employee_category',
                        headerHozAlign: 'center',
                        hozAlign: 'center',
                        formatter: function (cell) {
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
                        formatter: function (cell) {
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
                        formatter: function (cell) {
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
                        formatter: function (cell) {
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
                        formatter: function (cell) {
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
                        formatter: function (cell) {
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
                        formatter: function (cell) {
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
            if (!this.isOnSearch) {
                this.isOnSearch = true
                setTimeout(() => {
                    this.table.setFilter('name', "=", this.filterName);
                }, 1000)
            }
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
            this.table.setFilter('department_id', "=", this.filterDepartment.odoo_department_id);
        },
        filterEmployeeCategoryName() {
            if (this.filterEmployeeCategory === null) {
                this.table.clearFilter();
                return;
            }
            this.table.setFilter('employee_category', "=", this.filterEmployeeCategory.value);
        },
        filterEmployeeTypeName() {
            if (this.filterEmployeeType === "") {
                this.table.clearFilter();
                return;
            }
            this.table.setFilter('employee_type', "=", this.filterEmployeeType.value);
        },
        filtering() {
            this.showFilter = !this.showFilter;
        },
        viewDetailsFormatter(cell) {
            return `<button class="button-icon button-success" data-id="${cell.getRow().getData().id}"><i class="fa fa-eye"></i> </button>`;
        },
        viewData(id) {
            this.$router.push({name: 'employee_detail', params: {id}});
        },
        onJobSearchName(val) {
            this.jobPagination.name = val

            if (!this.jobPagination.onSearch) {
                this.jobPagination.onSearch = true
                setTimeout(() => {
                    this.getJobs()
                }, 1000)
            }
        },
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
