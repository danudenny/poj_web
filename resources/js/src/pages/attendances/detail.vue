<template>
    <div class="container-fluid">
        <Breadcrumbs main="Detail Incident Reporting"/>
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
                                                <input type="text" class="form-control" v-model="attendance.real_check_in" disabled>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="mt-2">
                                                <label for="name">Check In Type</label>
                                                <input type="text" class="form-control" v-model="attendance.checkin_type" disabled>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="mt-2">
                                                <label for="name">Check Out Time</label>
                                                <input type="text" class="form-control" v-model="attendance.real_check_out" disabled>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="mt-2">
                                                <label for="name">Check Out Type</label>
                                                <input type="text" class="form-control" v-model="attendance.checkout_type" disabled>
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

                                    <div class="row" v-for="(item, index) in attendance.employee_attendance_history" :key="index">
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
                            <hr/>
                            <button class="btn btn-primary">Back</button>
                        </div>
                    </div>
                </div>
                <div class="card-footer text-end">
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import VerticalModal from "@components/modal/verticalModal.vue";
import L from "leaflet";

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
                employee: {
                    name: null
                },
                employee_attendance_history: []
            },
            checkInMap: {
                mapContainer: null,
                map: null,
                marker: null,
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
                    this.generateCheckInMap()
                    this.generateCheckOutMap()
                })
                .catch(error => {
                    console.error(error);
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
        }
    }
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
