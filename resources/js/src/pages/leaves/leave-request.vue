<template>
    <div class="container-fluid">
        <Breadcrumbs main="Izin/Cuti"/>

        <div class="container-fluid">
            <div class="email-wrap bookmark-wrap">
                <div class="row">
                    <div class="col-md-12">
                        <div class="card card-absolute">
                            <div class="card-header bg-primary">
                                <h5>Izin/Cuti</h5>
                            </div>
                            <div class="card-body">
	                            <div class="row">
		                            <div class="col-md-3 mb-3">
			                            <label>Nama Pegawai</label>
			                            <input type="text" placeholder="Cari Nama Pegawai" class="form-control" v-model="filter.employee_name" @change="onSearchEmployeeName">
		                            </div>
		                            <div class="col-md-3 mb-3">
			                            <label>Tanggal</label>
			                            <Datepicker
				                            v-model="filter.leave_date"
				                            :enable-time-picker="false"
				                            range
				                            multi-calendars
				                            auto-apply
				                            @update:model-value="onCheckInDateChange"
			                            >
			                            </Datepicker>
		                            </div>
		                            <div class="col-md-3 mb-3">
			                            <label>Jenis</label>
			                            <multiselect
				                            v-model="selectedType"
				                            :options="types"
				                            :multiple="false"
				                            label="leave_name"
				                            track-by="id"
				                            placeholder="Pilih Jenis Izin/Cuti"
				                            @select="onSelectedType"
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
import Datepicker from '@vuepic/vue-datepicker';

export default {
	components: {
		Datepicker
	},
    data() {
        return {
            table: null,
            filterName: '',
            filterType: '',
            currentPage: 1,
            pageSize: 10,
	        filter: {
				employee_name: '',
		        leave_date: null
	        },
	        selectedType: null,
	        types: [],
	        queryFilter: '',
	        isProcessDownload: false
        }
    },
    mounted() {
		this.getMasterLeave()
        this.initializeLeaveRequest();
    },
    methods: {
	    getMasterLeave() {
		    const ls = localStorage.getItem('USER_ROLES')
		    this.$axios.get(`/api/v1/admin/master_leave`, {
			    headers: {
				    'X-Selected-Role': ls
			    }
		    })
			    .then(response => {
				    this.types = response.data.data.data
			    })
			    .catch(error => {
				    console.error(error);
			    });
	    },
        initializeLeaveRequest() {
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
					let localFilter = {
						employeeName: this.filter.employee_name,
						startDate: '',
						endDate: '',
						leaveTypeID: ''
					}

					if (this.filter.leave_date != null) {
						const startDate = this.filter.leave_date[0]
						const endDate = this.filter.leave_date[1]
						localFilter.startDate = startDate.getFullYear() + "-" + ("0" + (startDate.getMonth() + 1)).slice(-2) + "-" + ("0" + (startDate.getDate())).slice(-2)
						localFilter.endDate = endDate.getFullYear() + "-" + ("0" + (endDate.getMonth() + 1)).slice(-2) + "-" + ("0" + (endDate.getDate())).slice(-2)
					}

					if (this.selectedType != null) {
						localFilter.leaveTypeID = this.selectedType.id
					}

					this.queryFilter = `employee_name=${localFilter.employeeName}&start_date=${localFilter.startDate}&end_date=${localFilter.endDate}&leave_type_id=${localFilter.leaveTypeID}`

                    return `${url}?page=${params.page}&per_page=${params.size}&${this.queryFilter}`
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
                        title: 'Nama',
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
                        title: 'Tanggal',
                        headerHozAlign: 'center',
                        headerSort: false,
                        columns: [
                            {
                                title: 'Tanggal Mulai',
                                field: 'start_date',
                                hozAlign: 'center',
                                headerHozAlign: 'center',
                                headerSort: false,
                                formatter: function (cell) {
                                    return moment(cell.getValue()).format('DD MMMM YYYY')
                                }
                            },
                            {
                                title: 'Tanggal Selesai',
                                field: 'end_date',
                                hozAlign: 'center',
                                headerHozAlign: 'center',
                                headerSort: false,
                                formatter: function (cell) {
                                    return moment(cell.getValue()).format('DD MMMM YYYY')
                                }
                            },
                            {
                                title: 'Total Hari',
                                field: 'days',
                                hozAlign: 'center',
                                headerHozAlign: 'center',
                                headerSort: false,
                            },
                        ]
                    },
                    {
                        title: 'Kategori',
                        field: 'leave_type.leave_name',
                        headerHozAlign: 'center',
                        headerSort: false,
                    },
                    {
                        title: 'Tipe',
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
                                return '<span class="badge badge-success">Diterima</span>'
                            } else if (cell.getValue() === 'rejected') {
                                return '<span class="badge badge-danger">Ditolak</span>'
                            }
                        }
                    },
                    {
                        title: 'Alasan',
                        field: 'reason',
                        hozAlign: 'center',
                        headerHozAlign: 'center',
                        headerSort: false,
                    },
	                {
		                title: 'Catatan',
		                field: 'last_approver',
		                hozAlign: 'center',
		                headerHozAlign: 'center',
		                headerSort: false,
		                formatter: function (cell) {
							let value = cell.getValue()

			                if (value === null || (value != null && (value.notes === '' || value.notes === null))) {
				                return '-'
			                } else {
				                return value.notes
			                }
		                }
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
	    onSearchEmployeeName() {
		    this.table.setFilter('refresh', '=', 'refresh');
	    },
	    onCheckInDateChange() {
		    this.table.setFilter('refresh', '=', 'refresh');
	    },
	    onSelectedType() {
		    this.table.setFilter('refresh', '=', 'refresh');
	    },
	    onResetFilter() {
			this.filter.employee_name = ''
		    this.filter.leave_date = null
		    this.selectedType = null
		    this.table.setFilter('refresh', '=', 'refresh');
	    },
	    downloadFile() {
		    if (this.isProcessDownload) {
			    return
		    }

		    const ls = localStorage.getItem('my_app_token')
		    const role = JSON.parse(localStorage.getItem('USER_ROLES'))
		    fetch(
			    `/api/v1/admin/leave_request/download?${this.queryFilter}`,
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
			    link.setAttribute('download', 'leave.xlsx');
			    document.body.appendChild(link);
			    link.click();
			    document.body.removeChild(link);

			    window.URL.revokeObjectURL(url);
		    })

		    this.isProcessDownload = false
	    }
    }
}
</script>
