<template>
    <Breadcrumbs title="Attendances" />

    <div class="container-fluid">
        <div class="email-wrap bookmark-wrap">
            <div class="row">
                <div class="col-md-12">
                    <div class="card card-absolute">
                        <div class="card-header bg-primary">
                            <h5>Attendances List</h5>
                        </div>
                        <div class="card-body">
                            <div>
                                <h5>
                                    <i class="fa fa-filter text-warning"></i>&nbsp; Filter
                                </h5>
                                <div class="row">
                                    <div class="col-md-12">
                                        <div class="row">
                                            <div class="col-md-3">
                                                <div class="form-group">
                                                    <label for="name">Name</label>
                                                    <input type="text" class="form-control" v-model="filter.name" id="name" v-on:keyup="getAttendances">
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label for="name">Date Range</label>
                                                    <VueDatePicker v-model="date" range multi-calendars @update:model-value="getAttendances"/>
                                                </div>
                                            </div>
                                            <div class="col-md-3">
                                                <div class="form-group">
                                                    <label for="name">Location</label>
                                                    <select class="form-control" v-model="filter.location" v-on:change="getAttendances">
                                                        <option value="">-- Select Location --</option>
                                                        <option value="onsite">OnSite</option>
                                                        <option value="offsite">OffSite</option>
                                                    </select>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <hr>
                            <div class="table-responsive">
                                <table class="table table-striped table-hover">
                                    <thead>
                                    <tr>
                                        <th>No</th>
                                        <th>Name</th>
                                        <th>Check-In</th>
                                        <th>Check-Out</th>
                                        <th>Location</th>
                                        <th>Approved</th>
                                        <th>Action</th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    <tr v-for="(item, index) in attendances">
                                        <td>{{index + 1}}</td>
                                        <td class="text-center">{{item.employee.name}}</td>
                                        <td class="text-center">{{item.real_check_in}}</td>
                                        <td class="text-center">{{item.real_check_out}}</td>
                                        <td class="text-center">
                                            <span class="badge badge-success" v-if="item.checkin_type === 'onsite'">OnSite</span>
                                            <span class="badge badge-danger" v-else>OffSite</span>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge badge-success" v-if="item.approved">Yes</span>
                                            <span class="badge badge-danger" v-else>No</span>
                                        </td>
                                        <td>
                                            <button class="button-icon button-success" @click="editData(item.id)">
                                                <i class="fa fa-eye text-center"></i>
                                            </button>
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
    </div>
</template>

<script>
import axios from "axios"
import Datepicker from "vue3-datepicker";

export default {
    components: {
        Datepicker
    },
    data() {
        return {
            filter: {
                name: "",
                check_in: null,
                check_out: null,
                location: ""
            },
            date: null,
            attendances: [],
            formattedCheckIn: "",
            formattedCheckOut: ""
        }
    },
    mounted() {
        this.getAttendances()
        if (this.filter.check_in && this.filter.check_out) {
            this.date = [this.filter.check_in, this.filter.check_out];
        }
    },
    methods: {
        formatDate(date) {
            const year = date.getFullYear();
            const month = String(date.getMonth() + 1).padStart(2, '0');
            const day = String(date.getDate()).padStart(2, '0');
            const hours = String(date.getHours()).padStart(2, '0');
            const minutes = String(date.getMinutes()).padStart(2, '0');
            const seconds = String(date.getSeconds()).padStart(2, '0');
            return `${year}-${month}-${day}`;
        },
        getAttendances() {
            if (this.date) {
                this.filter.check_in = this.date[0]
                this.filter.check_out = this.date[1]
            }

            if (this.filter.check_in && this.filter.check_out) {
                this.formattedCheckIn = this.formatDate(this.filter.check_in)
                this.formattedCheckOut = this.formatDate(this.filter.check_out)
            }

            axios.get(`/api/v1/admin/attendance?name=${this.filter.name}&check_in=${this.formattedCheckIn}&check_out=${this.formattedCheckOut}&checkin_type=${this.filter.location}`)
                .then(response => {
                    this.attendances = response.data.data.data
                })
                .catch(error => {
                    console.log(error)
                })
        }
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
