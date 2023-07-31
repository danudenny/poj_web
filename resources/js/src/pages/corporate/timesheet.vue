<template>
    <div>
        <div class="d-flex justify-content-end mb-2">
<!--            <button class="btn btn-success" type="button" data-bs-toggle="modal"-->
<!--                    data-bs-target="#exampleModalCenter" @click="showModal">-->
<!--                <i class="fa fa-plus"></i> &nbsp; Create-->
<!--            </button>-->
            <button @click="openModal" class="btn btn-primary">Create</button>
        </div>
        <table class="table table-striped table-hover table-responsive">
            <thead>
            <tr>
                <th>No</th>
                <th>Name</th>
                <th>From</th>
                <th>To</th>
                <th>Shift Type</th>
                <th>Days</th>
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
                    <span class="badge badge-warning" v-if="item.shift_type === 'shift'">Shift</span>
                    <span class="badge badge-primary" v-else>Non Shift</span>
                </td>
                <td class="text-center">
                    <span v-if="!item.days" class="badge badge-danger">N/A</span>
                    <template v-else v-for="(day, index) in item.days">
                        <span class="badge badge-success">{{ day }}</span>
                        <br v-if="(index + 1) % 3 === 0 && index + 1 !== item.days.length">
                    </template>
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
    </div>
    <div>
        <Modal :visible="isModalVisible" @save="saveChanges" :title="modalTitle" @update:visible="isModalVisible = $event">
            <div class="row">
                <div class="col-md-12">
                    <div>
                        <label for="name">Name:</label>
                        <input type="text" class="form-control" id="name" v-model="timesheet.name" required>
                    </div>

                    <div class="mt-3">
                        <label for="name">Shift Type :</label>
                        <select id="status" class="form-select" v-model="timesheet.shift_type" required @change="onChangeShiftType">
                            <option value='shift'>Shift</option>
                            <option value='non_shift'>Non Shift</option>
                        </select>
                    </div>
                    <div class="mt-3" v-if="timesheet.shift_type === 'non_shift'">
                        <label for="time_start">Select Days:</label>
                        <div class="d-flex column-gap-3">
                            <input type="checkbox" id="monday" name="monday" value="monday" v-model="timesheet.days"> Monday
                            <input type="checkbox" id="tuesday" name="tuesday" value="tuesday" v-model="timesheet.days"> Tuesday
                            <input type="checkbox" id="wednesday" name="wednesday" value="wednesday" v-model="timesheet.days"> Wednesday
                            <input type="checkbox" id="thursday" name="thursday" value="thursday" v-model="timesheet.days"> Thursday
                            <input type="checkbox" id="friday" name="friday" value="friday" v-model="timesheet.days"> Friday
                            <input type="checkbox" id="saturday" name="saturday" value="saturday" v-model="timesheet.days"> Saturday
                            <input type="checkbox" id="sunday" name="sunday" value="sunday" v-model="timesheet.days"> Sunday
                        </div>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="mt-3 col-md-6">
                    <label for="time_start">Start Time:</label>
                    <input type="time" class="form-control" id="time_start" v-model="timesheet.start_time">
                </div>
                <div class="mt-3 col-md-6">
                    <label for="time_end">End Time:</label>
                    <input type="time" class="form-control" id="time_end" v-model="timesheet.end_time">
                </div>
            </div>
            <div class="row">
                <div class="mt-3">
                    <label for="status">Status:</label>
                    <select id="status" class="form-select" v-model="timesheet.is_active" required>
                        <option :value=true>Active</option>
                        <option :value=false>Inactive</option>
                    </select>
                </div>
            </div>
        </Modal>
    </div>
</template>
<script>
import {useToast} from "vue-toastification";
import Modal from "../../components/modal.vue";
export default {
    props: {
        id: {
            type: Number,
            required: true
        }
    },
    components: {
        Modal
    },
    data() {
        return {
            modalTitle: 'Timesheet Master Data',
            modalUpdateTitle: "Update Employee Timesheet Master Data",
            timesheetData: [],
            page: 1,
            selectedDays: [],
            timesheet: {
                name: '',
                start_time: '',
                shift_type: '',
                end_time: '',
                days: [],
                is_active: true
            },
            singleTimesheet: {
                name: '',
                start_time: '',
                end_time: '',
                is_active: true,
                days: [],
                shift_type: ''
            },
            days: ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'],
            isModalVisible: false,
        }
    },
    created() {
        this.getTimesheet()
    },
    methods: {
        openModal() {
            this.isModalVisible = true;
        },
        close() {
            this.visible = false;
        },
        async getTimesheet() {
            try {
                const response = await this.$axios.get(`api/v1/admin/employee-timesheet/${this.id}`);
                this.timesheetData = response.data.data;
            } catch (error) {
                console.log(error);
            }
        },
        showModal() {
            this.isModalVisible = true;
        },
        closeModal() {
            this.timesheet.name = '';
            this.timesheet.start_time = '';
            this.timesheet.end_time = '';
            this.timesheet.shift_type = '';
            this.timesheet.days = [];
            this.timesheet.is_active = false;
            this.isModalVisible = false;

        },
        saveChanges() {
            this.$axios.post(`api/v1/admin/employee-timesheet/create/${this.id}`, this.timesheet)
                .then(() => {
                    useToast().success("Data saved successfully!");
                    this.getTimesheet();
                    this.closeModal();
                })
                .catch(error => {
                    useToast().warning(error.message);
                });
        },
        deleteTimesheet(id) {
            this.basic_warning_alert(id);
        },
        getSingleData(id) {
            this.$axios.get(`api/v1/admin/employee-timesheet/view/${this.id}/${id}`)
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
                            useToast().success("Data deleted successfully!");
                            this.getTimesheet();
                        })
                        .catch(error => {
                            this.warning_alert_state(error.message);
                        });
                }
            });
        },
        onChangeShiftType() {
            if (this.singleTimesheet.shift_type === 'shift') {
                this.singleTimesheet.days = null;
            }
        },
        isDaySelected(day) {
            if (this.singleTimesheet.days) {
                return this.singleTimesheet.days.includes(day);
            }
       },
        toggleDay(day) {
            if (!this.singleTimesheet.days) {
                this.singleTimesheet.days = [];
            }
            const index = this.singleTimesheet.days.indexOf(day);
            if (index === -1) {
                this.singleTimesheet.days.push(day);
            } else {
                this.singleTimesheet.days.splice(index, 1);
            }
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
    font-size: 10px;
    padding: 5px 10px;
    border-radius: 5px;
    font-weight: 500;
    text-transform: capitalize;
    letter-spacing: 1px;
    margin: 5px 5px;
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
