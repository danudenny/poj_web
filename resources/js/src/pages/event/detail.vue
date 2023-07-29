<template>
    <div class="container-fluid">
        <Breadcrumbs main="Detail Incident Reporting"/>
        <div class="col-sm-12">
            <div class="card">
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mt-2">
                                <label for="status">Kategori Event:</label>
                                <select id="status" name="status" class="form-select" v-model="event.event_type" required :disabled="!isEdit">
                                    <option value="anggaran" :selected="event.event_type === 'anggaran' ? 'selected' : ''">Anggaran</option>
                                    <option value="non-anggaran" :selected="event.event_type === 'non-anggaran' ? 'selected' : ''">Non Anggaran</option>
                                </select>
                                <br/>
                            </div>
                            <div class="mb-3">
                                <div class="mt-2" v-if="isEdit">
                                    <label for="name">Foto Tema</label>
                                    <input type="file" class="form-control" id="name" @change="onChangeFile" required>
                                    <br/>
                                </div>
                                <img class="image-theme" :src="event.image_url"/>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Judul Event</label>
                                <input class="form-control" type="text" v-model="event.title" :disabled="!isEdit">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Deskripsi Event</label>
                                <textarea class="form-control chronology-text-area" v-model="event.description" :disabled="!isEdit">{{event.description}}</textarea>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Alamat</label>
                                <input class="form-control" type="text" v-model="event.address" disabled>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Tanggal Event</label>
                                <input class="form-control" type="date" v-model="event.date_event" :disabled="!isEdit || !isGenerateNewDate">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Waktu Event</label>
                                <input class="form-control" type="text" v-model="event.time_event" :disabled="!isEdit || !isGenerateNewDate">
                            </div>
                            <div class="mb-3" v-if="event.is_repeat">
                                <label class="form-label">Recurring Event</label>
                                <input class="form-control" type="text" v-model="event.event_repeat_description" disabled>
                            </div>
                            <div class="form-group mb-0">
                                <div class="checkbox p-0">
                                    <input id="is_address_free_text" type="checkbox" v-model="isGenerateNewDate" :disabled="!isEdit">
                                    <label class="text-muted" for="is_address_free_text">Apakah Ingin Generate Ulang Tanggal?</label>
                                </div>
                            </div>
                            <div class="form-group mb-0" v-if="isGenerateNewDate">
                                <div class="checkbox p-0">
                                    <input id="is_repeat" type="checkbox" v-model="event.is_repeat">
                                    <label class="text-muted" for="is_repeat">Recurring Event?</label>
                                </div>
                            </div>
                            <br/>
                            <div v-if="event.is_repeat && isGenerateNewDate">
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
                        </div>
                        <div class="col-md-6">
                            <div id="map" ref="map" class="mb-4"></div>

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
                                            <tr>
                                                <td>Notes</td>
                                                <td>: {{item.notes}}</td>
                                            </tr>
                                        </table>
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
                    <div class="row">
                        <div class="col-md-6">
                            <div ref="listAttendance"></div>
                        </div>
                        <div class="col-md-6">
                            <div ref="listEventDates"></div>
                        </div>
                    </div>
                </div>
                <div class="card-footer text-end">
                    <button class="btn btn-secondary" @click="$router.push('/event')">Back</button>&nbsp
                    <div
                        class="btn btn-primary button-info"
                        v-if="this.event.last_status === 'draft' && this.isEdit"
                        data-bs-toggle="modal"
                        data-bs-target="#editEvent"
                    >
                        <span>Simpan</span>
                    </div>
                    <button
                        class="btn btn-primary button-info"
                        v-if="this.event.last_status === 'draft' && !this.isEdit"
                        @click="onEdit"
                    >
                        <span>Edit</span>
                    </button>
                    &nbsp
                    <div
                        class="btn btn-primary button-info"
                        data-bs-toggle="modal"
                        data-bs-target="#publishEvent"
                        v-if="this.event.last_status === 'draft' && !this.isEdit"
                    >
                        <span>Publish</span>
                    </div>
                    <div
                        class="btn btn-primary button-info"
                        data-bs-toggle="modal"
                        data-bs-target="#approvalModal"
                        v-if="this.event.is_can_approve"
                    >
                        Approval
                    </div>
                </div>
            </div>
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
        <div class="modal fade" id="publishEvent" ref="publishEvent" tabindex="-1" role="dialog" aria-labelledby="exampleModalCenter" aria-hidden="true">
            <VerticalModal title="Publish Event?" @save="onPublish()">
                <div class="row">
                    <div class="col-md-12">
                        <p>Are you sure want to publish this event?</p>
                    </div>
                </div>
            </VerticalModal>
        </div>
        <div class="modal fade" id="editEvent" ref="editEvent" tabindex="-1" role="dialog" aria-labelledby="exampleModalCenter" aria-hidden="true">
            <VerticalModal title="Edit Event?" @save="onUpdateEvent()">
                <div class="row">
                    <div class="col-md-12">
                        <p>Are you sure want to edit this event?</p>
                    </div>
                </div>
            </VerticalModal>
        </div>
        <div class="modal fade" id="deleteAttendance" ref="deleteAttendance" tabindex="-1" role="dialog" aria-labelledby="exampleModalCenter" aria-hidden="true">
            <VerticalModal title="Edit Event?" @save="onDeleteAttendance()">
                <div class="row">
                    <div class="col-md-12">
                        <p>Are you sure want to delete {{ this.selectedAttendance.employee.name }} from this event?</p>
                    </div>
                </div>
            </VerticalModal>
        </div>
        <div class="modal fade" id="addAttendance" ref="addAttendance" tabindex="-1" role="dialog" aria-labelledby="exampleModalCenter" aria-hidden="true">
            <VerticalModal title="Add New Attendance" @save="onAddNewAttendance()">
                <div class="row">
                    <div class="col-md-12">
                        <div ref="employeeTable"></div>
                    </div>
                </div>
            </VerticalModal>
        </div>
    </div>
    <div>
        <div
            ref="deleteAttendanceModalButton"
            id="deleteAttendanceModalButton"
            style="display:none"
            data-bs-toggle="modal"
            data-bs-target="#deleteAttendance"
        ></div>
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
                repeat_is_monthly_fixed: false,
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
            selectedAttendance: {
                id: null,
                employee: {
                    name: null
                }
            },
            employeePagination: {
                currentPage: 1,
                pageSize: 10,
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
            addNewAttendanceList: [],
            isEdit: false,
            isGenerateNewDate: false,
            mapContainer: null,
            map: null,
            marker: null,
            incidentImages: [
                {
                    src: 'http://alakad.optimajasa.co.id:9000/att-poj-bucket/uploads/incident/64aab2e041114_pagar_rusak.jpeg',
                    description: 'Sunken dreams II. by Arbebuk',
                }
            ]
        }
    },
    created() {
        this.getEvent()
    },
    mounted() {
        this.generateEmployeesTable()
    },
    methods: {
        async getEvent() {
            await this.$axios.get(`/api/v1/admin/event/view/${this.$route.params.id}`)
                .then(response => {
                    this.event = response.data.data;
                    this.processWeeklyDaysGetter(this.event.repeat_days)
                    this.generateMap()
                    this.initializeEventAttendanceTable()
                    this.initializeEventDates()
                })
                .catch(error => {
                    console.error(error);
                });
        },
        async generateMap() {
            if (this.map != null) {
                return
            }

            this.mapContainer = this.$refs.map;

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
                        width: 20
                    },
                    {
                        title: 'Name',
                        field: 'employee.name',
                        headerFilter:"input"
                    },
                    {
                        title: 'Unit',
                        field: 'employee.last_unit.name',
                        headerFilter:"input"
                    },
                    {
                        title: this.isEdit ?
                            `<p align="center" style="margin-top:10px"><button class="button-icon button-success" data-bs-toggle="modal" data-bs-target="#addAttendance"><i class="fa fa-plus"></i> </button></p>` :
                            `<p align="center" style="margin-top:10px"><button class="button-icon button-success disabled" disabled><i class="fa fa-plus"></i> </button></p>`,
                        formatter: (cell, formatterParams, onRendered) => {
                            if (this.isEdit) {
                                return `<button class="button-icon button-danger" data-id="${cell.getRow().getData().id}"><i class="fa fa-trash"></i> </button>`;
                            } else {
                                return `<button class="button-icon button-danger ${!this.isEdit? 'disabled' : ''}" ${!this.isEdit?'disabled' : ''} data-id="${cell.getRow().getData().id}"><i class="fa fa-trash"></i> </button>`;
                            }
                        },
                        width: 70,
                        hozAlign: 'center',
                        sortable: false,
                        headerSort: false,
                        cellClick: (e, cell) => {
                            if (this.isEdit) {
                                this.selectedAttendance = cell.getRow().getData();
                                this.$refs.deleteAttendanceModalButton.click();
                            }
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
                        field: 'date_time_with_timezone',
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
        async generateEmployeesTable() {
            const ls = localStorage.getItem('my_app_token')
            const table = new Tabulator(this.$refs.employeeTable, {
                ajaxURL: '/api/v1/admin/employee/paginated',
                layout: 'fitColumns',
                columns: [
                    {
                        formatter: "rowSelection",
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
                        title: 'Work Email',
                        field: 'work_email'
                    },
                    {
                        title: 'Current Unit',
                        field: 'last_unit.name'
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
                        "X-Unit-Relation-ID": this.$store.state.activeAdminUnit?.unit_relation_id ?? ''
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
                    return `${url}?page=${params.page}&per_page=${params.size}&name=${localFilter.name}`
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
                    if (this.addNewAttendanceList.includes(row.getData().id)) {
                        row.select()
                    }
                },
            });
            table.on("rowSelectionChanged", (data, rows, selected, deselected) => {
                if(selected.length > 0) {
                    if(!this.addNewAttendanceList.includes(selected[0].getData().id)) {
                        this.addNewAttendanceList.push(selected[0].getData().id)
                    }
                }
                if (deselected.length > 0) {
                    let deselectedID = deselected[0].getData().id
                    this.addNewAttendanceList = this.addNewAttendanceList.filter((val) => {
                        return deselectedID !== val
                    })
                }
            })
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
        onPublish() {
            this.$axios.post(`/api/v1/admin/event/publish/${this.$route.params.id}`, this.approval)
                .then(response => {
                    useToast().success("Success to publish event", { position: 'bottom-right' });
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
        onUpdateEvent() {
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
                console.log("MonthlyFormat", this.monthlyFormat)
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

            if (this.isGenerateNewDate) {
                this.event.is_change_schedule = true
            }

            this.$axios.post(`/api/v1/admin/event/edit/${this.$route.params.id}`, this.event)
                .then(response => {
                    useToast().success("Success to edit event", { position: 'bottom-right' });
                    this.getEvent()
                    this.onEdit()
                })
                .catch(error => {
                    if(error.response.data.message instanceof Object) {
                        for (const key in error.response.data.message) {
                            useToast().error(error.response.data.message[key][0], { position: 'bottom-right' });
                        }
                    } else {
                        useToast().error(error.response.data.message , { position: 'bottom-right' });
                    }
                    this.onEdit()
                });
        },
        onEdit() {
            this.isEdit = !this.isEdit
            if (!this.isEdit) {
                this.isGenerateNewDate = false
            }
            this.initializeEventAttendanceTable()
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
        onDeleteAttendance() {
            this.$axios.delete(`/api/v1/admin/event/remove-attendance/${this.selectedAttendance.id}`)
                .then(response => {
                    useToast().success("Success to remove attendance", { position: 'bottom-right' });
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
        onAddNewAttendance() {
            console.log(this.addNewAttendanceList)
            this.$axios.post(`/api/v1/admin/event/add-attendance/${this.$route.params.id}`, {
                'employee_ids': this.addNewAttendanceList
            })
                .then(response => {
                    useToast().success("Success to add new attendance", { position: 'bottom-right' });
                    this.addNewAttendanceList = [];
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
        processWeeklyDaysGetter(repeat_days) {
            if (this.event.repeat_type === 'weekly') {
                let days = repeat_days.split(",")

                days.forEach(day => {
                    this.weeklyDays[day] = true
                })
            } else if (this.event.repeat_type === 'monthly') {
                let days = repeat_days.split(",")

                if (days.length >= 2) {
                    this.event.repeat_is_monthly_fixed = false

                    this.monthlyFormat = {
                        first: days[0],
                        second: days[1]
                    }
                } else {
                    this.event.repeat_is_monthly_fixed = true
                }
            }
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

.image-theme {
    width: 100% !important;
}
</style>
