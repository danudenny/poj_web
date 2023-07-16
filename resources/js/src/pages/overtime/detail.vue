<template>
    <div class="container-fluid">
        <Breadcrumbs main="Detail Overtime Request"/>
        <div class="col-sm-12">
            <div class="card">
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mt-2">
                                <label for="name">Requestor Name</label>
                                <input type="text" class="form-control" v-model="overtime.requestor_employee.name" disabled>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mt-2">
                                        <label for="name">Waktu Mulai Lembur</label>
                                        <input type="text" class="form-control" v-model="overtime.check_in_time" disabled>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mt-2">
                                        <label for="name">Waktu Selesai Lembur</label>
                                        <input type="text" class="form-control" v-model="overtime.check_out_time" disabled>
                                    </div>
                                </div>
                            </div>
                            <div class="mt-2">
                                <label class="form-label">Catatan</label>
                                <textarea class="form-control" v-model="overtime.notes" required disabled>{{overtime.notes}}</textarea>
                            </div>
                            <div class="mt-2">
                                <label for="name">Unit Name</label>
                                <input type="text" class="form-control" v-model="overtime.unit.name" disabled>
                            </div>
                            <br/>
                            <div ref="employeeTable"></div>
                        </div>
                        <div class="col-md-6">
                            <div class="row" v-for="(item, index) in overtime.overtime_histories" :key="index">
                                <div class="col-md-12">
                                    <div class="alert-border alert alert-primary" v-if="item.history_type !== 'rejected'">
                                        <table>
                                            <tr>
                                                <td>Status</td>
                                                <td>: {{item.history_type}}</td>
                                            </tr>
                                            <tr>
                                                <td>Timestamp</td>
                                                <td>: {{item.created_at}}</td>
                                            </tr>
                                            <tr>
                                                <td>Name</td>
                                                <td>: {{item.employee.name}}</td>
                                            </tr>
                                        </table>
                                    </div>
                                    <div class="alert-border alert alert-danger" v-if="item.history_type === 'rejected'">
                                        <table>
                                            <tr>
                                                <td>Status</td>
                                                <td>: {{item.history_type}}</td>
                                            </tr>
                                            <tr>
                                                <td>Timestamp</td>
                                                <td>: {{item.created_at}}</td>
                                            </tr>
                                            <tr>
                                                <td>Name</td>
                                                <td>: {{item.employee.name}}</td>
                                            </tr>
                                            <tr>
                                                <td>Notes</td>
                                                <td>: {{item.notes}}</td>
                                            </tr>
                                        </table>
                                    </div>
                                    <div v-if="index < (overtime.overtime_histories.length - 1)">
                                        <p align="center">
                                            <i class="fa fa-arrow-down history-arrow mb-3"/>
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card-footer text-end">
                    <button class="btn btn-secondary" @click="$router.push('/attendance/overtime')">Back</button>&nbsp
                    <div
                        class="btn btn-primary button-info"
                        data-bs-toggle="modal"
                        data-bs-target="#approvalModal"
                        v-if="overtime.is_can_approve"
                    >
                        Approval
                    </div>
                </div>
            </div>
        </div>
        <div class="modal fade" id="approvalModal" ref="approvalModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalCenter" aria-hidden="true">
            <VerticalModal title="Approval Modal" @save="overtimeApproval()">
                <div class="row">
                    <div class="col-md-12">
                        <div class="mt-2">
                            <label for="status">Status:</label>
                            <select id="status" name="status" class="form-select" v-model="approval.status" required>
                                <option value="approved" :selected="approval.status === 'approved' ? 'selected' : ''">Approve</option>
                                <option value="rejected" :selected="approval.status === 'rejected' ? 'selected' : ''">Reject</option>
                            </select>
                        </div>
                        <div class="mt-2" v-if="approval.status === 'rejected'">
                            <label for="name">Note:</label>
                            <input type="text" class="form-control" id="reason" v-model="approval.notes" required>
                        </div>
                    </div>
                </div>
            </VerticalModal>
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
                                    <input type="text" class="form-control" v-model="overtime.check_in_time" disabled>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mt-2">
                                    <label for="name">End Time</label>
                                    <input type="text" class="form-control" v-model="overtime.check_out_time" disabled>
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
import L from 'leaflet';
import VerticalModal from "@components/modal/verticalModal.vue";
import VerticalModalWithoutSave from "@components/modal/verticalModalWithoutSave.vue";
import { useToast } from "vue-toastification";
import {TabulatorFull as Tabulator} from "tabulator-tables";
import axios from "axios";

export default {
    components: {
        VerticalModal,
        VerticalModalWithoutSave
    },
    data() {
        return {
            overtime: {
                id: null,
                requestor_employee_id: null,
                unit_relation_id: null,
                last_status: null,
                last_status_at: null,
                timezone: null,
                notes: null,
                image_url: null,
                location_lat: null,
                location_long: null,
                created_at: null,
                updated_at: null,
                check_in_time: null,
                check_out_time: null,
                is_can_approve: false,
                requestor_employee: {
                    id: null,
                    name: null,
                    status: null
                },
                unit: {
                    id: null,
                    name: null,
                },
                overtime_histories: [],
                overtime_employees: []
            },
            approval: {
                status: null,
                notes:null
            },
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
            }
        }
    },
    mounted() {
        this.getDetailOvertime()
    },
    methods: {
        generateEmployeeTable() {
            const table = new Tabulator(this.$refs.employeeTable, {
                data: this.overtime.overtime_employees,
                layout: 'fitColumns',
                columns: [
                    {
                        title: 'No',
                        field: '',
                        formatter: 'rownum',
                        width: 10
                    },
                    {
                        title: 'Name',
                        field: 'employee.name',
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
                pagination: 'local',
                paginationSize: 10,
                paginationSizeSelector: [10, 20, 50, 100],
                headerFilter: true,
                paginationInitialPage:1,
                rowFormatter: (row) => {
                    //
                }
            });
        },
        getDetailOvertime() {
            this.$axios.get(`/api/v1/admin/overtime/view/${this.$route.params.id}`)
                .then(response => {
                    this.overtime = response.data.data
                    this.generateEmployeeTable()
                })
                .catch(error => {
                    console.error(error);
                });
        },
        overtimeApproval() {
            this.$axios.post(`/api/v1/admin/overtime/approval/${this.$route.params.id}`, this.approval)
                .then(response => {
                    useToast().success("Success to update data", { position: 'bottom-right' });
                    this.getDetailOvertime()
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
    },
};
</script>

<style>
#mapCheckIn {
    height: 300px
}
#mapCheckOut {
    height: 300px
}
</style>
