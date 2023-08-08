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
                        width: 70,
                        headerSort: false,
                        hozAlign: 'center',
                        headerHozAlign: 'center'
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
                    {
                        title: 'Total Employee',
                        field: 'employee_count',
                        hozAlign: 'center',
                        headerHozAlign: 'center',
                        headerSort: false,
                        width: 130,
                        formatter: function (cell) {
                            const rowData = cell.getRow().getData();
                            if (rowData.employee_count > 0) {
                                return `<span class="badge badge-primary">${rowData.employee_count}</span>`
                            } else {
                                return `<span class="badge badge-danger">0</span>`
                            }
                        }
                    },
                    {
                        title: 'Total Teams',
                        field: '',
                        headerFilter:"input",
                        hozAlign: 'center',
                        headerHozAlign: 'center',
                        headerSort: false,
                        width: 100,
                        formatter: (cell) => {
                            const rowData = cell.getRow().getData();
                           if (rowData.teams.length > 0) {
                               return `<span class="badge badge-primary">${rowData.teams.length}</span>`
                           } else {
                               return `<span class="badge badge-danger">0</span>`
                           }
                        }
                    },
                    {
                        title: '',
                        formatter: this.viewDetailsFormatter,
                        width: 100,
                        headerSort: false,
                        hozAlign: 'center',
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
                },
                placeholder:"No Data Available",
            });
            this.loading = false
        },
        viewDetailsFormatter(cell) {
            const rowData = cell.getRow().getData();
            return `
                <button class="button-icon button-warning" data-action="edit" data-row-id="${rowData.id}"><i data-action="edit" class="fa fa-pencil"></i> </button>
             `;
        },
        handleActionButtonClick(e, cell) {
            const action = e.target.dataset.action

            if (action === 'edit') {
                this.$router.push({
                    name: 'department-edit',
                    params: {
                        id: cell.getRow().getData().id
                    }
                })
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
</style>
