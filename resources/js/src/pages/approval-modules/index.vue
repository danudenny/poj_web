<template>
    <Breadcrumbs title="Approvals / Approval Modules" />

    <div class="container-fluid">
        <div class="email-wrap bookmark-wrap">
            <div class="row">
                <div class="col-md-12">
                    <div class="card card-absolute">
                        <div class="card-header bg-primary">
                            <h5>Approval Module List</h5>
                        </div>
                        <div class="card-body">
                            <div class="d-flex justify-content-end">
                                <button class="btn btn-primary" type="button" data-bs-toggle="modal"
                                        data-bs-target="#exampleModalCenter">
                                    <i class="fa fa-plus"></i> &nbsp; Create
                                </button>
                            </div>
                            <hr>
                            <table class="table table-striped table-hover table-responsive">
                                <thead>
                                <tr>
                                    <th>No</th>
                                    <th>Name</th>
                                    <th>Approval Form</th>
                                    <th>Action</th>
                                </tr>
                                </thead>
                                <tbody>
                                <tr v-for="(item, index) in approvalModules.data">
                                    <td>{{index + 1}}</td>
                                    <td class="text-center">{{item.name}}</td>
                                    <td class="text-center">
                                        <span class="badge badge-success">{{item.approvals.length}}</span>
                                    </td>
                                    <td>
                                        <button class="button-icon button-info" data-bs-toggle="modal"
                                                data-bs-target="#updateModal" @click="getSingleData(item.id)">
                                            <i class="fa fa-pencil text-center"></i>
                                        </button>
                                        <button class="button-icon button-danger">
                                            <i class="fa fa-trash text-center" @click="deleteData(item.id)"></i>
                                        </button>
                                    </td>
                                </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="modal fade" id="exampleModalCenter" tabindex="-1" role="dialog"
             aria-labelledby="exampleModalCenter" aria-hidden="true">
            <VerticalModal :title="modalTitle" @save="saveChanges">
                <div class="row">
                    <div class="col-md-12">
                        <div>
                            <label for="name">Name:</label>
                            <input type="text" class="form-control" id="name" v-model="approvalModule.name" required>
                        </div>
                    </div>
                </div>
            </VerticalModal>
        </div>
        <div class="modal fade" id="updateModal" tabindex="-1" role="dialog"
             aria-labelledby="exampleModalCenter" aria-hidden="true">
            <VerticalModal :title="modalUpdateTitle" @save="updateData(approvalModule.id)">
                <div class="row">
                    <div class="col-md-12">
                        <div>
                            <label for="name">Name:</label>
                            <input type="text" class="form-control" id="name" v-model="approvalModule.name" required>
                        </div>
                    </div>
                </div>
            </VerticalModal>
        </div>
    </div>
</template>

<script>
import axios from "axios";
import VerticalModal from "@components/modal/verticalModal.vue";

export default {
    components: {VerticalModal},
    data() {
        return {
            modalUpdateTitle: 'Update Approval Module',
            modalTitle: 'Create a new Approval Module',
            approvalModules: [],
            approvalModule: {
                name: "",
            }
        }
    },
    created() {
        this.getApprovalModules();
    },
    methods: {
        async getApprovalModules() {
            await axios.get('/api/v1/admin/approval-module')
                .then(response => {
                    this.approvalModules = response.data.data;
                })
                .catch(error => {
                    console.log(error);
                })
        },
        async getSingleData(id) {
            await axios.get(`api/v1/admin/approval-module/view/${id}`)
                .then(response => {
                    this.approvalModule = response.data.data;
                })
                .catch(error => {
                    this.warning_alert_state(error.message);
                })
        },
        async updateData(id) {
            await axios.put(`api/v1/admin/approval-module/update/${id}`, this.approvalModule)
                .then(() => {
                    this.basic_success_alert("Data updated successfully!");
                    this.getApprovalModules();
                    this.$nextTick(() => {
                        this.$refs.updateModal.$el.setAttribute('data-bs-dismiss', 'modal');
                    });
                })
                .catch(error => {
                    this.warning_alert_state(error.message);
                })
        },
        async saveChanges() {
            await axios.post('/api/v1/admin/approval-module/create', this.approvalModule)
                .then(() => {
                    this.basic_success_alert('Approval Module Created Successfully');
                    this.getApprovalModules();
                })
                .catch(() => {
                    this.warning_alert_state('Approval Module Creation Failed');
                })
        },
        deleteData(id) {
            this.basic_warning_alert(id);
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
        basic_warning_alert:function(id){
            this.$swal({
                icon: 'warning',
                title:"Delete Data?",
                text:'Once deleted, you will not be able to recover the data!',
                showCancelButton: true,
                confirmButtonText: 'Ok',
                confirmButtonColor: '#e64942',
                cancelButtonText: 'Cancel',
                cancelButtonColor: '#efefef',
            }).then((result)=>{
                if(result.value){
                    axios.delete(`api/v1/admin/approval-module/delete/${id}`)
                        .then(() => {
                            this.basic_success_alert("Data successfully deleted!");
                            this.getApprovalModules();
                        })
                        .catch(error => {
                            this.warning_alert_state(error.message);
                        });
                }else{
                    this.$swal({
                        text:'Your data is safe!'
                    });
                }
            });
        },
    },
}
</script>

<style scoped>
.table thead th {
    text-align: center;
    font-weight: bold;
    background-color: #0A5640;
    color: #fff;
}

.table tbody td:last-child {
    text-align: center;
}
.table tbody td:first-child {
    text-align: center;
}

.badge {
    font-size: 12px;
    padding: 5px 10px;
    border-radius: 20px;
    font-weight: 500;
    text-transform: uppercase;
    letter-spacing: 1px;
    margin: 0 5px;
}

.badge-success {
    background-color: #28a745;
    color: #fff
}

.badge-danger {
    background-color: #dc3545;
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

.button-success:hover {
    background-color: #218838;
    color: #fff
}

.button-danger {
    background-color: #dc3545;
    color: #fff
}

.button-danger:hover {
    background-color: #c82333;
    color: #fff
}

.button-info {
    background-color: #17a2b8;
    color: #fff
}

.button-info:hover {
    background-color: #138496;
    color: #fff
}
</style>
