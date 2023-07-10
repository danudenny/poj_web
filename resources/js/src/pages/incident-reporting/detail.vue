<template>
    <div class="container-fluid">
        <Breadcrumbs main="Detail Incident Reporting"/>
        <div class="col-sm-12">
            <form class="card">
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Kategori Kejadian</label>
                                <input class="form-control" type="text" v-model="incident.category" disabled>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Name</label>
                                <input class="form-control" type="text" v-model="incident.name" disabled>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Nama Lokasi</label>
                                <input class="form-control" type="text" v-model="incident.location_name" disabled>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Tanggal & Jam</label>
                                <input class="form-control" type="text" v-model="incident.incident_time" disabled>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Pelaku</label>
                                <input class="form-control" type="text" v-model="incident.person" disabled>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Saksi</label>
                                <input class="form-control" type="text" v-model="incident.witness" disabled>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Penyebab Kejadian</label>
                                <input class="form-control" type="text" v-model="incident.cause" disabled>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Kronologi Kejadian</label>
                                <textarea class="form-control chronology-text-area" v-mode="incident.chronology" disabled>{{incident.chronology}}</textarea>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div id="map" class="mb-4"></div>

                            <hr/>

                            <div class="row" v-for="(item, index) in incident.incident_histories" :key="index">
                                <div class="col-md-12">
                                    <div class="alert-border alert alert-primary" v-if="item.status !== 'reject'">
                                        <table>
                                            <tr>
                                                <td>Stage</td>
                                                <td>: {{item.history_type}}</td>
                                            </tr>
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
                                            <tr v-if="item.incident_analysis">
                                                <td>Analisis Kejadian</td>
                                                <td>: {{item.incident_analysis}}</td>
                                            </tr>
                                            <tr v-if="item.follow_up_incident">
                                                <td>Tindak Lanjut Kejadian</td>
                                                <td>: {{item.follow_up_incident}}</td>
                                            </tr>
                                            <tr v-if="item.reason">
                                                <td>Reason</td>
                                                <td>: {{item.reason}}</td>
                                            </tr>
                                        </table>
                                        <div v-if="item.status == 'approve'">
                                            <br/>
                                            <img :src="incident.incident_image_follow_up[0].image_url" style="width: 100%"/>
                                        </div>
                                    </div>
                                    <div class="alert-border alert alert-danger" v-if="item.status === 'reject'">
                                        <table>
                                            <tr>
                                                <td>Stage</td>
                                                <td>: {{item.history_type}}</td>
                                            </tr>
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
                                            <tr v-if="item.reason">
                                                <td>Reason</td>
                                                <td>: {{item.reason}}</td>
                                            </tr>
                                        </table>
                                    </div>
                                    <div v-if="index < (incident.incident_histories.length - 1)">
                                        <p align="center">
                                            <i class="fa fa-arrow-down history-arrow mb-3"/>
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <hr/>

                    <h4>Foto Kejadian</h4>
                    <div class="row">
                        <div class="col-md-3 mb-3" v-for="(item, index) in incident.incident_images" :key="index">
                            <img :src="item.image_url" style="width: 100%"/>
                        </div>
                    </div>
                </div>
                <div class="card-footer text-end">
                    <button class="btn btn-secondary" @click="$router.push('/incident-reporting')">Back</button> &nbsp;
                    <div
                        class="btn btn-primary button-info"
                        data-bs-toggle="modal"
                        data-bs-target="#approvalModal"
                        v-if="!this.incident.is_finished && (this.incident.last_status === 'submitted' || this.incident.last_status === 'close' || this.incident.last_status === 'disclose' || this.incident.last_status === 'reject')"
                    >
                        Approval
                    </div>
                    <div
                        class="btn btn-primary button-info"
                        data-bs-toggle="modal"
                        data-bs-target="#closureModal"
                        v-if="!this.incident.is_finished && this.incident.last_status === 'approve'"
                    >
                        Closure
                    </div>
                </div>
            </form>
        </div>
        <div class="modal fade" id="approvalModal" ref="approvalModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalCenter" aria-hidden="true">
            <VerticalModal title="Approval Modal" @save="incidentApproval()">
                <div class="row">
                    <div class="col-md-12">
                        <div class="mt-2">
                            <label for="status">Status:</label>
                            <select id="status" name="status" class="form-select" v-model="approval.status" required>
                                <option value="approve" :selected="approval.status === 'approve' ? 'selected' : ''">Approve</option>
                                <option value="reject" :selected="approval.status === 'reject' ? 'selected' : ''">Reject</option>
                            </select>
                        </div>
                        <div class="mt-2" v-if="approval.status === 'approve'">
                            <label for="name">Analisa Kejadian:</label>
                            <input type="text" class="form-control" id="incident_analysis" v-model="approval.incident_analysis" required>
                        </div>
                        <div class="mt-2" v-if="approval.status === 'approve'">
                            <label for="name">Tindak Lanjut:</label>
                            <input type="text" class="form-control" id="follow_up_incident" v-model="approval.follow_up_incident" required>
                        </div>
                        <div class="mt-2" v-if="approval.status === 'approve'">
                            <label for="name">Foto Tindak Lanjut:</label>
                            <input type="file" class="form-control" id="name" @change="onChangeFile" required>
                        </div>
                        <div class="mt-2" v-if="approval.status === 'reject'">
                            <label for="name">Note:</label>
                            <input type="text" class="form-control" id="reason" v-model="approval.reason" required>
                        </div>
                    </div>
                </div>
            </VerticalModal>
        </div>
        <div class="modal fade" id="closureModal" ref="closureModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalCenter" aria-hidden="true">
            <VerticalModal title="Closure Modal" @save="incidentClosure()">
                <div class="row">
                    <div class="col-md-12">
                        <div class="mt-2">
                            <label for="status">Status:</label>
                            <select id="status" name="status" class="form-select" v-model="closure.status" required>
                                <option value="close" :selected="closure.status === 'close' ? 'selected' : ''">Close</option>
                                <option value="disclose" :selected="closure.status === 'disclose' ? 'selected' : ''">Disclose</option>
                            </select>
                        </div>
                        <div class="mt-2">
                            <label for="name">Keterangan:</label>
                            <input type="text" class="form-control" id="reason" v-model="closure.reason" required>
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

export default {
    components: {
        VerticalModal
    },
    data() {
        return {
            incident: {
                id: 0,
                employee_id: 0,
                category: "",
                name: "",
                latitude: "",
                longitude: "",
                location_name: "",
                incident_time: "",
                person: "",
                witness: "",
                cause: "",
                chronology: "",
                last_stage: "",
                last_status: "",
                is_finished: false,
                incident_images: [],
                incident_image_follow_up: [],
                incident_histories: []
            },
            approval: {
                status: null,
                incident_analysis: null,
                follow_up_incident: null,
                file: null,
                reason: null
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
        this.getIncident()
    },
    methods: {
        async getIncident() {
            await this.$axios.get(`/api/v1/admin/incident/view/${this.$route.params.id}`)
                .then(response => {
                    this.incident = response.data.data;
                    this.generateImagesGallery()
                    this.generateMap()
                })
                .catch(error => {
                    console.error(error);
                });
        },
        generateMap() {
            this.mapContainer = this.$el.querySelector('#map');

            this.map = L.map(this.mapContainer, {
                scrollWheelZoom: false
            }).setView([this.incident.latitude, this.incident.longitude], 16);

            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '© OpenStreetMap contributors'
            }).addTo(this.map);

            this.marker = L.marker([this.incident.latitude, this.incident.longitude], {icon: L.icon({
                    iconUrl: '/marker-icon.png'
                })}).addTo(this.map);
        },
        incidentApproval() {
            this.$axios.post(`/api/v1/admin/incident/approval/${this.$route.params.id}`, this.approval)
                .then(response => {
                    useToast().success("Success to update data", { position: 'bottom-right' });
                    this.getIncident()
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
        incidentClosure() {
            this.$axios.post(`/api/v1/admin/incident/closure/${this.$route.params.id}`, this.closure)
                .then(response => {
                    useToast().success("Success to update data", { position: 'bottom-right' });
                    this.getIncident()
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
        onChangeFile(e) {
            let formData = new FormData()
            formData.set('files[]', e.target.files[0])

            this.$axios.post(`/api/v1/admin/incident/upload-image`, formData)
                .then(response => {
                    this.approval.file = response.data.urls[0]
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
        generateImagesGallery() {
            this.incident.incident_images.forEach((item, index) => {
                console.log("IncidentImage", {
                    item: item,
                    idx: index
                })
            })
        }
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

.chronology-text-area {
    min-height: 200px !important
}
</style>
