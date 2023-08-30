<template>
    <div class="container-fluid">
        <Breadcrumbs main="Public Holiday"/>

        <div class="container-fluid">
            <div class="email-wrap bookmark-wrap">
                <div class="row">
                    <div class="col-md-12">
                        <div class="card card-absolute">
                            <div class="card-header bg-primary">
                                <h5>Public Holiday</h5>
                            </div>
                            <div class="card-body">
                                <div class="d-flex justify-content-end mb-2">
                                    <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#createPublicHolidayModal">
                                        <i class="fa fa-plus-circle" /> &nbsp; Create Public Holiday
                                    </button>
                                </div>
                                <div ref="listPublicHoliday"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="modal fade" id="createPublicHolidayModal" ref="createPublicHolidayModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalCenter" aria-hidden="true">
            <VerticalModal title="Create Public Holiday" @save="onCreate()">
                <div class="row">
                    <div class="col-md-12">
                        <div class="mt-2">
                            <label for="date">Date:</label>
                            <input type="date" class="form-control" id="date" v-model="createPublicHolidayPayload.date" required>
                        </div>
                    </div>
                    <div class="col-md-12">
                        <div class="mt-2">
                            <label for="name">Name:</label>
                            <input type="text" class="form-control" id="name" v-model="createPublicHolidayPayload.name" required>
                        </div>
                    </div>
                    <div class="col-md-12">
                        <div class="mt-2">
                            <label for="type">Type:</label>
                            <select id="type" name="type" class="form-select" v-model="createPublicHolidayPayload.type" required>
                                <option value="normal_day_off" :selected="createPublicHolidayPayload.type === 'normal_day_off' ? 'selected' : ''">Libur Hari Biasa</option>
                                <option value="national_day_off" :selected="createPublicHolidayPayload.type === 'national_day_off' ? 'selected' : ''">Libur Hari Nasional</option>
                                <option value="extended_day_off" :selected="createPublicHolidayPayload.type === 'extended_day_off' ? 'selected' : ''">Cuti Bersama</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-12">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group mt-4">
                                    <div class="checkbox p-0">
                                        <input id="is_shift" type="checkbox" v-model="createPublicHolidayPayload.is_shift">
                                        <label class="text-muted" for="is_shift">Is Shift?</label>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group mt-4">
                                    <div class="checkbox p-0">
                                        <input id="is_non_shift" type="checkbox" v-model="createPublicHolidayPayload.is_non_shift">
                                        <label class="text-muted" for="is_non_shift">Is Non Shift?</label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </VerticalModal>
        </div>

        <div class="modal fade" id="updatePublicHolidayModal" ref="updatePublicHolidayModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalCenter" aria-hidden="true">
            <VerticalModal title="Update Public Holiday" @save="onUpdate()">
                <div class="row">
                    <div class="col-md-12">
                        <div class="mt-2">
                            <label for="update_date">Date:</label>
                            <input type="date" class="form-control" id="update_date" v-model="selectedPublicHoliday.holiday_date" required>
                        </div>
                    </div>
                    <div class="col-md-12">
                        <div class="mt-2">
                            <label for="update_name">Name:</label>
                            <input type="text" class="form-control" id="update_name" v-model="selectedPublicHoliday.name" required>
                        </div>
                    </div>
                    <div class="col-md-12">
                        <div class="mt-2">
                            <label for="update_type">Type:</label>
                            <select id="update_type" name="update_type" class="form-select" v-model="selectedPublicHoliday.holiday_type" required>
                                <option value="normal_day_off" :selected="selectedPublicHoliday.holiday_type === 'normal_day_off' ? 'selected' : ''">Libur Hari Biasa</option>
                                <option value="national_day_off" :selected="selectedPublicHoliday.holiday_type === 'national_day_off' ? 'selected' : ''">Libur Hari Nasional</option>
                                <option value="extended_day_off" :selected="selectedPublicHoliday.holiday_type === 'extended_day_off' ? 'selected' : ''">Cuti Bersama</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-12">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group mt-4">
                                    <div class="checkbox p-0">
                                        <input id="update_is_shift" type="checkbox" v-model="selectedPublicHoliday.is_shift">
                                        <label class="text-muted" for="update_is_shift">Is Shift?</label>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group mt-4">
                                    <div class="checkbox p-0">
                                        <input id="update_is_non_shift" type="checkbox" v-model="selectedPublicHoliday.is_non_shift">
                                        <label class="text-muted" for="update_is_non_shift">Is Non Shift?</label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </VerticalModal>
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
            createPublicHolidayPayload: {
                date: null,
                type: null,
                name: null,
                is_shift: true,
                is_non_shift: true
            },
            pagination: {
                currentPage: 1,
                pageSize: 10
            },
            selectedPublicHoliday: {
                id: null,
                holiday_date: null,
                holiday_type: null,
                is_non_shift: null,
                is_shift: true,
                name: true
            },
        }
    },
    mounted() {
        this.generatePublicHolidayTable()
    },
    methods: {
        generatePublicHolidayTable() {
            const ls = localStorage.getItem('my_app_token');
            this.table = new Tabulator(this.$refs.listPublicHoliday ,{
                paginationCounter:"rows",
                ajaxURL: '/api/v1/admin/public_holiday',
                ajaxConfig: {
                    headers: {
                        Authorization: `Bearer ${ls}`,
                        "X-Unit-Relation-ID": this.$store.state.activeAdminUnit?.unit_relation_id ?? '',
                        "X-Selected-Role": this.$store.state.currentRole,
                    }
                },
                ajaxParams: {
                    page: this.pagination.currentPage,
                    size: this.pagination.pageSize,
                },
                ajaxResponse: function (url, params, response) {
                    return {
                        data: response.data.data,
                        last_page: response.data.last_page,
                    }
                },
                ajaxURLGenerator: (url, config, params) => {
                    return `${url}?page=${params.page}&per_page=${params.size}`
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
                        title: 'Name',
                        field: 'name',
                        headerHozAlign: 'center',
                        headerSort: false,
                    },
                    {
                        title: 'Date',
                        field: 'holiday_date',
                        headerHozAlign: 'center',
                        headerSort: false,
                    },
                    {
                        title: 'Holiday Type',
                        field: 'holiday_type',
                        headerHozAlign: 'center',
                        headerSort: false,
                        formatter: function (cell) {
                            let value = cell.getValue()

                            if (value === 'normal_day_off') {
                                return `<span class="badge badge-primary">Libur Hari Biasa</span>`
                            } else if (value === 'national_day_off') {
                                return `<span class="badge badge-success">Libur Hari Nasional</span>`
                            } else if (value === 'extended_day_off') {
                                return `<span class="badge badge-info">Cuti Bersama</span>`
                            } else {
                                return `<span class="badge badge-warning">${value}</span>`
                            }
                        }
                    },
                    {
                        title: 'Type',
                        headerHozAlign: 'center',
                        headerSort: false,
                        columns: [
                            {
                                title: 'Is Shift',
                                field: 'is_shift',
                                hozAlign: 'center',
                                headerHozAlign: 'center',
                                headerSort: false,
                                formatter: function (cell) {
                                    let value = cell.getValue()

                                    if (value) {
                                        return `<button class="button-icon button-success"><i class="fa fa-check-circle"></i> </button>`
                                    } else {
                                        return `<button class="button-icon button-danger"><i class="fa fa-minus-circle"></i> </button>`
                                    }
                                }
                            },
                            {
                                title: 'Is Non Shift',
                                field: 'is_non_shift',
                                hozAlign: 'center',
                                headerHozAlign: 'center',
                                headerSort: false,
                                formatter: function (cell) {
                                    let value = cell.getValue()

                                    if (value) {
                                        return `<button class="button-icon button-success"><i class="fa fa-check-circle"></i> </button>`
                                    } else {
                                        return `<button class="button-icon button-danger"><i class="fa fa-minus-circle"></i> </button>`
                                    }
                                }
                            },
                            {
                                title: 'Action',
                                hozAlign: 'center',
                                headerHozAlign: 'center',
                                headerSort: false,
                                formatter: (cell, formatterParams, onRendered) => {
                                    return `
                                        <button class="button-icon button-warning" data-bs-toggle="modal" data-bs-target="#updatePublicHolidayModal" data-action="update"><i data-action="update" class="fa fa-pencil"></i> </button>
                                        <button class="button-icon button-danger" data-action="delete"><i data-action="delete" class="fa fa-trash"></i> </button>
                                     `;
                                },
                                width: 150,
                                sortable: false,
                                cellClick: (e, cell) => {
                                    const action = e.target.dataset.action
                                    const data = cell.getData()

                                    if (action === 'delete') {
                                        this.onDelete(data.id)
                                    } else {
                                        this.selectedPublicHoliday = data
                                    }
                                }
                            },
                        ]
                    },
                ],
                placeholder: 'No Data Available',
                pagination: true,
                paginationMode: 'remote',
                filterMode:"remote",
                paginationSize: this.pagination.pageSize,
                paginationSizeSelector: [10, 20, 50, 100],
                headerFilter: true,
                paginationInitialPage:1,
            })
        },
        onCreate() {
            this.$swal({
                icon: 'warning',
                title:"Are you sure want to add public holiday?",
                showCancelButton: true,
                confirmButtonText: 'Yes!',
                confirmButtonColor: '#126850',
                cancelButtonText: 'Cancel',
                cancelButtonColor: '#efefef',
            }).then((result)=>{
                if(result.isConfirmed){
                    this.$axios.post(`api/v1/admin/public_holiday/create`, this.createPublicHolidayPayload)
                        .then(() => {
                            useToast().success("Success to create public holiday!");
                            this.generatePublicHolidayTable()
                        })
                        .catch(error => {
                            useToast().error(error.response.data.message);
                        });
                }
            });
        },
        onUpdate() {
            this.$swal({
                icon: 'warning',
                title:"Are you sure want to update public holiday?",
                showCancelButton: true,
                confirmButtonText: 'Yes!',
                confirmButtonColor: '#126850',
                cancelButtonText: 'Cancel',
                cancelButtonColor: '#efefef',
            }).then((result)=>{
                if(result.isConfirmed){
                    this.$axios.post(`api/v1/admin/public_holiday/update/${this.selectedPublicHoliday.id}`, {
                        date: this.selectedPublicHoliday.holiday_date,
                        type: this.selectedPublicHoliday.holiday_type,
                        name: this.selectedPublicHoliday.name,
                        is_shift: this.selectedPublicHoliday.is_shift,
                        is_non_shift: this.selectedPublicHoliday.is_non_shift
                    })
                        .then(() => {
                            useToast().success("Success to update public holiday!");
                            this.generatePublicHolidayTable()
                        })
                        .catch(error => {
                            useToast().error(error.response.data.message);
                        });
                }
            });
        },
        onDelete(id) {
            this.$swal({
                icon: 'warning',
                title:"Are you sure want to delete public holiday?",
                text:'Once deleted data success, you will not be able to revert the data!',
                showCancelButton: true,
                confirmButtonText: 'Yes!',
                confirmButtonColor: '#f64545',
                cancelButtonText: 'Cancel',
                cancelButtonColor: '#efefef',
            }).then((result)=>{
                if(result.isConfirmed){
                    this.$axios.delete(`api/v1/admin/public_holiday/delete/${id}`,)
                        .then(() => {
                            useToast().success("Success to delete public holiday!");
                            this.generatePublicHolidayTable()
                        })
                        .catch(error => {
                            useToast().error(error.response.data.message);
                        });
                }
            });
        }
    }
}
</script>
