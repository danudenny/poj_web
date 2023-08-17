<template>
    <div>
        <table class="table table-striped table-responsive">
            <thead>
            <tr>
                <th>No</th>
                <th>Job Title</th>
                <th>Reporting</th>
                <th>Total Normal</th>
                <th>Total Backup</th>
                <th>Total Overtime</th>
                <th></th>
            </tr>
            </thead>
            <tbody>
            <tr v-for="(job, index) in reportJobs" :key="job.id">
                <td>{{index+1}}</td>
                <td>{{ job.name }}</td>
                <td class="text-center">
                    <span class="badge badge-pill badge-success" v-if="job.is_mandatory_reporting">Yes</span>
                    <span class="badge badge-pill badge-danger" v-else>No</span>
                </td>
                <td class="text-center">{{job.total_normal}}</td>
                <td class="text-center">{{job.total_backup}}</td>
                <td class="text-center">{{job.total_overtime}}</td>
                <td>
                    <button
                        class="btn btn-primary" type="button" data-bs-toggle="modal"
                        data-bs-target=".bd-example-modal-lg"
                        @click="onSelectWorkReporting(job)"
                    >
                        Manage
                    </button>
                    <div class="modal fade bd-example-modal-lg" tabindex="-1" role="dialog" aria-labelledby="myLargeModalLabel"
                         aria-hidden="true">
                        <AssignWorkReporting modalTitle="Work Reporting" @save="updateData(job.id)">
                            <div class="row">
                                <div class="col-md-12">
                                    <label class="col-form-label">Total Normal Reporting</label>
                                    <input type="number" class="form-control" v-model="updateWorkReporting.total_normal" min="0">
                                </div>
                                <div class="col-md-12">
                                    <label class="col-form-label">Total Backup Reporting</label>
                                    <input type="number" class="form-control" v-model="updateWorkReporting.total_backup" min="0">
                                </div>
                                <div class="col-md-12">
                                    <label class="col-form-label">Total Overtime Reporting</label>
                                    <input type="number" class="form-control" v-model="updateWorkReporting.total_overtime" min="0">
                                </div>
                            </div>
                        </AssignWorkReporting>
                    </div>
                </td>
            </tr>
            </tbody>
        </table>
    </div>
</template>
<script>
import {useToast} from "vue-toastification";
import AssignWorkReporting from "@/pages/outlet/modal/assignWorkReporting.vue";
export default {
    props: {
        unit_id: {
            type: Number,
            required: true
        }
    },
    components: {
        AssignWorkReporting
    },
    data() {
        return {
            reportJobs: [],
            updateWorkReporting: {
                type: "",
                total_reporting: 1,
                reporting_names: [],
                is_reporting: true,
                total_normal: 0,
                total_backup: 0,
                total_overtime: 0,
                job_ids: []
            },
        }
    },
    mounted() {
        this.getReportingData()
    },
    methods: {
        getReportingData() {
            this.$axios.get(`/api/v1/admin/job/show/${this.unit_id}?is_mandatory_reporting=true`)
                .then(response => {
                    this.reportJobs = response.data.data.jobs
                })
                .catch(error => {
                    console.error(error);
                });
        },
        onSelectWorkReporting(workReport) {
            this.updateWorkReporting = {
                type: workReport.reporting_type,
                total_reporting: workReport.total_reporting,
                reporting_names: [],
                is_reporting: workReport.is_reporting,
                total_normal: workReport.total_normal,
                total_backup: workReport.total_backup,
                total_overtime: workReport.total_overtime,
                job_ids: [workReport.id]
            }
        },
        updateData(id) {
            this.$axios.put(`/api/v1/admin/job/update-mandatory/${this.$route.params.id}`, this.updateWorkReporting)
                .then(response => {
                    window.location.reload()
                    useToast().success(response.data.message);
                })
                .catch(error => {
                    console.error(error);
                    useToast().error(error.response.data.message);
                });
        }
    }
}
</script>


<style>

</style>
