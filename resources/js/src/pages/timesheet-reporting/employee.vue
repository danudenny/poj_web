<template>
    <div class="container-fluid">
        <Breadcrumbs main="Timesheet Reporting Saya"/>

        <div class="container-fluid">
            <div class="email-wrap bookmark-wrap">
                <div class="row">
                    <div class="col-md-12">
                        <div class="card card-absolute">
                            <div class="card-header bg-primary">
                                <h5>Timesheet Reporting Saya</h5>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-4 mb-3">
                                        <label>Bulan - Tahun :</label>
                                        <Datepicker
                                            :model-value="selectedMonth"
                                            :enable-time-picker="false"
                                            month-picker
                                            auto-apply
                                            @update:model-value="onMonthSelected"
                                        >
                                        </Datepicker>
                                    </div>
                                </div>

                                <hr/>
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
import Datepicker from '@vuepic/vue-datepicker';

export default {
    components: {
        VerticalModal,
        Modal,
        Datepicker
    },
    data() {
        return {
            pagination: {
                currentPage: 1,
                pageSize: 10
            },
            reportingDetails: [],
            isLoadingReportingDetails: true,
            table: null,
            selectedMonth: {
                month: new Date().getMonth(),
                year: new Date().getFullYear()
            }
        }
    },
    mounted() {
        this.getTimesheetReportingDetails()
    },
    methods: {
        getTimesheetReportingDetails() {
            this.isLoadingReportingDetails = true
            let monthYear = this.selectedMonth.year + "-" + ("0" + (this.selectedMonth.month + 1)).slice(-2);
            this.reportingDetails = []

            this.generateTimesheetReportingTable()
            this.$axios.get(`/api/v1/admin/timesheet-report/employee-auto-report?month=${monthYear}`)
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
            this.table = new Tabulator(this.$refs.listTimesheetReporting ,{
                data: this.reportingDetails,
                layout: 'fitData',
                renderHorizontal:"virtual",
                height: '100%',
                columns: [
                    {
                        title: 'Name',
                        field: 'employee_name',
                        hozAlign: 'center',
                        headerHozAlign: 'center',
                        headerSort: false,
                        width: 200,
                        frozen: true
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
        onMonthSelected(val) {
            this.selectedMonth = val
            this.getTimesheetReportingDetails()
        }
    }
}
</script>
