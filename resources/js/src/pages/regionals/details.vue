<template>
    <div class="container-fluid">
        <Breadcrumbs :title="$route.name"/>
        <div class="col-sm-12">
            <div class="card">
                <div className="card-header bg-primary">
                    <div class="d-flex justify-content-between">
                        <h5>{{item.name}}</h5>
                        <button class="btn btn-sm btn-outline-warning" @click="$router.push('/kanwil')">
                            <i class="icofont icofont-double-left"></i>&nbsp;Back
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <ul class="nav nav-pills nav-primary" id="pills-icontab" role="tablist">
                                <li class="nav-item"><a class="nav-link active" id="pills-iconhome-tab" data-bs-toggle="pill" href="#pills-iconhome" role="tab" aria-controls="pills-iconhome" aria-selected="true"><i class="icofont icofont-info"></i>Basic Information</a></li>
                                <li class="nav-item"><a class="nav-link" id="pills-employee-tab" data-bs-toggle="pill" href="#pills-employee" role="tab" aria-controls="pills-employee" aria-selected="false"><i class="icofont icofont-users"></i>Employee</a></li>
                            </ul>
                        </div>
                    </div>
                    <div class="tab-content" id="pills-icontabContent">
                        <hr>
                        <div class="tab-pane fade show active" id="pills-iconhome" role="tabpanel" aria-labelledby="pills-iconhome-tab">
                            <div>
                                <button type="button" v-if="!editing && this.$store.state.permissions?.includes('unit-update')" class="btn btn-warning" @click="onEditData">
                                    <i class="fa fa-pencil-square"></i> Edit Data
                                </button>
                                <div v-else-if="editing && this.$store.state.permissions?.includes('unit-update')" class="d-flex justify-content-end column-gap-2">
                                    <button type="button" class="btn btn-success" @click="onSaveData">
                                        <i class="fa fa-save"></i> Save Data
                                    </button>
                                    <button type="button" class="btn btn-danger" @click="onCloseEdit">
                                        <i class="fa fa-times-circle"></i> Cancel Edit
                                    </button>
                                </div>
                            </div>
                            <hr>
                            <div class="row">
                                <div class="col-md-12">
                                    <div class="mb-3">
                                        <label class="form-label">Name</label>
                                        <input class="form-control" type="text" v-model="item.name" disabled="disabled">
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="tab-pane fade" id="pills-employee" role="tabpanel" aria-labelledby="pills-employee-tab">
                            <Employee :id="this.$route.query.unit_id"/>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import {TabulatorFull as Tabulator} from "tabulator-tables";
import OperatingUnit from "./operating-unit.vue";
import Employee from "./employee.vue";
import axios from "axios";
import {useToast} from "vue-toastification";
import Timesheet from "./timesheet.vue";
import WorkReporting from "@components/work_reporting.vue";
import L from "leaflet";
import {buffer, point} from "@turf/turf";

export default {
    components: {Timesheet, Employee, OperatingUnit, WorkReporting},
    data() {
        return {
            item: {
	            lat: null,
	            long: null
            },
            childs: [],
            loading: false,
            editing: false,
            hideJob: true,
            assignJob: [],
            selectedJobs: [],
	        maps: null,
        }
    },
    async mounted() {
        await this.getKantorPerwakilan();
        this.initializeRegionalTable();
        this.getAllJobs()
    },
    methods: {
	    initMap() {
		    document.getElementById('mapContainer').innerHTML = '';

		    console.log(this.item.lat, this.item.long, this.item.radius)
		    const centerLatLng = [this.item.lat, this.item.long];
		    this.map = L.map('mapContainer').setView(centerLatLng, 18);

		    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png').addTo(this.map);
		    L.marker(centerLatLng, {icon: L.icon({
				    iconUrl: '/marker-icon.png'
			    })}).addTo(this.map);

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
        async getKantorPerwakilan() {
            this.loading = true;
            await this.$axios.get(`/api/v1/admin/unit/detail/${this.$route.params.id}`)
                .then(response => {
                    this.item = response.data.data;
                    // this.initMap()
                })
                .catch(error => {
                    console.error(error);
                });
        },
        initializeRegionalTable() {
            const table = new Tabulator(this.$refs.regionalTable, {
                data: this.childs,
                layout: 'fitColumns',
                columns: [
                    {
                        title: 'No',
                        field: '',
                        formatter: 'rownum',
                        width: 100
                    },
                    {
                        title: 'Name',
                        field: 'name',
                        headerFilter:"input",
                        formatter: function(row) {
                            return row.getData().name;
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
            this.loading = false
        },
        async getAllJobs() {
            await this.$axios.get(`/api/v1/admin/job?flat=true&unit_id=${this.$route.query.unit_id}`).then(response => {
                this.assignJob = response.data.data.data
            })
        },
        onEditData() {
            this.editing = !this.editing
        },
        onSaveData() {
	        axios
		        .put(`/api/v1/admin/unit/update/${this.$route.params.id}`, {
			        lat: this.item.lat,
			        long: this.item.long,
			        early_buffer: this.item.early_buffer,
			        late_buffer: this.item.late_buffer,
			        radius: this.item.radius
		        })
		        .then(() => {
			        useToast().success('Data successfully updated');
			        window.location.reload();
		        })
		        .catch(error => {
			        console.error(error);
			        useToast().error('Data failed to update');
		        });
        },
        onCloseEdit() {
            this.onEditData()
        },
        hideTableJob() {
            this.hideJob = !this.hideJob;
            this.initializeJobTable()
        },
        showTableJob() {
            this.hideJob = true;
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
    }
};
</script>
