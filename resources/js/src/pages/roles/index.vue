<template>
    <div class="container-fluid">
        <Breadcrumbs main="Role List"/>

        <div class="container-fluid">
            <div class="email-wrap bookmark-wrap">
                <div class="row">
                    <div class="col-md-12">
                        <div class="card card-absolute">
                            <div class="card-header bg-primary">
                                <h5>Role List</h5>
                            </div>
                            <div class="card-body">
<!--                                <div class="d-flex justify-content-end mb-2">-->
<!--                                    <button class="btn btn-success" type="button" @click="createData">-->
<!--                                        <i class="fa fa-plus-circle" /> &nbsp; Create New-->
<!--                                    </button>-->
<!--                                </div>-->
                                <div v-if="loading" class="text-center">
                                    <img src="../../assets/loader.gif" alt="loading" width="100">
                                </div>
                                <div ref="rolesTable"></div>
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
            roles: [],
            loading: false,
            table: null,
        }
    },
    async mounted() {
        await this.getRoles();
        this.initializeRolesTable();
    },
    methods: {
        createData() {
            this.$router.push({name: 'Role Create'});
        },
        async getRoles() {
            this.loading = true;
            await axios
                .get(`/api/v1/admin/role`)
                .then(response => {
                    this.roles = response.data.data;
                })
                .catch(error => {
                    console.error(error);
                });
        },
        initializeRolesTable() {
             this.table = new Tabulator(this.$refs.rolesTable, {
                data: this.roles,
                layout: 'fitColumns',
                columns: [
                    {
                        title: 'No',
                        field: '',
                        hozAlign: 'center',
                        formatter: 'rownum',
                        width: 80,
                    },
                    {
                        title: 'Name',
                        field: 'name',
                        headerFilter:"input",
                        headerSort: false,
                        headerHozAlign: 'center'
                    },
                    {
                        title: 'Role Level',
                        field: 'role_level',
                        headerFilter:"input",
                        hozAlign: 'center',
                        headerSort: false,
                        headerHozAlign: 'center',
                        formatter: function(cell, formatterParams) {
                            return `<span class="badge badge-primary">${cell.getValue().toUpperCase()}</span>`
                        }
                    },
                    {
                        title: 'Permissions',
                        field: 'permissions',
                        hozAlign: 'center',
                        headerSort: false,
                        headerHozAlign: 'center',
                        formatter: function(cell, formatterParams) {
                            return `<span class="badge badge-info">${cell.getValue().length}</span>`

                        }
                    },
                    {
                        title: 'Status',
                        field: 'is_active',
                        headerFilter:"list",
                        hozAlign: 'center',
                        headerSort: false,
                        headerHozAlign: 'center',
                        headerFilterParams: {
                            valuesLookup:"active",
                            values: ["1", "0"],
                            itemFormatter: function (value, title) {
                                return title === "1" ? "Active" : "Inactive";
                            },
                        },
                        formatter: function(cell, formatterParams) {
                            return `<span class="badge badge-${cell.getValue() === 1 ? 'success' : 'danger' }">
                                        ${cell.getValue() === 1 ? 'Active' : 'Inactive' }
                                    </span>`
                        }
                    },
                    {
                        title: '',
                        formatter: this.actionButtonFormatter,
                        hozAlign: 'center',
                        headerSort: false,
                        headerHozAlign: 'center',
                        cellClick: (e, cell) => {
                            this.handleActionButtonClick(e, cell);
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
        actionButtonFormatter(cell) {
            const rowData = cell.getRow().getData();
            return `
                <button class="button-icon button-success" data-action="view" data-row-id="${rowData.id}"><i data-action="view" class="fa fa-eye"></i> </button>
                <button class="button-icon button-warning" data-action="edit" data-row-id="${rowData.id}"><i data-action="edit" class="fa fa-pencil"></i> </button>
                <button class="button-icon button-danger" data-action="delete" data-row-id="${rowData.id}"><i data-action="delete" class="fa fa-trash"></i> </button>
             `;
        },
        redrawTable() {
            this.$nextTick(() => {
                this.table.redraw(true);
            });
        },
        handleActionButtonClick(e, cell) {
            const action = e.target.dataset.action
            const rowData = cell.getRow().getData();

            if (action === 'edit') {
                this.$router.push({
                    name: 'Role Edit',
                    params: { id: rowData.id }
                })
            } else if (action === 'view') {
                this.$router.push({
                    name: 'Role Detail',
                    params: { id: rowData.id }
                })
            } else if (action === 'delete') {
                this.basic_warning_alert(rowData.id);
            }
        },
        basic_warning_alert:function(id){
            this.$swal({
                icon: 'warning',
                title:"Delete Data?",
                text:'Once deleted, you will not be able to recover the data!',
                showCancelButton: true,
                confirmButtonText: 'Yes, delete it!',
                confirmButtonColor: '#e64942',
                cancelButtonText: 'Cancel',
                cancelButtonColor: '#efefef',
            }).then((result)=>{
                if(result.value){
                    this.$axios.delete(`api/v1/admin/role/delete?id=${id}`)
                        .then(async (res) => {
                            const pluck = this.roles.filter((item) => item.id !== id);
                            this.loading = true
                            this.table.setData(pluck);
                            this.redrawTable();
                            this.loading = false
                            useToast().success('Success Delete Data' , {
                                position: 'bottom-right'
                            });
                        })
                        .catch(error => {
                            this.warning_alert_state(error.message);
                        });
                }else{
                    this.$swal({
                        text:'Your data is safe!'
                    });
                }
            });
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

.button-warning {
    background-color: #ffc107;
    color: #fff
}

.button-danger {
    background-color: #dc3545;
    color: #fff
}
</style>
