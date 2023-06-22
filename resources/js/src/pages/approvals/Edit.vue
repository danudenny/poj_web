<template>
    <Breadcrumbs title="Approvals / Edit Approval" />

    <div class="container-fluid">
        <div class="email-wrap bookmark-wrap">
            <div class="row">
                <div class="col-md-12">
                    <div class="card card-absolute">
                        <div class="card-header bg-primary">
                            <h5>Edit Approval</h5>
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
                                        <div class="col-md-12">
                                            <div class="mb-3">
                                                <label class="form-label" for="name">Title</label>
                                                <input class="form-control" id="title" type="text" v-model="approval.name" placeholder="Approval Title">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label class="form-label" for="name">Approval Type</label>
                                                <multiselect
                                                    v-model="approval.approval_module"
                                                    placeholder="Select Attendance Type"
                                                    label="name"
                                                    track-by="id"
                                                    :options="approvalModules"
                                                    :multiple="false">
                                                </multiselect>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label class="form-label" for="name">Level</label>
                                                <select class="form-select digits" v-model="selectedLevel" id="level" v-on:change="generateLevel">
                                                    <option :selected="selectedLevel === 1 ? 'selected' : ''">1</option>
                                                    <option :selected="selectedLevel === 2 ? 'selected' : ''">2</option>
                                                    <option :selected="selectedLevel === 3 ? 'selected' : ''">3</option>
                                                    <option :selected="selectedLevel === 4 ? 'selected' : ''">4</option>
                                                    <option :selected="selectedLevel === 5 ? 'selected' : ''">5</option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div v-for="(divElement, index) in appendedOrganization" :key="divElement.id">
                                                    <div class="mb-3">
                                                        <label class="form-label" for="name">Organization {{index+1}}</label>
                                                        <multiselect
                                                            v-model="appendedOrganization[index]"
                                                            placeholder="Select Organization"
                                                            label="name"
                                                            track-by="id"
                                                            :options="organization"
                                                            :multiple="false">
                                                        </multiselect>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div v-for="(divElement, index) in appendedApprover" :key="divElement.id">
                                                    <div class="mb-3">
                                                        <label class="form-label" for="name">Approver {{index+1}}</label>
                                                        <multiselect
                                                            v-model="appendedApprover[index]"
                                                            placeholder="Select User"
                                                            label="name"
                                                            track-by="id"
                                                            :options="users"
                                                            :multiple="false">
                                                        </multiselect>
                                                    </div>
                                                </div>
                                            </div>

                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="card-footer d-flex justify-content-end column-gap-2">
                            <button class="btn btn-warning" type="button" @click="backToApproval">
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
import axios from "axios"

export default {
    data() {
        return{
            approval: {
                name: '',
                approval_module_id: '',
                level: '',
                active: '',
                user_id: [],
                approval_module: {},
            },
            approvalId: this.$route.params.id,
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
        }
    },
    created() {
        this.getApprovals()
        this.getApprovalModules()
        this.loadCabang()
        this.getUsers()
    },
    methods: {
        async getApprovals() {
            await axios.get(`/api/v1/admin/approval/view/${this.approvalId}`)
                .then(response => {
                    this.approval = response.data.data
                    console.log(this.approval.approval_module)
                    this.selectedLevel = this.approval.users.length
                })
                .catch(error => {
                    console.error(error)
                })
        },
        async loadCabang() {
            await axios.get('/api/v1/admin/cabang')
                .then(response => {
                    this.organization = response.data.data.data
                })
                .catch(error => {
                    console.error(error)
                })
        },
        async getApprovalModules() {
            await axios.get('/api/v1/admin/approval-module')
                .then(response => {
                    this.approvalModules = response.data.data.data
                })
                .catch(error => {
                    console.error(error)
                })
        },
        generateLevel(){
            this.appendedApprover = [];
            this.appendedOrganization = [];
            for (let i = 0; i < this.selectedLevel; i++) {
                this.appendedApprover.push(this.appendLevelApprover());
                this.appendedOrganization.push(this.appendLevelOrganization());
            }
        },
        appendLevelOrganization() {
            this.appendedMultiselects.push(this.generateMultiselect());
            this.appendedMultiselectModels.push(null);
            return document.createElement('div');
        },
        appendLevelApprover() {
            this.appendedMultiselects.push(this.generateMultiselect());
            this.appendedMultiselectModels.push(null);
            return document.createElement('div');
        },
        async getUsers() {
            await axios.get('/api/v1/admin/user')
                .then(response => {
                    this.users = response.data.data.data
                })
                .catch(error => {
                    console.error(error)
                })
        },
        generateMultiselect() {
            return {
                id: Math.random()
            };
        },
        async saveChanges(){
            let userIds = []
            this.appendedApprover.forEach((element, index) => {
                userIds.push(element.id)
            })
            // console.log(this.approval.name, this.approval.approval_module_id, this.approval.active, this.approval.user_id)

            await axios.post('/api/v1/admin/approval/create', {
                name: this.approval.name,
                approval_module_id: this.approval.approval_module_id.id,
                is_active: true,
                user_id: userIds,
            }).then(response => {
                this.basic_success_alert(response.data.message)
                this.$router.push('/approval')
            }).catch(error => {
                this.warning_alert_state(error.response.data.message)
            })

        },
        backToApproval(){
            this.basic_warning_alert()
        },
        basic_warning_alert:function(){
            this.$swal({
                icon: 'warning',
                title:"Cancel?",
                text:'You will back to Approval Page, your data will not saved!',
                showCancelButton: true,
                confirmButtonText: 'Yes',
                confirmButtonColor: '#e64942',
                cancelButtonText: 'Cancel',
                cancelButtonColor: '#efefef',
            }).then((result)=>{
                if(result.value){
                    this.$router.push('/approval')
                }else{
                    setTimeout(()=>{
                        this.$swal({
                            text:'Continue Create Data!',
                            icon: 'info',
                        });
                    }, 1000)
                }
            });
        },
        basic_success_alert:function(message){
            this.$swal({
                icon: 'success',
                title:'Success',
                text:message,
                type:'success'
            });
        },
        warning_alert_state: function (message) {
            this.$swal({
                icon: "error",
                title: "Failed!",
                text: message,
                type: "error",
            });
        },
    }
}
</script>
