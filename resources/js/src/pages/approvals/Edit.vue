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
                                        <div class="col-md-12">
                                            <div v-if="loading" class="text-center">
                                                <img src="../../assets/loader.gif" alt="loading" width="100">
                                            </div>
                                            <div ref="approverTable"></div>
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
import {TabulatorFull as Tabulator} from 'tabulator-tables';

export default {
    data() {
        return{
            approval: {
                name: '',
                approval_module_id: '',
                level: '',
                is_active: '',
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
            loading: false,
        }
    },
    async mounted() {
        await this.getApprovals()
        await this.getApprovalModules()
        await this.loadCabang()
        await this.getUsers()
        this.initializeApproverTable()
    },
    methods: {
        async getApprovals() {
            this.loading = true
            await axios.get(`/api/v1/admin/approval/view/${this.approvalId}`)
                .then(response => {
                    this.approval = response.data.data
                    console.log(this.approval.users)
                    this.selectedLevel = this.approval.users.length
                    this.appendedApprover = response.data.data.users
                })
                .catch(error => {
                    console.error(error)
                })
        },
        initializeApproverTable() {
            new Tabulator(this.$refs.approverTable, {
                data: this.approval.users,
                layout: 'fitColumns',
                columns: [
                    {
                        title: 'No',
                        field: '',
                        formatter: 'rownum',
                        width: 100
                    },
                    {
                        title: 'Approver Name',
                        field: 'name',
                    },
                    {
                        title: 'Units Name',
                        field: 'employee.unit.name',
                    },
                ],
                paginationInitialPage:1,
            });
            this.loading = false
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
        async getUsers() {
            // await axios.get('/api/v1/admin/user')
            //     .then(response => {
            //         this.users = response.data.data.data
            //     })
            //     .catch(error => {
            //         console.error(error)
            //     })
        },
        generateMultiselect() {
            return {
                id: Math.random()
            };
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
        async saveChanges() {
            let userIds = []
            this.appendedApprover.forEach((element, index) => {
                userIds.push(element.id)
            })

            await axios.put(`/api/v1/admin/approval/update/${this.approvalId}`, {
                name: this.approval.name,
                approval_module_id: this.approval.approval_module.id,
                is_active: this.approval.is_active,
                user_id: userIds,
            }).then(response => {
                this.basic_success_alert(response.data.message)
            }).catch(error => {
                this.warning_alert_state(error.response.data.message)
            })
        }
    }
}
</script>

<style>
.tabulator .tabulator-header .tabulator-col {
    background-color: #0A5640 !important;
    color: #fff
}
.button-icon {
    width: 28px;
    height: 28px;
    border-radius: 20%;
    border: none;
    margin: 2px;
}

.button-success {
    background-color: #28a745;
    color: #fff
}
</style>

