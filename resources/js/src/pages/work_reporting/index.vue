<template>
    <div class="container-fluid">
        <Breadcrumbs main="Work Reporting"/>

        <div class="container-fluid">
            <div class="email-wrap bookmark-wrap">
                <div class="row">
                    <div class="col-md-12">
                        <div class="card card-absolute">
                            <div class="card-header bg-primary">
                                <h5>Work Reporting</h5>
                            </div>
                            <div class="card-body">
                                <div v-if="loading" class="text-center">
                                    <img src="../../assets/loader.gif" alt="loading" width="100">
                                </div>
                                <div ref="wrTable"></div>
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
            workReportings: [],
            loading: false,
            table: null,
            currentPage: 1,
            pageSize: 10,
            filterTitle: '',
        }
    },
    async mounted() {
        await this.initializeWrTable();
    },
    methods: {
        async initializeWrTable() {
            const ls = localStorage.getItem('my_app_token')
            const roles = JSON.parse(localStorage.getItem('USER_ROLES'));
            this.table = new Tabulator(this.$refs.wrTable, {
                ajaxURL:"/api/v1/admin/work-reporting",
                ajaxConfig: {
                    headers: {
                        Authorization: `Bearer ${ls}`,
                        "X-Unit-Relation-ID": this.$store.state.activeAdminUnit?.unit_relation_id ?? '',
                        "X-Selected-Role": roles
                    },
                },
                ajaxParams: {
                    page: this.currentPage,
                    size: this.pageSize,
                },
                ajaxURLGenerator: (url, config, params) => {
                    return `${url}?page=${params.page}&per_page=${params.size}`
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
                        width: 100,
                        headerHozAlign: 'center'
                    },
                    {
                        title: 'Employee Name',
                        field: 'employee.name',
                        headerFilter:"input",
                        headerHozAlign: 'center'
                    },
                    {
                        title: 'Unit Name',
                        field: 'employee.last_unit.name',
                        headerFilter:"input",
                        headerHozAlign: 'center'
                    },
                    {
                        title: 'Title',
                        field: 'title',
                        headerFilter:"input",
                        headerHozAlign: 'center'
                    },
                    {
                        title: 'Job Type',
                        field: 'job_type',
                        headerFilter:"input",
                        headerHozAlign: 'center',
                    },
                    {
                        title: 'Date',
                        field: 'date',
                        headerFilter:"date",
                        headerHozAlign: 'center',
                        hozAlign: 'center'
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
            console.log(this.table)
            this.loading = false
        },
        viewDetailsFormatter(cell, formatterParams, onRendered) {
            return `
                <div>
                    <button class="button-icon button-success" data-action="view" data-id="${cell.getRow().getData().id}">
                        <i class="fa fa-eye" data-action="view" data-id="${cell.getRow().getData().id}"></i>
                    </button>
                </div>`;

        },
        handleActionButtonClick(e, cell) {
            const action = e.target.dataset.action;
            const rowData = cell.getRow().getData();

            if (action === 'view') {
                this.$router.push({
                    name: 'Work Reporting Detail',
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
