<template>
    <div class="container-fluid">
        <Breadcrumbs main=""/>

        <div class="container-fluid">
            <div class="email-wrap bookmark-wrap">
                <div class="row">
                    <div class="col-md-12">
                        <div class="card card-absolute">
                            <div class="card-header bg-primary">
                                <h5>Employee Overtime</h5>
                            </div>
                            <div class="card-body">
                                <div class="d-flex justify-content-end mb-2">
                                </div>
                                <div v-if="loading" class="text-center">
                                    <img src="../../assets/loader.gif" alt="loading" width="100">
                                </div>
                                <div ref="overtimeTable"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="modal fade" id="detailEmployeeModal" ref="detailEmployeeModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalCenter" aria-hidden="true">
            <VerticalModalWithoutSave title="Detail">
                <div class="row">
                    <div class="col-md-12">
                        <div class="mt-2">
                            <label for="name">Employee Name</label>
                            <input type="text" class="form-control" v-model="selectedData.employee.name" disabled>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mt-2">
                                    <label for="name">Start Time</label>
                                    <input type="text" class="form-control" v-model="selectedData.overtime.check_in_time" disabled>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mt-2">
                                    <label for="name">End Time</label>
                                    <input type="text" class="form-control" v-model="selectedData.overtime.check_out_time" disabled>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mt-2">
                                    <label for="name">Check In Time</label>
                                    <input type="text" class="form-control" v-model="selectedData.check_in_time_with_unit_timezone" disabled>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mt-2">
                                    <label for="name">Check Out Time</label>
                                    <input type="text" class="form-control" v-model="selectedData.check_out_time_with_unit_timezone" disabled>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mt-2">
                                    <label for="name">Check In Time (In Employee Timezone)</label>
                                    <input type="text" class="form-control" v-model="selectedData.check_in_time_with_employee_timezone" disabled>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mt-2">
                                    <label for="name">Check Out Time (In Employee Timezone)</label>
                                    <input type="text" class="form-control" v-model="selectedData.check_out_time_with_employee_timezone" disabled>
                                </div>
                            </div>
                        </div>
                        <div v-if="selectedData.check_in_lat != null && selectedData.check_in_long != null">
                            <p>Check In Location</p>
                            <div id="mapCheckIn" class="mb-4"></div>
                        </div>
                        <div v-if="selectedData.check_out_lat != null && selectedData.check_out_long != null">
                            <p>Check Out Location</p>
                            <div id="mapCheckOut" class="mb-4"></div>
                        </div>
                    </div>
                </div>
            </VerticalModalWithoutSave>
        </div>

        <div>
            <div
                ref="showModalButton"
                id="showModalButton"
                style="display:none"
                data-bs-toggle="modal"
                data-bs-target="#detailEmployeeModal"
            ></div>
        </div>
    </div>
</template>

<script>
import {TabulatorFull as Tabulator} from 'tabulator-tables';
import VerticalModalWithoutSave from "@components/modal/verticalModalWithoutSave.vue";
import L from "leaflet";

export default {
    components: {
        VerticalModalWithoutSave
    },
    data() {
        return {
            selectedData: {
                check_in_lat: null,
                check_in_long: null,
                check_out_lat: null,
                check_out_long: null,
                check_in_time_with_unit_timezone: null,
                check_out_time_with_unit_timezone: null,
                check_in_time_with_employee_timezone: null,
                check_out_time_with_employee_timezone: null,
                employee: {
                    name: null,
                },
                overtime: {
                    check_in_time: null,
                    check_out_time: null
                }
            },
            pageSize: 10,
            currentPage: 1,
            loading: false,
        }
    },
    async mounted() {
        this.generateOvertimeTable();
    },
    methods: {
        generateOvertimeTable() {
            const ls = localStorage.getItem('my_app_token')
            const table = new Tabulator(this.$refs.overtimeTable, {
                ajaxURL: '/api/v1/admin/overtime/employee-overtime',
                layout: 'fitColumns',
                columns: [
                    {
                        title: 'No',
                        field: '',
                        formatter: 'rownum',
                        width: 100
                    },
                    {
                        title: 'Employee Name',
                        field: 'employee.name',
                    },
                    {
                        title: 'Start Time',
                        field: 'overtime.check_in_time',
                    },
                    {
                        title: 'End Time',
                        field: 'overtime.check_out_time',
                    },
                    {
                        title: 'Check In Time',
                        field: 'check_in_time_with_unit_timezone',
                    },
                    {
                        title: 'Check Out Time',
                        field: 'check_out_time_with_unit_timezone',
                    },
                    {
                        title: '',
                        formatter: this.viewDetailsFormatter,
                        width: 70,
                        hozAlign: 'center',
                        sortable: false,
                        cellClick: (e, cell) => {
                            this.viewData(cell.getRow().getData());
                        }
                    },
                ],
                pagination: true,
                paginationMode: 'remote',
                responsiveLayout: true,
                filterMode:"remote",
                paginationSize: this.pageSize,
                ajaxConfig: {
                    headers: {
                        Authorization: `Bearer ${ls}`,
                    },
                },
                ajaxParams: {
                    page: this.currentPage,
                    size: this.pageSize,
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
        },
        viewDetailsFormatter(cell, formatterParams, onRendered) {
            return `<button class="button-icon button-success" data-id="${cell.getRow().getData().id}"><i class="fa fa-eye"></i> </button>`;
        },
        viewData(data) {
            this.selectedData = data
            this.$refs.showModalButton.click()
            this.generateCheckInMap()
            this.generateCheckOutMap()
        },
        generateCheckInMap() {
            if (this.selectedData.check_in_lat === null || this.selectedData.check_in_long === null) {
                return
            }

            setTimeout(() => {
                let mapContainer = this.$el.querySelector('#mapCheckIn');

                let map = L.map(mapContainer, {
                    scrollWheelZoom: false
                }).setView([this.selectedData.check_in_lat, this.selectedData.check_in_long], 16);

                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    attribution: '© OpenStreetMap contributors'
                }).addTo(map);

                let marker = L.marker([this.selectedData.check_in_lat, this.selectedData.check_in_long], {icon: L.icon({
                        iconUrl: '/marker-icon.png'
                    })}).addTo(map);
            }, 100)
        },
        generateCheckOutMap() {
            if (this.selectedData.check_out_lat === null || this.selectedData.check_out_long === null) {
                return
            }

            setTimeout(() => {
                let mapContainer = this.$el.querySelector('#mapCheckOut');

                let map = L.map(mapContainer, {
                    scrollWheelZoom: false
                }).setView([this.selectedData.check_out_lat, this.selectedData.check_out_long], 16);

                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    attribution: '© OpenStreetMap contributors'
                }).addTo(map);

                let marker = L.marker([this.selectedData.check_out_lat, this.selectedData.check_out_long], {icon: L.icon({
                        iconUrl: '/marker-icon.png'
                    })}).addTo(map);
            }, 100)
        }
    }
}
</script>

<style>
.tabulator .tabulator-header .tabulator-col {
    background-color: #0A5640 !important;
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

#mapCheckIn {
    height: 300px
}
#mapCheckOut {
    height: 300px
}
</style>
