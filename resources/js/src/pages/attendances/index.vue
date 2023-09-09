<template>
    <Breadcrumbs main="Attendances" />

    <div class="container-fluid">
        <div class="email-wrap bookmark-wrap">
            <div class="row">
                <div class="col-md-12">
                    <div class="card card-absolute">
                        <div class="card-header bg-primary">
                            <h5>Attendances List</h5>
                        </div>
                        <div class="card-body">
                            <div v-if="loading" class="text-center">
                                <img src="../../assets/loader.gif" alt="loading" width="100">
                            </div>
                            <div ref="attendanceTable"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import axios from "axios"
import Datepicker from "vue3-datepicker";
import {TabulatorFull as Tabulator} from "tabulator-tables";

export default {
    components: {
        Datepicker
    },
    data() {
        return {
            filter: {
                name: "",
                check_in: null,
                check_out: null,
                location: ""
            },
            date: null,
            attendances: [],
            formattedCheckIn: "",
            formattedCheckOut: "",
            table: null,
            loading: false,
            currentPage: 1,
            pageSize: 10,
        }
    },
    mounted() {
        this.initializeAttendanceTable()
    },
    methods: {
        formatDate(date) {
            const year = date.getFullYear();
            const month = String(date.getMonth() + 1).padStart(2, '0');
            const day = String(date.getDate()).padStart(2, '0');
            const hours = String(date.getHours()).padStart(2, '0');
            const minutes = String(date.getMinutes()).padStart(2, '0');
            const seconds = String(date.getSeconds()).padStart(2, '0');
            return `${year}-${month}-${day}`;
        },
        initializeAttendanceTable() {
            const ls = localStorage.getItem('my_app_token')
            const role = JSON.parse(localStorage.getItem('USER_ROLES'))
            this.table = new Tabulator(this.$refs.attendanceTable, {
                paginationCounter:"rows",
                ajaxURL: '/api/v1/admin/attendance',
                ajaxConfig: {
                    headers: {
                        Authorization: `Bearer ${ls}`,
                        "X-Unit-Relation-ID": this.$store.state.activeAdminUnit?.unit_relation_id ?? '',
                        "X-Selected-Role": role
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
                    let localFilter = {
                        realCheckIn: '',
                        realCheckOut: '',
                        name: ''
                    }

                    params.filter.map((item) => {
                        if (item.field === 'check_in_time_with_client_timezone') localFilter.realCheckIn = item.value
                        if (item.field === 'check_out_time_with_client_timezone') localFilter.realCheckOut = item.value
                        if (item.field === 'employee.name') localFilter.name = item.value
                    })

                    return `${url}?page=${params.page}&per_page=${params.size}&name=${localFilter.name}&check_in_date=${localFilter.realCheckIn}&check_out_date=${localFilter.realCheckOut}`
                },
                layout: 'fitColumns',
                columns: [
                    {
                        title: 'No',
                        field: '',
                        width: 80,
                        formatter: 'rownum',
                    },
                    {
                        title: 'Name',
                        field: 'employee.name',
                        headerFilter:"input"
                    },
                    {
                        title: 'Check In',
                        field: 'check_in_time_with_client_timezone',
                        headerFilter:"date"
                    },
                    {
                        title: 'Check Out',
                        field: 'check_out_time_with_client_timezone',
                        headerFilter:"date"
                    },
                    {
                        title: 'Approved',
                        field: 'approved',
                        headerFilter:"input",
                        formatter: function(value) {
                            if (value.getData().is_need_approval) {
                                return '<span class="badge badge-warning">Waiting Approval</span>'
                            } else {
                                if (value.getValue()) {
                                    return '<span class="badge badge-success">Approved</span>'
                                } else {
                                    return '<span class="badge badge-danger">Rejected</span>'
                                }
                            }
                        }
                    },
                    {
                        title: 'Unit Target',
                        field: 'unit_target.formatted_name'
                    },
                    {
                        title: 'Attendance Type',
                        field: 'attendance_types',
                        formatter: function(cell) {
                            let value = cell.getValue()

                            if (value === 'backup') {
                                return '<span class="badge badge-warning">Backup</span>'
                            } else if (value === 'overtime') {
                                return '<span class="badge badge-success">Overtime</span>'
                            } else if (value === 'normal') {
                                return '<span class="badge badge-info">Normal</span>'
                            } else {
                                return `<span class="badge badge-primary">${value}</span>`
                            }
                        }
                    },
                    {
                        title: '',
                        formatter: this.viewDetailsFormatter,
                        hozAlign: 'center',
                        sortable: false,
                        cellClick: (e, cell) => {
                            this.viewData(cell.getRow().getData().id);
                        }
                    },
                ],
                pagination: true,
                paginationMode: 'remote',
                filterMode:"remote",
                paginationSize: this.pageSize,
                paginationSizeSelector: [10, 20, 50, 100],
                headerFilter: true,
                paginationInitialPage:1,
                rowFormatter: (row) => {
                    //
                },
                placeholder: 'No Data Available',
            });
            this.loading = false
        },
        viewDetailsFormatter(cell, formatterParams, onRendered) {
            return `<button class="button-icon button-success" data-id="${cell.getRow().getData().id}"><i class="fa fa-eye"></i> </button>`;
        },
        viewData(id) {
            this.$router.push({name: 'Detail Attendance', params: {id}});
        },
    }
}
</script>

<style scoped>
.table thead th {
    text-align: center;
    font-weight: bold;
    background-color: #0A5640;
    color: #fff;
}

.table tbody td:last-child {
    text-align: center;
}
.table tbody td:first-child {
    text-align: center;
}

.badge {
    font-size: 12px;
    padding: 5px 10px;
    border-radius: 20px;
    font-weight: 500;
    text-transform: uppercase;
    letter-spacing: 1px;
    margin: 0 5px;
}

.badge-success {
    background-color: #28a745;
    color: #fff
}

.badge-danger {
    background-color: #dc3545;
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

.button-success:hover {
    background-color: #218838;
    color: #fff
}

.button-danger {
    background-color: #dc3545;
    color: #fff
}

.button-danger:hover {
    background-color: #c82333;
    color: #fff
}

.button-info {
    background-color: #17a2b8;
    color: #fff
}

.button-info:hover {
    background-color: #138496;
    color: #fff
}
</style>
