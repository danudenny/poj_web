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
                                <label>Corporate</label>
                                <input class="form-control" :value="employee.corporate?.name || '-'" readonly type="text">
                            </div>
                        </div>
                        <div class="col-md-12 col-sm-12">
                            <div class="mb-3">
                                <label>Office</label>
                                <input class="form-control" :value="employee.employee.last_unit.name" readonly type="text">
                            </div>
                        </div>
                        <div class="col-md-12 col-sm-12">
                            <div class="mb-3">
                                <label>Job</label>
                                <input class="form-control" :value="employee.employee.job.name" readonly type="text">
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
                this.employee.employee.corporate,
                this.employee.employee.kanwil,
                this.employee.employee.area,
                this.employee.employee.cabang,
                this.employee.employee.outlet
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
