<template>
    <div class="container-fluid">
        <Breadcrumbs :title="$route.name"/>
        <div class="col-sm-12">
            <div class="card">
                <div className="card-header bg-primary">
                    <div class="d-flex justify-content-between">
                        <h5>{{item.name}}</h5>
                        <button class="btn btn-sm btn-outline-warning" @click="$router.push('/kanwils')">
                            <i class="icofont icofont-double-left"></i>&nbsp;Back
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <ul class="nav nav-pills nav-primary" id="pills-icontab" role="tablist">
                                <li class="nav-item"><a class="nav-link active" id="pills-iconhome-tab" data-bs-toggle="pill" href="#pills-iconhome" role="tab" aria-controls="pills-iconhome" aria-selected="true"><i class="icofont icofont-info"></i>Basic Information</a></li>
                                <li class="nav-item"><a class="nav-link" id="pills-iconprofile-tab" data-bs-toggle="pill" href="#pills-iconprofile" role="tab" aria-controls="pills-iconprofile" aria-selected="false"><i class="icofont icofont-sub-listing"></i>Sub Unit</a></li>
                                <li class="nav-item"><a class="nav-link" id="pills-job-tab" data-bs-toggle="pill" href="#pills-job" role="tab" aria-controls="pills-job" aria-selected="false"><i class="icofont icofont-file-document"></i>Jobs</a></li>
                                <li class="nav-item"><a class="nav-link" id="pills-timesheet-tab" data-bs-toggle="pill" href="#pills-timesheet" role="tab" aria-controls="pills-timesheet" aria-selected="false"><i class="icofont icofont-clock-time"></i>Timesheet</a></li>
                                <li class="nav-item"><a class="nav-link" id="pills-reporting-tab" data-bs-toggle="pill" href="#pills-reporting" role="tab" aria-controls="pills-reporting" aria-selected="false"><i class="icofont icofont-calendar"></i>Reporting</a></li>
                                <li class="nav-item"><a class="nav-link" id="pills-employee-tab" data-bs-toggle="pill" href="#pills-employee" role="tab" aria-controls="pills-employee" aria-selected="false"><i class="icofont icofont-users"></i>Employee</a></li>
                            </ul>
                        </div>
                    </div>
                    <div class="tab-content" id="pills-icontabContent">
                        <hr>
                        <div class="tab-pane fade show active" id="pills-iconhome" role="tabpanel" aria-labelledby="pills-iconhome-tab">
                            <div>
                                <button type="button" v-if="editing" class="btn btn-warning" @click="editData">
                                    <i class="fa fa-pencil-square"></i> Edit Data
                                </button>
                                <div v-else class="d-flex justify-content-end column-gap-2">
                                    <button type="button" class="btn btn-success" @click="saveData">
                                        <i class="fa fa-save"></i> Save Data
                                    </button>
                                    <button type="button" class="btn btn-danger" @click="closeEdit">
                                        <i class="fa fa-times-circle"></i> Cancel Edit
                                    </button>
                                </div>
                            </div>
                            <hr>
                            <div class="row">
                                <div class="col-md-12">
                                    <div class="mb-3">
                                        <label class="form-label">Name</label>
                                        <input class="form-control" type="text" v-model="item.name" disabled>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Latitude</label>
                                        <input class="form-control" type="text" v-model="item.lat" :disabled="editing">
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Early Tolerance (minutes)</label>
                                        <input class="form-control" type="text" v-model="item.early_buffer" :disabled="editing">
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Radius Buffer (meters)</label>
                                        <input class="form-control" type="text" v-model="item.radius" :disabled="editing">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Longitude</label>
                                        <input class="form-control" type="text" v-model="item.long" :disabled="editing">
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Late Tolerance (minutes)</label>
                                        <input class="form-control" type="text" v-model="item.late_buffer" :disabled="editing">
                                    </div>
                                </div>
                                <div class="col-md-12" v-show="item.lat && item.long">
                                    <div id="mapContainer" style="height: 400px; z-index: 1; width: 100%">
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="tab-pane fade" id="pills-iconprofile" role="tabpanel" aria-labelledby="pills-iconprofile-tab">
                            <div class="row">
                                <div class="col-md-12">
                                    <table class="table table-striped table-bordered">
                                        <thead>
                                        <tr>
                                            <th>
                                                Unit Kantor Wilayah
                                                <input class="form-control mt-2" type="text" v-model="search" @input="handleInputChange" placeholder="Search">
                                            </th>
                                        </tr>
                                        </thead>
                                        <tbody>
                                        <tr v-for="(it, index) in item.child" :key="it" v-if="item.child && item.child.length > 0">
                                            <td>{{ it.name }}</td>
                                        </tr>
                                        <tr v-else>
                                            <td><span>No Data Available</span></td>
                                        </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                        <div class="tab-pane fade" id="pills-job" role="tabpanel" aria-labelledby="pills-job-tab">
                            <div v-show="hideJob">
                                <div v-if="loading" className="text-center">
                                    <img src="../../assets/loader.gif" alt="loading" width="100">
                                </div>
                                <div class="d-flex justify-content-end mb-2">
                                    <button class="btn btn-success" @click.prevent="hideTableJob">
                                        <i class="icofont icofont-briefcase"></i> &nbsp; Assign Jobs
                                    </button>
                                </div>
                                <div ref="jobTable"></div>
                            </div>
                            <div v-show="!hideJob">
                                <div class="d-flex justify-content-end mb-2 column-gap-2">
                                    <button class="btn btn-success" @click.prevent="showTableJob">
                                        <i class="icofont icofont-arrow-left"></i> &nbsp; Back
                                    </button>
                                    <button class="btn btn-outline-warning" @click.prevent="saveAssignJob">
                                        <i class="icofont icofont-save"></i> &nbsp; Save
                                    </button>
                                </div>
                                <table class="table table-striped">
                                    <thead>
                                    <tr>
                                        <th></th>
                                        <th>Job Title</th>
                                        <th>Need Camera</th>
                                        <th>Need Upload</th>
                                        <th>Need Reporting</th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    <tr class="text-center" v-for="job in assignJob" :key="job.id" v-if="assignJob.length > 0">
                                        <td><input type="checkbox" v-model="selectedJobs" :value="job.job_id"></td>
                                        <td>{{ job.job_name }}</td>
                                        <td><input type="checkbox" v-model="job.is_camera"></td>
                                        <td><input type="checkbox" v-model="job.is_upload"></td>
                                        <td><input type="checkbox" v-model="job.is_mandatory_reporting"></td>
                                    </tr>
                                    <tr v-else>
                                        <td colspan="5">
                                            <span> No Data Available </span>
                                        </td>
                                    </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <div class="tab-pane fade" id="pills-timesheet" role="tabpanel" aria-labelledby="pills-timesheet-tab">
                          <Timesheet :id="paramsId"/>
                        </div>
                        <div class="tab-pane fade" id="pills-reporting" role="tabpanel" aria-labelledby="pills-reporting-tab">
                            <WorkReporting :unit_id="queryUnitId"/>
                        </div>
                        <div class="tab-pane fade" id="pills-employee" role="tabpanel" aria-labelledby="pills-employee-tab">
                            <Employee :id="paramsId" />
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

</template>

<script>
import axios from 'axios';
import {useToast} from 'vue-toastification';
import {TabulatorFull as Tabulator} from "tabulator-tables";
import Timesheet from "./timesheet.vue";
import AssignWorkReporting from "./modal/assignWorkReporting.vue";
import WorkReporting from "@components/work_reporting.vue";
import Employee from "./employee.vue";
import L from "leaflet";
import {buffer, point} from "@turf/turf";

export default {
    components: {
        Employee,
        Timesheet,
        AssignWorkReporting,
        WorkReporting
    },
    data() {
        return {
            modalTitle: "Work Reporting",
            paramsId: this.$route.params.id,
            queryUnitId: this.$route.query.unit_id,
            item: [],
            search: null,
            editing: true,
            loading: false,
            table: null,
            jobs: [],
            hideJob: true,
            tableAssignJob: null,
            assignJob: [],
            selectedJob: null,
            is_camera: false,
            is_upload: false,
            is_reporting: false,
            is_mandatory_reporting: false,
            selectedJobs: [],
            reportJobs: [],
            wrMultiple: false,
            singleWork: true,
            multipleWork: false,
            numberOfWorks: 0,
            updateMandatory: {
                type: "",
                total_reporting: 1,
                reporting_names: [],
                is_reporting: true,
                job_ids: []
            },
            maps: null,
        }
    },
    async mounted() {
        this.getUnit();
        await this.getAllJobs();
        await this.getJobs();
        this.initializeJobTable();
        await this.getReportingData();
        if (this.item.lat && this.item.long && this.item.radius) {
            this.initMap();
        }
    },
    methods: {
        initMap() {
            document.getElementById('mapContainer').innerHTML = '';

            console.log(this.item.lat, this.item.long, this.item.radius)
            const centerLatLng = [this.item.lat, this.item.long];
            this.map = L.map('mapContainer').setView(centerLatLng, 18);
            this.map.scrollWheelZoom.disable();

            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png').addTo(this.map);
            L.marker(centerLatLng).addTo(this.map);

            const centerPoint = point([parseFloat(this.item.long), parseFloat(this.item.lat)]);
            const buffered = buffer(centerPoint, parseInt(this.item.radius), { units: 'meters' });

            if (buffered.geometry && buffered.geometry.type === 'Polygon') {
                const bufferPolygon = L.geoJSON(buffered);
                bufferPolygon.setStyle({ fillColor: 'blue', fillOpacity: 0.3 }).addTo(this.map);

                const bufferBounds = bufferPolygon.getBounds();
                this.map.fitBounds(bufferBounds);
            } else {
                console.warn('Buffer geometry is not valid or empty.');
            }
        },
        hideTableJob() {
            this.hideJob = !this.hideJob;
            this.initializeJobTable()
        },
        showTableJob() {
            this.hideJob = true;
        },
        closeEdit() {
            this.editing = true;
            this.getUnit();
        },
        editData() {
            this.editing = false;
        },
        saveData() {
            axios
                .put(`/api/v1/admin/unit/update/${this.$route.params.id}`, {
                    lat: this.item.lat,
                    long: this.item.long,
                    early_buffer: this.item.early_buffer,
                    late_buffer: this.item.late_buffer,
                    radius: this.item.radius
                })
                .then(() => {
                    this.editing = true;
                    this.getUnit();
                    this.initMap();
                    useToast().success('Data successfully updated');
                })
                .catch(error => {
                    console.error(error);
                    useToast().success('Data failed to update');
                });
        },
        getUnit() {
            axios
                .get(`/api/v1/admin/unit/view/${this.$route.params.id}?unit_level=4`)
                .then(response => {
                    this.item = response.data.data[0];
                })
                .catch(error => {
                    console.error(error);
                });
        },
        handleInputChange() {
            if (this.search) {
                this.item.child = this.item.child.filter((item) => {
                    return item.name.toLowerCase().includes(this.search.toLowerCase());
                });
            } else {
                this.getUnit();
            }
        },
        async getJobs() {
          await this.$axios.get(`/api/v1/admin/job/show/${this.$route.query.unit_id}`)
            .then(response => {
                this.jobs = response.data.data.jobs
            })
            .catch(error => {
                console.error(error);
            });
        },
        async getAllJobs() {
            await this.$axios.get(`/api/v1/admin/job?flat=true&unit_id=${this.$route.query.unit_id}`).then(response => {
                this.assignJob = response.data.data.data
            })
        },
        initializeJobTable() {
            const ls = localStorage.getItem('my_app_token')
            this.table = new Tabulator(this.$refs.jobTable, {
                data: this.jobs,
                layout: 'fitColumns',
                columns: [
                    {
                        title: 'No',
                        field: '',
                        formatter: 'rownum',
                        width: 70,
                        headerSort:false,
                        hozAlign: 'center',
                        headerHozAlign: 'center',
                    },
                    {
                        title: 'Name',
                        field: 'name',
                        headerFilter: "input",
                        formatter: function(cell) {
                            return cell.getRow().getData().name;
                        }
                    },
                    {
                        title:"Camera",
                        field:"is_camera",
                        hozAlign:"center",
                        headerHozAlign: "center",
                        width: 100,
                        formatter:"tickCross",
                        headerSort:false
                    },
                    {
                        title:"Upload",
                        field:"is_upload",
                        hozAlign:"center",
                        headerHozAlign: "center",
                        width: 120,
                        formatter:"tickCross",
                        headerSort:false
                    },
                    {
                        title:"Reporting",
                        field:"is_mandatory_reporting",
                        hozAlign:"center",
                        headerHozAlign: "center",
                        width: 120,
                        formatter:"tickCross",
                        headerSort:false
                    },
                    {
                        title: '',
                        formatter: this.actionButtonFormatter,
                        width: 100,
                        hozAlign: 'center',
                        cellClick: (e, cell) => {
                            this.handleActionButtonClick(e, cell);
                        }
                    },
                ],
                pagination: true,
                paginationMode: "local",
                paginationSize: 10,
                paginationSizeSelector: [10, 20, 50, 100],
                headerFilter: true,
                paginationInitialPage: 1,
            });
            this.loading = false
        },
        actionButtonFormatter(cell) {
            const rowData = cell.getRow().getData();
            return `
                <button class="button-icon button-warning" data-action="edit" data-row-id="${rowData.id}"><i data-action="edit" class="fa fa-pencil"></i> </button>
                <button class="button-icon button-danger" data-action="delete" data-row-id="${rowData.id}"><i data-action="delete" class="fa fa-trash"></i> </button>
             `;
        },
        handleActionButtonClick(e, cell) {
            const action = e.target.dataset.action
            const rowData = cell.getRow().getData();

            if (action === 'edit') {
                this.$router.push({
                    name: 'Role Edit',
                    params: { id: rowData.id }
                })
            } else if (action === 'delete') {
                this.basic_warning_alert(rowData.id);
            }
        },
        saveAssignJob() {
            const dataToSave = this.assignJob
                .filter(job => this.selectedJobs.includes(job.job_id))
                .map(job => {
                    return {
                        job_ids: [job.job_id],
                        is_camera: job.is_camera || false,
                        is_upload: job.is_upload || false,
                        is_reporting: job.is_reporting || false,
                        is_mandatory_reporting: job.is_mandatory_reporting || false
                    };
                });

            axios
                .post(`/api/v1/admin/job/save/${this.$route.query.unit_id}`, {
                    units: [...dataToSave]
                })
                .then(response => {
                    window.location.reload();
                    useToast().success(response.data.message);
                })
                .catch(async (error) => {
                    console.log(...dataToSave)
                    useToast().error(error.response.data.message);
                });
        },
        async getReportingData() {
            await this.$axios.get(`/api/v1/admin/job/show/${this.$route.query.unit_id}?is_mandatory_reporting=true`)
                .then(response => {
                    this.reportJobs = response.data.data.jobs
                })
                .catch(error => {
                    console.error(error);
                });
        },
        handleMultipleWorkChange() {
            this.singleWork = !this.multipleWork;
        },
        async updateData(id) {
            if (this.singleWork) {
                this.updateMandatory.type = 'normal';
                this.updateMandatory.total_reporting = 1
            } else {
                this.updateMandatory.type = 'multiple';
                this.updateMandatory.total_reporting = this.numberOfWorks
            }

            this.updateMandatory.job_ids.push(id)
            await this.$axios.put(`/api/v1/admin/job/update-mandatory/${this.$route.params.id}`, this.updateMandatory)
                .then(response => {
                    window.location.reload()
                    useToast().success(response.data.message);
                })
                .catch(error => {
                    console.error(error);
                    useToast().error(error.response.data.message);
                });
        }
    },
    watch: {
        singleWork(newVal) {
            if (newVal) {
                this.multipleWork = false;
            }
        },
        numberOfWorks(newVal) {
            if (newVal < 1) {
                this.numberOfWorks = 1;
            }
        }
    }
};
</script>

<style>
.tabulator .tabulator-header .tabulator-col .tabulator-col-content .tabulator-col-title {
    overflow: hidden !important;
}
.table thead th {
    text-align: center;
    font-weight: bold;
    background-color: #0A5640;
    color: #fff;
}

.table tbody td:last-child {
    text-align: center;
}
.table tbody td:first-child {
    text-align: center;
}

.badge {
    font-size: 12px;
    padding: 5px 10px;
    border-radius: 20px;
    font-weight: 500;
    text-transform: uppercase;
    letter-spacing: 1px;
    margin: 0 5px;
}

.badge-success {
    background-color: #28a745;
    color: #fff
}

.badge-danger {
    background-color: #dc3545;
    color: #fff
}

.button-icon {
    width: 28px;
    height: 28px;
    border-radius: 20%;
    border: none;
    margin: 2px;
}

.button-danger {
    background-color: #dc3545;
    color: #fff
}

.button-danger:hover {
    background-color: #c82333;
    color: #fff
}

</style>
