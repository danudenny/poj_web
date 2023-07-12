<template>
    <div class="container-fluid">
        <Breadcrumbs title="Company Setting"/>

        <div class="container-fluid">
            <div class="email-wrap bookmark-wrap">
                <div class="row">
                    <div class="col-md-12">
                        <div class="card card-absolute">
                            <div class="card-header bg-primary">
                                <h5>Company Setting</h5>
                            </div>
                            <div class="card-body">
                                <h5 class="text-primary"><b>Location Information</b></h5>
                                <hr>
                                <div class="row">
                                    <div class="col-sm-6">
                                        <div class="mb-3">
                                            <label>Company Name</label>
                                            <input class="form-control" type="text" placeholder="" v-model="workLocations.name" disabled>
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <label>Latitude</label>
                                            <input class="form-control" type="text" placeholder="" v-model="workLocations.lat">
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <label>Longitude</label>
                                            <input class="form-control" type="text" placeholder="" v-model="workLocations.long">
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <label>Buffer Radius Location</label>
                                            <input class="form-control" type="number" placeholder="" v-model="workLocations.radius">
                                        </div>
                                    </div>
                                    <div class="mb-3">
                                        <a class="text-danger" :href="`https://www.google.com/maps/@${workLocations.lat},${workLocations.long},17z?entry=ttu`" target="_blank"><i class="fa fa-map-marker"></i> Show in google maps</a>
                                    </div>
                                </div>

                                <h5 class="text-primary"><b>Work Information</b></h5>
                                <hr>
                                <div class="row">
                                    <div class="col-sm-6">
                                        <div class="mb-3">
                                            <label>Early Attendance Tolerance (minutes)</label>
                                            <input class="form-control" type="number" placeholder="" v-model="workLocations.early_buffer">
                                        </div>
                                    </div>
                                    <div class="col-sm-6">
                                        <div class="mb-3">
                                            <label>Late Attendance Tolerance (minutes)</label>
                                            <input class="form-control" type="number" placeholder="" v-model="workLocations.late_buffer">
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="card-footer">
                                <div class="d-flex justify-content-end column-gap-2">
                                    <button class="btn btn-primary" type="button" @click="saveData"><i class="fa fa-save"></i> Save</button>
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
import {useToast} from 'vue-toastification'

export default {
    data() {
        return {
            workLocations: {
                company: {
                    name: '',
                },
                lat: '',
                long: '',
                radius: '',
                early_buffer: '',
                late_buffer: '',
            },
        }
    },
    async mounted() {
        await this.getWorkLocations()
    },
    methods: {
        async getWorkLocations() {
            const ls = JSON.parse(localStorage.getItem('USER_STORAGE_KEY'))
            await this.$axios.get(`/api/v1/admin/work-locations/view?unit_id=${ls.unit_id}`)
                .then(response => {
                    this.workLocations = response.data.data;
                })
                .catch(error => {
                    console.log(error);
                })
        },
        async saveData() {
            const ls = JSON.parse(localStorage.getItem('USER_STORAGE_KEY'))
            await this.$axios.put(`/api/v1/admin/work-locations/update?unit_id=${ls.unit_id}`, {
                lat: this.workLocations.lat,
                long: this.workLocations.long,
                radius: this.workLocations.radius,
                early_buffer: this.workLocations.early_buffer,
                late_buffer: this.workLocations.late_buffer,
            }).then(() => {
                useToast().success('Work Location updated successfully', 200)
            }).catch(() => {
                useToast().error('Failed save data', 500)
            })
        },
    }
}
</script>
