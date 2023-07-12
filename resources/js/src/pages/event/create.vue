<template>
    <div class="container-fluid">
        <Breadcrumbs main="Detail Incident Reporting"/>
        <div class="col-sm-12">
            <form class="card">
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <form v-on:submit.prevent="onSubmitForm">
                                <div class="mt-2">
                                    <label for="status">Kategori Event:</label>
                                    <select id="status" name="status" class="form-select" v-model="event.event_type" required>
                                        <option value="anggaran" :selected="event.event_type === 'anggaran' ? 'selected' : ''">Anggaran</option>
                                        <option value="non-anggaran" :selected="event.event_type === 'non-anggaran' ? 'selected' : ''">Non Anggaran</option>
                                    </select>
                                </div>
                                <div class="mt-2">
                                    <label for="name">Foto Tema</label>
                                    <input type="file" class="form-control" id="name" @change="onChangeFile" required>
                                </div>
                                <div class="mt-2">
                                    <label for="name">Judul Event</label>
                                    <input type="text" class="form-control" v-model="event.title" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Deskripsi</label>
                                    <textarea class="form-control" v-model="event.description" required></textarea>
                                </div>
                                <div class="mt-2">
                                    <label for="name">Alamat</label>
                                    <input type="text" class="form-control" v-model="event.address" required>
                                </div>
                                <div class="mt-2">
                                    <label for="name">Tanggal</label>
                                    <input type="date" class="form-control" v-model="event.date_event" required>
                                </div>
                                <div class="mt-2">
                                    <label for="name">Waktu</label>
                                    <input type="time" class="form-control" v-model="event.time_event" required>
                                </div>
                                <br/>
                                <div class="form-group mb-0">
                                    <div class="checkbox p-0">
                                        <input id="is_need_absence" type="checkbox" v-model="event.is_need_absence">
                                        <label class="text-muted" for="is_need_absence">Apakah membutuhkan absen?</label>
                                    </div>
                                </div>
                                <br/>
                                <div class="form-group mb-0">
                                    <div class="checkbox p-0">
                                        <input id="is_repeat" type="checkbox" v-model="event.is_repeat">
                                        <label class="text-muted" for="is_repeat">Recurring Event?</label>
                                    </div>
                                </div>

                                <div v-if="event.is_repeat">
                                    <div class="mt-2">
                                        <label for="status">Repeat Type:</label>
                                        <select id="status" name="status" class="form-select" v-model="event.repeat_type" required>
                                            <option value="daily" :selected="event.repeat_type === 'daily' ? 'selected' : ''">Daily</option>
                                            <option value="weekly" :selected="event.repeat_type === 'weekly' ? 'selected' : ''">Weekly</option>
                                            <option value="monthly" :selected="event.repeat_type === 'monthly' ? 'selected' : ''">Monthly</option>
                                            <option value="yearly" :selected="event.repeat_type === 'yearly' ? 'selected' : ''">Yaerly</option>
                                        </select>
                                    </div>
                                    <div class="mt-2">
                                        <label for="name">Repeat Every:</label>
                                        <input type="number" class="form-control" v-model="event.repeat_every" required>
                                    </div>
                                    <div class="form-group mb-0" v-if="event.repeat_type === 'weekly'">
                                        <br/>
                                        <p>Weekly Days</p>
                                        <div class="checkbox p-0">
                                            <input id="repeat-sunday" type="checkbox" v-model="weeklyDays.sunday">
                                            <label class="text-muted" for="repeat-sunday">Sunday</label>
                                        </div>
                                        <div class="checkbox p-0">
                                            <input id="repeat-monday" type="checkbox" v-model="weeklyDays.monday">
                                            <label class="text-muted" for="repeat-monday">Monday</label>
                                        </div>
                                        <div class="checkbox p-0">
                                            <input id="repeat-tuesday" type="checkbox" v-model="weeklyDays.tuesday">
                                            <label class="text-muted" for="repeat-tuesday">Tuesday</label>
                                        </div>
                                        <div class="checkbox p-0">
                                            <input id="repeat-wednesday" type="checkbox" v-model="weeklyDays.wednesday">
                                            <label class="text-muted" for="repeat-wednesday">Wednesday</label>
                                        </div>
                                        <div class="checkbox p-0">
                                            <input id="repeat-thursday" type="checkbox" v-model="weeklyDays.thursday">
                                            <label class="text-muted" for="repeat-thursday">Thursday</label>
                                        </div>
                                        <div class="checkbox p-0">
                                            <input id="repeat-friday" type="checkbox" v-model="weeklyDays.friday">
                                            <label class="text-muted" for="repeat-friday">Friday</label>
                                        </div>
                                        <div class="checkbox p-0">
                                            <input id="repeat-saturday" type="checkbox" v-model="weeklyDays.saturday">
                                            <label class="text-muted" for="repeat-saturday">Saturday</label>
                                        </div>
                                    </div>
                                    <div v-if="event.repeat_type === 'monthly'">
                                        <div class="form-group mb-0">
                                            <div class="checkbox p-0" v-if="event.date_event">
                                                <input id="repeat_is_monthly_fixed" type="checkbox" v-model="event.repeat_is_monthly_fixed">
                                                <label class="text-muted" for="repeat_is_monthly_fixed">Setiap {{ (new Date(Date.parse(event.date_event))).getDate() }} {{ months[(new Date(Date.parse(event.date_event))).getMonth()] }}</label>
                                            </div>
                                        </div>
                                        <div v-if="event.repeat_is_monthly_fixed === false">
                                            <br/>
                                            <p>Setiap</p>
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <div class="mt-2">
                                                        <select id="status" name="status" class="form-select" v-model="monthlyFormat.first" required>
                                                            <option value="first" :selected="monthlyFormat.first === 'first' ? 'selected' : ''">First</option>
                                                            <option value="second" :selected="monthlyFormat.first === 'second' ? 'selected' : ''">Second</option>
                                                            <option value="third" :selected="monthlyFormat.first === 'third' ? 'selected' : ''">Third</option>
                                                            <option value="fourth" :selected="monthlyFormat.first === 'fourth' ? 'selected' : ''">Fourth</option>
                                                            <option value="last" :selected="monthlyFormat.first === 'last' ? 'selected' : ''">Last</option>
                                                        </select>
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="mt-2">
                                                        <select id="status" name="status" class="form-select" v-model="monthlyFormat.second" required>
                                                            <option value="sunday" :selected="monthlyFormat.second === 'sunday' ? 'selected' : ''">Sunday</option>
                                                            <option value="monday" :selected="monthlyFormat.second === 'monday' ? 'selected' : ''">Monday</option>
                                                            <option value="tuesday" :selected="monthlyFormat.second === 'tuesday' ? 'selected' : ''">Tuesday</option>
                                                            <option value="wednesday" :selected="monthlyFormat.second === 'wednesday' ? 'selected' : ''">Wednesday</option>
                                                            <option value="thursday" :selected="monthlyFormat.second === 'thursday' ? 'selected' : ''">Thursday</option>
                                                            <option value="friday" :selected="monthlyFormat.second === 'friday' ? 'selected' : ''">Friday</option>
                                                            <option value="saturday" :selected="monthlyFormat.second === 'saturday' ? 'selected' : ''">Saturday</option>
                                                        </select>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="mt-2">
                                        <label for="name">Recurring Berakhir Pada Tanggal</label>
                                        <input type="date" class="form-control" v-model="event.repeat_end_date" required>
                                    </div>
                                </div>
                                <br/>
                                <button class="btn btn-primary">Simpan</button>
                            </form>
                        </div>
                        <div class="col-md-6">
                            <div id="map" class="mb-4"></div>

                            <hr/>

                            <div ref="employeesTable"></div>
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
            event: {
                event_type: null,
                image_url: null,
                title: null,
                description: null,
                latitude: null,
                longitude: null,
                address: null,
                date: null,
                date_event: null,
                time_event: null,
                is_need_absence: false,
                is_repeat: false,
                repeat_type: null,
                repeat_every: null,
                repeat_end_date: null,
                repeat_days: null,
                repeat_is_monthly_fixed: false,
                event_attendances: []
            },
            weeklyDays: {
                sunday: false,
                monday: false,
                tuesday: false,
                wednesday: false,
                thursday: false,
                friday: false,
                saturday: false
            },
            monthlyFormat: {
                first: null,
                second: null
            },
            employees: [],
            months: ['Januri', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'],
            map: null,
            marker: null
        }
    },
    mounted() {
        this.generateMap()
        this.getEmployees()
    },
    methods: {
        getEmployees() {
            const unitId = JSON.parse(localStorage.getItem('USER_STORAGE_KEY'));
            this.$axios.get(`/api/v1/admin/employee?unit_id=${parseInt(unitId.unit_id)}&sort=asc`)
                .then(response => {
                    this.employees = response.data.data;
                    this.generateEmployeesTable()
                })
                .catch(error => {
                    console.error(error);
                });
        },
        generateEmployeesTable() {
            const table = new Tabulator(this.$refs.employeesTable, {
                data: this.employees,
                layout: 'fitDataStretch',
                columns: [
                    {
                        formatter: "rowSelection",
                        titleFormatter: "rowSelection",
                        hozAlign: "center",
                        headerSort: false,
                        cellClick: function (e, cell) {
                            cell.getRow()
                        },
                    },
                    {
                        title: 'Name',
                        field: 'name',
                        headerFilter:"input"
                    }
                ],
                pagination: 'local',
                paginationSize: 20,
                paginationSizeSelector: [10, 20, 50, 100],
                headerFilter: true,
                rowFormatter: (row) => {

                },
            });
            table.on("rowSelectionChanged", (data, rows, selected, deselected) => {
                this.event.event_attendances = rows.map(row => row.getData().id);
            })
        },
        generateMap() {
            this.mapContainer = this.$el.querySelector('#map');

            this.map = L.map(this.mapContainer, {}).setView([-6.174824, 106.826656], 16);

            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '© OpenStreetMap contributors'
            }).addTo(this.map);

            this.map.on('click', (e) => {
                this.event.latitude = e.latlng.lat
                this.event.longitude = e.latlng.lng

                if(this.marker != null) {
                    this.map.removeLayer(this.marker)
                }

                this.marker = L.marker([this.event.latitude, this.event.longitude], {icon: L.icon({
                        iconUrl: '/marker-icon.png'
                    })}).addTo(this.map)
            })
        },
        onChangeFile(e) {
            let formData = new FormData()
            formData.set('files[]', e.target.files[0])

            this.$axios.post(`/api/v1/admin/incident/upload-image`, formData)
                .then(response => {
                    this.event.image_url = response.data.urls[0]
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
        onSubmitForm(e) {
            if (this.event.repeat_type === 'weekly') {
                let weeklyDays = [];
                for (const key in this.weeklyDays) {
                    if (this.weeklyDays[key]) {
                        weeklyDays.push(key)
                    }
                }

                if (weeklyDays.length === 0) {
                    useToast().error("Mohon memilih hari pada mingguan" , { position: 'bottom-right' });
                    return
                }

                this.event.repeat_days = weeklyDays.join(",")
            } else if (this.event.repeat_type === 'monthly') {
                let monthlyDays = [];
                if (this.event.repeat_is_monthly_fixed) {
                    monthlyDays.push(this.event.date_event)
                } else {
                    monthlyDays.push(this.monthlyFormat.first)
                    monthlyDays.push(this.monthlyFormat.second)
                }

                if (monthlyDays.length === 0) {
                    useToast().error("Mohon memilih tanggal pada bulanan" , { position: 'bottom-right' });
                    return
                }

                this.event.repeat_days = monthlyDays.join(",")
            }

            this.$axios.post(`/api/v1/admin/event/create`, this.event)
                .then(response => {
                    useToast().success("Success to create data", { position: 'bottom-right' });
                    this.$router.push({name: 'event_request_detail', params: {id: response.data.data.id}});
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
