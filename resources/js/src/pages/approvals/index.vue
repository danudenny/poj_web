<template>
    <Breadcrumbs title="Approvals / Approval" />

    <div class="container-fluid">
        <div class="email-wrap bookmark-wrap">
            <div class="row">
                <div class="col-md-12">
                    <div class="card card-absolute">
                        <div class="card-header bg-primary">
                            <h5>Approval List</h5>
                        </div>
                        <div class="card-body">
                            <div class="d-flex justify-content-end">
                                <button class="btn btn-primary" type="button" @click="createData">
                                    <i class="fa fa-plus"></i> &nbsp; Create
                                </button>
                            </div>
                            <hr>
                            <table class="table table-striped table-hover table-responsive">
                                <thead>
                                <tr>
                                    <th>No</th>
                                    <th>Name</th>
                                    <th>Approval Module</th>
                                    <th>Level</th>
                                    <th>Active</th>
                                    <th>Action</th>
                                </tr>
                                </thead>
                                <tbody>
                                <tr v-for="(item, index) in approvals.data">
                                    <td>{{index + 1}}</td>
                                    <td class="text-center">{{item.name}}</td>
                                    <td class="text-center">{{item.approval_module.name}}</td>
                                    <td class="text-center">
                                        <span class="badge badge-info">{{item.users.length}}</span>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge badge-success" v-if="item.is_active">Active</span>
                                        <span class="badge badge-danger" v-else>Inactive</span>
                                    </td>
                                    <td>
                                        <button class="button-icon button-info" @click="editData(item.id)">
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
    </div>
</template>

<script>
import axios from "axios"
import VerticalModal from "@components/modal/verticalModal.vue";


export default {
    components: {
        VerticalModal
    },
    data() {
        return {
            approvals: [],
        }
    },
    created() {
        this.getApproval();
    },
    methods: {
        async getApproval() {
            await this.$axios.get('/api/v1/admin/approval')
                .then(response => {
                    this.approvals = response.data.data
                })
                .catch(error => {
                    console.log(error)
                })
        },
        createData() {
            this.$router.push({ path: '/approval/create' })
        },
        editData(id) {
            this.$router.push({ path: `/approval/edit/${id}` })
        },
        deleteData(id) {
            this.basic_warning_alert(id);
        },
        basic_warning_alert:function(id){
            this.$swal({
                icon: 'warning',
                title:"Delete Data?",
                text:'Once deleted, you will not be able to recover the data!',
                showCancelButton: true,
                confirmButtonText: 'Yes, delete it!',
                confirmButtonColor: '#e64942',
                cancelButtonText: 'Cancel',
                cancelButtonColor: '#efefef',
            }).then((result)=>{
                if(result.value){
                    this.$axios.delete(`api/v1/admin/approval/delete/${id}`)
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

    }
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
