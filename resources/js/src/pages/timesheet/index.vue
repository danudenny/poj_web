<template>
    <Breadcrumbs title="Timesheet" />

    <div class="container-fluid">
        <div class="email-wrap bookmark-wrap">
            <div class="row">
                <div class="col-md-12">
                    <div class="card card-absolute">
                        <div class="card-header bg-primary">
                            <h5>Timesheet List</h5>
                        </div>
                        <div class="card-body">
                            <div class="d-flex justify-content-end">
                                <button class="btn btn-primary" type="button" data-bs-toggle="modal"
                                        data-bs-target="#exampleModalCenter">
                                    <i class="fa fa-plus"></i> &nbsp; Create
                                </button>
                            </div>
                            <div>
                                Filter
                            </div>
                            <hr>
                            <table class="table table-striped table-hover table-responsive">
                                <thead>
                                    <tr>
                                        <th>No</th>
                                        <th>Name</th>
                                        <th>From</th>
                                        <th>To</th>
                                        <th>Active</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="(item, index) in timesheetData.data">
                                        <td>{{index + 1}}</td>
                                        <td class="text-center">{{item.name}}</td>
                                        <td class="text-center">{{item.start_time}}</td>
                                        <td class="text-center">{{item.end_time}}</td>
                                        <td class="text-center">
                                            <span class="badge badge-success" v-if="item.is_active">Active</span>
                                            <span class="badge badge-danger" v-else>Inactive</span>
                                        </td>
                                        <td>
                                            <button class="button-icon button-info" data-bs-toggle="modal"
                                                    data-bs-target="#updateModal" @click="getSingleData(item.id)">
                                                <i class="fa fa-pencil text-center"></i>
                                            </button>
                                            <button class="button-icon button-danger">
                                                <i class="fa fa-trash text-center" @click="deleteTimesheet(item.id)"></i>
                                            </button>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                            <hr>
<!--                            <Bootstrap5Pagination-->
<!--                                :data="timesheetData"-->
<!--                                @pagination-change-page="getTimesheet"-->
<!--                            />-->
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
                            <input type="text" class="form-control" id="name" v-model="timesheet.name" required>
                        </div>

                        <div class="mt-1">
                            <label for="time_start">Start Time:</label>
                            <input type="text" class="form-control" id="time_start" v-model="timesheet.start_time" required>
                        </div>

                        <div class="mt-1">
                            <label for="time_end">End Time:</label>
                            <input type="text" class="form-control" id="time_end" v-model="timesheet.end_time" required>
                        </div>

                        <div class="mt-1">
                            <label for="status">Status:</label>
                            <select id="status" class="form-select" v-model="timesheet.is_active" required>
                                <option :value=true>Active</option>
                                <option :value=false>Inactive</option>
                            </select>
                        </div>
                    </div>
                </div>
            </VerticalModal>
        </div>

        <div class="modal fade" id="updateModal" tabindex="-1" role="dialog"
             aria-labelledby="exampleModalCenter" aria-hidden="true">
            <VerticalModal :title="modalUpdateTitle" @save="updateTimesheet(singleTimesheet.id)">
                <div class="row">
                    <div class="col-md-12">
                        <div>
                            <label for="name">Name:</label>
                            <input type="text" class="form-control" id="name" v-model="singleTimesheet.name" required>
                        </div>

                        <div class="mt-1">
                            <label for="time_start">Start Time:</label>
                            <input type="text" class="form-control" id="time_start" v-model="singleTimesheet.start_time" required>
                        </div>

                        <div class="mt-1">
                            <label for="time_end">End Time:</label>
                            <input type="text" class="form-control" id="time_end" v-model="singleTimesheet.end_time" required>
                        </div>

                        <div class="mt-1">
                            <label for="status">Status:</label>
                            <select id="status" class="form-select" v-model="singleTimesheet.is_active" required>
                                <option :value=true :selected="singleTimesheet.is_active === true ? 'selected' : ''">Active</option>
                                <option :value=false :selected="singleTimesheet.is_active === false ? 'selected' : ''">Inactive</option>
                            </select>
                        </div>
                    </div>
                </div>
            </VerticalModal>
        </div>
    </div>
</template>

<script>
import axios from 'axios'
import VerticalModal from "@components/modal/verticalModal.vue";
import { Bootstrap5Pagination } from 'laravel-vue-pagination';
export default {
    components: {
        VerticalModal,
        Bootstrap5Pagination
    },
    data() {
        return {
            modalTitle: 'Employee Timesheet Master Data',
            modalUpdateTitle: "Update Employee Timesheet Master Data",
            timesheetData: [],
            page: 1,
            timesheet: {
                name: '',
                start_time: '',
                end_time: '',
                is_active: true
            },
            singleTimesheet: {
                name: '',
                start_time: '',
                end_time: '',
                is_active: true
            }
        }
    },
    created() {
        this.getTimesheet()
    },
    methods: {
        async getTimesheet(page) {
            try {
                const response = await this.$axios.get(`api/v1/admin/employee-timesheet?limit=10`);
                this.timesheetData = response.data.data;
            } catch (error) {
                console.log(error);
            }
        },
        saveChanges() {
            this.$axios.post('api/v1/admin/employee-timesheet/create', this.timesheet)
                .then(() => {
                    this.basic_success_alert("Data saved successfully!");
                    this.getTimesheet();
                    this.$nextTick(() => {
                        this.$refs.modal.$el.setAttribute('data-bs-dismiss', 'modal');
                    });
                })
                .catch(error => {
                    this.warning_alert_state(error.message);
                });
        },
        deleteTimesheet(id) {
            this.basic_warning_alert(id);
        },
        getSingleData(id) {
            this.$axios.get(`api/v1/admin/employee-timesheet/view/${id}`)
                .then(response => {
                    this.singleTimesheet = response.data.data;
                })
                .catch(error => {
                    this.warning_alert_state(error.message);
                })
        },
        updateTimesheet(id) {
            this.$axios.put(`api/v1/admin/employee-timesheet/update/${id}`, this.singleTimesheet)
                .then(() => {
                    this.basic_success_alert("Data updated successfully!");
                    this.getTimesheet();
                    this.$nextTick(() => {
                        this.$refs.updateModal.$el.setAttribute('data-bs-dismiss', 'modal');
                    });
                })
                .catch(error => {
                    this.warning_alert_state(error.message);
                })
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
                    this.$axios.delete(`api/v1/admin/employee-timesheet/delete/${id}`)
                        .then(() => {
                            this.basic_success_alert("Data successfully deleted!");
                            this.getTimesheet();
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
