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
                                <label>Current Work</label>
                                <input class="form-control" :value="employee.partner?.name" readonly type="text">
                            </div>
                        </div>
                        <div class="col-md-12 col-sm-12">
                            <div class="mb-3">
                                <label>Default Operating Unit</label>
                                <div v-if="employee.operating_unit">
                                    <input class="form-control" :value="employee.operating_unit?.name" readonly type="text">
                                </div>
                                <div v-else>
                                    <span class="badge badge-danger">No Operating Unit</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-12 col-sm-12">
                            <div class="mb-3">
                                <label>Working Area</label>
                                <input class="form-control" :value="workingArea.name" readonly type="text">
                            </div>
                        </div>
                        <div class="col-md-12 col-sm-12">
                            <div class="mb-3">
                                <label>Job Title</label>
                                <input class="form-control" :value="employee.job.name" readonly type="text">
                            </div>
                        </div>
                        <div class="col-md-12 col-sm-12">
                            <div class="mb-3">
                                <label>Department</label>
                                <div v-if="employee.department">
                                    <input class="form-control" :value="employee.department?.name" readonly type="text">
                                </div>
                                <div v-else>
                                    <span class="badge badge-danger">No Department</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-12 col-sm-12">
                            <div class="mb-3">
                                <label>Team</label>
                                <div v-if="employee.team">
                                    <input class="form-control" :value="employee.team?.name" readonly type="text">
                                </div>
                                <div v-else>
                                    <span class="badge badge-danger">No Team</span>
                                </div>
                            </div>
                        </div>
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
            workingArea: {},
        };
    },
    mounted() {
        this.getWorkingArea();
    },
    methods: {
        getWorkingArea() {
            const hierarchy = [
                this.employee.kanwil,
                this.employee.area,
                this.employee.cabang,
                this.employee.outlet
            ];

            const sortedHierarchy = hierarchy
                .filter(data => data && data.value !== null)
                .sort((a, b) => a.unit_level - b.unit_level);

            this.workingArea = sortedHierarchy[sortedHierarchy.length - 1];
            return this.workingArea
        }
    }
}
</script>

<style>
#map {
    height: 400px;
}
</style>
