<template>
    <div class="tab-pane fade active show" id="pills-app" role="tabpanel" aria-labelledby="pills-app-tab">
        <div class="card mb-0">
            <div class="card-header d-flex bg-primary">
                <h5 class="mb-0">Basic Information</h5>
                <button type="button" class="btn btn-secondary ms-auto" @click="$router.push('/management/employees')">
                    <i class="fa fa-rotate-left"></i>&nbsp; Back</button>
            </div>
            <div class="card-body p-4">
                <div class="taskadd">
                    <div class="row">
                        <div class="col">
                            <div class="mb-3">
                                <label>Fullname</label>
                                <input class="form-control" :value="employee.name" readonly type="text">
                            </div>
                        </div>
                        <div class="col">
                            <div class="mb-3">
                                <label>Registration Number</label>
                                <input class="form-control" :value="employee.registration_number" readonly type="text">
                            </div>
                        </div>
                        <div class="col-md-12 col-sm-12">
                            <div class="mb-3">
                                <label>Identification ID</label>
                                <input class="form-control" :value="employee.identification_id" readonly type="text">
                            </div>
                        </div>
                        <div class="col-md-4 col-sm-12">
                            <div class="mb-3">
                                <label>Mobile Phone</label>
                                <input class="form-control" :value="employee.mobile_phone" readonly type="text">
                            </div>
                        </div>
                        <div class="col-md-8 col-sm-12">
                            <div class="mb-3">
                                <label>Email</label>
                                <input class="form-control" :value="employee.work_email" readonly type="text">
                            </div>
                        </div>
                        <div class="col-md-6 col-sm-12">
                            <div class="mb-3">
                                <label>Employee Category</label>
                                <input class="form-control" :value="employee.employee_category.split('_').join(' ').toUpperCase()" readonly type="text">
                            </div>
                        </div>
                        <div class="col-md-6 col-sm-12">
                            <div class="mb-3">
                                <label>Employee Type</label>
                                <input class="form-control" :value="employee.employee_type.split('_').join(' ').toUpperCase()" readonly type="text">
                            </div>
                        </div>
                        <div class="col-md-6 col-sm-12">
                            <div class="mb-3">
                                <label>First Contract Date</label>
                                <input class="form-control" :value="employee.first_contract_date" readonly type="text">
                            </div>
                        </div>
                        <div class="col-md-6 col-sm-12">
                            <div class="mb-3">
                                <label>BPJS Kesehatan</label>
                                <input class="form-control" :value="employee.bpjs_kesehatan" readonly type="text">
                            </div>
                        </div>
                        <div class="col-md-6 col-sm-12">
                            <div class="mb-3">
                                <label>BPJS Ketenagakerjaan</label>
                                <input class="form-control" :value="employee.bpjs_ketenagakerjaan" readonly type="text">
                            </div>
                        </div>
                        <div class="col-md-6 col-sm-12">
                            <div class="mb-3">
                                <label>Gender</label>
                                <input class="form-control" :value="employee.gender.toUpperCase()" readonly type="text">
                            </div>
                        </div>
                        <div class="col-md-6 col-sm-12">
                            <div class="mb-3">
                                <label>Marital Status</label>
                                <input class="form-control" :value="employee.marital.toUpperCase()" readonly type="text">
                            </div>
                        </div>
                        <div class="col-md-6 col-sm-12">
                            <div class="mb-3">
                                <label>Religion</label>
                                <input class="form-control" :value="employee.agama_id.toUpperCase()" readonly type="text">
                            </div>
                        </div>
                        <div class="col-md-12">
                            <div class="mt-3">
                                <label>Batas Lembur</label>
                            </div>
                            <hr/>
                        </div>
                        <div class="col-md-3 col-sm-12">
                            <div class="mb-3">
                                <label>Hari Libur Nasional</label>
                                <input class="form-control" :value="employee?.master_overtime_limit?.public_holiday" readonly type="text">
                            </div>
                        </div>
                        <div class="col-md-3 col-sm-12">
                            <div class="mb-3">
                                <label>Sequence</label>
                                <input class="form-control" :value="employee?.master_overtime_limit?.sequence" readonly type="text">
                            </div>
                        </div>
                        <div class="col-md-3 col-sm-12">
                            <div class="mb-3">
                                <label>Hari Kerja</label>
                                <input class="form-control" :value="employee?.master_overtime_limit?.daily_work" readonly type="text">
                            </div>
                        </div>
                        <div class="col-md-3 col-sm-12">
                            <div class="mb-3">
                                <label>Hari Libur</label>
                                <input class="form-control" :value="employee?.master_overtime_limit?.day_off" readonly type="text">
                            </div>
                        </div>
                        <div class="col-md-12">
                            <hr/>
                        </div>
                    </div>
                    <div class="col-md-12" v-if="employee.working_hour">
                        <div class="row">
                            <div class="col-md-12">
                                <div class="mt-3">
                                    <label><b>Jam Kerja ({{ employee.working_hour.name }})</b></label>
                                </div>
                                <hr/>
                            </div>
                            <div class="col-md-12">
                                <div ref="workingHourTable"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import {TabulatorFull as Tabulator} from "tabulator-tables";

export default {
    props: {
        employee: {
            type: Object,
            required: true
        }
    },
    mounted() {
        this.generateWorkingHourTable()
    },
    methods: {
        generateWorkingHourTable() {
            if (this.employee.working_hour === null) {
                return
            }

            const table = new Tabulator(this.$refs.workingHourTable, {
                data: this.employee.working_hour.working_hour_details,
                layout: 'fitColumns',
                columns: [
                    {
                        title: 'No',
                        field: '',
                        formatter: 'rownum',
                        width: 40
                    },
                    {
                        title: 'Nama',
                        field: 'name',
                    },
                    {
                        title: 'Hari',
                        field: 'day_of_week_string',
                    },
                    {
                        title: 'Periode',
                        field: 'day_period',
                    },
                    {
                        title: 'Jam Mulai',
                        field: 'hour_from_string',
                    },
                    {
                        title: 'Jam Selesai',
                        field: 'hour_to_string',
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
    }
}
</script>
