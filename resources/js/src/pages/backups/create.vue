<template>
    <div class="container-fluid">
        <Breadcrumbs main="Buat Backup"/>
        <div class="col-sm-12">
            <form class="card">
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-12">
                            <form v-on:submit.prevent="onSubmitForm">
                                <div class="row">
                                    <div class="col-md-6">
                                        <label for="name">Unit Tujuan</label>
                                        <multiselect
                                            v-model="selectedUnit"
                                            placeholder="Pilih Unit Tujuan"
                                            label="name"
                                            track-by="id"
                                            :options="units"
                                            :multiple="false"
                                            :required="true"
                                            @select="onUnitSelected"
                                            @search-change="onUnitSearchName"
                                        >
                                        </multiselect>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="name">Tujuan Pekerjaan</label>
                                        <multiselect
                                            v-model="selectedJob"
                                            placeholder="Pilih Tujuan Pekerjaan"
                                            label="job_name"
                                            track-by="id"
                                            :options="jobs"
                                            :multiple="false"
                                            :required="true"
                                            :disabled="jobs.length === 0"
                                            @select="onJobSelected"
                                        >
                                        </multiselect>
                                    </div>
                                </div>
                                <br/>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mt-2">
                                            <label for="status">Kategori Backup:</label>
                                            <select id="status" name="status" class="form-select" v-model="backup.shift_type" @change="onChangeBackupType" required>
                                                <option value="Shift" :selected="backup.shift_type === 'Shift' ? 'selected' : ''">Shift</option>
                                                <option value="Non Shift" :selected="backup.shift_type === 'Non Shift' ? 'selected' : ''">Non Shift</option>
                                            </select>
                                        </div>
                                        <div class="mt-2">
                                            <label for="status">Jenis Request:</label>
                                            <select id="status" name="request-type" class="form-select" v-model="backup.request_type" required>
                                                <option value="assignment" :selected="backup.request_type === 'assignment' ? 'selected' : ''">Assignment</option>
                                                <option value="request" :selected="backup.request_type === 'request' ? 'selected' : ''">Request</option>
                                            </select>
                                        </div>
                                        <div class="mt-2">
                                            <label for="name">Tanggal Mulai</label>
                                            <input type="date" class="form-control" v-model="backup.start_date" :min="today" @change="onDateChanged" required>
                                        </div>
                                        <div class="mt-2">
                                            <label for="name">Tanggal Selesai</label>
                                            <input type="date" class="form-control" v-model="backup.end_date" @change="onDateChanged" :min="backup.start_date" required>
                                        </div>
                                        <div class="mt-2">
                                            <label for="name">Durasi</label>
                                            <input type="text" class="form-control" v-model="backup.duration" disabled required>
                                        </div>
                                        <div class="mt-2">
                                            <label for="name">Berkas</label>
                                            <input type="file" class="form-control" id="name" @change="onChangeFile">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="row" v-for="(item, index) in backup.dates" :key="index">
                                            <div class="col-md-3">
                                                <div class="mt-2">
                                                    <label for="name">Tanggal</label>
                                                    <input type="date" class="form-control" :value="index" disabled required>
                                                </div>
                                            </div>
                                            <div class="col-md-3">
                                                <div class="mt-2">
                                                    <label for="name">Jam Mulai</label>
                                                    <input type="time" class="form-control" v-model="backup.dates[index].start_time" disabled required>
                                                </div>
                                            </div>
                                            <div class="col-md-3">
                                                <div class="mt-2">
                                                    <label for="name">Jam Akhir</label>
                                                    <input type="time" class="form-control" v-model="backup.dates[index].end_time" disabled required>
                                                </div>
                                            </div>
                                            <div class="col-md-3">
                                                <div class="mt-4">
                                                    <div
                                                        :class="'btn btn-primary mt-3' + (backup.unit_relation_id === null ? ' disabled' : '')"
                                                        data-bs-toggle="modal"
                                                        data-bs-target="#timesheetModal"
                                                        @click="onSelectDateSheet(index)"
                                                    >
                                                        Pilih Timesheet
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <br/>
                                <br/>
                                <div class="row">
                                    <div class="col-md-6">
                                        <label for="name">Unit Pegawai</label>
                                        <multiselect
                                            v-model="selectedEmployeeUnit"
                                            placeholder="Pilih Unit Pegawai"
                                            label="name"
                                            track-by="id"
                                            :options="employeeUnits"
                                            :multiple="false"
                                            :required="true"
                                            @select="onEmployeeUnitSelected"
                                            @search-change="onEmployeeUnitSearchName"
                                        >
                                        </multiselect>
                                    </div>
                                    <div class="col-md-6">
                                    </div>
                                </div>
                                <br/>
                                <div ref="employeeTable"></div>
                                <br/>
                                <button class="btn btn-secondary" @click="$router.go(-1)"><i data-action="view" class="fa fa-arrow-left"></i> Kembali</button>&nbsp;
                                <button class="btn btn-primary" :disabled="this.isOnProcess">
                                    <span v-if="this.isOnProcess">
                                        ...
                                    </span>
                                    <span v-else>
                                        <i data-action="view" class="fa fa-save"></i> Simpan
                                    </span>
                                </button> &nbsp;
                            </form>
                        </div>
                    </div>
                </div>
                <div class="card-footer text-end">
                </div>
            </form>
        </div>
        <div class="modal fade" id="timesheetModal" ref="timesheetModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalCenter" aria-hidden="true">
            <VerticalModal :title="'Select Timesheet for ' + this.selectedDateTimesheet">
                <div class="row">
                    <div class="col-md-12">
                        <div ref="timesheetTable"></div>
                    </div>
                </div>
            </VerticalModal>
        </div>
    </div>
</template>

<script>
import VerticalModal from "@components/modal/verticalModalWithoutSave.vue";
import {TabulatorFull as Tabulator} from "tabulator-tables";
import {useToast} from "vue-toastification";

export default {
    components: {
        VerticalModal
    },
    data() {
        return {
            isOnProcess: false,
            backup: {
                unit_relation_id:null,
                start_date: null,
                end_date: null,
                job_id:null,
                shift_type: 'Shift',
                timesheet_id:1,
                duration: null,
                dates: {},
                employee_ids: [],
                file_url: null,
                request_type: 'request',
                requestor_unit_id: 0
            },
            selectedDateTimesheet: null,
            selectedJob: {
                id: '',
                odoo_id: ''
            },
            selectedUnit: {
                id: null,
                relation_id: null
            },
            selectedEmployeeUnit: {
                id: null,
                relation_id: null
            },
            selectedTimesheet: {
                id: null,
                start_time: null,
                end_time: null,
                is_active: null,
                name: null,
                unit_id: null
            },
            unitPagination: {
                currentPage: 1,
                pageSize: 30,
                name: '',
                onSearch: true
            },
            employeeUnitPagination: {
                currentPage: 1,
                pageSize: 30,
                name: '',
                onSearch: true
            },
            timeSheetPagination: {
                currentPage: 1,
                pageSize: 10
            },
            employeePagination: {
                currentPage: 1,
                pageSize: 10
            },
            units: [],
            jobs: [],
            employeeUnits: [],
            today: ''
        }
    },
    mounted() {
        let d = new Date();
        this.today = d.getFullYear() + "-" + ("0" + (d.getMonth() + 1)).slice(-2) + "-" + ("0" + (d.getDate())).slice(-2)

        this.getUnitsData()
        this.getEmployeeUnitsData()
    },
    methods: {
        getUnitsData() {
            this.$axios.get(`/api/v1/admin/unit/paginated?per_page=${this.unitPagination.pageSize}&page=${this.unitPagination.currentPage}&name=${this.unitPagination.name}&unit_level=4,5,6,7`)
                .then(response => {
                    this.units = response.data.data.data
                    this.unitPagination.onSearch = false
                })
                .catch(error => {
                    console.error(error);
                });
        },
        getEmployeeUnitsData() {
            this.$axios.get(`/api/v1/admin/unit/paginated?per_page=${this.employeeUnitPagination.pageSize}&page=${this.employeeUnitPagination.currentPage}&name=${this.employeeUnitPagination.name}`)
                .then(response => {
                    this.employeeUnits = response.data.data.data
                    this.employeeUnitPagination.onSearch = false
                })
                .catch(error => {
                    console.error(error);
                });
        },
        getJobsData() {
            if (this.backup.unit_relation_id < 1) {
                return
            }

            this.$axios.get(`/api/v1/admin/unit-job?unit_relation_id=${this.selectedUnit.relation_id}&append=job_name&is_corporate_job=1`)
                .then(response => {
                    this.jobs = response.data.data
                })
                .catch(error => {
                    console.error(error);
                });
        },
        onUnitSearchName(val) {
            this.unitPagination.name = val

            if (!this.unitPagination.onSearch) {
                this.unitPagination.onSearch = true
                setTimeout(() => {
                    this.getUnitsData()
                }, 1000)
            }
        },
        onEmployeeUnitSearchName(val) {
            this.employeeUnitPagination.name = val

            if (!this.employeeUnitPagination.onSearch) {
                this.employeeUnitPagination.onSearch = true
                setTimeout(() => {
                    this.getEmployeeUnitsData()
                }, 1000)
            }
        },
        onUnitSelected(val) {
            this.backup.unit_relation_id = this.selectedUnit.relation_id
	        this.selectedJob = null
            this.jobs = []
            this.getJobsData()
        },
        onEmployeeUnitSelected(val) {
            this.backup.requestor_unit_id = this.selectedEmployeeUnit.id
            this.generateEmployeesTable()
        },
        onJobSelected() {
            this.backup.job_id = this.selectedJob.job.id
        },
        generateTimeSheetTable() {
            const ls = localStorage.getItem('my_app_token')
            const table = new Tabulator(this.$refs.timesheetTable, {
                ajaxURL: `/api/v1/admin/employee-timesheet/${this.selectedUnit.id}`,
                layout: 'fitData',
                columns: [
	                {
		                title: 'Nama',
		                field: 'formatted_name',
	                },
                    {
                        title: 'Mulai',
                        field: 'start_time',
                    },
                    {
                        title: 'Selesai',
                        field: 'end_time',
                    },
                    {
                        title: 'Pilih',
                        formatter: (cell, formatterParams, onRendered) => {
                            return `<button class="button-icon button-success" data-bs-dismiss="modal" data-id="${cell.getRow().getData().id}"><i class="fa fa-arrow-right"></i> </button>`;
                        },
                        width: 70,
                        hozAlign: 'center',
                        sortable: false,
                        cellClick: (e, cell) => {
                            let data = cell.getRow().getData()
                            this.backup.dates[this.selectedDateTimesheet] = {
                                start_time: data.start_time,
                                end_time: data.end_time
                            }
                        }
                    }
                ],
                selectable: 1,
                pagination: true,
                paginationMode: 'remote',
                responsiveLayout: true,
                filterMode:"remote",
                paginationSize: this.timeSheetPagination.pageSize,
                ajaxConfig: {
                    headers: {
                        Authorization: `Bearer ${ls}`,
                        "X-Unit-Relation-ID": this.$store.state.activeAdminUnit?.unit_relation_id ?? '',
                        "X-Selected-Role": this.$store.state.currentRole,
                    },
                },
                ajaxParams: {
                    page: this.timeSheetPagination.currentPage,
                    size: this.timeSheetPagination.pageSize,
                },
                ajaxURLGenerator: (url, config, params) => {
                    let localFilter = {
                        name: '',
                        shift_type: 'non_shift'
                    }

                    if (this.backup.shift_type === 'Shift') {
                        localFilter.shift_type = "shift"
                    }

                    params.filter.map((item) => {
                        if (item.field === 'name') localFilter.name = item.value
                    })
                    return `${url}?page=${params.page}&per_page=${params.size}&size=${params.size}&is_with_corporate=1&name=${localFilter.name}&shift_type=${localFilter.shift_type}`
                },
                ajaxResponse: (url, params, response) => {
                    return {
                        data: this.processData(response.data.data),
                        last_page: response.data.last_page,
                    }
                },
                paginationSizeSelector: [100],
                headerFilter: true,
                rowFormatter: (row) => {
                },
            });
            table.on("rowSelectionChanged", (data, rows, selected, deselected) => {
                if(selected.length > 0) {
                    this.selectedTimesheet = selected[0].getData()
                }
                if (selected.length === 0 && deselected.length > 0) {
                    this.selectedTimesheet = {
                        id: null,
                        start_time: null,
                        end_time: null,
                        is_active: null,
                        name: null,
                        unit_id: null
                    }
                }
            })
        },
        processData(datas) {
            if(this.selectedDateTimesheet === null) {
                return
            }

            console.log(this.selectedDateTimesheet)

            let daysName = ["Sunday", "Monday", "Tuesday", "Wednesday", "Thursday", "Friday", "Saturday"]
            let currDate = new Date(this.selectedDateTimesheet)
            let currDay = daysName[currDate.getDay()]

            let respondedData = [];

            datas.forEach((value) => {
                if (value.start_time === null && value.end_time === null) {
                    value.timesheet_days.forEach((day) => {
                        if (day.day === currDay) {
                            respondedData.push({
                                timesheet_name: value.name,
                                start_time: day.start_time,
                                end_time: day.end_time,
	                            formatted_name: value.formatted_name
                            })
                        }
                    })
                } else {
                    respondedData.push({
                        timesheet_name: value.name,
                        start_time: value.start_time,
                        end_time: value.end_time,
	                    formatted_name: value.formatted_name
                    })
                }
            })

            return respondedData
        },
        generateEmployeesTable() {
            if (this.selectedEmployeeUnit.relation_id === null) {
                return
            }

            const ls = localStorage.getItem('my_app_token')
            const table = new Tabulator(this.$refs.employeeTable, {
                ajaxURL: '/api/v1/admin/employee/paginated',
                layout: 'fitColumns',
                columns: [
                    {
                        formatter: "rowSelection",
                        hozAlign: "center",
                        width: 10,
                        headerSort: false,
                        titleFormatterParams: {
                            rowRange: "active"
                        },
                        cellClick: function (e, cell) {
                            cell.getRow()
                        },
                    },
                    {
                        title: 'Nama',
                        field: 'name',
                        headerFilter:"input"
                    },
                    {
                        title: 'Email',
                        field: 'work_email'
                    },
                    {
                        title: 'Unit',
                        field: 'last_unit.name'
                    },
                    {
                        title: 'Pekerjaan',
                        field: 'job.name'
                    }
                ],
                pagination: true,
                paginationMode: 'remote',
                responsiveLayout: true,
                filterMode:"remote",
                paginationSize: this.employeePagination.pageSize,
                ajaxConfig: {
                    headers: {
                        Authorization: `Bearer ${ls}`,
                        "X-Unit-Relation-ID": this.$store.state.activeAdminUnit?.unit_relation_id ?? ''
                    },
                },
                ajaxParams: {
                    page: this.employeePagination.currentPage,
                    size: this.employeePagination.pageSize,
                },
                ajaxURLGenerator: (url, config, params) => {
                    let localFilter = {
                        name: ''
                    }
                    params.filter.map((item) => {
                        if (item.field === 'name') localFilter.name = item.value
                    })
                    return `${url}?page=${params.page}&per_page=${params.size}&name=${localFilter.name}&last_unit_relation_id=${this.selectedEmployeeUnit.relation_id}`
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
                    if (this.backup.employee_ids.includes(row.getData().id)) {
                        row.select()
                    }
                },
            });
            table.on("rowSelectionChanged", (data, rows, selected, deselected) => {
                if(selected.length > 0) {
                    this.backup.employee_ids.push(selected[0].getData().id)
                }
                if (deselected.length > 0) {
                    let deselectedID = deselected[0].getData().id
                    this.backup.employee_ids = this.backup.employee_ids.filter((val) => {
                        return deselectedID !== val
                    })
                }
            })
        },
        onChangeBackupType(e) {
        },
        onDateChanged() {
            if (this.backup.start_date != null && this.backup.end_date != null) {
                let i = 0
                let data = {}

                for (let d = new Date(this.backup.start_date); d <= new Date(this.backup.end_date); d.setDate(d.getDate() + 1)) {
                    i++
                    data[d.getFullYear() + "-" + ("0" + (d.getMonth() + 1)).slice(-2) + "-" + ("0" + (d.getDate())).slice(-2)] = {
                        start_time: null,
                        end_time: null
                    }
                }

                this.backup.dates = data
                this.backup.duration = `${i} Hari`
            }
        },
        onSelectDateSheet(data) {
            this.selectedDateTimesheet = data
            this.generateTimeSheetTable()
        },
        onChangeFile(e) {
            let formData = new FormData()
            formData.set('files[]', e.target.files[0])

            this.$axios.post(`/api/v1/admin/incident/upload-image`, formData)
                .then(response => {
                    this.backup.file_url = response.data.urls[0]
                })
                .catch(error => {
                    if(error.response.data.message instanceof Object) {
                        for (const key in error.response.data.message) {
                            useToast().error(error.response.data.message[key][0], { position: 'bottom-right' });
                        }
                    } else {
                        useToast().error(error.response.data.message , { position: 'bottom-right' });
                    }
                });
        },
        onSubmitForm() {
            this.$swal({
                icon: 'warning',
                title:"Apakah anda ingin membuat backup?",
                showCancelButton: true,
                confirmButtonText: 'Ya!',
                confirmButtonColor: '#126850',
                cancelButtonText: 'Batal',
                cancelButtonColor: '#f64545',
            }).then((result)=>{
                if(result.isConfirmed){
                    this.isOnProcess = true
                    this.$axios.post(`/api/v1/admin/backup/create`, this.backup)
                        .then(response => {
                            useToast().success("Success to create data", { position: 'bottom-right' });
                            this.isOnProcess = false;
                            this.$router.push({name: 'Backup', params: {}});
                        })
                        .catch(error => {
                            this.isOnProcess = false;
                            if(error.response.data.message instanceof Object) {
                                for (const key in error.response.data.message) {
                                    useToast().error(error.response.data.message[key][0], { position: 'bottom-right' });
                                }
                            } else {
                                useToast().error(error.response.data.message , { position: 'bottom-right' });
                            }
                        });
                }
            });
        }
    }
};
</script>

<style>
</style>
