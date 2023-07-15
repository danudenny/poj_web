<template>
    <div class="container-fluid">
        <Breadcrumbs main="Detail Incident Reporting"/>
        <div class="col-sm-12">
            <form class="card">
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-12">
                            <form v-on:submit.prevent="onSubmitForm">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div ref="unitsTable"></div>
                                    </div>
                                    <div class="col-md-6">
                                        <div ref="jobsTable"></div>
                                    </div>
                                </div>
                                <br/>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mt-2">
                                            <label for="name">Tanggal Mulai</label>
                                            <input type="date" class="form-control" v-model="backup.start_date" required>
                                        </div>
                                        <div class="mt-2">
                                            <label for="name">Tanggal Selesai</label>
                                            <input type="date" class="form-control" v-model="backup.end_date" required>
                                        </div>
                                        <div class="mt-2">
                                            <label for="status">Kategori Backup:</label>
                                            <select id="status" name="status" class="form-select" v-model="backup.shift_type" @change="onChangeBackupType" required>
                                                <option value="Shift" :selected="backup.shift_type === 'Shift' ? 'selected' : ''">Shift</option>
                                                <option value="Non Shift" :selected="backup.shift_type === 'Non Shift' ? 'selected' : ''">Non Shift</option>
                                            </select>
                                        </div>
                                        <div class="mt-2">
                                            <label for="name">Durasi</label>
                                            <input type="number" class="form-control" v-model="backup.duration" required>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div ref="timesheetTable" v-if="backup.shift_type === 'Shift'"></div>
                                    </div>
                                </div>
                                <div ref="employeeTable"></div>
                                <br/>
                                <button class="btn btn-primary">Simpan</button>
                            </form>
                        </div>
                    </div>
                </div>
                <div class="card-footer text-end">
                </div>
            </form>
        </div>
    </div>
</template>

<script>
import VerticalModal from "@components/modal/verticalModal.vue";
import {TabulatorFull as Tabulator} from "tabulator-tables";
import {useToast} from "vue-toastification";

export default {
    components: {
        VerticalModal
    },
    data() {
        return {
            backup: {
                unit_id: '',
                job_id: '',
                assignee_id: null,
                shift_type: 'Shift',
                timesheet_id: null,
                start_date: null,
                end_date: null,
                duration: null
            },
            selectedJob: {
                id: '',
                odoo_id: ''
            },
            selectedUnit: {
                id: '',
                odoo_id: ''
            },
            unitPagination: {
                currentPage: 1,
                pageSize: 10
            },
            jobPagination: {
                currentPage: 1,
                pageSize: 10
            },
            timeSheetPagination: {
                currentPage: 1,
                pageSize: 10
            },
            employeePagination: {
                currentPage: 1,
                pageSize: 10
            }
        }
    },
    mounted() {
        this.generateUnitsTable()
        this.generateJobsTable()
        this.generateTimeSheetTable()
        this.generateEmployeesTable()
    },
    methods: {
        generateUnitsTable() {
            const ls = localStorage.getItem('my_app_token')
            const table = new Tabulator(this.$refs.unitsTable, {
                ajaxURL: '/api/v1/admin/unit/paginated',
                layout: 'fitColumns',
                columns: [
                    {
                        formatter: "rowSelection",
                        titleFormatter: "rowSelection",
                        hozAlign: "center",
                        headerSort: false,
                        width: 10,
                        titleFormatterParams: {
                            rowRange: "active"
                        },
                        cellClick: function (e, cell) {
                            cell.getRow()
                        },
                    },
                    {
                        title: 'Unit Name',
                        field: 'name',
                        headerFilter:"input"
                    }
                ],
                selectable: 1,
                pagination: true,
                paginationMode: 'remote',
                responsiveLayout: true,
                filterMode:"remote",
                paginationSize: this.unitPagination.pageSize,
                ajaxConfig: {
                    headers: {
                        Authorization: `Bearer ${ls}`,
                    },
                },
                ajaxParams: {
                    page: this.unitPagination.currentPage,
                    size: this.unitPagination.pageSize,
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
                    if (this.selectedUnit.id === row.getData().id) {
                        row.select()
                    }
                },
            });
            table.on("rowSelectionChanged", (data, rows, selected, deselected) => {
                if(selected.length > 0) {
                    this.selectedUnit = {
                        id: selected[0].getData().id,
                        odoo_id: selected[0].getData().relation_id
                    }
                }
                if (selected.length === 0 && deselected.length > 0) {
                    this.selectedUnit = {
                        id: '',
                        odoo_id: ''
                    }
                }
                this.generateEmployeesTable()
            })
        },
        generateJobsTable() {
            const ls = localStorage.getItem('my_app_token')
            const table = new Tabulator(this.$refs.jobsTable, {
                ajaxURL: '/api/v1/admin/job',
                layout: 'fitColumns',
                columns: [
                    {
                        formatter: "rowSelection",
                        titleFormatter: "rowSelection",
                        hozAlign: "center",
                        headerSort: false,
                        width: 10,
                        titleFormatterParams: {
                            rowRange: "active"
                        },
                        cellClick: function (e, cell) {
                            cell.getRow()
                        },
                    },
                    {
                        title: 'Job Name',
                        field: 'name',
                        headerFilter:"input"
                    }
                ],
                selectable: 1,
                pagination: true,
                paginationMode: 'remote',
                responsiveLayout: true,
                filterMode:"remote",
                paginationSize: this.jobPagination.pageSize,
                ajaxConfig: {
                    headers: {
                        Authorization: `Bearer ${ls}`,
                    },
                },
                ajaxParams: {
                    page: this.jobPagination.currentPage,
                    size: this.jobPagination.pageSize,
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
                    if (this.selectedJob.id === row.getData().id) {
                        row.select()
                    }
                },
            });
            table.on("rowSelectionChanged", (data, rows, selected, deselected) => {
                if(selected.length > 0) {
                    this.selectedJob = {
                        id: selected[0].getData().id,
                        odoo_id: selected[0].getData().odoo_job_id
                    }
                }
                if (selected.length === 0 && deselected.length > 0) {
                    this.selectedJob = {
                        id: '',
                        odoo_id: ''
                    }
                }

                this.generateEmployeesTable()
            })
        },
        generateTimeSheetTable() {
            const ls = localStorage.getItem('my_app_token')
            const table = new Tabulator(this.$refs.timesheetTable, {
                ajaxURL: '/api/v1/admin/employee-timesheet',
                layout: 'fitColumns',
                columns: [
                    {
                        formatter: "rowSelection",
                        titleFormatter: "rowSelection",
                        hozAlign: "center",
                        headerSort: false,
                        width: 10,
                        titleFormatterParams: {
                            rowRange: "active"
                        },
                        cellClick: function (e, cell) {
                            cell.getRow()
                        },
                    },
                    {
                        title: 'Timtesheet Name',
                        field: 'name',
                    },
                    {
                        title: 'From',
                        field: 'name',
                    },
                    {
                        title: 'To',
                        field: 'name',
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
                    if (this.backup.timesheet_id !== null && (this.backup.timesheet_id === row.getData().id)) {
                        row.select()
                    }
                },
            });
            table.on("rowSelectionChanged", (data, rows, selected, deselected) => {
                if(selected.length > 0) {
                    this.backup.timesheet_id = selected[0].getData().id
                }
                if (selected.length === 0 && deselected.length > 0) {
                    this.backup.timesheet_id = ''
                }
            })
        },
        generateEmployeesTable() {
            const ls = localStorage.getItem('my_app_token')
            const table = new Tabulator(this.$refs.employeeTable, {
                ajaxURL: '/api/v1/admin/employee',
                layout: 'fitColumns',
                columns: [
                    {
                        formatter: "rowSelection",
                        titleFormatter: "rowSelection",
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
                        title: 'Job Name',
                        field: 'job.name'
                    }
                ],
                selectable: 1,
                pagination: true,
                paginationMode: 'remote',
                responsiveLayout: true,
                filterMode:"remote",
                paginationSize: this.employeePagination.pageSize,
                ajaxConfig: {
                    headers: {
                        Authorization: `Bearer ${ls}`,
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
                    return `${url}?page=${params.page}&size=${params.size}&name=${localFilter.name}&unit_id=${this.selectedUnit.odoo_id}&job_id=${this.selectedJob.odoo_id}`
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
                    if (this.backup.assignee_id !== null && (this.backup.assignee_id === row.getData().id)) {
                        row.select()
                    }
                },
            });
            table.on("rowSelectionChanged", (data, rows, selected, deselected) => {
                if(selected.length > 0) {
                    this.backup.assignee_id = selected[0].getData().id
                }
                if (selected.length === 0 && deselected.length > 0) {
                    this.backup.assignee_id = ''
                }
            })
        },
        onChangeBackupType(e) {
            if (e.target.value === 'Shift') {
                this.generateTimeSheetTable()
            } else {
                this.backup.timesheet_id = null
            }
        },
        onSubmitForm() {
            this.backup.unit_id = this.selectedUnit.id
            this.backup.job_id = this.selectedJob.id

            this.$axios.post(`/api/v1/admin/backup/create`, this.backup)
                .then(response => {
                    useToast().success("Success to create data", { position: 'bottom-right' });
                    // this.$router.push({name: 'event_request_detail', params: {id: response.data.data.id}});
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
    }
};
</script>

<style>
</style>
