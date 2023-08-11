<template>
    <Breadcrumbs main="Detail Approval" />

    <div class="container-fluid">
        <div class="email-wrap bookmark-wrap">
            <div class="row">
                <div class="col-md-12">
                    <div class="card card-absolute">
                        <div class="card-header bg-primary">
                            <h5>Detail Approval</h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-12">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label class="form-label" for="name">Title</label>
                                                <input class="form-control" id="title" type="text" v-model="approval.name" placeholder="Approval Title" disabled>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label class="form-label" for="name">Approval Type</label>
                                                <multiselect
                                                    v-model="approval.approval_module"
                                                    placeholder="Select Attendance Type"
                                                    label="name"
                                                    track-by="id"
                                                    disabled="disabled"
                                                    :options="approvalModules"
                                                    :multiple="false">
                                                </multiselect>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label>Unit Level :</label>
                                                <select class="form-select digits" v-model="approval.unit_level" disabled>
                                                    <option value="0">-- Select Unit Level --</option>
                                                    <option value="1">Head Office</option>
                                                    <option value="2">Regional</option>
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
                                                v-model="approval.unit"
                                                placeholder="Select Unit"
                                                label="name"
                                                track-by="id"
                                                :options="units"
                                                :multiple="false"
                                                disabled
                                            >
                                            </multiselect>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="row" v-for="(item, index) in approval.approval_users">
                                    <hr/>
                                    <div class="row">
                                        <div class="col-md-6">
                                            <b><label>Approver {{ index + 1}} </label></b>
                                        </div>
                                    </div>
                                    <div class="row mt-3">
                                        <div class="col-md-4">
                                            <div class="mb-3">
                                                <label>Unit Level</label>
                                                <select class="form-select digits" v-model="approval.approval_users[index].unit_level" disabled>
                                                    <option value="0">-- Select Unit Level --</option>
                                                    <option value="1">Head Office</option>
                                                    <option value="2">Regional</option>
                                                    <option value="3">Corporate</option>
                                                    <option value="4">Kanwil</option>
                                                    <option value="5">Area</option>
                                                    <option value="6">Cabang</option>
                                                    <option value="7">Outlet</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <label>Select Unit</label>
                                            <multiselect
                                                v-model="approval.approval_users[index].unit"
                                                placeholder="Select Unit"
                                                label="name"
                                                track-by="id"
                                                :options="units"
                                                :multiple="false"
                                                disabled
                                            >
                                            </multiselect>
                                        </div>
                                        <div class="col-md-4">
                                            <label>Select Employee</label>
                                            <multiselect
                                                v-model="approval.approval_users[index].employee"
                                                placeholder="Select Unit"
                                                label="name"
                                                track-by="id"
                                                :options="units"
                                                :multiple="false"
                                                disabled
                                            >
                                            </multiselect>
                                        </div>
                                    </div>
                                    <hr/>
                                </div>
                            </div>
                        </div>
                        <div class="card-footer text-start">
                            <button class="btn btn-secondary" @click="$router.push('/approval')">
                                <i class="fa fa-arrow-left"></i>&nbsp;Back
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
                approval_users: []
            },
            approvalModules: [],
            units: [],
            selectedUnitLevel: 0,
            selectedUnit: null,
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
                    this.approval = response.data.data
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

            this.$axios.get(`/api/v1/admin/unit/paginated?unit_level=${unitFilter.unit_level}&unit_relation_id_structured=${unitFilter.unit_relation_id_restructured}`)
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
            }

            this.$axios.get(`/api/v1/admin/employee/paginated?last_unit_relation_id=${employeeFilter.last_unit_relation_id}`)
                .then(response => {
                    this.approval.approvers[index].employees = response.data.data
                })
                .catch(error => {
                    console.error(error)
                })
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
        onSave() {
            let approval = {
                unit_relation_id: this.approval.unit_relation_id,
                unit_level: this.approval.unit_level,
                name: this.approval.name,
                approval_module_id: this.approval.approval_module_id?.id,
                approvers: []
            }

            this.approval.approvers.forEach((val, index) => {
                approval.approvers.push({
                    employee_id: null,
                    unit_relation_id: null,
                    unit_level: 0
                })

                if (val.selectedEmployee != null && val.selectedUnit != null) {
                    approval.approvers[index].employee_id = val.selectedEmployee.id
                    approval.approvers[index].unit_relation_id = val.selectedUnit.relation_id
                    approval.approvers[index].unit_level = val.unit_level
                }
            })

            console.log(approval)

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
                    this.$axios.post(`api/v1/admin/approval/create`, approval)
                        .then(() => {
                            useToast().success("Data successfully saved!", { position: 'bottom-right' });
                            this.$router.push('/approval')
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
