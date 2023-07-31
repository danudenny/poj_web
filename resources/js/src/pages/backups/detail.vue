<template>
    <div class="container-fluid">
        <Breadcrumbs main="Detail Backup"/>
        <div class="col-sm-12">
            <div class="card">
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mt-2">
                                <label for="name">Unit Name</label>
                                <input type="text" class="form-control" v-model="backup.unit.name" disabled>
                            </div>
                            <div class="mt-2">
                                <label for="name">Requestor Name</label>
                                <input type="text" class="form-control" v-model="backup.requestor_employee.name" disabled>
                            </div>
                            <div class="mt-2">
                                <label for="name">Status</label>
                                <input type="text" class="form-control" v-model="backup.status" disabled>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mt-2">
                                        <label for="name">Tanggal Mulai Lembur</label>
                                        <input type="date" class="form-control" v-model="backup.start_date" disabled>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mt-2">
                                        <label for="name">Tanggal Selesai Lembur</label>
                                        <input type="date" class="form-control" v-model="backup.end_date" disabled>
                                    </div>
                                </div>
                            </div>
                            <a
                                class="btn btn-primary button-info mt-3"
                                target="_blank"
                                :href="backup.file_url"
                                v-if="backup.file_url"
                            >
                                Buka Berkas
                            </a>
                        </div>
                        <div class="col-md-6">
                            <div class="row" v-for="(item, index) in backup.backup_times" :key="index">
                                <div class="col-md-4">
                                    <div class="mt-2">
                                        <label for="name">Waktu Mulai</label>
                                        <input type="text" class="form-control" v-model="item.start_time_with_timezone" disabled required>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="mt-2">
                                        <label for="name">Waktu Selesai</label>
                                        <input type="text" class="form-control" v-model="item.end_time_with_timezone" disabled required>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="mt-4">
                                        <div
                                            :class="'btn btn-primary mt-3'"
                                            data-bs-toggle="modal"
                                            data-bs-target="#detailOvertimeDate"
                                            @click="onSelectOvertimeDate(item)"
                                        >
                                            Daftar Karyawan
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card-footer text-end">
                    <button class="btn btn-secondary" @click="$router.push('/attendance/backup')">Back</button>&nbsp;
                    <div
                        class="btn btn-secondary button-info"
                        data-bs-toggle="modal"
                        data-bs-target="#historyModal"
                    >
                        History
                    </div>&nbsp;
                    <div
                        class="btn btn-primary button-info"
                        data-bs-toggle="modal"
                        data-bs-target="#approvalModal"
                        v-if="backup.is_can_approve"
                    >
                        Approval
                    </div>
                </div>
            </div>
        </div>
        <div class="modal fade" id="approvalModal" ref="approvalModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalCenter" aria-hidden="true">
            <VerticalModal title="Approval Modal" @save="backupApproval()">
                <div class="row">
                    <div class="col-md-12">
                        <div class="mt-2">
                            <label for="status">Status:</label>
                            <select id="status" name="status" class="form-select" v-model="approval.status" required>
                                <option value="approved" :selected="approval.status === 'approved' ? 'selected' : ''">Approve</option>
                                <option value="rejected" :selected="approval.status === 'rejected' ? 'selected' : ''">Reject</option>
                            </select>
                        </div>
                        <div class="mt-2" v-if="approval.status === 'rejected'">
                            <label for="name">Note:</label>
                            <input type="text" class="form-control" id="reason" v-model="approval.notes" required>
                        </div>
                    </div>
                </div>
            </VerticalModal>
        </div>
        <div class="modal fade" id="historyModal" ref="historyModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalCenter" aria-hidden="true">
            <VerticalModalWithoutSave title="History">
                <div class="row" v-for="(item, index) in backup.backup_history" :key="index">
                    <div class="col-md-12">
                        <div class="alert-border alert alert-primary" v-if="item.history_type !== 'rejected'">
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
                        <div class="alert-border alert alert-danger" v-if="item.history_type === 'rejected'">
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
                                    <td>Notes</td>
                                    <td>: {{item.notes}}</td>
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
            </VerticalModalWithoutSave>
        </div>
        <div class="modal fade modal-lg" id="detailOvertimeDate" ref="detailOvertimeDate" tabindex="-1" role="dialog" aria-labelledby="exampleModalCenter" aria-hidden="true">
            <VerticalModalWithoutSave title="Daftar Karyawan">
                <div class="row">
                    <div ref="employeeTable"></div>
                </div>
            </VerticalModalWithoutSave>
        </div>
    </div>
</template>

<script>
import VerticalModal from "@components/modal/verticalModal.vue";
import VerticalModalWithoutSave from "@components/modal/verticalModalWithoutSave.vue";
import {TabulatorFull as Tabulator} from "tabulator-tables";
import {useToast} from "vue-toastification";

export default {
    components: {
        VerticalModal,
        VerticalModalWithoutSave
    },
    data() {
        return {
            backup: {
                id: null,
                start_date: null,
                end_date: null,
                shift_type: null,
                duration: null,
                file_url: null,
                unit: {
                    name: null
                },
                job: {
                    name: null
                },
                backup_history: [],
                requestor_employee: {
                    name: null
                }
            },
            approval: {
                status: null,
                notes: null
            },
            selectedBackupDate: null,
            is_can_approve: false
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
                })
                .catch(error => {
                    console.error(error);
                });
        },
        generateEmployeeTable() {
            const table = new Tabulator(this.$refs.employeeTable, {
                data: this.selectedBackupDate.backup_employees,
                layout: 'fitColumns',
                columns: [
                    {
                        title: 'No',
                        field: '',
                        formatter: 'rownum',
                        width: 10
                    },
                    {
                        title: 'Name',
                        field: 'employee.name',
                    },
                    {
                        title: 'Check In Time',
                        field: 'check_in_time_with_unit_timezone',
                    },
                    {
                        title: 'Check Out Time',
                        field: 'check_out_time_with_unit_timezone',
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
        onSelectOvertimeDate(data) {
            this.selectedBackupDate = data
            this.generateEmployeeTable()
        },
        onChangeBackupType(e) {
        },
        onSubmitForm() {
        },
        backupApproval() {
            this.$axios.post(`/api/v1/admin/backup/approval/${this.$route.params.id}`, this.approval)
                .then(response => {
                    useToast().success("Success to update data", { position: 'bottom-right' });
                    this.getDetailBackup()
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
        }
    }
};
</script>

<style>
</style>
