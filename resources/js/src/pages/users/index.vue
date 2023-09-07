<template>
    <div class="container-fluid">
        <Breadcrumbs main="User Management"/>

        <div class="container-fluid">
            <div class="email-wrap bookmark-wrap">
                <div class="row">
                    <div class="col-md-12">
                        <div class="card card-absolute">
                            <div class="card-header bg-primary">
                                <h5>User List</h5>
                            </div>
                            <div class="card-body">
                                <div>
                                    <h5>
                                        <i class="fa fa-filter text-warning"></i>&nbsp; Filter
                                    </h5>
                                    <div class="row">
                                        <div class="col-md-12">
                                            <div class="row d-flex justify-content-start">
                                                <div class="col-md-4">
                                                    <multiselect
                                                        v-model="selectedUnit"
                                                        placeholder="Select Unit"
                                                        label="formatted_name"
                                                        track-by="id"
                                                        :options="units"
                                                        :multiple="false"
                                                        :required="true"
                                                        @select="onUnitSelected"
                                                        @deselect="onUnitDeselected"
                                                        @search-change="onUnitSearchName"
                                                    >
                                                    </multiselect>
                                                </div>
                                                <div class="col-md-4">
                                                    <multiselect
                                                        v-model="selectedDepartment"
                                                        placeholder="Select Department"
                                                        label="name"
                                                        track-by="id"
                                                        :options="departments"
                                                        :multiple="false"
                                                        :required="true"
                                                        @select="onDepartmentSelected"
                                                    >
                                                    </multiselect>
                                                </div>
                                                <div class="col-md-4">
                                                   <button class="btn btn-warning" @click="onClearFilter">Clear Filter</button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <br/>
                                <div v-if="loading" class="text-center">
                                    <img src="../../assets/loader.gif" alt="loading" width="100">
                                </div>
                                <div ref="usersTable"></div>
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
            users: [],
            loading: false,
            syncLoading: false,
            table: null,
            countdown: 0,
            timerId: null,
            currentPage: 1,
            pageSize: 10,
            perPage: 10,
            filterName: '',
            filterEmail: '',
            filterDepartment: '',
            filterJob: '',
            filter: {
                unit_relation_id: ''
            },
            unitPagination: {
                currentPage: 1,
                pageSize: 100,
                name: '',
                onSearch: false
            },
            selectedUnit: {
                relation_id: ''
            },
            units: [],
            departments: [],
            selectedDepartment: null,
        }
    },
    async mounted() {
        this.getUnitsData();
        await this.getDepartments()
        this.initializeUsersTable();
    },
    methods: {
        onClearFilter() {
            this.selectedUnit = null
            this.selectedDepartment = null
            this.filterDepartment = ''
            this.filterEmail = ''
            this.filter.unit_relation_id = ''
            this.table.clearFilter()
            this.table.clearHeaderFilter().then(() => {
                this.table.refreshData()
            })
        },
        onDepartmentSelected() {
            this.filterDepartment = this.selectedDepartment.odoo_department_id
            this.table.setFilter('employee.department.name', '=', this.filterDepartment)
        },
        async getDepartments() {
            await this.$axios.get('/api/v1/admin/department/all')
                .then(response => {
                    this.departments = response.data.data
                })
        },
        getUnitsData() {
            const ls = localStorage.getItem('USER_ROLES')
            this.$axios.get(`/api/v1/admin/unit/paginated?per_page=${this.unitPagination.pageSize}&page=${this.unitPagination.currentPage}&name=${this.unitPagination.name}`, {
                headers: {
                    'X-Selected-Role': ls
                }
            })
                .then(response => {
                    this.units = response.data.data.data
                    this.unitPagination.onSearch = false
                })
                .catch(error => {
                    console.error(error);
                });
        },
        initializeUsersTable() {
            const ls = localStorage.getItem('my_app_token')
            const selectedROle = localStorage.getItem('USER_ROLES')
            this.table = new Tabulator(this.$refs.usersTable, {
                ajaxURL:"/api/v1/admin/user",
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
                ajaxURLGenerator: (url, config, params) => {
                    params.filter.map((item) => {
                        if (item.field === 'name') this.filterName = item.value
                        if (item.field === 'email') this.filterEmail = item.value
                        if (item.field === 'employee.department.name') this.filterDepartment = item.value
                        if (item.field === 'employee.job.name') this.filterJob = item.value
                    })
                    return `${url}?page=${params.page}&per_page=${params.size}&name=${this.filterName}&email=${this.filterEmail}&last_unit_id=${this.filter.unit_relation_id}&department_id=${this.filterDepartment}&job_name=${this.filterJob}`
                },
                ajaxResponse: function (url, params, response) {
                    return {
                        data: response.data.data,
                        last_page: response.data.last_page,
                    }
                },
                layout: 'fitData',
                renderHorizontal:"virtual",
                height: '100%',
                filterMode:"remote",
                columns: [
                    {
                        title: 'No',
                        field: '',
                        formatter: 'rownum',
                        hozAlign: 'center',
                        headerHozAlign: 'center',
                        headerSort: false,
                        width: 70
                    },
                    {
                        title: 'Name',
                        field: 'name',
                        headerFilter:"input",
                        formatter: function(row) {
                           return row.getData().name;
                        }
                    },
                    {
                        title: 'Email',
                        field: 'email',
                        headerFilter:"input"
                    },
                    {
                        title: 'Unit',
                        field: 'employee.last_unit.formatted_name',
                        hozAlign: 'center',
                        headerHozAlign: 'center',
                        headerSort: 'false',
                        formatter: function(row) {
                            if (row.getData().employee.last_unit.name === null) {
                                return `<span class='badge badge-danger '>No Unit</span>`;
                            } else {
                                return row.getData().employee.last_unit.formatted_name;
                            }
                        }
                    },
                    {
                        title: 'Department',
                        field: 'employee.department.name',
                        formatter: function(row) {
                            if (row.getData().employee.department.name === null) {
                                return `<span class='badge badge-danger '>No Department</span>`;
                            } else {
                                return row.getData().employee.department.name;
                            }
                        }
                    },
                    {
                        title: 'Job',
                        field: 'employee.job.name',
                        headerHozAlign: 'center',
                        hozAlign: 'center',
                        headerSort: 'false',
                        headerFilter: 'input',
                        formatter: function(row) {
                            if (row.getData().employee.job.name === null) {
                                return `<span class='badge badge-danger '>No Job</span>`;
                            } else {
                                return row.getData().employee.job.name;
                            }
                        }
                    },
                    {
                        title: 'Role',
                        field: 'roles',
                        hozAlign: 'center',
                        headerHozAlign: 'center',
                        headerFilter:"input",
                        width: 300,
                        formatter: function(row) {
                            let roles = row.getValue()
                            let html = ''
                            roles.forEach((role) => {
                                html += `<span class="badge badge-primary">${role.name}</span> `
                            })
                            return html
                        }
                    },
                    {
                        title: 'Allowed Operating Unit',
                        field: 'allowed_operating_units',
                        hozAlign: 'center',
                        headerHozAlign: 'center',
                        headerFilter:"input",
                        width: 600,
                        formatter: function(cell) {
                            let values = cell.getValue()
                            let html = ''
                            values.forEach((value) => {
                                html += `<span class="badge badge-primary">${value.name}</span> `
                            })
                            return `<div>${html}</div>`
                        }
                    },
                    {
                        title: '',
                        formatter: this.viewDetailsFormatter,
                        width: 100,
                        hozAlign: 'center',
                        sortable: false,
                        cellClick: (e, cell) => {
                            this.handleActionButtonClick(e, cell)
                        }
                    },
                ],
                pagination: true,
                paginationMode: "remote",
                paginationSize: this.pageSize,
                paginationSizeSelector: [10, 25, 50, 100],
                headerFilter: true,
                paginationInitialPage:1,
                placeholder: 'No Data Available',
                rowFormatter: (row) => {
                }
            });
            this.loading = false
        },
        viewDetailsFormatter(cell) {
            if (this.$store.state.permissions?.includes("user-update")) {
                return `
                <div>
                    <button class="button-icon button-success" data-action="view" data-id="${cell.getRow().getData().id}">
                        <i class="fa fa-eye" data-action="view" data-id="${cell.getRow().getData().id}"></i>
                    </button>
                    <button class="button-icon button-warning" data-action="edit" data-id="${cell.getRow().getData().id}">
                        <i class="fa fa-pencil" data-action="edit" data-id="${cell.getRow().getData().id}"></i>
                    </button>
                </div>`;
            } else {
                return `
                <div>
                    <button class="button-icon button-success" data-action="view" data-id="${cell.getRow().getData().id}">
                        <i class="fa fa-eye" data-action="view" data-id="${cell.getRow().getData().id}"></i>
                    </button>
                </div>`;
            }

        },
        handleActionButtonClick(e, cell) {
            const action = e.target.dataset.action;
            const rowData = cell.getRow().getData();

            if (action === 'edit') {
                this.$router.push({
                    name: 'user-edit',
                    params: {
                        id: rowData.id
                    },
                    query: {
                        dept_id: rowData.employee.department.id,
                        unit_id: rowData.employee.unit_id
                    }
                })
            } else if (action === 'view') {
                this.$router.push({
                    name: 'user-detail',
                    params: {id: rowData.id},
                    query: {
                        dept_id: rowData.employee.department.id,
                        unit_id: rowData.employee.unit_id
                    }
                })
            }
        },
        async syncFromEmployee() {
            this.syncLoading = true;
            this.loading = true
            this.startCountdown();
            this.table.destroy()

            await this.$axios.get('/api/v1/admin/employee/sync-to-users')
                .then(async (response) => {
                    this.syncLoading = false;
                    this.loading = false;
                    this.table.setData();
                    useToast().success(response.data.message);

                }).catch(() => {
                    this.syncLoading = false;
                    useToast().error("Failed to Sync Data! Check connection.");
                }).finally(() => {
                    this.syncLoading = false;
                    clearInterval(this.timerId);
                });
        },
        onUnitSearchName(val) {
            this.unitPagination.name = val

            if (!this.unitPagination.onSearch) {
                this.unitPagination.onSearch = true
                setTimeout(() => {
                    this.getUnitsData()
                }, 1000)
            }
        },
        onUnitSelected(val) {
            this.filter.unit_relation_id = this.selectedUnit.relation_id
            this.initializeUsersTable()
        },
        onUnitDeselected(val) {
            this.filter.unit_relation_id = ''
            this.initializeUsersTable()
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
