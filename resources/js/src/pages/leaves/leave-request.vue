<template>
    <div class="container-fluid">
        <Breadcrumbs main="Leave Request"/>

        <div class="container-fluid">
            <div class="email-wrap bookmark-wrap">
                <div class="row">
                    <div class="col-md-12">
                        <div class="card card-absolute">
                            <div class="card-header bg-primary">
                                <h5>Leave Request</h5>
                            </div>
                            <div class="card-body">
                                <div ref="leaveMasterTable"></div>
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
import moment from "moment";

export default {
    data() {
        return {
            table: null,
            filterName: '',
            filterType: '',
            currentPage: 1,
            pageSize: 10
        }
    },
    mounted() {
        this.masterLeaveTable();
    },
    methods: {
        masterLeaveTable() {
            const ls = localStorage.getItem('my_app_token');
            this.table = new Tabulator(this.$refs.leaveMasterTable ,{
                paginationCounter:"rows",
                ajaxURL: '/api/v1/admin/leave_request',
                ajaxConfig: {
                    headers: {
                        Authorization: `Bearer ${ls}`,
                        "X-Unit-Relation-ID": this.$store.state.activeAdminUnit?.unit_relation_id ?? '',
                        "X-Selected-Role": this.$store.state.currentRole,
                    }
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
                        if (item.field === 'leave_name') this.filterName = item.value
                        if (item.field === 'leave_type') this.filterType = item.value
                    })
                    return `${url}?page=${params.page}&per_page=${params.size}&leave_name=${this.filterName}&leave_type=${this.filterType}`
                },
                layout: 'fitColumns',
                renderHorizontal:"virtual",
                height: '100%',
                columns: [
                    {
                        title: 'No',
                        field: '',
                        formatter: 'rownum',
                        hozAlign: 'center',
                        headerHozAlign: 'center',
                        headerSort: false,
                        width: 50,
                        frozen: true
                    },
                    {
                        title: 'Employee Name',
                        field: 'employee.name',
                        headerHozAlign: 'center',
                        headerSort: false,
                        width: 250,
                        frozen: true,
                        formatter: function (cell) {
                            return `<span class="text-success" title="Go To Details"><b>${cell.getValue()}</b></span>`
                        },
                        cellClick: (e, cell) => {}
                    },
                    {
                        title: 'Unit',
                        field: 'employee.last_unit.name',
                        headerHozAlign: 'center',
                        headerSort: false,
                    },
                    {
                        title: 'Date',
                        headerHozAlign: 'center',
                        headerSort: false,
                        columns: [
                            {
                                title: 'Start Date',
                                field: 'start_date',
                                hozAlign: 'center',
                                headerHozAlign: 'center',
                                headerSort: false,
                                formatter: function (cell) {
                                    return moment(cell.getValue()).format('DD MMMM YYYY')
                                }
                            },
                            {
                                title: 'End Date',
                                field: 'end_date',
                                hozAlign: 'center',
                                headerHozAlign: 'center',
                                headerSort: false,
                                formatter: function (cell) {
                                    return moment(cell.getValue()).format('DD MMMM YYYY')
                                }
                            },
                            {
                                title: 'Days',
                                field: 'days',
                                hozAlign: 'center',
                                headerHozAlign: 'center',
                                headerSort: false,
                            },
                        ]
                    },
                    {
                        title: 'Leave Category',
                        field: 'leave_type.leave_name',
                        headerHozAlign: 'center',
                        headerSort: false,
                    },
                    {
                        title: 'Leave Type',
                        field: 'leave_type.leave_type',
                        hozAlign: 'center',
                        headerHozAlign: 'center',
                        headerSort: false,
                        formatter: function (cell) {
                            if (cell.getValue() === 'leave') {
                                return '<span class="badge badge-warning">Cuti</span>'
                            } else if (cell.getValue() === 'permit') {
                                return '<span class="badge badge-danger">Izin</span>'
                            }
                        }
                    },
                    {
                        title: 'Status',
                        field: 'last_status',
                        headerHozAlign: 'center',
                        headerSort: false,
                        formatter: function (cell) {
                            if (cell.getValue() === 'on process') {
                                return '<span class="badge badge-info">Pending</span>'
                            } else if (cell.getValue() === 'approved') {
                                return '<span class="badge badge-success">Approved</span>'
                            } else if (cell.getValue() === 'rejected') {
                                return '<span class="badge badge-danger">Rejected</span>'
                            }
                        }
                    },
                    {
                        title: 'Reason',
                        field: 'reason',
                        hozAlign: 'center',
                        headerHozAlign: 'center',
                        headerSort: false,
                    },
                    {
                        title: 'File',
                        field: 'file_url',
                        hozAlign: 'center',
                        headerHozAlign: 'center',
                        headerSort: false,
                        formatter: function (cell) {
                            let value = cell.getValue()
                            if (value) {
                                return `<a target="_blank" class="button-icon button-success p-2 mt-3" href="${value}"><i class="fa fa-file"></i> </a>`;
                            } else {
                                return "-"
                            }
                        }
                    },
                ],
                placeholder: 'No Data Available',
                pagination: true,
                paginationMode: 'remote',
                filterMode:"remote",
                paginationSize: this.pageSize,
                paginationSizeSelector: [10, 20, 50, 100],
                headerFilter: true,
                paginationInitialPage:1,
            })
        },
    }
}
</script>
