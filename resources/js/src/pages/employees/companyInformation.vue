<template>
    <div class="tab-pane fade" id="pills-company" role="tabpanel" aria-labelledby="pills-company-tab">
        <div class="card mb-0">
            <div class="card-header d-flex bg-primary">
                <h5 class="mb-0">Company Information</h5>
            </div>
            <div class="card-body p-4">
                <div class="taskadd">
                    <div class="row">
                        <div class="col-md-12 col-sm-12">
                            <div class="mb-3">
                                <label>Office</label>
                                <input class="form-control" :value="employee.unit.name" readonly type="text">
                            </div>
                        </div>
                        <div class="col-md-6 col-sm-12">
                            <div class="mb-3">
                                <label>Work Arrangement</label>
                                <input class="form-control" :value="employee.employee_detail.work_arrangement" readonly type="text">
                            </div>
                        </div>
                        <div class="col-md-6 col-sm-12">
                            <div class="mb-3">
                                <label>Work Shift</label>
                                <input class="form-control" :value="employee.employee_detail.employee_timesheet.name" readonly type="text">
                            </div>
                        </div>
                        <div class="col-md-6 col-sm-12">
                            <div class="mb-3">
                                <label>Start Shift</label>
                                <input class="form-control" :value="employee.employee_detail.employee_timesheet.start_time" readonly type="text">
                            </div>
                        </div>
                        <div class="col-md-6 col-sm-12">
                            <div class="mb-3">
                                <label>End Shift</label>
                                <input class="form-control" :value="employee.employee_detail.employee_timesheet.end_time" readonly type="text">
                            </div>
                        </div>
                        <div class="col-md-6 col-sm-12">
                            <div class="mb-3">
                                <label>Job</label>
                                <input class="form-control" :value="employee.job.name" readonly type="text">
                            </div>
                        </div>
                        <div id="map"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import 'leaflet/dist/leaflet.css';
import L from 'leaflet';
export default {
    props: {
        employee: {
            type: Object,
            required: true
        }
    },
    data() {
        return {
            mapContainer: null,
            map: null,
            marker: null,
            lat: 0,
            long: 0
        };
    },
    mounted() {
        this.lat = this.employee.unit.work_locations[0].lat;
        this.long = this.employee.unit.work_locations[0].long;

        console.log(this.lat, this.long);
        this.mapContainer = this.$el.querySelector('#map');
        this.map = L.map(this.mapContainer, {
            scrollWheelZoom: false
        }).setView([this.lat, this.long], 16);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '© OpenStreetMap contributors'
        }).addTo(this.map);

        this.marker = L.marker([this.lat, this.long]).addTo(this.map);

        window.addEventListener('resize', this.handleMapResize);
    },
    beforeUnmount() {
        window.removeEventListener('resize', this.handleMapResize);
    },
    methods: {
        handleMapResize() {
            if (this.map) {
                this.map.invalidateSize();
            }
        }
    }
}
</script>

<style>
#map {
    height: 400px;
}
</style>
