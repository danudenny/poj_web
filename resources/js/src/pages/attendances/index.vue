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
	                        <div class="row">
		                        <div class="col-md-3 mb-3">
			                        <label>Nama Pegawai</label>
			                        <input type="text" placeholder="Cari Nama Pegawai" class="form-control" v-model="filter.employee_name" @change="onSearchEmployeeName">
		                        </div>
		                        <div class="col-md-3">
			                        <label>Check In</label>
			                        <Datepicker
				                        v-model="filter.check_in_date"
				                        :enable-time-picker="false"
				                        range
				                        multi-calendars
				                        auto-apply
				                        @update:model-value="onCheckInDateChange"
			                        >
			                        </Datepicker>
		                        </div>
		                        <div class="col-md-3 mb-3">
			                        <label>Check Out</label>
			                        <Datepicker
				                        v-model="filter.check_out_date"
				                        :enable-time-picker="false"
				                        range
				                        multi-calendars
				                        auto-apply
				                        @update:model-value="onCheckOutDateChange"
			                        >
			                        </Datepicker>
		                        </div>
		                        <div class="col-md-3 mb-3" v-if="false">
			                        <label>Unit Kerja</label>
			                        <multiselect
				                        v-model="selectedWorkingUnit"
				                        :options="workingUnits"
				                        :multiple="false"
				                        label="formatted_name"
				                        track-by="relation_id"
				                        placeholder="Pilih Unit Kerja"
				                        @search-change="onWorkingUnitSearchName"
				                        @select="onSelectedWorkingUnit"
			                        ></multiselect>
		                        </div>
		                        <div class="col-md-3 mb-3">
			                        <label>Tipe Kehadiran</label>
			                        <multiselect
				                        v-model="selectedAttendanceType"
				                        :options="attendanceTypes"
				                        :multiple="false"
				                        label="display_name"
				                        track-by="value"
				                        placeholder="Pilih Jenis Kehadiran"
				                        @select="onSelectAttendanceType"
			                        ></multiselect>
		                        </div>
	                        </div>
                            <div class="d-flex justify-content-end mb-2">
	                            <button class="btn btn-danger" type="button" @click="this.onResetFilter">
		                            <i class="fa fa-filter" /> &nbsp;Reset Filter
	                            </button> &nbsp;
                                <button :disabled="this.isProcessDownload" class="btn btn-success" type="button" @click="this.downloadFile">
                                    <div v-if="!this.isProcessDownload">
                                        <i class="fa fa-file" /> &nbsp;Download
                                    </div>
                                    <div v-else>
                                        ...
                                    </div>
                                </button>
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
import Datepicker from '@vuepic/vue-datepicker';
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
                location: "",

	            employee_name: '',
	            check_in_date: null,
	            check_out_date: null
            },
            date: null,
            attendances: [],
            formattedCheckIn: "",
            formattedCheckOut: "",
            table: null,
            loading: false,
            currentPage: 1,
            pageSize: 10,
            isProcessDownload: false,
            query: null,
	        isOnSearch: false,
	        workingUnitPagination: {
		        name: '',
		        pageSize: 50,
		        isOnSearch: false
	        },
	        selectedWorkingUnit: null,
	        workingUnits: [],
	        selectedAttendanceType: null,
	        attendanceTypes: [
		        {
					'display_name': 'Normal',
			        'value': 'normal',
		        },
		        {
			        'display_name': 'Backup',
			        'value': 'backup',
		        },
		        {
			        'display_name': 'Lembur',
			        'value': 'overtime',
		        },
	        ]
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
	    getWorkingUnitsData() {
		    const ls = localStorage.getItem('USER_ROLES')
		    this.$axios.get(`/api/v1/admin/unit/paginated?per_page=${this.workingUnitPagination.pageSize}&page=1&name=${this.workingUnitPagination.name}`, {
			    headers: {
				    'X-Selected-Role': ls
			    }
		    })
			    .then(response => {
				    this.workingUnits = response.data.data.data
				    this.workingUnitPagination.isOnSearch = false
			    })
			    .catch(error => {
				    console.error(error);
			    });
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
                ajaxResponse: (url, params, response) => {
					this.isOnSearch = false
                    return {
                        data: response.data.data,
                        last_page: response.data.last_page,
                    }
                },
                ajaxURLGenerator: (url, config, params) => {
                    let localFilter = {
                        name: '',
	                    startCheckInDate: '',
	                    endCheckInDate: '',
	                    startCheckOutDate: '',
	                    endCheckOutDate: '',
	                    attendanceType: '',
                    }

	                localFilter.name = this.filter.employee_name

	                if (this.filter.check_out_date != null) {
		                const startDate = this.filter.check_out_date[0]
		                const endDate = this.filter.check_out_date[1]
		                localFilter.startCheckOutDate = startDate.getFullYear() + "-" + ("0" + (startDate.getMonth() + 1)).slice(-2) + "-" + ("0" + (startDate.getDate())).slice(-2)
		                localFilter.endCheckOutDate = endDate.getFullYear() + "-" + ("0" + (endDate.getMonth() + 1)).slice(-2) + "-" + ("0" + (endDate.getDate())).slice(-2)
	                }

	                if (this.filter.check_in_date != null) {
		                const startDate = this.filter.check_in_date[0]
		                const endDate = this.filter.check_in_date[1]
		                localFilter.startCheckInDate = startDate.getFullYear() + "-" + ("0" + (startDate.getMonth() + 1)).slice(-2) + "-" + ("0" + (startDate.getDate())).slice(-2)
		                localFilter.endCheckInDate = endDate.getFullYear() + "-" + ("0" + (endDate.getMonth() + 1)).slice(-2) + "-" + ("0" + (endDate.getDate())).slice(-2)
	                }

					if (this.selectedAttendanceType != null) {
						localFilter.attendanceType = this.selectedAttendanceType.value
					}

                    this.query = `page=${params.page}&per_page=${params.size}&name=${localFilter.name}&start_check_in_date=${localFilter.startCheckInDate}&end_check_in_date=${localFilter.endCheckInDate}&start_check_out_date=${localFilter.startCheckOutDate}&end_check_out_date=${localFilter.endCheckOutDate}&attendance_type=${localFilter.attendanceType}`
                    return `${url}?` + this.query
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
                    },
                    {
                        title: 'Check In',
                        field: 'check_in_time_with_client_timezone',
                    },
                    {
                        title: 'Check Out',
                        field: 'check_out_time_with_client_timezone',
                    },
                    {
                        title: 'Approved',
                        field: 'approved',
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
        downloadFile() {
            if (this.isProcessDownload) {
                return
            }

            const ls = localStorage.getItem('my_app_token')
            const role = JSON.parse(localStorage.getItem('USER_ROLES'))
            fetch(
                `/api/v1/admin/attendance/export-list-attendance?${this.query}`,
                {
                    headers: {
                        Authorization: `Bearer ${ls}`,
                        "X-Unit-Relation-ID": this.$store.state.activeAdminUnit?.unit_relation_id ?? '',
                        "X-Selected-Role": role
                    },
                },
            ).then(async (response) => {
                const blob = await response.blob();
                const url = window.URL.createObjectURL(blob);
                const link = document.createElement('a');
                link.href = url;
                link.setAttribute('download', 'attendance.xlsx');
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);

                window.URL.revokeObjectURL(url);
            })

            this.isProcessDownload = false
        },
	    onWorkingUnitSearchName(val) {
		    this.workingUnitPagination.name = val

		    if (!this.workingUnitPagination.isOnSearch) {
			    this.workingUnitPagination.isOnSearch = true
			    setTimeout(() => {
				    this.getWorkingUnitsData()
			    }, 1000)
		    }
	    },
	    onSearchEmployeeName() {
		    if (!this.isOnSearch) {
				this.isOnSearch = true

			    setTimeout(() => {
				    this.table.setFilter('refresh', '=', 'refresh');
			    }, 1000)
		    }
	    },
	    onCheckInDateChange() {
		    this.table.setFilter('refresh', '=', 'refresh');
	    },
	    onCheckOutDateChange() {
		    this.table.setFilter('refresh', '=', 'refresh');
	    },
	    onSelectAttendanceType() {
		    this.table.setFilter('refresh', '=', 'refresh');
	    },
	    onSelectedWorkingUnit() {
		    console.log(this.selectedWorkingUnit)
	    },
		onResetFilter() {
			this.filter.employee_name = ''
			this.filter.check_in_date = null
			this.filter.check_out_date = null
			this.selectedAttendanceType = null
			this.table.setFilter('refresh', '=', 'refresh');
		}
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
