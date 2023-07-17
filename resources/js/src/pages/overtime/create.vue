<template>
    <div class="container-fluid">
        <Breadcrumbs main="Create Overtime Request"/>
        <div class="col-sm-12">
            <form class="card" v-on:submit.prevent="onSubmitForm">
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mt-2">
                                        <label for="name">Waktu Mulai Lembur</label>
                                        <input type="datetime-local" class="form-control" v-model="dateTime.start_datetime" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mt-2">
                                        <label for="name">Waktu Selesai Lembur</label>
                                        <input type="datetime-local" class="form-control" v-model="dateTime.end_datetime" required>
                                    </div>
                                </div>
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

                        </div>
                    </div>
                    <hr/>
                    <div class="row">
                        <div class="col-md-6">
                            <div ref="unitsTable"></div>
                        </div>
                        <div class="col-md-6">
                            <div ref="employeeTable"></div>
                        </div>
                    </div>
                </div>
                <div class="card-footer text-end">
                    <button class="btn btn-primary">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</template>

<script>
import L from 'leaflet';
import VerticalModal from "@components/modal/verticalModal.vue";
import { useToast } from "vue-toastification";
import {TabulatorFull as Tabulator} from "tabulator-tables";
import axios from "axios";

export default {
    components: {
        VerticalModal
    },
    data() {
        return {
            dateTime: {
                start_datetime: null,
                end_datetime: null
            },
            overtime: {
                start_datetime: null,
                end_datetime: null,
                notes: null,
                image_url: null,
                unit_relation_id: null,
                employees: []
            },
            unitPagination: {
                currentPage: 1,
                pageSize: 10,
            },
            employeePagination: {
                currentPage: 1,
                pageSize: 10,
            }
        }
    },
    mounted() {
        this.generateUnitTable()
    },
    methods: {
        generateUnitTable() {
            const ls = localStorage.getItem('my_app_token')
            const table = new Tabulator(this.$refs.unitsTable, {
                ajaxURL: '/api/v1/admin/unit/paginated',
                layout: 'fitColumns',
                columns: [
                    {
                        formatter: "rowSelection",
                        titleFormatter: "rowSelection",
                        width: 10,
                        hozAlign: "center",
                        headerSort: false,
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
                    if (this.overtime.unit_relation_id === row.getData().relation_id) {
                        row.select()
                    }
                },
            });
            table.on("rowSelectionChanged", (data, rows, selected, deselected) => {
                if(selected.length > 0) {
                    this.overtime.unit_relation_id = selected[0].getData().relation_id
                    this.overtime.employees = []
                    this.generateEmployeesTable()
                }
                if (selected.length === 0 && deselected.length > 0) {
                    this.overtime.unit_relation_id = null
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
                    return `${url}?page=${params.page}&size=${params.size}&name=${localFilter.name}&unit_id=${this.overtime.unit_relation_id}`
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
                    if (this.overtime.employees.includes(row.getData().id)) {
                        row.select()
                    }
                },
            });
            table.on("rowSelectionChanged", (data, rows, selected, deselected) => {
                if(selected.length > 0) {
                    this.overtime.employees.push(selected[0].getData().id)
                }
                if (deselected.length > 0) {
                    let deselectedID = deselected[0].getData().id
                    this.overtime.employees = this.overtime.employees.filter((val) => {
                        return deselectedID !== val
                    })
                }
            })
        },
        onChangeFile() {
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
            if (this.dateTime.start_datetime == null || this.dateTime.end_datetime == null) {
                return
            }

            let startDateTime = new Date(this.dateTime.start_datetime)
            this.overtime.start_datetime = startDateTime.getFullYear() + "-" + ("0" + (startDateTime.getMonth() + 1)).slice(-2) + "-" + ("0" + (startDateTime.getDate())).slice(-2) + " " + ("0" + (startDateTime.getHours())).slice(-2) + ":" + ("0" + (startDateTime.getMinutes())).slice(-2) + ":00"

            let endDateTime = new Date(this.dateTime.end_datetime)
            this.overtime.end_datetime = startDateTime.getFullYear() + "-" + ("0" + (endDateTime.getMonth() + 1)).slice(-2) + "-" + ("0" + (endDateTime.getDate())).slice(-2) + " " + ("0" + (endDateTime.getHours())).slice(-2) + ":" + ("0" + (endDateTime.getMinutes())).slice(-2) + ":00"

            this.$axios.post(`/api/v1/admin/overtime`, this.overtime)
                .then(response => {
                    useToast().success("Success to create data", { position: 'bottom-right' });
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
