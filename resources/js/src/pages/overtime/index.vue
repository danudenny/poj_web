<template>
    <div class="container-fluid">
        <Breadcrumbs main=""/>

        <div class="container-fluid">
            <div class="email-wrap bookmark-wrap">
                <div class="row">
                    <div class="col-md-12">
                        <div class="card card-absolute">
                            <div class="card-header bg-primary">
                                <h5>Employee Overtime Request</h5>
                            </div>
                            <div class="card-body">
                                <div class="d-flex justify-content-end mb-2">
                                    <button class="btn btn-primary" type="button" @click="$router.push('/attendance/overtime/create')">
                                        <i class="fa fa-plus" /> &nbsp;Assign Overtime
                                    </button>
                                </div>
                                <div v-if="loading" class="text-center">
                                    <img src="../../assets/loader.gif" alt="loading" width="100">
                                </div>
                                <div ref="overtimeTable"></div>
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
            pageSize: 10,
            currentPage: 1,
            loading: false,
        }
    },
    async mounted() {
        this.generateOvertimeTable();
    },
    methods: {
        generateOvertimeTable() {
            const ls = localStorage.getItem('my_app_token')
            const table = new Tabulator(this.$refs.overtimeTable, {
                ajaxURL: '/api/v1/admin/overtime',
                layout: 'fitColumns',
                columns: [
                    {
                        title: 'No',
                        field: '',
                        formatter: 'rownum',
                        width: 100
                    },
                    {
                        title: 'Requestor Name',
                        field: 'requestor_employee.name',
                        headerFilter:"input"
                    },
                    {
                        title: 'Status',
                        field: 'last_status',
                        headerFilter:"input"
                    },
                    {
                        title: 'Unit Name',
                        field: 'unit.name',
                        headerFilter:"input"
                    },
                    {
                        title: 'Start Time',
                        field: 'start_date',
                    },
                    {
                        title: 'End Time',
                        field: 'end_date',
                    },
                    {
                        title: 'File',
                        formatter: (cell, formatterParams, onRendered) => {
                            if (cell.getRow().getData().image_url) {
                                return `<a target="_blank" class="button-icon button-success p-2" href="${cell.getRow().getData().image_url}"><i class="fa fa-file"></i> </a>`;
                            } else {
                                return "-"
                            }
                        }
                    },
                    {
                        title: 'Created At',
                        field: 'created_at',
                    },
                    {
                        title: '',
                        formatter: this.viewDetailsFormatter,
                        width: 70,
                        hozAlign: 'center',
                        sortable: false,
                        cellClick: (e, cell) => {
                            this.viewData(cell.getRow().getData().id);
                        }
                    },
                ],
                pagination: true,
                paginationMode: 'remote',
                responsiveLayout: true,
                filterMode:"remote",
                paginationSize: this.pageSize,
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
                ajaxURLGenerator: (url, config, params) => {
                    let localFilter = {
                        unitName: '',
                        lastStatus: '',
                        requestorName: ''
                    }
                    console.log("URLGenerateParam", params)
                    params.filter.map((item) => {
                        if (item.field === 'unit.name') localFilter.unitName = item.value
                        if (item.field === 'last_status') localFilter.lastStatus = item.value
                        if (item.field === 'requestor_employee.name') localFilter.requestorName = item.value
                    })
                    return `${url}?page=${params.page}&per_page=${params.size}&size=${params.size}&unit_name=${localFilter.unitName}&last_status=${localFilter.lastStatus}&requestor_name=${localFilter.requestorName}`
                },
                ajaxResponse: function (url, params, response) {
                    return {
                        data: response.data.data,
                        last_page: response.data.last_page,
                    }
                },
                paginationSizeSelector: [10, 20, 50, 100],
                headerFilter: true,
                rowFormatter: (row) => {

                },
            });
        },
        viewDetailsFormatter(cell, formatterParams, onRendered) {
            return `<button class="button-icon button-success" data-id="${cell.getRow().getData().id}"><i class="fa fa-eye"></i> </button>`;
        },
        viewData(id) {
            this.$router.push({name: 'Detail Overtime', params: {id}});
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
