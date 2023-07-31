<template>
    <div class="container-fluid">
        <Breadcrumbs main="Create Overtime Request"/>
        <div class="col-sm-12">
            <form class="card" v-on:submit.prevent="onSubmitForm">
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <label for="name">Select Unit</label>
                            <multiselect
                                v-model="selectedUnit"
                                placeholder="Select Unit"
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
                            <label for="name">Select Job</label>
                            <multiselect
                                v-model="selectedJob"
                                placeholder="Select Job"
                                label="name"
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
                                <label for="status">Jenis Request:</label>
                                <select id="status" name="request-type" class="form-select" v-model="overtime.request_type" required>
                                    <option value="assignment" :selected="overtime.request_type === 'assignment' ? 'selected' : ''">Assignment</option>
                                    <option value="request" :selected="overtime.request_type === 'request' ? 'selected' : ''">Request</option>
                                </select>
                            </div>
                            <div class="mt-2">
                                <label for="name">Tanggal Mulai</label>
                                <input type="date" class="form-control" v-model="overtime.start_date" :min="today" @change="onDateChanged" required>
                            </div>
                            <div class="mt-2">
                                <label for="name">Tanggal Selesai</label>
                                <input type="date" class="form-control" v-model="overtime.end_date" @change="onDateChanged" :min="overtime.start_date" required>
                            </div>
                            <div class="mt-2">
                                <label for="name">Durasi</label>
                                <input type="text" class="form-control" v-model="overtime.duration" disabled required>
                            </div>
                            <div class="mt-2">
                                <label class="form-label">Catatan</label>
                                <textarea class="form-control" v-model="overtime.notes" required></textarea>
                            </div>
                            <div class="mt-2">
                                <label for="name">Foto Berkas</label>
                                <input type="file" class="form-control" id="name" @change="onChangeFile">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="row" v-for="(item, index) in overtime.dates" :key="index">
                                <div class="col-md-3">
                                    <div class="mt-2">
                                        <label for="name">Tanggal</label>
                                        <input type="date" class="form-control" :value="index" disabled required>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="mt-2">
                                        <label for="name">Jam Mulai</label>
                                        <input type="time" class="form-control" v-model="overtime.dates[index].start_time" :disabled="overtime.shift_type === 'Shift'" required>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="mt-2">
                                        <label for="name">Jam Akhir</label>
                                        <input type="time" class="form-control" v-model="overtime.dates[index].end_time" :min="overtime.dates[index].start_time" :disabled="overtime.shift_type === 'Shift'" required>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="mt-4">
                                        <div
                                            :class="'btn btn-primary mt-3' + (overtime.unit_relation_id === null ? ' disabled' : '')"
                                            data-bs-toggle="modal"
                                            data-bs-target="#timesheetModal"
                                            v-if="overtime.shift_type === 'Shift'"
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
                    <div ref="employeeTable"></div>
                    <br/>
                    <button class="btn btn-primary">Simpan</button>
                </div>
            </form>
        </div>
        <div class="modal fade" id="timesheetModal" ref="timesheetModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalCenter" aria-hidden="true">
            <VerticalModal :title="'Select Timesheet for ' + this.selectedDateTimesheet">
                <div class="row">
                    <div class="col-md-12">
                        <div ref="timesheetTable" v-if="overtime.shift_type === 'Shift'"></div>
                    </div>
                </div>
            </VerticalModal>
        </div>
    </div>
</template>

<script>
import L from 'leaflet';
import VerticalModal from "@components/modal/verticalModalWithoutSave.vue";
import { useToast } from "vue-toastification";
import {TabulatorFull as Tabulator} from "tabulator-tables";
import axios from "axios";

export default {
    components: {
        VerticalModal
    },
    data() {
        return {
            overtime: {
                unit_relation_id: null,
                job_id: null,
                start_date: null,
                end_date: null,
                duration: null,
                shift_type: 'Non Shift',
                timesheet_id: 1,
                dates: {},
                employee_ids: [],
                notes: null,
                image_url: null,
                request_type: null
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
                pageSize: 50,
                name: ''
            },
            timeSheetPagination: {
                currentPage: 1,
                pageSize: 10
            },
            employeePagination: {
                currentPage: 1,
                pageSize: 10,
            },
            today: '',
            units: [],
            jobs: []
        }
    },
    mounted() {
        let d = new Date();
        this.today = d.getFullYear() + "-" + ("0" + (d.getMonth() + 1)).slice(-2) + "-" + ("0" + (d.getDate())).slice(-2)

        this.getUnitsData()
    },
    methods: {
        getUnitsData() {
            this.$axios.get(`/api/v1/admin/unit/paginated?per_page=${this.unitPagination.pageSize}&page=${this.unitPagination.currentPage}&name=${this.unitPagination.name}`)
                .then(response => {
                    this.units = response.data.data.data
                    this.unitPagination.onSearch = false
                })
                .catch(error => {
                    console.error(error);
                });
        },
        getJobsData() {
            if (this.overtime.unit_relation_id < 1) {
                return
            }

            this.$axios.get(`/api/v1/admin/job/${this.selectedUnit.id}`)
                .then(response => {
                    this.jobs = response.data.data
                })
                .catch(error => {
                    console.error(error);
                });
        },
        generateEmployeesTable() {
            if (this.overtime.unit_relation_id === null || this.overtime.job_id === null) {
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
                        title: 'Employee Name',
                        field: 'name',
                        headerFilter:"input"
                    },
                    {
                        title: 'Work Email',
                        field: 'work_email'
                    },
                    {
                        title: 'Current Unit',
                        field: 'last_unit.name'
                    },
                    {
                        title: 'Job Name',
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
                    return `${url}?page=${params.page}&per_page=${params.size}&name=${localFilter.name}&last_unit_relation_id=${this.overtime.unit_relation_id}&job_id=${this.overtime.job_id}`
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
                    if (this.overtime.employee_ids.includes(row.getData().id)) {
                        row.select()
                    }
                },
            });
            table.on("rowSelectionChanged", (data, rows, selected, deselected) => {
                if(selected.length > 0) {
                    this.overtime.employee_ids.push(selected[0].getData().id)
                }
                if (deselected.length > 0) {
                    let deselectedID = deselected[0].getData().id
                    this.overtime.employee_ids = this.overtime.employee_ids.filter((val) => {
                        return deselectedID !== val
                    })
                }
            })
        },
        generateTimeSheetTable() {
            if (this.selectedUnit.id == null) {
                return
            }

            const ls = localStorage.getItem('my_app_token')
            const table = new Tabulator(this.$refs.timesheetTable, {
                ajaxURL: `/api/v1/admin/employee-timesheet/${this.selectedUnit.id}`,
                layout: 'fitColumns',
                columns: [
                    {
                        title: 'Timtesheet Name',
                        field: 'name',
                    },
                    {
                        title: 'From',
                        field: 'start_time',
                    },
                    {
                        title: 'To',
                        field: 'end_time',
                    },
                    {
                        title: 'Select',
                        formatter: (cell, formatterParams, onRendered) => {
                            return `<button class="button-icon button-success" data-bs-dismiss="modal" data-id="${cell.getRow().getData().id}"><i class="fa fa-arrow-right"></i> </button>`;
                        },
                        width: 70,
                        hozAlign: 'center',
                        sortable: false,
                        cellClick: (e, cell) => {
                            let data = cell.getRow().getData()
                            this.overtime.dates[this.selectedDateTimesheet] = {
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
                        "X-Unit-Relation-ID": this.$store.state.activeAdminUnit?.unit_relation_id ?? ''
                    },
                },
                ajaxParams: {
                    page: this.timeSheetPagination.currentPage,
                    size: this.timeSheetPagination.pageSize,
                },
                ajaxURLGenerator: (url, config, params) => {
                    let localFilter = {
                        name: ''
                    }
                    params.filter.map((item) => {
                        if (item.field === 'name') localFilter.name = item.value
                    })
                    return `${url}?page=${params.page}&per_page=${params.size}&size=${params.size}&name=${localFilter.name}`
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
        onDateChanged() {
            if (this.overtime.start_date != null && this.overtime.end_date != null) {
                let i = 0
                let data = {}

                for (let d = new Date(this.overtime.start_date); d <= new Date(this.overtime.end_date); d.setDate(d.getDate() + 1)) {
                    i++
                    data[d.getFullYear() + "-" + ("0" + (d.getMonth() + 1)).slice(-2) + "-" + ("0" + (d.getDate())).slice(-2)] = {
                        start_time: null,
                        end_time: null
                    }
                }

                this.overtime.dates = data
                this.overtime.duration = `${i} Hari`
            }
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
        onUnitSelected(val) {
            this.overtime.unit_relation_id = this.selectedUnit.relation_id
            this.jobs = []
            this.overtime.employee_ids = [];
            this.getJobsData()
            this.generateEmployeesTable()
        },
        onJobSelected() {
            this.overtime.job_id = this.selectedJob.id
            this.overtime.employee_ids = [];
            this.generateEmployeesTable()
        },
        onChangeOvertimeType(e) {
            if (e.target.value === 'Shift') {
            }
        },
        onSelectDateSheet(data) {
            this.selectedDateTimesheet = data
        },
        onChangeFile(e) {
            let formData = new FormData()
            formData.set('files[]', e.target.files[0])

            this.$axios.post(`/api/v1/admin/incident/upload-image`, formData)
                .then(response => {
                    this.overtime.image_url = response.data.urls[0]
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
            this.$axios.post(`/api/v1/admin/overtime`, this.overtime)
                .then(response => {
                    useToast().success("Success to create data", { position: 'bottom-right' });
                    this.$router.push({name: 'Overtime', params: {}});
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
        }
    },
};
</script>

<style>
</style>
