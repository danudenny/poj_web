<template>
    <Breadcrumbs title="Approvals / Create Approval" />

    <div class="container-fluid">
        <div class="email-wrap bookmark-wrap">
            <div class="row">
                <div class="col-md-12">
                    <div class="card card-absolute">
                        <div class="card-header bg-primary">
                            <h5>Create Approval</h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-12">
                                    <div class="alert alert-warning" role="alert">
                                        <h6>
                                            <i class="fa fa-info-circle"></i> &nbsp; Infomasi
                                        </h6>
                                        <p class="text-white">
                                            Maksimal level yang diijinkan adalah 5 level. Urutan Organisasi dan Approver dimulai dari yang pertama atau paling atas. Approver yang dipilih meruapakan Karyawan pada Organisai yang dipilih.
                                        </p>
                                    </div>
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
                                                    :multiple="false">
                                                </multiselect>
                                            </div>
                                        </div>
                                    </div>
                                    <hr>
                                    <div class="d-flex justify-content-between mb-3">
                                        <div class="col-md-4">
                                            <div class="mb-3">
                                                <select class="form-select digits" v-model="selectedUnitLevel" @change="generateLevel">
                                                    <option value="">-- Select Unit Level --</option>
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
                                        <div>
                                            <div class="d-flex justify-content-end column-gap-2">
                                                <div v-if="rows.length > 1">
                                                    <button class="btn btn-outline-danger" @click="removeRow(index)">
                                                        <i class="fa fa-times-circle"></i> Remove Last Row
                                                    </button>
                                                </div>
                                                <button class="btn btn-outline-success" @click="addRow" v-if="rows.length < 5">
                                                    <i class="fa fa-plus-circle"></i> Add More Approvals
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                    <div v-for="(row, index) in rows" :key="index">
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label class="form-label" for="name">Organization {{ index + 1 }}</label>
                                                    <multiselect
                                                        v-model="row.selectedOrg"
                                                        placeholder="Select Organization"
                                                        label="name"
                                                        track-by="id"
                                                        :options="filteredUnit"
                                                        :multiple="false"
                                                        :close-on-select="true"
                                                        @select="onOrganizationSelect(index)">
                                                    </multiselect>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label class="form-label">Approver {{ index + 1 }}</label>
                                                    <multiselect
                                                        v-model="row.selectedUser"
                                                        placeholder="Select User"
                                                        label="name"
                                                        track-by="id"
                                                        :options="getFilteredUserOptions(index)"
                                                        :multiple="false"
                                                        @search-change="asyncFindUser"
                                                    ></multiselect>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="card-footer d-flex justify-content-end column-gap-2">
                            <button class="btn btn-warning" type="button" @click="this.$router.push('/approval')">
                                <i class="fa fa-arrow-circle-left"></i> &nbsp; Back
                            </button>
                            <button class="btn btn-primary" type="button" @click="saveChanges">
                                <i class="fa fa-save"></i> &nbsp; Save
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import { useToast } from 'vue-toastification'
export default {
    data() {
        return{
            selectedOrg: [],
            filteredUnit: [],
            approval: {
                name: '',
                approval_module_id: '',
                level: '',
                active: '',
                user_id: []
            },
            users: [],
            approvalModules: [],
            appendedApprover: [],
            appendedOrganization: [],
            organization: [],
            organization_id: 0,
            level: 0,
            selectedLevel: 0,
            appendedMultiselects: [],
            appendedMultiselectModels: [],
            filteredUsers: [],
            ls: JSON.parse(localStorage.getItem('USER_STORAGE_KEY')),
            selectedUnitLevel: "",
            rows: [
                {
                    selectedUnitLevel: '',
                    selectedOrg: null,
                    selectedUser: null
                }
            ]
        }
    },
    created() {
        this.getApprovalModules()
        this.loadOrg()
        this.getUsers()
   },
    methods: {
        // asyncFindUser (query) {
        //     this.isLoading = true
        //     if (query.length > 2) {
        //         this.$axios.get(`/api/v1/admin/employee?name=${query}&unit_id=${this.rows[0].selectedOrg.id}`)
        //         .then(response => {
        //             this.users = response.data.data.data
        //             this.isLoading = false
        //         })
        //         .catch(error => {
        //             console.log(error)
        //         })
        //     }
        // },
        addRow() {
            if (this.rows.length < 5) {
                this.rows.push({
                    selectedUnitLevel: '',
                    selectedOrg: null,
                    selectedUser: null
                });
                this.filteredUsers.push([]);
            }
        },
        removeRow(index) {
            this.rows.splice(index, 1);
            this.filteredUsers.splice(index, 1);
        },
        onOrganizationSelect(index) {
            const organizationId = this.rows[index].selectedOrg?.id;
            this.filteredUsers[index] = this.users.filter(user => {
                return (
                    (user.corporate != null && user.corporate.id === organizationId) ||
                    (user.kanwil != null && user.kanwil.id === organizationId) ||
                    (user.area != null && user.area.id === organizationId) ||
                    (user.cabang != null && user.cabang.id === organizationId) ||
                    (user.outlet != null && user.outlet.id === organizationId)
                );
            });
        },
        generateLevel() {
            this.filteredUnit = this.organization.filter(organization => {
                return organization.unit_level === parseInt(this.selectedUnitLevel);
            });
        },
        getFilteredUnitOptions(index) {
            const selectedLevel = this.rows[index].selectedUnitLevel;
            return this.organization.filter(organization => organization.unit_level === parseInt(selectedLevel));
        },
        getFilteredUserOptions(index) {
            const organizationId = this.rows[index].selectedOrg?.id;
            return this.users.filter(user => {
                return (
                    (user.corporate != null && user.corporate.id === organizationId) ||
                    (user.kanwil != null && user.kanwil.id === organizationId) ||
                    (user.area != null && user.area.id === organizationId) ||
                    (user.cabang != null && user.cabang.id === organizationId) ||
                    (user.outlet != null && user.outlet.id === organizationId)
                );
            });
        },
        async loadOrg() {
            await this.$axios.get(`/api/v1/admin/unit/related-unit`)
                .then(response => {
                    this.organization = response.data.data
                })
                .catch(error => {
                    console.error(error)
                })
        },
        async getApprovalModules() {
            await this.$axios.get('/api/v1/admin/approval-module')
                .then(response => {
                    this.approvalModules = response.data.data.data
                })
                .catch(error => {
                    console.error(error)
                })
        },
        async getUsers() {
            await this.$axios.get(`/api/v1/admin/employee?per_page=11000`)
                .then(response => {
                    this.users = response.data.data.data
                })
                .catch(error => {
                    console.error(error)
                })
        },
        async saveChanges(){
            let userIds = []
            this.rows.forEach(row => {
                userIds.push(row.selectedUser.id)
            })

            await this.$axios.post('/api/v1/admin/approval/create', {
                name: this.approval.name,
                approval_module_id: this.approval.approval_module_id.id,
                is_active: true,
                unit_level: this.selectedUnitLevel,
                user_id: userIds,
                unit_id: this.rows[0].selectedOrg.id
            }).then(response => {
                useToast().success(response.data.message)
                this.$router.push('/approval')
            }).catch(error => {
                useToast().error(error.response.data.message)
            })

        }
    }
}
</script>
