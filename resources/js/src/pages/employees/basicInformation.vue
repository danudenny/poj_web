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
                                <label>Employee Type</label>
                                <input class="form-control" :value="employee.employee_category" readonly type="text">
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
                                <input class="form-control" :value="employee.gender" readonly type="text">
                            </div>
                        </div>
                        <div class="col-md-6 col-sm-12">
                            <div class="mb-3">
                                <label>Marital Status</label>
                                <input class="form-control" :value="employee.marital" readonly type="text">
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
