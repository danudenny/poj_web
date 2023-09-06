<template>
    <Breadcrumbs main="Approval Create" />

    <div class="container-fluid">
        <div class="email-wrap bookmark-wrap">
            <div class="row">
                <div class="col-md-12">
                    <div class="card card-absolute">
                        <div class="card-header bg-primary">
                            <h5>Approval Create</h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-12">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label class="form-label" for="name">Title</label>
                                                <input class="form-control" id="title" type="text" v-model="approval.name" placeholder="Approval Title">
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label class="form-label" for="name">Approval Type</label>
                                                <multiselect
                                                    v-model="approval.approval_module_id"
                                                    placeholder="Select Attendance Type"
                                                    label="name"
                                                    track-by="id"
                                                    :options="approvalModules"
                                                    :multiple="false"
                                                    disabled
                                                />
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label>Unit Level :</label>
                                                <select class="form-select digits" v-model="selectedUnitLevel" @change="onLoadUnit" disabled>
                                                    <option value="0">-- Select Unit Level --</option>
                                                    <option value="3">Corporate</option>
                                                    <option value="4">Kanwil</option>
                                                    <option value="5">Area</option>
                                                    <option value="6">Cabang</option>
                                                    <option value="7">Outlet</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label>Working Area :</label>
                                            <multiselect
                                                v-model="selectedUnit"
                                                placeholder="Select Unit"
                                                label="name"
                                                track-by="id"
                                                :options="units"
                                                :multiple="false"
                                                @select="onUnitSelected"
                                                @search-change="onUnitSearchName"
                                                disabled
                                            >
                                            </multiselect>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-4">
                                            <div class="mb-3">
                                                <label>Departments :</label>
                                                <multiselect
                                                    v-model="selectedDepartment"
                                                    placeholder="Select Department"
                                                    label="name"
                                                    track-by="id"
                                                    :options="departments"
                                                    :multiple="false"
                                                    disabled
                                                >
                                                </multiselect>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <label>Team :</label>
                                            <multiselect
                                                v-model="selectedTeam"
                                                placeholder="Select Team"
                                                label="name"
                                                track-by="id"
                                                :options="teams"
                                                :multiple="false"
                                                disabled
                                            >
                                            </multiselect>
                                        </div>
                                        <div class="col-md-4">
                                            <label>Job :</label>
                                            <multiselect
                                                v-model="selectedJob"
                                                placeholder="Select Job"
                                                label="name"
                                                track-by="id"
                                                :options="jobs"
                                                :multiple="false"
                                                disabled
                                            >
                                            </multiselect>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="row" v-for="(item, index) in approval.approvers">
                                    <hr/>
                                    <div class="row">
                                        <div class="col-md-6">
                                            <b><label>Approver {{ index + 1}} </label></b>
                                        </div>
                                        <div class="col-md-6" style="text-align-last: right;;">
                                            <button class="btn btn-sm btn-danger" @click="onRemoveApprover(index)">
                                                <i class="fa fa-trash"></i>
                                            </button>
                                        </div>
                                    </div>
                                    <div class="row mt-3">
                                        <div class="col-md-4">
                                            <div class="mb-3">
                                                <label>Unit Level</label>
                                                <select class="form-select digits" v-model="approval.approvers[index].unit_level" @change="getApproverUnit(index)">
                                                    <option value="0">-- Select Unit Level --</option>
                                                    <option value="3">Corporate</option>
                                                    <option value="4">Kanwil</option>
                                                    <option value="5">Area</option>
                                                    <option value="6">Cabang</option>
                                                    <option value="7">Outlet</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <label>Unit</label>
                                            <multiselect
                                                v-model="approval.approvers[index].selectedUnit"
                                                placeholder="Select Unit"
                                                label="name"
                                                track-by="id"
                                                :options="approval.approvers[index].units"
                                                :multiple="false"
                                                @select="onApprovalUnitSelected(index)"
                                            >
                                            </multiselect>
                                        </div>
                                        <div class="col-md-4">
                                            <label>Job :</label>
                                            <multiselect
                                                v-model="approval.approvers[index].selectedJob"
                                                placeholder="Select Job"
                                                label="job_name"
                                                track-by="id"
                                                :options="approval.approvers[index].jobs"
                                                :multiple="false"
                                                @select="onApprovalJobSelected(index)"
                                            >
                                            </multiselect>
                                        </div>
                                    </div>
                                    <div class="row mt-3 mb-3">
                                        <div class="col-md-4">
                                            <label>Department :</label>
                                            <multiselect
                                                v-model="approval.approvers[index].selectedDepartment"
                                                placeholder="Select Department"
                                                label="department_name"
                                                track-by="id"
                                                :options="approval.approvers[index].departments"
                                                :multiple="false"
                                                @select="onApproverDepartmentSelected(index)"
                                            >
                                            </multiselect>
                                        </div>
                                        <div class="col-md-4">
                                            <label>Team :</label>
                                            <multiselect
                                                v-model="approval.approvers[index].selectedTeam"
                                                placeholder="Select Team"
                                                label="name"
                                                track-by="id"
                                                :options="approval.approvers[index].teams"
                                                :multiple="false"
                                                @select="onApproverTeamSelected(index)"
                                            >
                                            </multiselect>
                                        </div>
                                        <div class="col-md-4">
                                            <label>Select Employee</label>
                                            <multiselect
                                                v-model="approval.approvers[index].selectedEmployee"
                                                placeholder="Select Employee"
                                                label="name"
                                                track-by="id"
                                                :options="approval.approvers[index].employees"
                                                :multiple="false"
                                            >
                                            </multiselect>
                                        </div>
                                    </div>
                                    <hr/>
                                </div>
                            </div>
                            <div style="align-self: flex-end;" v-if="approval.approvers.length < 6 && approval.unit_relation_id > 0">
                                <button class="btn btn-primary" @click="onAddApprover">
                                    <i class="fa fa-plus"></i>&nbsp;Add New Approver
                                </button>
                            </div>
                        </div>
                        <div class="card-footer text-start">
                            <button class="btn btn-primary m-r-10" @click="onSave">
                                <i class="fa fa-save"></i>&nbsp;Save
                            </button>
                            <button class="btn btn-secondary" @click="$router.push('/approval')">
                                <i class="fa fa-close"></i>&nbsp;Cancel
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
<script>
import {TabulatorFull as Tabulator, PersistenceModule as Persistence} from "tabulator-tables";
import {useToast} from "vue-toastification";
import Datepicker from '@vuepic/vue-datepicker';

export default {
    components: {
        Datepicker
    },
    data() {
        return {
            approvalID: this.$route.params.id,
            approval: {
                unit_relation_id: null,
                unit_level: null,
                name: null,
                approval_module_id: null,
                department: null,
                team: null,
                job: null,
                approvers: []
            },
            approvalModules: [],
            units: [],
            selectedUnitLevel: 0,
            selectedUnit: null,
            selectedDepartment: null,
            departments: [],
            selectedTeam: null,
            teams: [],
            selectedJob: null,
            jobs: [],
            unitsPagination: {
                size: 20,
                name: '',
                isOnSearch: false
            }
        }
    },
    async mounted() {
        this.getApprovalModules()
        this.getApproval()
    },
    methods: {
        getApproval() {
            this.$axios.get(`/api/v1/admin/approval/view/${this.approvalID}`)
                .then(response => {
                    let resp = response.data.data
                    let approvalObj = {
                        unit_relation_id: resp.unit.relation_id,
                        unit_level: resp.unit_level,
                        name: resp.name,
                        approval_module_id: resp.approval_module,
                        department_id: resp.department?.id ?? null,
                        team_id: resp.team?.id ?? null,
                        odoo_job_id: resp.job?.odoo_job_id ?? null,
                        approvers: []
                    }

                    this.units.push(resp.unit)
                    this.selectedUnit = resp.unit
                    this.selectedUnitLevel = resp.unit_level
                    this.selectedDepartment = resp.department
                    this.selectedTeam = resp.team
                    this.selectedJob = resp.job

                    resp.approval_users.forEach((val, index) => {
                        let job = null
                        if(val.job) {
                            job = {
                                id: 0,
                                job_name: val.job.name,
                                job: val.job
                            }
                        }

                        let department = null
                        if(val.department) {
                            department = {
                                department_name: val.department.name,
                                id: val.department.id
                            }
                        }

                        let team = null
                        if (val.team) {
                            team = {
                                id: val.team.id,
                                name: val.team.name
                            }
                        }

                        approvalObj.approvers.push({
                            employee_id: val.employee_id,
                            unit_relation_id: val.unit_relation_id,
                            unit_level: val.unit_level,
                            selectedUnit: val.unit,
                            selectedEmployee: val.employee,
                            units: [],
                            employees: [],
                            selectedJob: job,
                            jobs: [],
                            selectedDepartment: department,
                            departments: [],
                            selectedTeam: team,
                            teams: []
                        })
                    })

                    this.approval = approvalObj

                    this.approval.approvers.forEach((val, index) => {
                        this.getApproverUnit(index)
                        this.getApproverEmployee(index)
                        this.getApproverJobs(index)
                        this.getApproverDepartments(index)
                    })
                })
                .catch(error => {
                    console.error(error)
                })
        },
        getApprovalModules() {
            this.$axios.get('/api/v1/admin/approval-module')
                .then(response => {
                    this.approvalModules = response.data.data.data
                })
                .catch(error => {
                    console.error(error)
                })
        },
        getUnits() {
            this.$axios.get(`/api/v1/admin/unit/paginated?per_page=${this.unitsPagination.size}&unit_level=${this.selectedUnitLevel}&name=${this.unitsPagination.name}`)
                .then(response => {
                    this.units = response.data.data.data
                    this.unitsPagination.isOnSearch = false
                })
                .catch(error => {
                    console.error(error)
                })
        },
        getApproverUnit(index) {
            let unitFilter = {
                unit_level: this.approval.approvers[index].unit_level,
                unit_relation_id_restructured: this.approval.unit_relation_id
            }

            this.$axios.get(`/api/v1/admin/unit/paginated?unit_level=${unitFilter.unit_level}`)
                .then(response => {
                    this.approval.approvers[index].units = response.data.data
                })
                .catch(error => {
                    console.error(error)
                })
        },
        getApproverEmployee(index) {
            if (this.approval.approvers[index].selectedUnit === null) {
                return
            }

            let employeeFilter = {
                last_unit_relation_id: this.approval.approvers[index].selectedUnit.relation_id,
                odoo_job_id: this.approval.approvers[index].selectedJob?.job?.odoo_job_id ?? '',
                department_id: this.approval.approvers[index].selectedDepartment?.id ?? '',
                team_id: this.approval.approvers[index].selectedTeam?.id ?? '',
            }

            this.$axios.get(`/api/v1/admin/employee/paginated?last_unit_relation_id=${employeeFilter.last_unit_relation_id}&odoo_job_id=${employeeFilter.odoo_job_id}&department_id=${employeeFilter.department_id}&team_id=${employeeFilter.team_id}`)
                .then(response => {
                    this.approval.approvers[index].employees = response.data.data
                })
                .catch(error => {
                    console.error(error)
                })
        },
        getApproverJobs(index) {
            if (this.approval.approvers[index].selectedUnit === null) {
                return
            }

            let employeeFilter = {
                last_unit_relation_id: this.approval.approvers[index].selectedUnit.relation_id,
            }

            this.$axios.get(`/api/v1/admin/unit-job?unit_relation_id=${employeeFilter.last_unit_relation_id}&append=job_name`)
                .then(response => {
                    this.approval.approvers[index].jobs = response.data.data

                    if(this.approval.approvers[index].selectedJob != null) {
                        let currentJob = this.approval.approvers[index].selectedJob

                        this.approval.approvers[index].jobs.forEach(val => {
                            if(currentJob.job.id === val.job.id) {
                                this.approval.approvers[index].selectedJob = val
                            }
                        })
                    }
                })
                .catch(error => {
                    console.error(error)
                })
        },
        getApproverDepartments(index) {
            if (this.approval.approvers[index].selectedUnit === null) {
                return
            }

            let employeeFilter = {
                last_unit_relation_id: this.approval.approvers[index].selectedUnit.relation_id,
            }

            this.$axios.get(`/api/v1/admin/department?unit_id=${employeeFilter.last_unit_relation_id}`)
                .then(response => {
                    this.approval.approvers[index].departments = response.data.data.filter((item) => item.unit_id === employeeFilter.last_unit_relation_id)

                    if (this.approval.approvers[index].selectedDepartment != null) {
                        let currentDepartment = this.approval.approvers[index].selectedDepartment

                        this.approval.approvers[index].departments.forEach((val) => {
                            if(val.id === currentDepartment.id) {
                                this.approval.approvers[index].selectedDepartment = val
                            }
                        })

                        this.getApproverTeam(index)
                    }
                })
                .catch(error => {
                    console.error(error)
                })
        },
        getApproverTeam(index) {
            if (this.approval.approvers[index].selectedDepartment === null) {
                return
            }

            this.approval.approvers[index].teams = this.approval.approvers[index].selectedDepartment.teams

            if (this.approval.approvers[index].selectedTeam) {
                let currentTeam = this.approval.approvers[index].selectedTeam

                this.approval.approvers[index].teams.forEach((val) => {
                    if (val.id === currentTeam.id) {
                        this.approval.approvers[index].selectedTeam = val
                    }
                })
            }
        },
        onLoadUnit() {
            this.getUnits()
            this.approval.unit_level = this.selectedUnitLevel
        },
        onUnitSearchName(val) {
            this.unitsPagination.name = val

            if (!this.unitsPagination.isOnSearch) {
                this.unitsPagination.isOnSearch = true
                setTimeout(() => {
                    this.getUnits()
                }, 1000)
            }
        },
        onAddApprover() {
            if (this.approval.approvers.length >= 6) {
                return
            }

            this.approval.approvers.push({
                employee_id: null,
                unit_relation_id: null,
                unit_level: 0,
                selectedUnit: null,
                units: [],
                selectedEmployee: null,
                employees: []
            })
        },
        onRemoveApprover(index) {
            this.approval.approvers.splice(index, 1)
        },
        onUnitSelected() {
            this.approval.approvers = []
            this.approval.unit_relation_id = this.selectedUnit.relation_id
        },
        onApprovalUnitSelected(index) {
            this.getApproverEmployee(index)
        },
        onApproverDepartmentSelected(index) {
            this.getApproverTeam(index)

            this.approval.approvers[index].selectedEmployee = null
            this.getApproverEmployee(index)
        },
        onApprovalJobSelected(index) {
            this.approval.approvers[index].selectedEmployee = null
            this.getApproverEmployee(index)
        },
        onApproverTeamSelected(index) {
            this.approval.approvers[index].selectedEmployee = null
            this.getApproverEmployee(index)
        },
        onSave() {
            let approval = {
                unit_relation_id: this.approval.unit_relation_id,
                unit_level: this.approval.unit_level,
                name: this.approval.name,
                approval_module_id: this.approval.approval_module_id?.id ?? null,
                department_id: this.selectedDepartment?.id ?? null,
                team_id: this.selectedTeam?.id ?? null,
                odoo_job_id: this.selectedJob?.odoo_job_id ?? null,
                approvers: []
            }

            this.approval.approvers.forEach((val, index) => {
                approval.approvers.push({
                    employee_id: null,
                    unit_relation_id: null,
                    unit_level: 0,
                    department_id: null,
                    team_id: null,
                    odoo_job_id: null
                })

                if (val.selectedEmployee != null && val.selectedUnit != null) {
                    approval.approvers[index].employee_id = val.selectedEmployee.id
                    approval.approvers[index].unit_relation_id = val.selectedUnit.relation_id
                    approval.approvers[index].unit_level = val.unit_level
                    approval.approvers[index].department_id = val.selectedDepartment?.id ?? null
                    approval.approvers[index].team_id = val.selectedTeam?.id ?? null
                    approval.approvers[index].odoo_job_id = val.selectedJob?.odoo_job_id ?? null
                }
            })

            this.$swal({
                icon: 'info',
                title:"Do you want to save the data?",
                showCancelButton: true,
                confirmButtonText: 'Yes!',
                confirmButtonColor: '#126850',
                cancelButtonText: 'Cancel',
                cancelButtonColor: '#efefef',
            }).then((result)=>{
                if(result.value){
                    this.$axios.put(`api/v1/admin/approval/update/${this.approvalID}`, approval)
                        .then(() => {
                            useToast().success("Data successfully saved!", { position: 'bottom-right' });
                            this.$router.push({
                                name: 'approval_details',
                                params: { id: this.approvalID },
                            })
                        })
                        .catch(error => {
                            useToast().error(error.response.data.message, { position: 'bottom-right' });
                        });
                }
            });
        }
    }
}
</script>
