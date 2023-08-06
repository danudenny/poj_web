<template>
    <div class="container-fluid">
        <Breadcrumbs main="Job List"/>

        <div class="container-fluid">
            <div class="email-wrap bookmark-wrap">
                <div class="row">
                    <div class="col-md-12">
                        <div class="card card-absolute">
                            <div class="card-header bg-primary">
                                <h5>Job List</h5>
                            </div>
                            <div class="card-body">
                                <div v-if="loading" class="text-center">
                                    <img src="../../assets/loader.gif" alt="loading" width="100">
                                </div>
                                <div ref="jobTable"></div>
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
import {useToast} from "vue-toastification";

export default {
    data() {
        return {
            jobs: [],
            loading: false,
            syncLoading: false,
            table: null,
            countdown: 0,
            timerId: null,
            currentPage: 1,
            pageSize: 10,
        }
    },
    async mounted() {
        // await this.getJobs();
        this.initializeDepartmentTable();
    },
    methods: {
        startCountdown() {
            this.countdown = 1;
            this.timerId = setInterval(() => {
                this.countdown++;
            }, 1000);
        },
        initializeDepartmentTable() {
            const ls = localStorage.getItem('my_app_token');
            this.table = new Tabulator(this.$refs.jobTable, {
                paginationCounter:"rows",
                ajaxURL: '/api/v1/admin/job',
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
                    return `${url}?page=${params.page}&per_page=${params.size}`
                },
                layout: 'fitColumns',
                columns: [
                    {
                        title: 'No',
                        field: '',
                        formatter: 'rownum',
                        width: 70,
                        hozAlign: 'center',
                        headerHozAlign: 'center',
                    },
                    {
                        title: 'Name',
                        field: 'name',
                        headerFilter:"input",
                        headerHozAlign: 'center',
                        headerSort: false,
                    },
                    {
                        title: 'Roles',
                        field: 'roles',
                        hozAlign: 'center',
                        headerHozAlign: 'center',
                        headerSort: false,
                        formatter: function (cell, formatterParams, onRendered) {
                            const roles = cell.getValue();
                            let html = '';
                            roles.forEach((role) => {
                                html += `<span class="badge badge-success">${role.name}</span> `;
                            });
                            return html;
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
                pagination: true,
                paginationMode: 'remote',
                paginationSize: this.pageSize,
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
                <button class="button-icon button-success" data-action="view" data-row-id="${rowData.id}"><i data-action="view" class="fa fa-eye"></i> </button>
                <button class="button-icon button-warning" data-action="edit" data-row-id="${rowData.id}"><i data-action="edit" class="fa fa-pencil"></i> </button>
             `;
        },
        handleActionButtonClick(e, cell) {
            const action = e.target.dataset.action
            const rowData = cell.getRow().getData();

            if (action === 'edit') {
                this.$router.push({
                    name: 'job-edit',
                    params: { id: rowData.id }
                })
            } else if (action === 'view') {
                this.$router.push({
                    name: 'approval_details',
                    params: { id: rowData.id },
                })
            }
        },
    },
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
