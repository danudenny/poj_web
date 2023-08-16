<template>
    <div>
        <div class="d-flex justify-content-end mb-2">
            <button @click="openModal" class="btn btn-primary">Create</button>
        </div>
        <table class="table table-striped table-hover table-responsive">
            <thead>
            <tr>
                <th>No</th>
                <th>Name</th>
                <th>Start Time</th>
                <th>End Time</th>
                <th>Shift Type</th>
                <th>Days</th>
                <th>Action</th>
            </tr>
            </thead>
            <tbody>
            <tr v-for="(item, index) in timesheetData.data">
                <td>{{index + 1}}</td>
                <td class="text-center">{{item.name}}</td>
                <td class="text-center">
                    <span v-if="item.shift_type === 'shift'">{{item.start_time}}</span>
                    <span v-else class="badge badge-danger">N/A</span>
                </td>
                <td class="text-center">
                    <span v-if="item.shift_type === 'shift'">{{item.end_time}}</span>
                    <span v-else class="badge badge-danger">N/A</span>
                </td>
                <td class="text-center">
                    <span class="badge badge-warning" v-if="item.shift_type === 'shift'">Shift</span>
                    <span class="badge badge-primary" v-else>Non Shift</span>
                </td>
                <td class="text-center">
                    <span v-if="item.shift_type === 'shift'" class="badge badge-danger">N/A</span>
                    <button  v-else class="button-icon button-success" @click="showDays(item.id)">
                        <i class="fa fa-calendar-check-o"></i>
                    </button>
                </td>
                <td>
                    <button class="button-icon button-info" data-bs-toggle="modal"
                            data-bs-target="#updateModal" @click="openEditModal(item.id)">
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
            <div class="d-flex row-gap-2 flex-column">
                <div>
                    <label>Name:</label>
                    <multiselect
                        v-model="timesheet.name"
                        :options="shifts"
                        :multiple="false"
                        :searchable="false"
                        :close-on-select="true"
                        :clear-on-select="false"
                        :preserve-search="true"
                        placeholder="Select Shift"
                        label="name"
                        track-by="value"
                        :preselect-first="false">
                    </multiselect>
                </div>

                <div>
                    <label>Shift Type:</label>
                    <multiselect
                        v-model="timesheet.shift_type"
                        :options="shiftType"
                        :multiple="false"
                        :searchable="false"
                        :close-on-select="true"
                        :clear-on-select="false"
                        :preserve-search="true"
                        placeholder="Select Shift Type"
                        label="name"
                        track-by="value"
                        :preselect-first="false">
                    </multiselect>
                </div>

                <div v-if="timesheet.shift_type.value === 'shift'" class="d-flex column-gap-2">
                    <div class="col-md-4">
                        <label>Start Time:</label>
                        <input class="form-control" type="time" v-model="timesheet.start_time" required>
                    </div>
                    <div class="col-md-4">
                        <label>End Time:</label>
                        <input class="form-control" type="time" v-model="timesheet.end_time" required>
                    </div>
                </div>

                <div v-if="timesheet.shift_type.value === 'non_shift'">
                    <label>Days:</label>
                    <div v-for="(day, index) in days" :key="index">
                        <div class="d-flex justify-content-between column-gap-2 my-1">
                            <input class="form-control" v-model="day.day" disabled>
                            <input class="form-control" type="time" v-model="day.start_time">
                            <input class="form-control" type="time" v-model="day.end_time">
                        </div>
                    </div>
                </div>
            </div>
        </Modal>
    </div>
    <div>
        <Modal :visible="isEditModalVisible" @save="updateTimesheet(timesheet.id)" :title="editModalTitle" @update:visible="isEditModalVisible = $event">
            <div v-if="isLoading" class="d-flex justify-content-center">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
            </div>
            <div v-else class="d-flex row-gap-2 flex-column">
                <div>
                    <label>Name:</label>
                    <multiselect
                        v-model="timesheet"
                        :options="shifts"
                        :multiple="false"
                        :searchable="true"
                        :close-on-select="true"
                        placeholder="Select Shift"
                        label="name"
                        track-by="value">
                    </multiselect>
                </div>

                <div>
                    <label>Shift Type:</label>
                    <select class="form-control" v-model="timesheet.shift_type">
                        <option v-for="shift in shiftType" :key="shift.value" :value="shift.value">{{shift.name}}</option>
                    </select>
                </div>

                <div v-if="timesheet.shift_type === 'shift'" class="d-flex column-gap-2">
                    <div class="col-md-4">
                        <label>Start Time:</label>
                        <input class="form-control" type="time" v-model="timesheet.start_time" required>
                    </div>
                    <div class="col-md-4">
                        <label>End Time:</label>
                        <input class="form-control" type="time" v-model="timesheet.end_time" required>
                    </div>
                </div>

                <div v-if="timesheet.shift_type === 'non_shift'">
                    <label>Days:</label>
                    <div v-for="(day, index) in days" :key="index">
                        <div class="d-flex justify-content-between column-gap-2 my-1">
                            <input class="form-control" v-model="day.day" disabled>
                            <input class="form-control" type="time" :value="getStartTime(day)" @input="setStartTime(day, $event.target.value)">
                            <input class="form-control" type="time" :value="getEndTime(day)" @input="setEndTime(day, $event.target.value)">
                        </div>
                    </div>
                </div>
            </div>
        </Modal>
    </div>
    <div>
        <SmallModal :visible="isSmallModalVisible" :title="smallModalTitle" @update:visible="isSmallModalVisible = $event">
            <div class="row">
                <div class="col-md-12">
                    <table class="table table-hover table-responsive table-striped">
                        <thead>
                        <tr class="text-center" style="">
                            <td>Days</td>
                            <td>Start Time</td>
                            <td>End Time</td>
                        </tr>
                        </thead>
                        <tbody>
                        <tr v-for="value in item.timesheet_days" class="text-center">
                            <td>{{value.day}}</td>
                            <td>{{value.start_time}}</td>
                            <td>{{value.end_time}}</td>
                        </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </SmallModal>
    </div>
</template>
<script>
import {useToast} from "vue-toastification";
import Modal from "../../components/modal.vue";
import SmallModal from "../../components/small_modal.vue";
export default {
    props: {
        id: {
            type: Number,
            required: true
        }
    },
    components: {
        Modal,
        SmallModal
    },
    data() {
        return {
            modalTitle: 'Timesheet Master Data',
            editModalTitle: 'Edit Timesheet Master Data',
            smallModalTitle: "Timesheet Master Days",
            timesheetData: [],
            page: 1,
            timesheet: {
                name: '',
                start_time: '',
                shift_type: '',
                end_time: '',
                days: {
                    day: '',
                    start_time: '',
                    end_time: ''
                },
                timesheet_days: [],
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
            days: [
                { day: 'Monday', start_time: '', end_time: '' },
                { day: 'Tuesday', start_time: '', end_time: '' },
                { day: 'Wednesday', start_time: '', end_time: '' },
                { day: 'Thursday', start_time: '', end_time: '' },
                { day: 'Friday', start_time: '', end_time: '' },
                { day: 'Saturday', start_time: '', end_time: '' },
                { day: 'Sunday', start_time: '', end_time: '' },
            ],
            isModalVisible: false,
            isSmallModalVisible: false,
            isEditModalVisible: false,
            shiftType: [
                {
                    name: 'Shift',
                    value: 'shift'
                },
                {
                    name: 'Non Shift',
                    value: 'non_shift'
                }
            ],
            shifts: [
                {
                    name: 'Pagi',
                    value: 'Pagi'
                },
                {
                    name: 'Siang',
                    value: 'Siang'
                },
                {
                    name: 'Malam',
                    value: 'Malam'
                },
                {
                    name: 'Long Shift',
                    value: 'Long Shift'
                },
            ],
            selectedShifts: [],
            isLoading: false
        }
    },
    mounted() {
        this.getTimesheet()
    },
    methods: {
        getStartTime(day) {
            const timesheetDay = this.timesheet.timesheet_days.find(item => item.day === day.day);
            return timesheetDay ? timesheetDay.start_time : '';
        },
        setStartTime(day, value) {
            const timesheetDay = this.timesheet.timesheet_days.find(item => item.day === day.day);
            if (timesheetDay) {
                timesheetDay.start_time = value;
            }
        },
        getEndTime(day) {
            const timesheetDay = this.timesheet.timesheet_days.find(item => item.day === day.day);
            console.log(timesheetDay)

            return timesheetDay ? timesheetDay.end_time : '';
        },
        setEndTime(day, value) {
            const timesheetDay = this.timesheet.timesheet_days.find(item => item.day === day.day);
            if (timesheetDay) {
                timesheetDay.end_time = value;
            }
        },
        showDays(e) {
            this.timesheetData.data.forEach(item => {
                if (item.id === e) {
                    this.item = item;
                    this.isSmallModalVisible = true;
                }
            });
        },
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
        openEditModal(e) {
            this.isEditModalVisible = true;
            this.getSingleData(e);
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
        closeEditModal() {
            this.timesheet.name = '';
            this.timesheet.start_time = '';
            this.timesheet.end_time = '';
            this.timesheet.shift_type = '';
            this.timesheet.days = [];
            this.timesheet.is_active = false;
            this.isEditModalVisible = false;
        },
        saveChanges() {
            const filledDays = this.days.filter(day => day.start_time && day.end_time);

            const timesheet = {
                name: this.timesheet.name.value,
                start_time: this.timesheet.shift_type.value === 'shift' ? this.timesheet.start_time : null,
                end_time: this.timesheet.shift_type.value === 'shift' ? this.timesheet.end_time : null,
                shift_type: this.timesheet.shift_type.value,
                days: this.timesheet.shift_type.value === 'non_shift' ? filledDays : null,
                is_active: this.timesheet.is_active,
            }

            this.$axios.post(`api/v1/admin/employee-timesheet/create/${this.id}`, timesheet)
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
            this.loading = true;
            this.$axios.get(`api/v1/admin/employee-timesheet/view/${this.id}/${id}`)
                .then(response => {
                    this.timesheet = response.data.data;
                    this.loading = false
                })
                .catch(error => {
                    this.warning_alert_state(error.message);
                })
        },
        updateTimesheet(id) {
            const filledDays = this.days.filter(day => day.start_time && day.end_time);

            const timesheet = {
                name: this.timesheet.name,
                start_time: this.timesheet.shift_type === 'shift' ? this.timesheet.start_time : null,
                end_time: this.timesheet.shift_type === 'shift' ? this.timesheet.end_time : null,
                shift_type: this.timesheet.shift_type,
                days: this.timesheet.shift_type === 'non_shift' ? filledDays : null,
                is_active: this.timesheet.is_active,
            }

            this.$axios.put(`api/v1/admin/employee-timesheet/update/${id}`, timesheet)
                .then(() => {
                    useToast().success("Data updated successfully!");
                    this.getTimesheet();
                    this.closeEditModal();
                })
                .catch(error => {
                    useToast().warning(error.message);
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
                        .catch(() => {
                            seToast().error("Failed to delete data!");
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
