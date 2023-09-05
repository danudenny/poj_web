<template>
    <div class="container-fluid">
        <Breadcrumbs main="Detail Timesheet Reporting"/>

        <div class="container-fluid">
            <div class="email-wrap bookmark-wrap">
                <div class="row">
                    <div class="col-md-12">
                        <div class="card card-absolute">
                            <div class="card-header bg-primary">
                                <h5>Detail Timesheet Reporting</h5>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-3 mt-4">
                                        <label for="name">Unit</label>
                                        <input type="text" class="form-control" v-model="timesheetReport.unit.name" disabled>
                                    </div>
                                    <div class="col-md-3 mt-4">
                                        <label for="name">Tanggal Report Awal</label>
                                        <input type="date" class="form-control" v-model="timesheetReport.start_date" disabled>
                                    </div>
                                    <div class="col-md-3 mt-4">
                                        <label for="name">Tanggal Report Akhir</label>
                                        <input type="date" class="form-control" v-model="timesheetReport.end_date" disabled>
                                    </div>
                                    <div class="col-md-3 mt-4">
                                        <label for="name">Status</label>
                                        <input type="text" class="form-control" v-model="timesheetReport.status" disabled>
                                    </div>
                                    <div class="col-md-3 mt-4">
                                        <label for="name">Last Sync At</label>
                                        <input type="text" class="form-control" v-model="timesheetReport.last_sync_with_client_timezone" disabled>
                                    </div>
                                    <div class="col-md-3 mt-4">
                                        <label for="name">Last Sync By</label>
                                        <input type="text" class="form-control" v-model="timesheetReport.last_sync_by" disabled>
                                    </div>
                                    <div class="col-md-3 mt-4">
                                        <label for="name">Last Send At</label>
                                        <input type="text" class="form-control" v-model="timesheetReport.last_sent_with_client_timezone" disabled>
                                    </div>
                                    <div class="col-md-3 mt-4">
                                        <label for="name">Last Send By</label>
                                        <input type="text" class="form-control" v-model="timesheetReport.last_sent_by" disabled>
                                    </div>
                                </div>

                                <hr/>

                                <button class="btn btn-outline-success" v-if="!isLoadingReportingDetails" @click="exportExcel">
                                    <i class="fa fa-file-excel-o"></i>&nbsp; Export to Excel
                                </button> &nbsp;
                                <button class="btn btn-outline-success" v-if="!isLoadingReportingDetails" :disabled="isOnProcessRefreshData" @click="onRefreshReport">
                                    <span v-if="!this.isOnProcessRefreshData"><i class="fa fa-refresh"></i> Refresh Data</span>
                                    <span v-else>Collecting Data ({{ this.countdown }}s)</span>
                                </button> &nbsp;
                                <button class="btn btn-outline-success" v-if="!isLoadingReportingDetails" :disabled="isOnprocessSendingData" @click="onSendDataToERP">
                                    <span v-if="!this.isOnprocessSendingData"><i class="fa fa-cloud"></i> Send to ERP</span>
                                    <span v-else>Sending Data ({{ this.countdown }}s)</span>
                                </button> &nbsp;
                                <br/><br/>
                                <div v-if="isLoadingReportingDetails">
                                    <p align="center">
                                        <div class="spinner-border text-primary">
                                            <span class="sr-only">Loading...</span>
                                        </div>
                                    </p>
                                </div>
                                <div ref="listTimesheetReporting"></div>
                            </div>
                            <div class="card-footer text-start">
                                <button class="btn btn-secondary" @click="$router.go(-1)">Back</button>
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
import VerticalModal from "@components/modal/verticalModal.vue";
import Modal from "@components/modal.vue";
import moment from "moment/moment";

export default {
    components: {VerticalModal, Modal},
    data() {
        return {
            timesheetReport: {
                id: null,
                start_date: null,
                end_date: null,
                last_sync_with_client_timezone: null,
                last_sync_by: null,
                last_sent_with_client_timezone: null,
                last_sent_by: null,
                status: null,
                unit: {
                    name: null
                }
            },
            pagination: {
                currentPage: 1,
                pageSize: 10
            },
            reportingDetails: [],
            isLoadingReportingDetails: true,
            table: null,
            isOnProcessRefreshData: false,
            isOnprocessSendingData: false,
            countdown: 0,
            timerInterval: null
        }
    },
    mounted() {
        this.getTimesheetReportingDetail()
    },
    methods: {
        getTimesheetReportingDetail() {
            this.$axios.get(`/api/v1/admin/timesheet-report/view/${this.$route.params.id}`)
                .then(response => {
                    this.timesheetReport = response.data.data
                    this.getTimesheetReportingDetails()
                })
                .catch(error => {
                    console.error(error);
                });
        },
        getTimesheetReportingDetails() {
            this.isLoadingReportingDetails = true
            this.$axios.get(`/api/v1/admin/timesheet-report/list-timesheet-detail?timesheet_report_id=${this.$route.params.id}`)
                .then(response => {
                    this.isLoadingReportingDetails = false
                    this.reportingDetails = response.data.data
                    this.generateTimesheetReportingTable()
                })
                .catch(error => {
                    console.error(error);
                });
        },
        generateTimesheetReportingTable() {
            if (this.timesheetReport?.id === null) {
                return
            }

            const ls = localStorage.getItem('my_app_token');
            this.table = new Tabulator(this.$refs.listTimesheetReporting ,{
                data: this.reportingDetails,
                layout: 'fitData',
                renderHorizontal:"virtual",
                height: '100%',
                columns: [
                    {
                        title: 'Name',
                        field: 'employee.name',
                        hozAlign: 'center',
                        headerHozAlign: 'center',
                        headerSort: false,
                        width: 200,
                        frozen: true
                    },
                    {
                        title: 'Payslip',
                        headerHozAlign: 'center',
                        headerSort: false,
                        columns: [
                            {
                                title: 'Payslip ID',
                                field: 'odoo_payslip_id',
                                hozAlign: 'center',
                                headerHozAlign: 'center',
                                headerSort: false,
                                formatter: function (cell) {
                                    let value = cell.getValue()

                                    if (value === null) {
                                        return `<span class="text-danger"><i class="fa fa-times"></i></span>`
                                    } else {
                                        return `<span class="badge badge-success">${value}</span>`
                                    }
                                }
                            },
                            {
                                title: 'Status',
                                field: 'status',
                                hozAlign: 'center',
                                headerHozAlign: 'center',
                                headerSort: false,
                                formatter: function (cell) {
                                    let value = cell.getValue()

                                    if (value === 'pending') {
                                        return `<span class="badge badge-warning">Pending</span>`
                                    } else if (value === 'success') {
                                        return `<span class="badge badge-success">Success</span>`
                                    } else {
                                        return `<span class="badge badge-danger">Failed</span>`
                                    }
                                }
                            },
                            {
                                title: 'Message',
                                field: 'response_message',
                                hozAlign: 'center',
                                headerHozAlign: 'center',
                                headerSort: false,
                                formatter: function (cell) {
                                    let value = cell.getValue()

                                    if (value === null) {
                                        return `<span class="text-danger"><i class="fa fa-times"></i></span>`
                                    } else {
                                        return value
                                    }
                                }
                            },
                        ]
                    },
                    {
                        title: 'Workday',
                        field: 'total_work_day',
                        headerHozAlign: 'center',
                        hozAlign: 'center',
                        headerSort: false,
                        formatter: function (cell) {
                            let value = cell.getValue()

                            if (value > 0) {
                                return `<span class="badge badge-success">${value} Hari</span>`
                            } else {
                                return `<span class="badge badge-warning">${value} Hari</span>`
                            }
                        }
                    },
                    {
                        title: 'Kehadiran',
                        field: 'attendance_days',
                        headerHozAlign: 'center',
                        hozAlign: 'center',
                        headerSort: false,
                        formatter: function (cell) {
                            let value = cell.getValue()

                            if (value > 0) {
                                return `<span class="badge badge-success">${value} Hari</span>`
                            } else {
                                return `<span class="badge badge-warning">${value} Hari</span>`
                            }
                        }
                    },
                    {
                        title: 'Kehadiran Dinas/WFH',
                        field: 'work_from_home_days',
                        headerHozAlign: 'center',
                        hozAlign: 'center',
                        headerSort: false,
                        formatter: function (cell) {
                            let value = cell.getValue()

                            if (value > 0) {
                                return `<span class="badge badge-success">${value} Hari</span>`
                            } else {
                                return `<span class="badge badge-warning">${value} Hari</span>`
                            }
                        }
                    },
                    {
                        title: 'Workday (weekday)',
                        field: 'total_work_weekdays',
                        headerHozAlign: 'center',
                        hozAlign: 'center',
                        headerSort: false,
                        formatter: function (cell) {
                            let value = cell.getValue()

                            if (value > 0) {
                                return `<span class="badge badge-success">${value} Hari</span>`
                            } else {
                                return `<span class="badge badge-warning">${value} Hari</span>`
                            }
                        }
                    },
                    {
                        title: 'Workday (Holiday)',
                        field: 'total_work_day_off',
                        headerHozAlign: 'center',
                        hozAlign: 'center',
                        headerSort: false,
                        formatter: function (cell) {
                            let value = cell.getValue()

                            if (value > 0) {
                                return `<span class="badge badge-success">${value} Hari</span>`
                            } else {
                                return `<span class="badge badge-warning">${value} Hari</span>`
                            }
                        }
                    },
                    {
                        title: 'Libur Nasional',
                        field: 'total_work_national_holiday',
                        headerHozAlign: 'center',
                        hozAlign: 'center',
                        headerSort: false,
                        formatter: function (cell) {
                            let value = cell.getValue()

                            if (value > 0) {
                                return `<span class="badge badge-success">${value} Hari</span>`
                            } else {
                                return `<span class="badge badge-warning">${value} Hari</span>`
                            }
                        }
                    },
                    {
                        title: 'Total Leave',
                        field: 'total_leave',
                        headerHozAlign: 'center',
                        hozAlign: 'center',
                        headerSort: false,
                        formatter: function (cell) {
                            let value = cell.getValue()

                            if (value > 0) {
                                return `<span class="badge badge-success">${value} Hari</span>`
                            } else {
                                return `<span class="badge badge-warning">${value} Hari</span>`
                            }
                        }
                    },
                    {
                        title: 'Alpha/Sakit non SKD',
                        field: 'total_absent',
                        headerHozAlign: 'center',
                        hozAlign: 'center',
                        headerSort: false,
                        formatter: function (cell) {
                            let value = cell.getValue()

                            if (value > 0) {
                                return `<span class="badge badge-success">${value} Hari</span>`
                            } else {
                                return `<span class="badge badge-warning">${value} Hari</span>`
                            }
                        }
                    },
                    {
                        title: 'Lembur Jam Ke-1',
                        field: 'total_overtime_first',
                        headerHozAlign: 'center',
                        hozAlign: 'center',
                        headerSort: false,
                        formatter: function (cell) {
                            let value = cell.getValue()

                            if (value > 0) {
                                return `<span class="badge badge-success">${value} Jam</span>`
                            } else {
                                return `<span class="badge badge-warning">${value} Jam</span>`
                            }
                        }
                    },
                    {
                        title: 'Lembur Jam Ke-2',
                        field: 'total_overtime_second',
                        headerHozAlign: 'center',
                        hozAlign: 'center',
                        headerSort: false,
                        formatter: function (cell) {
                            let value = cell.getValue()

                            if (value > 0) {
                                return `<span class="badge badge-success">${value} Jam</span>`
                            } else {
                                return `<span class="badge badge-warning">${value} Jam</span>`
                            }
                        }
                    },
                    {
                        title: 'Lembur Libnas 1 Eksternal',
                        field: 'total_overtime_public_holiday_first',
                        headerHozAlign: 'center',
                        hozAlign: 'center',
                        headerSort: false,
                        formatter: function (cell) {
                            let value = cell.getValue()

                            if (value > 0) {
                                return `<span class="badge badge-success">${value} Jam</span>`
                            } else {
                                return `<span class="badge badge-warning">${value} Jam</span>`
                            }
                        }
                    },
                    {
                        title: 'Lembur Libnas 2 Eksternal',
                        field: 'total_overtime_public_holiday_second',
                        headerHozAlign: 'center',
                        hozAlign: 'center',
                        headerSort: false,
                        formatter: function (cell) {
                            let value = cell.getValue()

                            if (value > 0) {
                                return `<span class="badge badge-success">${value} Jam</span>`
                            } else {
                                return `<span class="badge badge-warning">${value} Jam</span>`
                            }
                        }
                    },
                    {
                        title: 'Lembur Libnas 3 Eksternal',
                        field: 'total_overtime_public_holiday_third',
                        headerHozAlign: 'center',
                        hozAlign: 'center',
                        headerSort: false,
                        formatter: function (cell) {
                            let value = cell.getValue()

                            if (value > 0) {
                                return `<span class="badge badge-success">${value} Jam</span>`
                            } else {
                                return `<span class="badge badge-warning">${value} Jam</span>`
                            }
                        }
                    },
                    {
                        title: 'Lembur Libur 1 Eksternal',
                        field: 'total_overtime_day_off_first',
                        headerHozAlign: 'center',
                        hozAlign: 'center',
                        headerSort: false,
                        formatter: function (cell) {
                            let value = cell.getValue()

                            if (value > 0) {
                                return `<span class="badge badge-success">${value} Jam</span>`
                            } else {
                                return `<span class="badge badge-warning">${value} Jam</span>`
                            }
                        }
                    },
                    {
                        title: 'Lembur Libur 2 Eksternal',
                        field: 'total_overtime_day_off_second',
                        headerHozAlign: 'center',
                        hozAlign: 'center',
                        headerSort: false,
                        formatter: function (cell) {
                            let value = cell.getValue()

                            if (value > 0) {
                                return `<span class="badge badge-success">${value} Jam</span>`
                            } else {
                                return `<span class="badge badge-warning">${value} Jam</span>`
                            }
                        }
                    },
                    {
                        title: 'Lembur Libur 3 Eksternal',
                        field: 'total_overtime_day_off_third',
                        headerHozAlign: 'center',
                        hozAlign: 'center',
                        headerSort: false,
                        formatter: function (cell) {
                            let value = cell.getValue()

                            if (value > 0) {
                                return `<span class="badge badge-success">${value} Jam</span>`
                            } else {
                                return `<span class="badge badge-warning">${value} Jam</span>`
                            }
                        }
                    },
                    {
                        title: 'Backup',
                        field: 'total_backup',
                        headerHozAlign: 'center',
                        hozAlign: 'center',
                        headerSort: false,
                        formatter: function (cell) {
                            let value = cell.getValue()

                            if (value > 0) {
                                return `<span class="badge badge-success">${value} Hari</span>`
                            } else {
                                return `<span class="badge badge-warning">${value} Hari</span>`
                            }
                        }
                    },
                    {
                        title: 'Lambat 15 Menit',
                        field: 'total_late_15',
                        headerHozAlign: 'center',
                        hozAlign: 'center',
                        headerSort: false,
                        formatter: function (cell) {
                            let value = cell.getValue()

                            if (value > 0) {
                                return `<span class="badge badge-success">${value} Kali</span>`
                            } else {
                                return `<span class="badge badge-warning">${value} Kali</span>`
                            }
                        }
                    },
                    {
                        title: 'Lambat 30 Menit',
                        field: 'total_late_30',
                        headerHozAlign: 'center',
                        hozAlign: 'center',
                        headerSort: false,
                        formatter: function (cell) {
                            let value = cell.getValue()

                            if (value > 0) {
                                return `<span class="badge badge-success">${value} Kali</span>`
                            } else {
                                return `<span class="badge badge-warning">${value} Kali</span>`
                            }
                        }
                    },
                    {
                        title: 'Lambat 45 Menit',
                        field: 'total_late_45',
                        headerHozAlign: 'center',
                        hozAlign: 'center',
                        headerSort: false,
                        formatter: function (cell) {
                            let value = cell.getValue()

                            if (value > 0) {
                                return `<span class="badge badge-success">${value} Kali</span>`
                            } else {
                                return `<span class="badge badge-warning">${value} Kali</span>`
                            }
                        }
                    },
                    {
                        title: 'Lambat 60 Menit',
                        field: 'total_late_60',
                        headerHozAlign: 'center',
                        hozAlign: 'center',
                        headerSort: false,
                        formatter: function (cell) {
                            let value = cell.getValue()

                            if (value > 0) {
                                return `<span class="badge badge-success">${value} Kali</span>`
                            } else {
                                return `<span class="badge badge-warning">${value} Kali</span>`
                            }
                        }
                    },
                    {
                        title: 'Lambat 75 Menit',
                        field: 'total_late_75',
                        headerHozAlign: 'center',
                        hozAlign: 'center',
                        headerSort: false,
                        formatter: function (cell) {
                            let value = cell.getValue()

                            if (value > 0) {
                                return `<span class="badge badge-success">${value} Kali</span>`
                            } else {
                                return `<span class="badge badge-warning">${value} Kali</span>`
                            }
                        }
                    },
                    {
                        title: 'Lambat 90 Menit',
                        field: 'total_late_90',
                        headerHozAlign: 'center',
                        hozAlign: 'center',
                        headerSort: false,
                        formatter: function (cell) {
                            let value = cell.getValue()

                            if (value > 0) {
                                return `<span class="badge badge-success">${value} Kali</span>`
                            } else {
                                return `<span class="badge badge-warning">${value} Kali</span>`
                            }
                        }
                    },
                    {
                        title: 'Lambat 105 Menit',
                        field: 'total_late_105',
                        headerHozAlign: 'center',
                        hozAlign: 'center',
                        headerSort: false,
                        formatter: function (cell) {
                            let value = cell.getValue()

                            if (value > 0) {
                                return `<span class="badge badge-success">${value} Kali</span>`
                            } else {
                                return `<span class="badge badge-warning">${value} Kali</span>`
                            }
                        }
                    },
                    {
                        title: 'Lambat 120 Menit',
                        field: 'total_late_120',
                        headerHozAlign: 'center',
                        hozAlign: 'center',
                        headerSort: false,
                        formatter: function (cell) {
                            let value = cell.getValue()

                            if (value > 0) {
                                return `<span class="badge badge-success">${value} Kali</span>`
                            } else {
                                return `<span class="badge badge-warning">${value} Kali</span>`
                            }
                        }
                    },
                    {
                        title: 'PLA 15 Menit',
                        field: 'early_check_out_15',
                        headerHozAlign: 'center',
                        hozAlign: 'center',
                        headerSort: false,
                        formatter: function (cell) {
                            let value = cell.getValue()

                            if (value > 0) {
                                return `<span class="badge badge-success">${value} Kali</span>`
                            } else {
                                return `<span class="badge badge-warning">${value} Kali</span>`
                            }
                        }
                    },
                    {
                        title: 'PLA 30 Menit',
                        field: 'early_check_out_30',
                        headerHozAlign: 'center',
                        hozAlign: 'center',
                        headerSort: false,
                        formatter: function (cell) {
                            let value = cell.getValue()

                            if (value > 0) {
                                return `<span class="badge badge-success">${value} Kali</span>`
                            } else {
                                return `<span class="badge badge-warning">${value} Kali</span>`
                            }
                        }
                    },
                    {
                        title: 'PLA 45 Menit',
                        field: 'early_check_out_45',
                        headerHozAlign: 'center',
                        hozAlign: 'center',
                        headerSort: false,
                        formatter: function (cell) {
                            let value = cell.getValue()

                            if (value > 0) {
                                return `<span class="badge badge-success">${value} Kali</span>`
                            } else {
                                return `<span class="badge badge-warning">${value} Kali</span>`
                            }
                        }
                    },
                    {
                        title: 'PLA 60 Menit',
                        field: 'early_check_out_60',
                        headerHozAlign: 'center',
                        hozAlign: 'center',
                        headerSort: false,
                        formatter: function (cell) {
                            let value = cell.getValue()

                            if (value > 0) {
                                return `<span class="badge badge-success">${value} Kali</span>`
                            } else {
                                return `<span class="badge badge-warning">${value} Kali</span>`
                            }
                        }
                    },
                    {
                        title: 'PLA 75 Menit',
                        field: 'early_check_out_75',
                        headerHozAlign: 'center',
                        hozAlign: 'center',
                        headerSort: false,
                        formatter: function (cell) {
                            let value = cell.getValue()

                            if (value > 0) {
                                return `<span class="badge badge-success">${value} Kali</span>`
                            } else {
                                return `<span class="badge badge-warning">${value} Kali</span>`
                            }
                        }
                    },
                    {
                        title: 'PLA 90 Menit',
                        field: 'early_check_out_90',
                        headerHozAlign: 'center',
                        hozAlign: 'center',
                        headerSort: false,
                        formatter: function (cell) {
                            let value = cell.getValue()

                            if (value > 0) {
                                return `<span class="badge badge-success">${value} Kali</span>`
                            } else {
                                return `<span class="badge badge-warning">${value} Kali</span>`
                            }
                        }
                    },
                    {
                        title: 'PLA 105 Menit',
                        field: 'early_check_out_105',
                        headerHozAlign: 'center',
                        hozAlign: 'center',
                        headerSort: false,
                        formatter: function (cell) {
                            let value = cell.getValue()

                            if (value > 0) {
                                return `<span class="badge badge-success">${value} Kali</span>`
                            } else {
                                return `<span class="badge badge-warning">${value} Kali</span>`
                            }
                        }
                    },
                    {
                        title: 'PLA 120 Menit',
                        field: 'early_check_out_120',
                        headerHozAlign: 'center',
                        hozAlign: 'center',
                        headerSort: false,
                        formatter: function (cell) {
                            let value = cell.getValue()

                            if (value > 0) {
                                return `<span class="badge badge-success">${value} Kali</span>`
                            } else {
                                return `<span class="badge badge-warning">${value} Kali</span>`
                            }
                        }
                    },
                    {
                        title: 'Insentif Cuti Bersama',
                        field: 'total_extended_day_off',
                        headerHozAlign: 'center',
                        hozAlign: 'center',
                        headerSort: false,
                        formatter: function (cell) {
                            let value = cell.getValue()

                            if (value > 0) {
                                return `<span class="badge badge-success">${value} Hari</span>`
                            } else {
                                return `<span class="badge badge-warning">${value} Hari</span>`
                            }
                        }
                    },
                ],
                placeholder: 'No Data Available',
                pagination: true,
                paginationMode: "local",
                paginationSize: 10,
                paginationSizeSelector: [10, 20, 50, 100],
                headerFilter: true,
                paginationInitialPage: 1,
            })
        },
        onStartTimer() {
            this.countdown = 0
            this.timerInterval = this.timerId = setInterval(() => {
                this.countdown++;
            }, 1000);
        },
        onKillTimer() {
            clearInterval(this.timerInterval)
            this.timerInterval = null;
            this.countdown = null;
        },
        exportExcel() {
            this.table.download("xlsx", "timesheet-reporting.xlsx", {
                sheetName: "Employees",
                columnGroups: false,
                columnCalcs: false,
            });
        },
        onRefreshReport() {
            this.isOnProcessRefreshData = true
            this.onStartTimer()
            this.$axios.post(`api/v1/admin/timesheet-report/sync/${this.$route.params.id}`)
                .then(() => {
                    this.isOnProcessRefreshData = false
                    useToast().success("Success to refresh timesheet!");
                    this.onKillTimer()
                    this.getTimesheetReportingDetail()
                })
                .catch(error => {
                    this.isOnProcessRefreshData = false
                    this.onKillTimer()
                    useToast().error(error.response.data.message);
                });
        },
        onSendDataToERP() {
            this.isOnprocessSendingData = true
            this.onStartTimer()
            this.$axios.post(`api/v1/admin/timesheet-report/send-to-erp/${this.$route.params.id}`)
                .then(() => {
                    this.isOnprocessSendingData = false
                    useToast().success("Success to send data!");
                    this.onKillTimer()
                    this.getTimesheetReportingDetail()
                })
                .catch(error => {
                    this.isOnprocessSendingData = false
                    this.onKillTimer()
                    useToast().error(error.response.data.message);
                });
        }
    }
}
</script>
