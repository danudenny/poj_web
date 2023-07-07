<template>
    <Breadcrumbs title="Timesheet Assignment / Timesheet Assignment Create" />

    <div class="container-fluid">
        <div class="email-wrap bookmark-wrap">
            <div class="row">
                <div class="col-md-12">
                    <div class="card card-absolute">
                        <div class="card-header bg-primary">
                            <h5>Timesheet Assignment Create</h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-12">
                                    <div class="row">
                                        <div class="col-md-3 mb-3">
                                            <label>Periods :</label>
                                            <select v-model="period_id" class="form-control" >
                                                <option value="">Select Periods</option>
                                                <option :value="period.id" v-for="period in periods">{{ period.month }} {{period.year}}</option>
                                            </select>
                                        </div>
                                        <div class="col-md-3 mb-3">
                                            <label>Date :</label>
                                            <select v-model="date" class="form-control" >
                                                <option value="">Select Date</option>
                                                <option :value="td" v-for="td in totalDays">{{ td }}</option>
                                            </select>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label>Timesheet :</label>
                                            <select v-model="timesheet_id" class="form-control" >
                                                <option value="">Select Timesheet</option>
                                                <option :value="item.id" v-for="item in timesheets">{{item.name}} ( {{item.start_time}} - {{item.end_time}} )</option>
                                            </select>
                                        </div>
                                        <div class="col-md-12 mb-3">
                                            <label>Assign To (Employees) :</label>
                                            <div v-if="loading" class="text-center">
                                                <img src="../../assets/loader.gif" alt="loading" width="100">
                                            </div>
                                            <div ref="employeeTable"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="card-footer text-start">
                            <button class="btn btn-primary m-r-10" @click="saveSchedule">Save</button>
                            <button class="btn btn-secondary" @click="$router.push('/timesheet-assign')">Back</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
<script>
import {TabulatorFull as Tabulator} from "tabulator-tables";
import {useToast} from "vue-toastification";

export default {
    data() {
        return {
            employees: [],
            selectedEmployees: [],
            timesheets: [],
            day: 0,
            totalDays: [],
            periods: [],
            loading: false,
            period_id: 0,
            date: 0,
            timesheet_id: 0,
        }
    },
    async mounted() {
        await this.getPeriods(),
        await this.getTotalDays(),
        await this.getTimesheet();
        await this.getEmployee();
        await this.initializeEmployeeTable();
    },
    methods: {
        async getTotalDays() {
            const date = new Date();
            const year = date.getFullYear();
            const month = date.getMonth();
            this.day = new Date(year, month, 0).getDate();
            for (let i = 1; i <= this.day; i++) {
                this.totalDays.push(i);
            }
        },
        async getPeriods() {
          await this.$axios.get(`/api/v1/admin/employee-timesheet/periods`)
              .then(response => {
                    this.periods = response.data.data;
                    this.periods.forEach((item, index) => {
                        this.periods[index].month = new Date(item.year, item.month - 1, 1).toLocaleString('default', {month: 'long'});
                    });
              })
              .catch(error => {
                  console.error(error);
              });
        },
        async getTimesheet() {
          await this.$axios.get(`/api/v1/admin/employee-timesheet`)
              .then(response => {
                  this.timesheets = response.data.data.data;
              })
              .catch(error => {
                  console.error(error);
              });
        },
        async getEmployee() {
            this.loading = true;
            const ls = JSON.parse(localStorage.getItem('USER_STORAGE_KEY'));
            await this.$axios.get(`/api/v1/admin/employee?unit_id=${ls.unit_id}`)
                .then(response => {
                    this.employees = response.data.data;
                })
                .catch(error => {
                    console.error(error);
                });
        },
        async initializeEmployeeTable() {
            const table = await new Tabulator(this.$refs.employeeTable, {
                data: this.employees,
                layout: 'fitColumns',
                columns: [
                    {
                        formatter: "rowSelection",
                        titleFormatter: "rowSelection",
                        hozAlign: "center",
                        width: 100,
                        headerSort: false,
                        cellClick: function (e, cell) {
                            cell.getRow()
                        },
                    },
                    {
                        title: 'Employee Name',
                        field: 'name',
                        headerFilter:"input"
                    },
                    {
                        title: 'Unit',
                        field: 'unit.name',
                        headerFilter:"input"
                    }
                ],
                pagination: 'local',
                paginationSize: 30,
                paginationSizeSelector: [10, 20, 50, 100],
                headerFilter: true,
                rowFormatter: (row) => {

                },
            });
            table.on("rowSelectionChanged", function(data, rows, selected, deselected)  {
                this.selectedEmployees = rows.map(row => row.getData().id);
                localStorage.setItem('selectedEmployees', JSON.stringify(this.selectedEmployees));
            })
            this.loading = false;
        },
        async saveSchedule() {
            const ls = JSON.parse(localStorage.getItem('selectedEmployees'));
            await this.$axios.post(`/api/v1/admin/employee-timesheet/assign-schedule`, {
                employee_ids: ls,
                period_id: this.period_id,
                timesheet_id: this.timesheet_id,
                date: this.date
            }).then(response => {
                localStorage.removeItem('selectedEmployees');
                useToast().success(response.data.message , { position: 'bottom-right' });
                this.$router.push('/timesheet-assign');
            }).catch(error => {
                useToast().error(response.data.message , { position: 'bottom-right' });
            });
        }
    }
}
</script>
