<template>
    <div class="container-fluid">
        <Breadcrumbs main="Detail Overtime Request"/>
        <div class="col-sm-12">
            <div class="card">
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mt-2">
                                <label for="name">Requestor Name</label>
                                <input type="text" class="form-control" v-model="overtime.requestor_employee.name" disabled>
                            </div>
                            <div class="mt-2">
                                <label for="name">Status</label>
                                <input type="text" class="form-control" v-model="overtime.last_status" disabled>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mt-2">
                                        <label for="name">Tanggal Mulai Lembur</label>
                                        <input type="date" class="form-control" v-model="overtime.start_date" disabled>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mt-2">
                                        <label for="name">Tanggal Selesai Lembur</label>
                                        <input type="date" class="form-control" v-model="overtime.end_date" disabled>
                                    </div>
                                </div>
                            </div>
                            <div class="mt-2">
                                <label class="form-label">Catatan</label>
                                <textarea class="form-control" v-model="overtime.notes" required disabled>{{overtime.notes}}</textarea>
                            </div>
                            <div class="mt-2">
                                <label for="name">Unit Name</label>
                                <input type="text" class="form-control" v-model="overtime.unit.name" disabled>
                            </div>
                            <hr/>

                            <p>Approval</p>
                            <div ref="approvalOvertimeList"></div>
                        </div>
                        <div class="col-md-6">
                            <div class="row" v-for="(item, index) in overtime.overtime_dates" :key="index">
                                <div class="col-md-6">
                                    <div class="mt-2">
                                        <label for="name">Waktu Mulai</label>
                                        <input type="datetime-local" class="form-control" v-model="item.start_time_with_timezone" disabled required>
                                    </div>
	                                <div class="mt-2">
		                                <label for="name">Waktu Selesai</label>
		                                <input type="datetime-local" class="form-control" v-model="item.end_time_with_timezone" disabled required>
	                                </div>
                                </div>
                                <div class="col-md-4">
	                                <div class="mt-2">
		                                <label for="name">Total Approved</label>
		                                <input type="text" class="form-control" v-model="item.total_overtime" disabled required>
	                                </div>
                                </div>
                                <div class="col-md-2">
                                    <div class="mt-4">
                                        <div
                                            :class="'btn btn-primary mt-3'"
                                            data-bs-toggle="modal"
                                            data-bs-target="#detailOvertimeDate"
                                            @click="onSelectOvertimeDate(item)"
                                        >
                                            Attendee
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card-footer text-end">
                    <button class="btn btn-secondary" @click="$router.go(-1)">Back</button>&nbsp;
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
                        v-if="overtime.is_can_approve"
                    >
                        Approval
                    </div>
                </div>
            </div>
        </div>
        <div class="modal fade" id="approvalModal" ref="approvalModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalCenter" aria-hidden="true">
            <form @submit.prevent="overtimeApproval()">
                <VerticalModal title="Approval Modal">
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
                            <div class="mt-2" v-if="approval.status === 'approved'">
                                <div class="row" v-for="(item, index) in approval.dates" :key="index">
                                    <div class="col-md-6">
                                        <div class="mt-2">
                                            <label for="name">Tanggal</label>
                                            <input type="text" class="form-control" :value="index" disabled required>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mt-2">
                                            <label for="name">Total Jam format (jam:menit:detik)</label>
                                            <input type="tel" class="form-control" v-model="approval.dates[index]" pattern="[0-9]{1,2}:[0-9]{1,2}:[0-9]{1,2}" required>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </VerticalModal>
            </form>
        </div>
        <div class="modal fade" id="historyModal" ref="historyModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalCenter" aria-hidden="true">
            <VerticalModalWithoutSave title="History">
                <div class="row" v-for="(item, index) in overtime.overtime_histories" :key="index">
                    <div class="col-md-12">
                        <div class="alert-border alert alert-primary" v-if="item.history_type !== 'rejected'">
                            <table>
                                <tr>
                                    <td>Status</td>
                                    <td>: {{item.history_type}}</td>
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
                        <div class="alert-border alert alert-danger" v-if="item.history_type === 'rejected'">
                            <table>
                                <tr>
                                    <td>Status</td>
                                    <td>: {{item.history_type}}</td>
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
                        <div v-if="index < (overtime.overtime_histories.length - 1)">
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
import L from 'leaflet';
import VerticalModal from "@components/modal/verticalModal.vue";
import VerticalModalWithoutSave from "@components/modal/verticalModalWithoutSave.vue";
import { useToast } from "vue-toastification";
import {TabulatorFull as Tabulator} from "tabulator-tables";
import axios from "axios";

export default {
    components: {
        VerticalModal,
        VerticalModalWithoutSave
    },
    data() {
        return {
            overtime: {
                id: null,
                requestor_employee_id: null,
                unit_relation_id: null,
                last_status: null,
                last_status_at: null,
                timezone: null,
                notes: null,
                image_url: null,
                location_lat: null,
                location_long: null,
                created_at: null,
                updated_at: null,
                check_in_time: null,
                check_out_time: null,
                is_can_approve: false,
                requestor_employee: {
                    id: null,
                    name: null,
                    status: null
                },
                unit: {
                    id: null,
                    name: null,
                },
                overtime_histories: [],
                overtime_employees: [],
                overtime_dates: [],
                overtime_approvals: []
            },
            approval: {
                status: null,
                notes:null,
                dates: {}
            },
            selectedOvertimeDate: null
        }
    },
    mounted() {
        this.getDetailOvertime()
    },
    methods: {
        generateApprovalBackupTable() {
            const table = new Tabulator(this.$refs.approvalOvertimeList, {
                data: this.overtime.overtime_approvals,
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
                    },
                    {
                        title: 'Unit',
                        field: 'employee.last_unit.name',
                    },
                    {
                        title: 'Status',
                        field: 'status',
                        formatter: (cell) => {
                            let val = cell.getValue()

                            if (val === 'approved') {
                                return `<span class="badge badge-success">Approved</span>`
                            } else if (val === 'rejected') {
                                return `<span class="badge badge-danger">Rejected</span>`
                            } else {
                                return `<span class="badge badge-warning">Pending</span>`
                            }
                        }
                    },
                    {
                        title: 'Notes',
                        field: 'notes',
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
        generateEmployeeTable() {
            const table = new Tabulator(this.$refs.employeeTable, {
                data: this.selectedOvertimeDate.overtime_employees,
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
        getDetailOvertime() {
            this.$axios.get(`/api/v1/admin/overtime/view/${this.$route.params.id}`)
                .then(response => {
                    this.overtime = response.data.data

                    this.overtime.overtime_dates.forEach((item) => {
                        this.approval.dates[item.date] = item.total_overtime
                    })

                    this.generateApprovalBackupTable()
                })
                .catch(error => {
                    console.error(error);
                });
        },
        overtimeApproval() {
            this.$axios.post(`/api/v1/admin/overtime/approval/${this.$route.params.id}`, this.approval)
                .then(response => {
                    useToast().success("Success to update data", { position: 'bottom-right' });
                    this.getDetailOvertime()
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
        onSelectOvertimeDate(data) {
            this.selectedOvertimeDate = data
            this.generateEmployeeTable()
        }
    },
};
</script>

<style>
#mapCheckIn {
    height: 300px
}
#mapCheckOut {
    height: 300px
}
</style>
