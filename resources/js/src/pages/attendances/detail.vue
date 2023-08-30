<template>
    <div class="container-fluid">
        <Breadcrumbs main="Detail Attendance"/>
        <div class="col-sm-12">
            <div class="card">
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-12">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mt-2">
                                        <label for="name">Employee Name</label>
                                        <input type="text" class="form-control" v-model="attendance.employee.name" disabled>
                                    </div>
                                    <div class="mt-2">
                                        <label for="name">Attendance Type</label>
                                        <input type="text" class="form-control" v-model="attendance.attendance_types" disabled>
                                    </div>
                                    <div class="form-group mb-0">
                                        <div class="checkbox p-0">
                                            <input id="is_need_approval" type="checkbox" v-model="attendance.is_need_approval" disabled>
                                            <label class="text-muted" for="is_need_approval">Is Need Approval?</label>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group mb-0">
                                                <div class="checkbox p-0">
                                                    <input id="is_need_approval" type="checkbox" v-model="attendance.is_early" disabled>
                                                    <label class="text-muted" for="is_need_approval">Is Early?</label>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group mb-0">
                                                <div class="checkbox p-0">
                                                    <input id="is_need_approval" type="checkbox" v-model="attendance.is_early" disabled>
                                                    <label class="text-muted" for="is_need_approval">Is Late?</label>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="mb-3" v-if="attendance.late_reason">
                                        <label class="form-label">Late Reason</label>
                                        <textarea class="form-control" v-model="attendance.late_reason" required></textarea>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="mt-2">
                                                <label for="name">Check In Time</label>
                                                <input type="text" class="form-control" v-model="attendance.check_in_time_with_client_timezone" disabled>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <div class="mt-2">
                                                        <label for="name">Type</label>
                                                        <input type="text" class="form-control" v-model="attendance.checkin_type" disabled>
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="mt-2">
                                                        <label for="name">Radius (Meter)</label>
                                                        <input type="text" class="form-control" v-model="attendance.checkin_real_radius" disabled>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="mt-2">
                                                <label for="name">Check Out Time</label>
                                                <input type="text" class="form-control" v-model="attendance.check_out_time_with_client_timezone" disabled>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <div class="mt-2">
                                                        <label for="name">Status</label>
                                                        <input type="text" class="form-control" v-model="attendance.checkout_type" disabled>
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="mt-2">
                                                        <label for="name">Radius (Meter)</label>
                                                        <input type="text" class="form-control" v-model="attendance.checkout_real_radius" disabled>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="mt-2">
                                                <label for="name">Early Duration (in minutes)</label>
                                                <input type="text" class="form-control" v-model="attendance.early_duration" disabled>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="mt-2">
                                                <label for="name">Late Duration (in minutes)</label>
                                                <input type="text" class="form-control" v-model="attendance.late_duration" disabled>
                                            </div>
                                        </div>
                                    </div>

                                    <hr/>

                                    <h5>Approval</h5>
                                    <div ref="approvalAttendanceList"></div>

                                    <div class="row" v-if="false" v-for="(item, index) in attendance.employee_attendance_history" :key="index">
                                        <div class="col-md-12">
                                            <div class="alert-border alert alert-primary" v-if="item.status !== 'reject'">
                                                <table>
                                                    <tr>
                                                        <td>Status</td>
                                                        <td>: {{item.status}}</td>
                                                    </tr>
                                                    <tr>
                                                        <td>Timestamp</td>
                                                        <td>: {{item.created_at}}</td>
                                                    </tr>
                                                </table>
                                            </div>
                                            <div class="alert-border alert alert-danger" v-if="item.status === 'reject'">
                                                <table>
                                                    <tr>
                                                        <td>Status</td>
                                                        <td>: {{item.status}}</td>
                                                    </tr>
                                                    <tr>
                                                        <td>Timestamp</td>
                                                        <td>: {{item.created_at}}</td>
                                                    </tr>
                                                </table>
                                            </div>
                                            <div v-if="index < (attendance.employee_attendance_history.length - 1)">
                                                <p align="center">
                                                    <i class="fa fa-arrow-down history-arrow mb-3"/>
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div>
                                        <p v-if="attendance.checkin_lat != null && attendance.checkin_long != null">Check In Location</p>
                                        <div id="mapCheckIn" class="mb-4"></div>
                                    </div>
                                    <div>
                                        <p v-if="attendance.checkout_lat != null && attendance.checkout_long != null">Check Out Location</p>
                                        <div id="mapCheckOut" class="mb-4"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card-footer text-end">
                    <div class="d-flex justify-content-end">
                        <button class="btn btn-warning" @click="$router.go(-1)">
                            <i class="fa fa-arrow-left"></i>&nbsp; Back
                        </button> &nbsp;
                        <div
                            class="btn btn-primary button-info"
                            data-bs-toggle="modal"
                            data-bs-target="#approvalModal"
                            v-if="attendance.is_can_approve"
                        >
                            <i class="fa fa-check"></i> Approval
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="modal fade" id="approvalModal" ref="approvalModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalCenter" aria-hidden="true">
            <VerticalModal title="Approval Modal" @save="attendanceApproval()">
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
    </div>
</template>

<script>
import VerticalModal from "@components/modal/verticalModal.vue";
import L from "leaflet";
import {TabulatorFull as Tabulator} from "tabulator-tables";
import {useToast} from "vue-toastification";

export default {
    components: {
        VerticalModal
    },
    data() {
        return {
            attendance: {
                id : null,
                real_check_in : null,
                real_check_out : null,
                duration : null,
                employee_id : null,
                created_at : null,
                updated_at : null,
                is_need_approval : null,
                checkin_lat : null,
                checkin_long : null,
                checkout_lat : null,
                checkout_long : null,
                checkin_type : null,
                checkout_type : null,
                attendance_types : null,
                checkin_real_radius : null,
                checkout_real_radius : null,
                is_late : null,
                is_early: false,
                late_reason: null,
                approved: false,
                late_duration: "0",
                early_duration: null,
                check_in_tz: null,
                check_out_tz: null,
                is_on_time: null,
                check_in_time_with_client_timezone: null,
                check_out_time_with_client_timezone: null,
                is_can_approve: false,
                employee: {
                    name: null
                },
                employee_attendance_history: [],
                attendance_approvals: []
            },
            checkInMap: {
                mapContainer: null,
                map: null,
                marker: null,
            },
            approval: {
                status: null,
                notes: null
            }
        }
    },
    created() {
        this.getDetailAttendance()
    },
    methods: {
        getDetailAttendance() {
            this.$axios.get(`/api/v1/admin/attendance/view/${this.$route.params.id}`)
                .then(response => {
                    this.attendance = response.data.data
                    this.generateApprovalAttendanceTable()
                    this.generateCheckInMap()
                    this.generateCheckOutMap()
                })
                .catch(error => {
                    console.error(error);
                });
        },
        generateApprovalAttendanceTable() {
            const table = new Tabulator(this.$refs.approvalAttendanceList, {
                data: this.attendance.attendance_approvals,
                layout: 'fitColumns',
                columns: [
                    {
                        title: 'No',
                        field: '',
                        formatter: 'rownum',
                        width: 20
                    },
                    {
                        title: 'Name',
                        field: 'employee.name',
                    },
                    {
                        title: 'Unit',
                        field: 'employee.last_unit.name',
                    },
                    {
                        title: 'Status',
                        field: 'status',
                        formatter: (cell) => {
                            let val = cell.getValue()

                            if (val === 'approved') {
                                return `<span class="badge badge-success">Approved</span>`
                            } else if (val === 'rejected') {
                                return `<span class="badge badge-danger">Rejected</span>`
                            } else {
                                return `<span class="badge badge-warning">Pending</span>`
                            }
                        }
                    },
                    {
                        title: 'Notes',
                        field: 'notes',
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
        generateCheckInMap() {
            if (this.attendance.checkin_lat === null || this.attendance.checkin_long === null) {
                return
            }

            let mapContainer = this.$el.querySelector('#mapCheckIn');

            let map = L.map(mapContainer, {
                scrollWheelZoom: false
            }).setView([this.attendance.checkin_lat, this.attendance.checkin_long], 16);

            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '© OpenStreetMap contributors'
            }).addTo(map);

            let marker = L.marker([this.attendance.checkin_lat, this.attendance.checkin_long], {icon: L.icon({
                    iconUrl: '/marker-icon.png'
                })}).addTo(map);
        },
        generateCheckOutMap() {
            if (this.attendance.checkout_lat === null || this.attendance.checkout_long === null) {
                return
            }

            let mapContainer = this.$el.querySelector('#mapCheckOut');

            let map = L.map(mapContainer, {
                scrollWheelZoom: false
            }).setView([this.attendance.checkout_lat, this.attendance.checkout_long], 16);

            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '© OpenStreetMap contributors'
            }).addTo(map);

            let marker = L.marker([this.attendance.checkout_lat, this.attendance.checkout_long], {icon: L.icon({
                    iconUrl: '/marker-icon.png'
                })}).addTo(map);
        },
        attendanceApproval() {
            this.$swal({
                icon: 'warning',
                title:"Do you want to do approval?",
                text:'Once you finished doing approval, you will not be able to revert the data!',
                showCancelButton: true,
                confirmButtonText: 'Yes!',
                confirmButtonColor: '#126850',
                cancelButtonText: 'Cancel',
                cancelButtonColor: '#efefef',
            }).then((result)=>{
                if(result.value){
                    this.$axios.put(`api/v1/admin/attendance/approve/${this.$route.params.id}`, this.approval)
                        .then(() => {
                            useToast().success("Success doing approval!");
                            this.getDetailAttendance();
                        })
                        .catch(error => {
                            useToast().error(error.response.data.message);
                        });
                }
            });
        }
    }
};
</script>

<style>
#mapCheckIn {
    height: 300px;
    z-index: 0 !important;
}
#mapCheckOut {
    height: 300px;
    z-index: 0 !important;
}
</style>
