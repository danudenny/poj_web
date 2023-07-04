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
                                <div class="d-flex justify-content-end mb-2">
                                    <button class="btn btn-warning" type="button" data-bs-toggle="modal"
                                            data-bs-target="#exampleModalCenter">
                                        <i class="fa fa-recycle" /> &nbsp; Sync From Employee
                                    </button>
                                </div>
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

export default {
    data() {
        return {
            users: [],
            loading: false,
        }
    },
    async mounted() {
        await this.getUsers();
        this.initializeUsersTable();
    },
    methods: {
        async getUsers() {
            this.loading = true;
            const unitId = JSON.parse(localStorage.getItem('USER_STORAGE_KEY'));
            await axios
                .get(`/api/v1/admin/user?unit_id=${parseInt(unitId.unit_id)}`)
                .then(response => {
                    this.users = response.data.data;
                })
                .catch(error => {
                    console.error(error);
                });
        },
        initializeUsersTable() {
            const table = new Tabulator(this.$refs.usersTable, {
                data: this.users,
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
                        title: 'Email',
                        field: 'email',
                        headerFilter:"input"
                    },
                    {
                        title: 'Role',
                        field: '',
                        hozAlign: 'center',
                        headerFilter:"input",
                        formatter: function(row) {
                            return row.getData().roles.map(function(role) {
                                return `<span class='badge badge-danger '>${role.name}</span>`;
                            }).join(", ");
                        }
                    },
                    {
                        title: '',
                        formatter: this.viewDetailsFormatter,
                        width: 50,
                        hozAlign: 'center',
                        sortable: false,
                        cellClick: (e, cell) => {
                            this.handleActionButtonClick(e, cell)
                        }
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
        viewDetailsFormatter(cell, formatterParams, onRendered) {
            // return 2 buttons
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
