<template>
    <div class="container-fluid">
        <Breadcrumbs title="User Management"/>

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
                                            <div class="row">
                                                <div class="col-md-3">
                                                    <multiselect
                                                        v-model="selectedUnit"
                                                        placeholder="Select Unit"
                                                        label="name"
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
                                                <div class="col-md-6">

                                                </div>
                                                <div class="col-md-3">
                                                    <div class="d-flex justify-content-end mb-2">
                                                        <button class="btn btn-warning"  :disabled="syncLoading" type="button" @click="syncFromEmployee">
                                        <span v-if="syncLoading">
                                            <i  class="fa fa-spinner fa-spin"></i> Processing ... ({{ countdown }}s)
                                        </span>
                                                            <span v-else>
                                            <i class="fa fa-recycle"></i> &nbsp; Sync From Employee
                                        </span>
                                                        </button>
                                                    </div>
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
            filterName: '',
            filterEmail: '',
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
            units: []
        }
    },
    async mounted() {
        await this.getUsers();
        this.getUnitsData();
        this.initializeUsersTable();
    },
    methods: {
        getUnitsData() {
            this.$axios.get(`/api/v1/admin/unit/paginated?per_page=${this.unitPagination.pageSize}&page=${this.unitPagination.currentPage}&name=${this.unitPagination.name}`)
                .then(response => {
                    this.units = response.data.data.data
                    this.unitPagination.onSearch = false
                })
                .catch(error => {
                    console.error(error);
                });
        },
        startCountdown() {
            this.countdown = 1;
            this.timerId = setInterval(() => {
                this.countdown++;
            }, 1000);
        },
        async getUsers() {
            this.loading = true;
            const unitId = JSON.parse(localStorage.getItem('USER_STORAGE_KEY'));
            await axios
                .get(`/api/v1/admin/user`)
                .then(response => {
                    this.users = response.data.data;
                })
                .catch(error => {
                    console.error(error);
                });
        },
        initializeUsersTable() {
            const ls = localStorage.getItem('my_app_token')
            this.table = new Tabulator(this.$refs.usersTable, {
                ajaxURL:"/api/v1/admin/user",
                ajaxConfig: {
                    headers: {
                        Authorization: `Bearer ${ls}`,
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
                    })
                    return `${url}?page=${params.page}&size=${params.size}&name=${this.filterName}&email=${this.filterEmail}&last_unit_id=${this.filter.unit_relation_id}`
                },
                ajaxResponse: function (url, params, response) {
                    return {
                        data: response.data.data,
                        last_page: response.data.last_page,
                    }
                },
                layout: 'fitColumns',
                fitColumns: true,
                responsiveLayout: true,
                filterMode:"remote",
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
                        title: 'Email',
                        field: 'email',
                        headerFilter:"input"
                    },
                    {
                        title: 'Unit',
                        field: 'employee.last_unit.name',
                        headerFilter:"input"
                    },
                    {
                        title: 'Role',
                        field: '',
                        hozAlign: 'center',
                        headerHozAlign: 'center',
                        headerFilter:"input",
                        width: 300,
                        formatter: function(row) {
                           return row.getData().roles.map(function(role) {
                                return `<span class='badge badge-danger '>${role.name.toUpperCase()}</span>`;
                            }).join(" ");
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
        viewDetailsFormatter(cell, formatterParams, onRendered) {
            return `
                <div>
                    <button class="button-icon button-success" data-action="view" data-id="${cell.getRow().getData().id}">
                        <i class="fa fa-eye" data-action="view" data-id="${cell.getRow().getData().id}"></i>
                    </button>
                    <button class="button-icon button-warning" data-action="edit" data-id="${cell.getRow().getData().id}">
                        <i class="fa fa-pencil" data-action="edit" data-id="${cell.getRow().getData().id}"></i>
                    </button>
                </div>`;

        },
        handleActionButtonClick(e, cell) {
            const action = e.target.dataset.action;
            const rowData = cell.getRow().getData();

            if (action === 'edit') {
                this.$router.push({
                    name: 'user-edit',
                    params: {id: rowData.id}
                })
            } else if (action === 'view') {
                this.$router.push({
                    name: 'user-detail',
                    params: {id: rowData.id}
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
                    await this.getUsers()
                    this.initializeUsersTable();
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
