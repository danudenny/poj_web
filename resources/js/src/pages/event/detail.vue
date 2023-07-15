<template>
    <div class="container-fluid">
        <Breadcrumbs main="Detail Incident Reporting"/>
        <div class="col-sm-12">
            <form class="card">
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Kategori Event</label>
                                <input class="form-control" type="text" v-model="event.event_type" disabled>
                            </div>
                            <div class="mb-3">
                                <img class="image-theme" :src="event.image_url"/>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Judul Event</label>
                                <input class="form-control" type="text" v-model="event.title" disabled>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Deskripsi Event</label>
                                <textarea class="form-control chronology-text-area" v-model="event.description" disabled>{{event.description}}</textarea>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Alamat</label>
                                <input class="form-control" type="text" v-model="event.address" disabled>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Tanggal Event</label>
                                <input class="form-control" type="text" v-model="event.date_event" disabled>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Waktu Event</label>
                                <input class="form-control" type="text" v-model="event.time_event" disabled>
                            </div>
                            <div class="mb-3" v-if="event.is_repeat">
                                <label class="form-label">Recurring Event</label>
                                <input class="form-control" type="text" v-model="event.event_repeat_description" disabled>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div ref="listAttendance"></div>
                                </div>
                                <div class="col-md-6">
                                    <div ref="listEventDates"></div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div id="map" class="mb-4"></div>

                            <hr/>

                            <div class="row" v-for="(item, index) in event.event_histories" :key="index">
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
                                            <tr>
                                                <td>Name</td>
                                                <td>: {{item.employee.name}}</td>
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
                                            <tr>
                                                <td>Name</td>
                                                <td>: {{item.employee.name}}</td>
                                            </tr>
                                        </table>
                                        <tr>
                                            <td>Notes</td>
                                            <td>: {{item.notes}}</td>
                                        </tr>
                                    </div>
                                    <div v-if="index < (event.event_histories.length - 1)">
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
                    <button class="btn btn-secondary" @click="$router.push('/event')">Back</button>&nbsp
                    <div
                        class="btn btn-primary button-info"
                        data-bs-toggle="modal"
                        data-bs-target="#approvalModal"
                        v-if="this.event.is_can_approve"
                    >
                        Approval
                    </div>
                </div>
            </form>
        </div>
        <div class="modal fade" id="approvalModal" ref="approvalModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalCenter" aria-hidden="true">
            <VerticalModal title="Approval Modal" @save="eventRequestApproval()">
                <div class="row">
                    <div class="col-md-12">
                        <div class="mt-2">
                            <label for="status">Status:</label>
                            <select id="status" name="status" class="form-select" v-model="approval.status" required>
                                <option value="approve" :selected="approval.status === 'approve' ? 'selected' : ''">Approve</option>
                                <option value="reject" :selected="approval.status === 'reject' ? 'selected' : ''">Reject</option>
                            </select>
                        </div>
                        <div class="mt-2" v-if="approval.status === 'reject'">
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
import L from 'leaflet';
import VerticalModal from "@components/modal/verticalModal.vue";
import { useToast } from "vue-toastification";
import {TabulatorFull as Tabulator} from "tabulator-tables";

export default {
    components: {
        VerticalModal
    },
    data() {
        return {
            event: {
                id: null,
                requestor_employee_id: null,
                last_status: null,
                event_type: null,
                image_url: null,
                title: null,
                description: null,
                latitude: null,
                longitude: null,
                address: null,
                date_event: null,
                time_event: null,
                is_need_absence: null,
                is_repeat: null,
                repeat_type: null,
                repeat_every: null,
                repeat_days: null,
                repeat_end_date: null,
                is_can_approve: false,
                event_dates: [],
                event_attendances: [],
                event_histories: []
            },
            approval: {
                status: null,
                notes: null
            },
            closure: {
                status: null,
                reason: null,
            },
            mapContainer: null,
            map: null,
            marker: null,
            incidentImages: [
                {
                    src: 'http://192.168.100.73:9000/att-poj-bucket/uploads/incident/64aab2e041114_pagar_rusak.jpeg',
                    description: 'Sunken dreams II. by Arbebuk',
                }
            ]
        }
    },
    created() {
        this.getEvent()
    },
    methods: {
        async getEvent() {
            await this.$axios.get(`/api/v1/admin/event/view/${this.$route.params.id}`)
                .then(response => {
                    this.event = response.data.data;
                    this.generateMap()
                    this.initializeEventAttendanceTable()
                    this.initializeEventDates()
                })
                .catch(error => {
                    console.error(error);
                });
        },
        generateMap() {
            this.mapContainer = this.$el.querySelector('#map');

            this.map = L.map(this.mapContainer, {
                scrollWheelZoom: false
            }).setView([this.event.latitude, this.event.longitude], 16);

            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '© OpenStreetMap contributors'
            }).addTo(this.map);

            this.marker = L.marker([this.event.latitude, this.event.longitude], {icon: L.icon({
                    iconUrl: '/marker-icon.png'
                })}).addTo(this.map);
        },
        initializeEventAttendanceTable() {
            const table = new Tabulator(this.$refs.listAttendance, {
                data: this.event.event_attendances,
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
                        headerFilter:"input"
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
        initializeEventDates() {
            console.log(this.event.is_repeat)
            const table = new Tabulator(this.$refs.listEventDates, {
                data: this.event.event_dates,
                layout: 'fitColumns',
                columns: [
                    {
                        title: 'No',
                        field: '',
                        formatter: 'rownum',
                        width: 10
                    },
                    {
                        title: 'Event Date',
                        field: 'event_date',
                        headerFilter:"input"
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
        eventRequestApproval() {
            this.$axios.post(`/api/v1/admin/event/approve/${this.$route.params.id}`, this.approval)
                .then(response => {
                    useToast().success("Success to update data", { position: 'bottom-right' });
                    this.getEvent()
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
    },
};
</script>

<style>
.alert-primary {
    background-color: #126850 !important;
    border-color: #0A5640 !important;
    color: #fff;
}

.history-arrow {
    font-size: 20px !important;
    color: #0A5640;
    text-align: center;
}

.alert-border {
    border-radius: 5px !important
}

.image-theme {
    width: 100% !important;
}
</style>
