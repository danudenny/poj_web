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
                                        <div class="col-md-6 mb-3">
                                            <label>Periods :</label>
                                            <select v-model="period_id" class="form-control" >
                                                <option value="">Select Periods</option>
                                                <option :value="period.id" v-for="period in periods">{{ period.month }} {{period.year}}</option>
                                            </select>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label>Date :</label>
                                            <select v-model="date" class="form-control" >
                                                <option value="">Select Date</option>
                                                <option :value="td" v-for="td in totalDays">{{ td }}</option>
                                            </select>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label>Unit :</label>
                                            <multiselect
                                                v-model="selectedOptions"
                                                placeholder="Select Unit"
                                                label="name"
                                                track-by="name"
                                                :options="units"
                                                :multiple="false"
                                                @select="selectedUnit"
                                            >
                                            </multiselect>
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
            workingArea: {},
            currentPage: 1,
            pageSize: 10,
            filterName: "",
            filterUnit: "",
            units: [],
            selectedOptions: [],
            visibleOptions: [],
        }
    },
    async mounted() {
        await this.getPeriods();
        await this.getTotalDays();
        await this.getEmployee();
        await this.getUnit();
        await this.initializeEmployeeTable();
    },
    methods: {
        async selectedUnit() {
            await this.getTimesheet(this.selectedOptions.id)
        },
        async getUnit() {
            await this.$axios.get(`api/v1/admin/unit/related-unit`)
                .then(response => {
                    this.units = response.data.data;
                }).catch(error => {
                    console.error(error);
                });
        },
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
          await this.$axios.get(`/api/v1/admin/periods`)
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
        async getTimesheet(id) {
            try {
                await this.$axios.get(`api/v1/admin/employee-timesheet/${id}`)
                    .then(response => {
                        this.timesheets = response.data.data.data;
                        console.log(this.timesheets)
                    }).catch(error => {
                        console.error(error);
                    });
            } catch (error) {
                console.log(error);
            }
        },
        async getEmployee() {
            this.loading = true;
            await this.$axios.get(`/api/v1/admin/employee`)
                .then(response => {
                    this.employees = response.data.data.data;
                })
                .catch(error => {
                    console.error(error);
                });
        },
        async initializeEmployeeTable() {
            const ls = localStorage.getItem('my_app_token')
            const table = await new Tabulator(this.$refs.employeeTable, {
                // data: this.employees,
                ajaxURL: '/api/v1/admin/employee',
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
                        field: '',
                        headerFilter:"input",
                        formatter: (cell, formatterParams) => {
                            const wd = cell.getData();
                            const hierarchy = [
                                wd.kanwil,
                                wd.area,
                                wd.cabang,
                                wd.outlet
                            ];

                            const sortedHierarchy = hierarchy
                                .filter(data => data && data.value !== null)
                                .sort((a, b) => a.unit_level - b.unit_level);

                            this.workingArea = sortedHierarchy[sortedHierarchy.length - 1];
                            return this.workingArea.name
                        }
                    }
                ],
                pagination: true,
                paginationMode: 'remote',
                responsiveLayout: true,
                filterMode:"remote",
                paginationSize: this.pageSize,
                ajaxConfig: {
                    headers: {
                        Authorization: `Bearer ${ls}`,
                    },
                },
                ajaxParams: {
                    page: this.currentPage,
                    size: this.pageSize,
                },
                ajaxURLGenerator: (url, config, params) => {
                    params.filter.map((item) => {
                        if (item.field === 'name') this.filterName = item.value
                    })
                    return `${url}?page=${params.page}&size=${params.size}&name=${this.filterName}`
                },
                ajaxResponse: function (url, params, response) {
                    return {
                        data: response.data.data,
                        last_page: response.data.last_page,
                    }
                },
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
                date: this.date,
            }).then(response => {
                localStorage.removeItem('selectedEmployees');
                useToast().success(response.data.message , { position: 'bottom-right' });
                this.$router.push('/timesheet-assignment');
            }).catch(error => {
                useToast().error(error.response.data.message , { position: 'bottom-right' });
            });
        }
    }
}
</script>
