<template>
    <Breadcrumbs main="Timesheet Assignment" />

    <div class="container-fluid">
        <div class="email-wrap bookmark-wrap">
            <div class="row">
                <div class="col-md-12">
                    <div class="card card-absolute">
                        <div class="card-header bg-primary">
                            <h5>Timesheet Assignment List</h5>
                        </div>
                        <div class="card-body">
                            <div class="d-flex justify-content-end mb-2">
                                <button class="btn btn-success" @click="createSchedule">
                                    <i class="fa fa-plus"></i>&nbsp;Create Schedule
                                </button>
                            </div>
                            <table class="table table-striped table-bordered table-condensed">
                                <thead>
                                    <tr>
                                        <th v-for="(header, index) in headers" :key="index">
                                            {{ header }}
                                        </th>
                                    </tr>
                                    <tr>
                                        <th colspan="4"></th>
                                        <th v-for="(header, index) in headerAbbrvs" :key="index" style="font-size:10px">
                                            {{ header.substring(0, 3) }}
                                        </th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="(timesheet, index) in timesheetData" :key="index" class="text-center">
                                        <td>{{ index + 1 }}</td>
                                        <td>{{ timesheet.employee_name }}</td>
                                        <td>{{ timesheet.unit }}</td>
                                        <td>{{ timesheet.job }}</td>
                                        <td v-for="day in dateRanges">
                                            <span v-if="timesheet[day] === null" class="text-danger"><i class="fa fa-times"></i></span>
                                            <div class="schedule-section" v-else>
                                                <span class="badge badge-danger" v-if="timesheet[day].check_in_time === null">{{ timesheet[day].time }}</span>
                                                <span class="badge badge-success" v-else>{{ timesheet[day].time }}</span>
                                                <br/>
                                                <span class="badge badge-warning">{{ timesheet[day].unit }}</span>
                                                <div class="action-button" v-if="timesheet[day].check_in_time === null">
                                                    <button class="button-icon button-warning" @click="onEditEmployeeTimesheet(timesheet[day].id)"><i data-action="view" class="fa fa-pencil"></i> </button>
                                                    <button class="button-icon button-danger" @click="onDeleteEmployeeTimesheet(timesheet[day].id)"><i data-action="view" class="fa fa-trash"></i> </button>
                                                </div>
                                            </div>
                                        </td>
                                        <td>{{ formatHours(timesheet.total_hours) }}</td>
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
import {useToast} from "vue-toastification";

export default {
    data() {
        return {
            loading: false,
            timesheetData: [],
            dateRanges: [],
            headers: [],
            headerAbbrvs: [],
            daysOfMonth: [],
            timesheetSplit: ''
        }
    },
    async mounted() {
        await this.fetchTimesheetData();
        this.dateRange()
    },
    methods: {
        dateRange() {
            const currentDate = new Date();
            const lastDayOfMonth = new Date(currentDate.getFullYear(), currentDate.getMonth() + 1, 0).getDate();
            this.dateRanges = Array.from({ length: lastDayOfMonth }, (_, index) => (index + 1).toString());
        },
        async fetchTimesheetData() {
            try {
                await this.$axios.get('/api/v1/admin/timesheet-schedule/schedules')
                    .then(response => {
                        this.timesheetData = response.data.data;
                        this.headers = response.data.header;
                        this.headerAbbrvs = response.data.header_abbrv;
                    })
            } catch (error) {
                console.error(error);
            }
        },
        onDeleteEmployeeTimesheet:function(id){
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
                    this.$axios.delete(`api/v1/admin/employee-timesheet/delete-employee-timesheet/${id}`)
                        .then(() => {
                            useToast().success("Data successfully deleted!");
                            this.fetchTimesheetData()
                        })
                        .catch(error => {
                            useToast().error(error.response.data.message);
                        });
                }
            });
        },
        onEditEmployeeTimesheet(id) {
            this.$router.push({ name: 'timesheet-schedule-edit', params: {id}})
        },
        formatHours(hours) {
            return hours === 0 ? '-' : hours.toString();
        },
        createSchedule() {
            this.$router.push({ name: 'timesheet-schedule-create' })
        },
        onClickSchedule(data) {
            console.log(data)
        }
    }

}

</script>

<style scoped>
table {
    display: block;
    overflow-x: auto;
    white-space: nowrap;
}
.table thead th {
    text-align: center;
    font-weight: bold;
    background-color: #0A5640;
    color: #fff;
    vertical-align: middle;
}

.table tbody td:last-child {
    text-align: center;
}
.table tbody td:first-child {
    text-align: center;
}

.badge {
    font-size: 9px;
    padding: 5px 10px;
    border-radius: 5px;
    font-weight: 400;
    text-transform: capitalize;
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
.drawer-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0, 0, 0, 0.3);
    z-index: 1999;
}

.drawer {
    position: relative;
    top: 0;
    right: -800px;
    height: 100%;
    width: 70%;
    background-color: #f0f0f0;
    transition: right 0.5s ease-in-out;
    z-index: 1000;
    overflow-y: auto;
    overflow-x: auto;
    scrollbar-width: thin;
}

.drawer.open {
    right: 0;
}
.past .offset2 .span1 .cv-item {
    background-color: #218838 !important;
}
.card-absolute .card-body {
    height: 550px !important;
}

.action-button {
    display: none;
}

.schedule-section:hover {

}

.schedule-section:hover .action-button {
    display: block;
}
</style>
