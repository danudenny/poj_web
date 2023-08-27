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
                                    <label for="status">Lokasi Event</label>
                                    <select id="status" name="status" class="form-select" v-model="event.location_type" @change="onLocationEventTypeChange" required>
                                        <option value="internal" :selected="event.location_type === 'internal' ? 'selected' : ''">Internal</option>
                                        <option value="external" :selected="event.location_type === 'external' ? 'selected' : ''">External</option>
                                    </select>
                                </div>
                                <br/>
                                <div class="form-group mb-0">
                                    <div class="checkbox p-0">
                                        <input id="is_address_free_text" type="checkbox" v-model="isFreeTextAddress">
                                        <label class="text-muted" for="is_address_free_text">Isi Manual Alamat?</label>
                                    </div>
                                </div>
                                <div class="mt-2">
                                    <label for="name">Alamat</label>
                                    <textarea class="form-control" v-model="event.address" required :disabled="!isFreeTextAddress"></textarea>
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
                                                <label class="text-muted" for="repeat_is_monthly_fixed">Setiap tanggal {{ (new Date(Date.parse(event.date_event))).getDate() }}</label>
                                            </div>
                                        </div>
                                        <div v-if="event.repeat_is_monthly_fixed === false">
                                            <br/>
                                            <p>Setiap Minggu</p>
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <div class="mt-2">
                                                        <select id="status" name="status" class="form-select" v-model="monthlyFormat.first" required>
                                                            <option value="first" :selected="monthlyFormat.first === 'first' ? 'selected' : ''">Pertama</option>
                                                            <option value="second" :selected="monthlyFormat.first === 'second' ? 'selected' : ''">Ke Dua</option>
                                                            <option value="third" :selected="monthlyFormat.first === 'third' ? 'selected' : ''">Ke Tiga</option>
                                                            <option value="fourth" :selected="monthlyFormat.first === 'fourth' ? 'selected' : ''">Ke Empat</option>
                                                            <option value="last" :selected="monthlyFormat.first === 'last' ? 'selected' : ''">Terakhir</option>
                                                        </select>
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="mt-2">
                                                        <select id="status" name="status" class="form-select" v-model="monthlyFormat.second" required>
                                                            <option value="sunday" :selected="monthlyFormat.second === 'sunday' ? 'selected' : ''">Minggu</option>
                                                            <option value="monday" :selected="monthlyFormat.second === 'monday' ? 'selected' : ''">Senin</option>
                                                            <option value="tuesday" :selected="monthlyFormat.second === 'tuesday' ? 'selected' : ''">Selasa</option>
                                                            <option value="wednesday" :selected="monthlyFormat.second === 'wednesday' ? 'selected' : ''">Rabu</option>
                                                            <option value="thursday" :selected="monthlyFormat.second === 'thursday' ? 'selected' : ''">Kamis</option>
                                                            <option value="friday" :selected="monthlyFormat.second === 'friday' ? 'selected' : ''">Jumat</option>
                                                            <option value="saturday" :selected="monthlyFormat.second === 'saturday' ? 'selected' : ''">Sabtu</option>
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
                                <button class="btn btn-primary"><i class="fa fa-save"></i> Simpan</button> &nbsp;
                                <button class="btn btn-secondary" @click="$router.push('/event')">
                                    <i class="fa fa-close"></i>&nbsp;Cancel
                                </button>
                            </form>
                        </div>
                        <div class="col-md-6">
                            <div class="row">
                                <div class="col-md-12">
                                    <label for="address_lat_long">Search Address</label>
                                    <multiselect
                                        v-model="selectedAddress"
                                        placeholder="Search Address"
                                        label="display_name"
                                        track-by="id"
                                        :disabled="event.location_type === 'internal'"
                                        :options="listAddress"
                                        :multiple="false"
                                        :required="true"
                                        @search-change="onUnitSearchAddress"
                                        @select="onAddressSelected"
                                    >
                                    </multiselect>
                                </div>
                            </div>
                            <br/>
                            <div id="map" class="mb-4 localMap" v-if="event.location_type === 'external'"></div>
                            <div ref="unitsTable" v-if="event.location_type === 'internal'"></div>

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
                location_type: 'external',
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
            addressQuery: {
                name: null,
                onSearch: false
            },
            isFreeTextAddress: false,
            currentPage: 1,
            pageSize: 10,
            filterName: "",
            filterUnit: "",
            employees: [],
            months: ['Januri', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'],
            map: null,
            marker: null,
            selectedEmployees: [],
            listAddress: [],
            selectedAddress: null,
            selectedUnitID: null,
        }
    },
    mounted() {
        this.generateMap()
        this.generateEmployeesTable()
    },
    methods: {
        generateEmployeesTable() {
            const ls = localStorage.getItem('my_app_token')
            const table = new Tabulator(this.$refs.employeesTable, {
                ajaxURL: '/api/v1/admin/employee',
                layout: 'fitColumns',
                columns: [
                    {
                        formatter: "rowSelection",
                        hozAlign: "center",
                        width: 20,
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
                        title: 'Unit',
                        field: 'last_unit.name',
                    }
                ],
                pagination: true,
                paginationMode: 'remote',
                responsiveLayout: true,
                filterMode:"remote",
                paginationSize: this.pageSize,
                ajaxConfig: {
                    headers: {
                        Authorization: `Bearer ${ls}`,
                        "X-Unit-Relation-ID": this.$store.state.activeAdminUnit?.unit_relation_id ?? '',
                        "X-Selected-Role": this.$store.state.currentRole,
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
                    return `${url}?page=${params.page}&size=${params.size}&name=${localFilter.name}`
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
                    if (this.selectedEmployees.includes(row.getData().id)) {
                        row.select()
                    }
                },
            });
            table.on("rowSelectionChanged", (data, rows, selected, deselected) => {
                if(selected.length > 0) {
                    if (!this.selectedEmployees.includes(selected[0].getData().id)) {
                        this.selectedEmployees.push(selected[0].getData().id)
                    }
                }
                if (deselected.length > 0) {
                    let deselectedID = deselected[0].getData().id
                    this.selectedEmployees = this.selectedEmployees.filter((val) => {
                        return deselectedID !== val
                    })
                }
            })
        },
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
                paginationSize: this.pageSize,
                ajaxConfig: {
                    headers: {
                        Authorization: `Bearer ${ls}`,
                        "X-Unit-Relation-ID": this.$store.state.activeAdminUnit?.unit_relation_id ?? '',
                        "X-Selected-Role": this.$store.state.currentRole,
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
                    console.log("URLGenerateParam", params)
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
                    if (this.selectedUnitID !== null && (this.selectedUnitID.id === row.getData().id)) {
                        row.select()
                    }
                },
            });
            table.on("rowSelectionChanged", (data, rows, selected, deselected) => {
                if(selected.length > 0) {
                    this.selectedUnitID = {
                        id: selected[0].getData().id,
                        latitude: selected[0].getData().lat,
                        longitude: selected[0].getData().long
                    }

                    this.fetchLatLngAddress(this.selectedUnitID.latitude, this.selectedUnitID.longitude)
                }
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

                this.fetchLatLngAddress(this.event.latitude, this.event.longitude)
            })
        },
        fetchLatLngAddress(latitude, longitude) {
            this.$axios.get(`https://nominatim.openstreetmap.org/reverse?format=jsonv2&lat=${latitude}&lon=${longitude}`)
                .then(resp => {
                    this.event.address = resp.data.display_name
                })
        },
        fetchListAddress() {
            this.$axios.get(`https://nominatim.openstreetmap.org/search?q=${this.addressQuery.name}&format=json&polygon=1&addressdetails=1`)
                .then(resp => {
                    this.listAddress = resp.data
                    this.addressQuery.onSearch = false
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

            this.event.event_attendances = this.selectedEmployees
            this.event.time_event = this.event.time_event + ":00"

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
        },
        onLocationEventTypeChange(e) {
            if(e.target.value === 'external') {
                this.generateMap()
            } else if (e.target.value === 'internal') {
                this.generateUnitsTable()
            }
        },
        onUnitSearchAddress(val) {
            this.addressQuery.name = val

            if (!this.addressQuery.onSearch) {
                this.addressQuery.onSearch = true
                setTimeout(() => {
                    this.fetchListAddress()
                }, 1000)
            }
        },
        onAddressSelected() {
            if (this.selectedAddress === null) {
                return
            }

            this.event.latitude = this.selectedAddress.lat
            this.event.longitude = this.selectedAddress.lon
            this.event.address = this.selectedAddress.display_name

            if(this.marker != null) {
                this.map.removeLayer(this.marker)
            }

            this.marker = L.marker([this.event.latitude, this.event.longitude], {icon: L.icon({
                    iconUrl: '/marker-icon.png'
                })}).addTo(this.map)

            this.map.setView([this.event.latitude, this.event.longitude], 16)
        }
    },
};
</script>

<style>
.localMap {
    z-index: 1 !important;
}
</style>
