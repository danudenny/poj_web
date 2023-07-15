<template>
    <div class="container-fluid">
        <Breadcrumbs main="Detail Incident Reporting"/>
        <div class="col-sm-12">
            <div class="card">
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-12">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mt-2">
                                        <label for="name">Assignee Name</label>
                                        <input type="text" class="form-control" v-model="backup.assignee.name" disabled>
                                    </div>
                                    <div class="mt-2">
                                        <label for="name">Unit</label>
                                        <input type="text" class="form-control" v-model="backup.unit.name" disabled>
                                    </div>
                                    <div class="mt-2">
                                        <label for="name">Job</label>
                                        <input type="text" class="form-control" v-model="backup.job.name" disabled>
                                    </div>
                                    <div class="mt-2">
                                        <label for="name">Start Date</label>
                                        <input type="text" class="form-control" v-model="backup.start_date" disabled>
                                    </div>
                                    <div class="mt-2">
                                        <label for="name">End Date</label>
                                        <input type="text" class="form-control" v-model="backup.end_date" disabled>
                                    </div>
                                    <div class="mt-2">
                                        <label for="name">Shift Type</label>
                                        <input type="text" class="form-control" v-model="backup.shift_type" disabled>
                                    </div>
                                    <div class="mt-2">
                                        <label for="name">Duration</label>
                                        <input type="text" class="form-control" v-model="backup.duration" disabled>
                                    </div>
                                    <div class="mt-2">
                                        <p>Timesheet</p>
                                        <div class="row">
                                            <div class="col-md-4">
                                                <div class="mt-2">
                                                    <label for="name">Name</label>
                                                    <input type="text" class="form-control" v-model="backup.timesheet.name" disabled>
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="mt-2">
                                                    <label for="name">Start</label>
                                                    <input type="text" class="form-control" v-model="backup.timesheet.start_time" disabled>
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="mt-2">
                                                    <label for="name">End</label>
                                                    <input type="text" class="form-control" v-model="backup.timesheet.end_time" disabled>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="row" v-for="(item, index) in backup.backup_history" :key="index">
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
                                                </table>
                                            </div>
                                            <div v-if="index < (backup.backup_history.length - 1)">
                                                <p align="center">
                                                    <i class="fa fa-arrow-down history-arrow mb-3"/>
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <hr/>
                            <button class="btn btn-primary">Back</button>
                        </div>
                    </div>
                </div>
                <div class="card-footer text-end">
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import VerticalModal from "@components/modal/verticalModal.vue";

export default {
    components: {
        VerticalModal
    },
    data() {
        return {
            backup: {
                id: null,
                start_date: null,
                end_date: null,
                shift_type: null,
                duration: null,
                unit: {
                    name: null
                },
                job: {
                    name: null
                },
                timesheet: {
                    start_time: null,
                    end_time: null,
                    name: null,
                },
                assignee: {
                    name: null
                },
                backup_history: []
            }
        }
    },
    created() {
        this.getDetailBackup()
    },
    methods: {
        getDetailBackup() {
            this.$axios.get(`/api/v1/admin/backup/view/${this.$route.params.id}`)
                .then(response => {
                    this.backup = response.data.data
                    console.log(this.backup)
                })
                .catch(error => {
                    console.error(error);
                });
        },
        onChangeBackupType(e) {
        },
        onSubmitForm() {
        }
    }
};
</script>

<style>
</style>
