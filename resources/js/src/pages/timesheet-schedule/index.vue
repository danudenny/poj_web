<template>
    <Breadcrumbs main="Timesheet Assignment" />

    <div class="container-fluid">
        <div class="email-wrap bookmark-wrap">
            <div class="row">
                <div class="col-md-12">
                    <div class="card card-absolute">
                        <div class="card-header bg-primary">
                            <h5>Daftar Jadwal Pegawai</h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-10">
                                    <div class="row">
                                        <div class="col-md-4 mb-3">
                                            <label>Unit Pegawai</label>
                                            <multiselect
                                                v-model="selectedUnit"
                                                :options="units"
                                                :multiple="false"
                                                label="formatted_name"
                                                track-by="relation_id"
                                                placeholder="Select Unit"
                                                @search-change="onUnitSearchName"
                                                @select="onSelectedUnit"
                                            ></multiselect>
                                        </div>
                                        <div class="col-md-2">
                                            <div class="form-group mt-4">
                                                <div class="checkbox p-0">
                                                    <input id="is_employee_unit_specific" type="checkbox" @change="fetchTimesheetData" v-model="isEmployeeUnitSpecific">
                                                    <label class="text-muted" for="is_employee_unit_specific">Is Spesific?</label>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-4 mb-3">
                                            <label>Unit Kerja</label>
                                            <multiselect
                                                v-model="selectedWorkingUnit"
                                                :options="workingUnits"
                                                :multiple="false"
                                                label="formatted_name"
                                                track-by="relation_id"
                                                placeholder="Select Unit"
                                                @search-change="onWorkingUnitSearchName"
                                                @select="onSelectedWorkingUnit"
                                            ></multiselect>
                                        </div>
                                        <div class="col-md-2">
                                            <div class="form-group mt-4">
                                                <div class="checkbox p-0">
                                                    <input id="is_working_unit_specific" type="checkbox" @change="fetchTimesheetData" v-model="isWorkingUnitSpecific">
                                                    <label class="text-muted" for="is_working_unit_specific">Is Spesific?</label>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-4 mb-3">
                                            <label>Bulan - Tahun :</label>
                                            <Datepicker
                                                :model-value="selectedMonth"
                                                :enable-time-picker="false"
                                                month-picker
                                                auto-apply
                                                @update:model-value="onMonthSelected"
                                            >
                                            </Datepicker>
                                        </div>
                                        <div class="col-md-4 mb-3">
                                            <label>Tipe Shift :</label>
                                            <select class="form-select digits" v-model="shiftType" @change="onChangeShiftType">
                                                <option value=""> - </option>
                                                <option value="shift">Shift</option>
                                                <option value="non_shift">Non Shift</option>
                                            </select>
                                        </div>
                                        <div class="col-md-4 mb-3">
                                            <label>Nama Pegawai</label>
                                            <input type="text" placeholder="Search Name" class="form-control" v-model="employeeName" @keyup="onChangeEmployeeName">
                                        </div>
                                        <div class="col-md-4 mb-3">
                                            <label>Pekerjaan</label>
                                            <multiselect
                                                v-model="selectedJob"
                                                :options="jobs"
                                                :multiple="false"
                                                label="name"
                                                track-by="id"
                                                placeholder="Select Job"
                                                @select="onSelectedJob"
                                            ></multiselect>
                                        </div>
                                        <div class="col-md-4 mb-3">
                                            <label>Operating Unit</label>
                                            <multiselect
                                                v-model="selectedOperatingUnit"
                                                :options="operatingUnits"
                                                :multiple="false"
                                                label="name"
                                                track-by="id"
                                                placeholder="Select Operating Unit"
                                                @select="onSelectOperatingUnit"
                                            ></multiselect>
                                        </div>
                                        <div class="col-md-4 mb-3">
                                            <div class="mt-4">
                                                <button class="btn btn-warning" @click="resetFilter">
                                                    <i class="fa fa-rotate-left"></i>&nbsp;Hapus Filter
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-2" v-if="this.$store.state.permissions?.includes('timesheet-update')">
                                    <div class="d-flex justify-content-end mb-2">
                                        <button class="btn btn-success" @click="createSchedule">
                                            <i class="fa fa-plus"></i>&nbsp;Buat Schedule
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <table v-if="!isOnSearch" class="table table-striped table-bordered table-condensed wrapable-table table-sticky">
                                <thead class="table-header-sticky">
                                    <tr>
                                        <th v-for="(header, index) in headers" :key="index" :rowspan="index < 4 ? 2 : 1" :class="header === 'Employee Name' ? 'column-sticky-table' : ''">
                                            {{ header }}
                                        </th>
                                    </tr>
                                    <tr>
                                        <th v-for="(header, index) in headerAbbrvs" :key="index" style="font-size:10px">
                                            {{ header.name }}
                                            <div v-if="header.public_holiday?.name != null">
                                                <span class="badge badge-success">{{ header.public_holiday.name }}</span>
                                            </div>
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
                                        <td v-for="day in dateRanges" :class="headerAbbrvs[day - 1].public_holiday ? 'background-public-holiday' : ''">
                                            <div class="empty-schedule-section" v-if="timesheet[day] === null">
                                                <span class="text-danger info-button"><i class="fa fa-times"></i></span>
                                                <div class="action-button">
                                                    <button
                                                        class="button-icon button-success"
                                                        v-if="isAllowToAdd(day)"
                                                        @click="() => {
                                                            onTriggerAdd(day, timesheet)
                                                        }"
                                                        data-bs-toggle="modal"
                                                        data-bs-target="#createTimesheet"
                                                    >
                                                        <i data-action="view" class="fa fa-plus"></i>
                                                    </button>
                                                </div>
                                            </div>
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
                            <div v-else>
                                <p align="center">
                                    <div class="spinner-border text-primary">
                                        <span class="sr-only">Loading...</span>
                                    </div>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="modal fade" id="createTimesheet" ref="createTimesheet" tabindex="-1" role="dialog" aria-labelledby="exampleModalCenter" aria-hidden="true">
            <VerticalModal title="Buat Jadwal" @save="onCreate()">
                <div class="row">
                    <div class="col-md-12">
                        <div class="mt-2">
                            <label for="date">Tanggal</label>
                            <input type="date" name="date" id="date" class="form-control" v-model="this.payloadCreateTimesheet.date" required disabled>
                        </div>
                    </div>
                    <div class="col-md-12">
                        <div class="mt-2">
                            <label for="name">Nama Pegawai</label>
                            <input type="text" name="name" id="name" class="form-control" v-model="selectedEmployeeTimesheet.employee_name" required disabled>
                        </div>
                    </div>
                    <div class="col-md-12">
                        <label>Target Unit Level:</label>
                        <multiselect
                                v-model="selectedLevel"
                                placeholder="Pilih Target Unit Level"
                                label="name"
                                track-by="name"
                                :options="unitLevels"
                                :multiple="false"
                                @select="onSelectUnitLevel"
                        >
                        </multiselect>
                    </div>
                    <div class="col-md-12">
                        <label>Target Unit :</label>
                        <multiselect
                                v-model="selectedUnitTarget"
                                placeholder="Pilih Target Unit"
                                label="formatted_name"
                                track-by="relation_id"
                                :options="unitTargets"
                                :multiple="false"
                                @select="onSelectedUnitTarget"
                                @search-change="onSearchNameUnitTarget"
                        >
                        </multiselect>
                    </div>
                    <div class="col-md-12">
                        <label>Timesheet :</label>
                        <select v-model="timesheet_id" class="form-control" >
                            <option value="null">Pilih Timesheet</option>
                            <option :value="item.id" v-for="item in timesheets">{{item.formatted_name}}</option>
                        </select>
                    </div>
                </div>
            </VerticalModal>
        </div>
    </div>
</template>

<script>
import {useToast} from "vue-toastification";
import Datepicker from '@vuepic/vue-datepicker';
import VerticalModal from "@components/modal/verticalModal.vue";
import moment from "moment/moment";

export default {
    components: {
        Datepicker,
        VerticalModal
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
            timesheetPage: 1,
            isEmployeeUnitSpecific: true,
            units: [],
            selectedEmployeeTimesheet: {
                employee_id: null,
                employee_name: null
            },
            unitPagination: {
                name: '',
                pageSize: 50,
                isOnSearch: false
            },
            selectedWorkingUnit: null,
            isWorkingUnitSpecific: true,
            workingUnits: [],
            workingUnitPagination: {
                name: '',
                pageSize: 50,
                isOnSearch: false
            },
            selectedJob: null,
            jobs: [],
            selectedOperatingUnit: null,
            operatingUnits: [],
            selectedMonth: {
                month: new Date().getMonth(),
                year: new Date().getFullYear()
            },
            shiftType: "",
            employeeName: "",
            isOnSearch: true,
            showFilter: true,
            selectedLevel: null,
            unitLevels: [
                {
                    name: 'Kanwil',
                    value: 4
                },
                {
                    name: 'Area',
                    value: 5
                },
                {
                    name: 'Cabang',
                    value: 6
                },
                {
                    name: 'Outlet',
                    value: 7
                },
            ],
            selectedUnitTarget: null,
            unitTargets: [],
            targetUnitPagination: {
                limit: 20,
                name: '',
                isOnSearch:false
            },
            timesheet_id: null,
            timesheets: [],
            payloadCreateTimesheet: {
                employee_ids: [],
                date: null,
                timesheet_id: null
            }
        }
    },
    async mounted() {
        this.getCurrentUnit()
        this.fetchTimesheetData();
        this.getUnitsData()
        this.getWorkingUnitsData()
        this.getJobsData()
        this.getOperatingUnit()
        this.dateRange()
    },
    methods: {
        getCurrentUnit() {
          if (this.$store.state.currentRole === 'admin_unit') {
            let activeAdminUnit = this.$store.state.activeAdminUnit
	          console.log(activeAdminUnit)
            if (activeAdminUnit != null) {
              this.selectedUnit = {
                relation_id: activeAdminUnit.unit_relation_id,
                name: activeAdminUnit.name.replace(" (Default)", ""),
	            formatted_name: activeAdminUnit.formatted_name
              }
            }
          } else {
              let currUser = JSON.parse(localStorage.getItem("USER_STORAGE_KEY"))
              this.selectedUnit = currUser.last_units
              this.unitPagination.name = this.selectedUnit.formatted_name
          }
        },
        dateRange() {
            const lastDayOfMonth = new Date(this.selectedMonth.year, this.selectedMonth.month + 1, 0).getDate();
            this.dateRanges = Array.from({ length: lastDayOfMonth }, (_, index) => (index + 1).toString());
        },
        fetchTimesheetData() {
            if (this.selectedMonth === null) {
                useToast().error("Need to select month - year", { position: 'bottom-right' })
                return
            }

            this.isOnSearch = true
            this.timesheetData = [];
            this.headers = [];
            this.headerAbbrvs = [];
            this.timesheetPage = 1;
            this.processFetchingData()

        },
        processFetchingData() {
            try {
                let localFilter = {
                    unit_relation_id: this.selectedUnit?.relation_id ?? '',
                    monthly_year: this.selectedMonth.year + "-" + ("0" + (this.selectedMonth.month + 1)).slice(-2),
                    shift_type: this.shiftType,
                    employee_name: this.employeeName,
                    odoo_job_id: this.selectedJob?.odoo_job_id ?? '',
                    is_specific_unit_relation_id: this.isEmployeeUnitSpecific ? 1 : 0,
                    working_unit_relation_id: this.selectedWorkingUnit?.relation_id ?? '',
                    is_specific_working_unit_relation_id: this.isWorkingUnitSpecific ? 1 : 0,
                    default_operating_unit_id: this.selectedOperatingUnit?.relation_id ?? '',
                    page: this.timesheetPage
                }
                this.$axios.get(`/api/v1/admin/timesheet-schedule/schedules?page=${localFilter.page}&default_operating_unit_id=${localFilter.default_operating_unit_id}&working_unit_relation_id=${localFilter.working_unit_relation_id}&is_specific_working_unit=${localFilter.is_specific_working_unit_relation_id}&unit_relation_id=${localFilter.unit_relation_id}&is_specific_unit_relation_id=${localFilter.is_specific_unit_relation_id}&monthly_year=${localFilter.monthly_year}&shift_type=${localFilter.shift_type}&employee_name=${localFilter.employee_name}&employee_job_id=${localFilter.odoo_job_id}`,
                    {
                        headers: {
                            'X-Client-Timezone': Intl.DateTimeFormat().resolvedOptions().timeZone
                        }
                    })
                    .then(response => {
                        if (localFilter.page != this.timesheetPage) {
                            return
                        }

                        this.dateRange()

                        this.timesheetData.push(...response.data.data);
                        this.headers = response.data.header;
                        this.headerAbbrvs = response.data.header_abbrv;

                        if (response.data.data.length > 0) {
                            this.timesheetPage += 1
                            this.processFetchingData()
                        }
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
        getWorkingUnitsData() {
            const ls = localStorage.getItem('USER_ROLES')
            this.$axios.get(`/api/v1/admin/unit/paginated?per_page=${this.workingUnitPagination.pageSize}&page=1&name=${this.workingUnitPagination.name}`, {
                headers: {
                    'X-Selected-Role': ls
                }
            })
                .then(response => {
                    this.workingUnits = response.data.data.data
                    this.workingUnitPagination.isOnSearch = false
                })
                .catch(error => {
                    console.error(error);
                });
        },
        getJobsData() {
            const ls = localStorage.getItem('USER_ROLES')
            this.$axios.get(`/api/v1/admin/job/structured-job/data`, {
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
        getOperatingUnit() {
            const ls = localStorage.getItem('USER_ROLES')
            this.$axios.get(`/api/v1/admin/unit/operating-unit`, {
                headers: {
                    'X-Selected-Role': ls
                }
            })
                .then(response => {
                    this.operatingUnits = response.data.data
                })
                .catch(error => {
                    console.error(error);
                });
        },
        async getUnitTargets() {
            if (this.selectedLevel === null) {
                return
            }

            await this.$axios.get(`api/v1/admin/unit/paginated?unit_level=${this.selectedLevel.value}&per_page=${this.targetUnitPagination.limit}&name=${this.targetUnitPagination.name}`)
                .then(response => {
                    this.unitTargets = response.data.data.data;
                    this.targetUnitPagination.isOnSearch = false
                }).catch(error => {
                    console.error(error);
                });
        },
        async getTimesheet(id) {
            try {
                await this.$axios.get(`api/v1/admin/employee-timesheet/${id}?is_with_corporate=1`)
                    .then(response => {
                        this.timesheets = response.data.data.data;
                    }).catch(error => {
                        console.error(error);
                    });
            } catch (error) {
                console.log(error);
            }
        },
        onDeleteEmployeeTimesheet:function(id){
            this.$swal({
                icon: 'warning',
                title:"Anda Yakin Ingin Menghapus Data?",
                text:'Setelah Menghapus, Data Tidak Dapat Dikembalikan!',
                showCancelButton: true,
                confirmButtonText: 'Ya, Hapus!',
                confirmButtonColor: '#e64942',
                cancelButtonText: 'Batal',
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
        },
        onWorkingUnitSearchName(val) {
            this.workingUnitPagination.name = val

            if (!this.workingUnitPagination.isOnSearch) {
                this.workingUnitPagination.isOnSearch = true
                setTimeout(() => {
                    this.getWorkingUnitsData()
                }, 1000)
            }
        },
        onSelectedWorkingUnit(val) {
            this.fetchTimesheetData()
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
        },
        onSelectOperatingUnit() {
            this.fetchTimesheetData()
        },
        isAllowToAdd(day) {
	        let d = new Date();
	        let today = moment(d.getFullYear() + "-" + ("0" + (d.getMonth() + 1)).slice(-2) + "-" + ("0" + (d.getDate())).slice(-2))
            let dateSelected = moment(this.selectedMonth.year + "-" + ("0" + (this.selectedMonth.month + 1)).slice(-2) + '-' + day)

            return (dateSelected.isSameOrAfter(today)) && this.$store.state.permissions?.includes('timesheet-update')
        },
        onTriggerAdd(day, timesheet) {
            let dateSelected = moment(this.selectedMonth.year + "-" + ("0" + (this.selectedMonth.month + 1)).slice(-2) + '-' + day)

            this.payloadCreateTimesheet.date = dateSelected.format('YYYY-MM-DD')
            this.selectedEmployeeTimesheet = timesheet
        },
        onCreate() {
            if (this.selectedEmployeeTimesheet === null) {
                return
            }

            let payload = {
                employee_ids: [this.selectedEmployeeTimesheet.employee_id],
                date_format: this.payloadCreateTimesheet.date,
                timesheet_id: this.timesheet_id,
                date: moment(this.payloadCreateTimesheet.date).format('DD'),
	            unit_relation_id: this.selectedUnitTarget.relation_id
            }

            this.$swal({
                icon: 'warning',
                title:"Apakah Anda Ingin Menambahkan Jadwal Kepada Pegawai?",
                text:'Mohon Periksa Kembali Sebelum Menambakan!',
                showCancelButton: true,
                confirmButtonText: 'Ya!',
                confirmButtonColor: '#126850',
                cancelButtonText: 'Batal',
                cancelButtonColor: '#efefef',
            }).then((result)=>{
                if(result.value){
                    this.$axios.post(`/api/v1/admin/employee-timesheet/assign-schedule`, payload).then(response => {
                        useToast().success(response.data.message , { position: 'bottom-right' });
                        this.fetchTimesheetData()
                        this.selectedLevel = null
                        this.selectedUnitTarget = null
                        this.unitTargets = []
                        this.timesheet_id = null
                        this.timesheets = []
                    }).catch(error => {
                        useToast().error(error.response.data.message , { position: 'bottom-right' });
                    });
                }
            });
        },
        onSelectUnitLevel() {
            this.selectedUnitTarget = null
            this.unitTargets = []
            this.getUnitTargets()
        },
        onSelectedUnitTarget() {
            this.getTimesheet(this.selectedUnitTarget.id)
        },
        onSearchNameUnitTarget(val) {
            this.targetUnitPagination.name = val

            if (!this.targetUnitPagination.isOnSearch) {
                this.targetUnitPagination.isOnSearch = true
                setTimeout(() => {
                    this.getUnitTargets()
                }, 1000)
            }
        },
        resetFilter() {
            this.selectedUnit = null;
            this.isEmployeeUnitSpecific = true;
            this.selectedWorkingUnit = null;
            this.isWorkingUnitSpecific = true;
            this.shiftType = '';
            this.employeeName = '';
            this.selectedJob = null
            this.selectedOperatingUnit = null;

            if (this.$store.state.currentRole === 'admin_operating_unit') {
                let activeAdminUnit = this.$store.state.activeAdminUnit
                if (activeAdminUnit != null) {
                    this.selectedUnit = {
                        relation_id: activeAdminUnit.unit_relation_id,
                        name: activeAdminUnit.name.replace(" (Default)", "")
                    }
                }
            } else {
	            let currUser = JSON.parse(localStorage.getItem("USER_STORAGE_KEY"))
	            this.selectedUnit = currUser.last_units
	            this.unitPagination.name = this.selectedUnit.name
            }

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

.action-button {
    display: none;
}

.schedule-section:hover {

}

.schedule-section:hover .action-button {
    display: block;
}

.wrapable-table {
  height: 600px;
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

.empty-schedule-section {

}

.empty-schedule-section:hover .action-button {
    display: block;
}

.empty-schedule-section:hover .info-button {
    display: none;
}

.background-public-holiday {
    background-color: #ff00006e !important;
}
</style>
