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
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
export default {
    props: {
        employee: {
            type: Object,
            required: true
        }
    }
}
</script>
