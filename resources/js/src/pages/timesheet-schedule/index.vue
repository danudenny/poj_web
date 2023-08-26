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
                            <div class="row">
                                <div class="col-md-10">
                                    <div class="row">
                                        <div class="col-md-3 mb-3">
                                            <label>Unit</label>
                                            <multiselect
                                                v-model="selectedUnit"
                                                :options="units"
                                                :multiple="false"
                                                label="name"
                                                track-by="relation_id"
                                                placeholder="Select Unit"
                                                @search-change="onUnitSearchName"
                                                @select="onSelectedUnit"
                                            ></multiselect>
                                        </div>
                                        <div class="col-md-2 mb-3">
                                            <label>Month - Year :</label>
                                            <Datepicker
                                                :model-value="selectedMonth"
                                                :enable-time-picker="false"
                                                month-picker
                                                auto-apply
                                                @update:model-value="onMonthSelected"
                                            >
                                            </Datepicker>
                                        </div>
                                        <div class="col-md-2 mb-3">
                                            <label>Shift Type :</label>
                                            <select class="form-select digits" v-model="shiftType" @change="onChangeShiftType">
                                                <option value=""> - </option>
                                                <option value="shift">Shift</option>
                                                <option value="non_shift">Non Shift</option>
                                            </select>
                                        </div>
                                        <div class="col-md-2 mb-3">
                                            <label>Employee Name</label>
                                            <input type="text" placeholder="Search Name" class="form-control" v-model="employeeName" @keyup="onChangeEmployeeName">
                                        </div>
                                        <div class="col-md-3 mb-3">
                                            <label>Job</label>
                                            <multiselect
                                                v-model="selectedJob"
                                                :options="jobs"
                                                :multiple="false"
                                                label="job_name"
                                                track-by="id"
                                                placeholder="Select Job"
                                                @select="onSelectedJob"
                                            ></multiselect>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-2" v-if="this.$store.state.permissions?.includes('timesheet-update')">
                                    <div class="d-flex justify-content-end mb-2">
                                        <button class="btn btn-success" @click="createSchedule">
                                            <i class="fa fa-plus"></i>&nbsp;Create Schedule
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <table class="table table-striped table-bordered table-condensed wrapable-table table-sticky">
                                <thead class="table-header-sticky">
                                    <tr>
                                        <th v-for="(header, index) in headers" :key="index" :rowspan="index < 4 ? 2 : 1" :class="header === 'Employee Name' ? 'column-sticky-table' : ''">
                                            {{ header }}
                                        </th>
                                    </tr>
                                    <tr>
                                        <th v-for="(header, index) in headerAbbrvs" :key="index" style="font-size:10px">
                                            {{ header.substring(0, 3) }}
                                        </th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="(timesheet, index) in timesheetData" :key="index" class="text-center">
                                        <td>{{ index + 1 }}</td>
                                        <td class="column-sticky-table">{{ timesheet.employee_name }}</td>
                                        <td>{{ timesheet.unit }}</td>
                                        <td>{{ timesheet.job }}</td>
                                        <td v-for="day in dateRanges">
                                            <span v-if="timesheet[day] === null" class="text-danger"><i class="fa fa-times"></i></span>
                                            <div class="schedule-section" v-else>
                                                <span :class="'badge badge-' + timesheet[day].color">{{ timesheet[day].time }}</span>
                                                <br/>
                                                <span class="badge badge-warning">{{ timesheet[day].unit }}</span>
                                                <div class="action-button" v-if="timesheet[day].is_can_change && this.$store.state.permissions?.includes('timesheet-update')">
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
import Datepicker from '@vuepic/vue-datepicker';

export default {
    components: {
        Datepicker
    },
    data() {
        return {
            loading: false,
            timesheetData: [],
            dateRanges: [],
            headers: [],
            headerAbbrvs: [],
            daysOfMonth: [],
            timesheetSplit: '',
            selectedUnit: null,
            units: [],
            unitPagination: {
                name: '',
                pageSize: 20,
                isOnSearch: false
            },
            selectedJob: null,
            jobs: [],
            selectedMonth: {
                month: new Date().getMonth(),
                year: new Date().getFullYear()
            },
            shiftType: "",
            employeeName: "",
            isOnSearch: true,
            showFilter: true
        }
    },
    async mounted() {
        this.getCurrentUnit()
        this.getUnitsData()
        this.getJobsData()
        await this.fetchTimesheetData();
        this.dateRange()
    },
    methods: {
        getCurrentUnit() {
			if (this.$store.state.currentRole === 'admin_operating_unit') {
				let activeAdminUnit = this.$store.state.activeAdminUnit
				if (activeAdminUnit != null) {
					this.selectedUnit = {
						relation_id: activeAdminUnit.unit_relation_id,
						name: activeAdminUnit.name.replace(" (Default)", "")
					}

					this.unitPagination.name = this.selectedUnit.name
					return
				}
			}

	        let currUser = JSON.parse(localStorage.getItem("USER_STORAGE_KEY"))
	        this.selectedUnit = currUser.last_units
	        this.unitPagination.name = this.selectedUnit.name
        },
        dateRange() {
            const lastDayOfMonth = new Date(this.selectedMonth.year, this.selectedMonth.month + 1, 0).getDate();
            this.dateRanges = Array.from({ length: lastDayOfMonth }, (_, index) => (index + 1).toString());
        },
        async fetchTimesheetData() {
            if (this.selectedUnit === null) {
                useToast().error("Need to select unit", { position: 'bottom-right' })
                return
            }
            if (this.selectedMonth === null) {
                useToast().error("Need to select month - year", { position: 'bottom-right' })
                return
            }

            this.isOnSearch = true

            try {
                let localFilter = {
                    unit_relation_id: this.selectedUnit.relation_id,
                    monthly_year: this.selectedMonth.year + "-" + ("0" + (this.selectedMonth.month + 1)).slice(-2),
                    shift_type: this.shiftType,
                    employee_name: this.employeeName,
                    odoo_job_id: this.selectedJob?.odoo_job_id ?? ''

                }
                await this.$axios.get(`/api/v1/admin/timesheet-schedule/schedules?unit_relation_id=${localFilter.unit_relation_id}&monthly_year=${localFilter.monthly_year}&shift_type=${localFilter.shift_type}&employee_name=${localFilter.employee_name}&employee_job_id=${localFilter.odoo_job_id}`,
                    {
                        headers: {
                            'X-Client-Timezone': Intl.DateTimeFormat().resolvedOptions().timeZone
                        }
                    })
                    .then(response => {
                        this.dateRange()

                        this.timesheetData = response.data.data;
                        this.headers = response.data.header;
                        this.headerAbbrvs = response.data.header_abbrv;
                        this.isOnSearch = false
                    })
            } catch (error) {
                console.error(error);
            }
        },
        getUnitsData() {
            const ls = localStorage.getItem('USER_ROLES')
            this.$axios.get(`/api/v1/admin/unit/paginated?per_page=${this.unitPagination.pageSize}&page=1&name=${this.unitPagination.name}`, {
                headers: {
                    'X-Selected-Role': ls
                }
            })
                .then(response => {
                    this.units = response.data.data.data
                    this.unitPagination.isOnSearch = false
                })
                .catch(error => {
                    console.error(error);
                });
        },
        getJobsData() {
            if (this.selectedUnit === null) {
                return
            }

            const ls = localStorage.getItem('USER_ROLES')
            this.$axios.get(`/api/v1/admin/unit-job?unit_relation_id=${this.selectedUnit.relation_id}&append=job_name`, {
                headers: {
                    'X-Selected-Role': ls
                }
            })
                .then(response => {
                    this.jobs = response.data.data
                })
                .catch(error => {
                    console.error(error);
                });
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
        },
        onUnitSearchName(val) {
            this.unitPagination.name = val

            if (!this.unitPagination.isOnSearch) {
                this.unitPagination.isOnSearch = true
                setTimeout(() => {
                    this.getUnitsData()
                }, 1000)
            }
        },
        onSelectedUnit(val) {
            this.selectedJob = null
            this.jobs = []

            this.fetchTimesheetData()
            this.getJobsData()
        },
        onMonthSelected(val) {
            this.selectedMonth = val
            console.log("DEBUGGER::SELECTED_DATE", new Date(this.selectedMonth.year, this.selectedMonth.month + 1, 0))
            this.fetchTimesheetData()
        },
        onChangeShiftType() {
            this.fetchTimesheetData()
        },
        onChangeEmployeeName(val) {
            if (!this.isOnSearch) {
                this.isOnSearch = true

                setTimeout(() => {
                    this.fetchTimesheetData()
                }, 1000)
            }
        },
        onSelectedJob() {
            this.fetchTimesheetData()
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

.button-danger {
    background-color: #dc3545;
    color: #fff
}

.button-danger:hover {
    background-color: #c82333;
    color: #fff
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

.wrapable-table {
  height: 90%;
  overflow-y: auto;
}

.table-sticky {
    position: relative;
}

.table-header-sticky {
    position: sticky;
    top: 0;
    z-index: 1;
}

.column-sticky-table {
    position: sticky;
    left: 0;
    z-index: 0;
}
</style>
