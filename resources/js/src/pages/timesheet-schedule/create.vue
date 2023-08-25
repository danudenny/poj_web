<template>
    <Breadcrumbs main="Timesheet Assignment" />

    <div class="container-fluid">
        <div class="email-wrap bookmark-wrap">
            <div class="row">
                <div class="col-md-12">
                    <div class="card card-absolute">
                        <div class="card-header bg-primary">
                            <h5>Timesheet Assignment</h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-12">
                                    <div class="row">
                                        <div class="col-md-12 mb-3">
                                            <label>Date :</label>
                                            <Datepicker
                                                :model-value="date"
                                                :enable-time-picker="false"
                                                :min-date="new Date()"
                                                auto-apply
                                                @update:model-value="handleDate"
                                            >
                                            </Datepicker>
                                        </div>
                                        <div class="col-md-4 mb-3">
                                            <label>Target Unit Level:</label>
                                            <multiselect
                                                v-model="selectedLevel"
                                                placeholder="Select Target Unit Level"
                                                label="name"
                                                track-by="name"
                                                :options="unitLevels"
                                                :multiple="false"
                                                @select="onSelectUnitLevel"
                                            >
                                            </multiselect>
                                        </div>
                                        <div class="col-md-4 mb-3">
                                            <label>Target Unit :</label>
                                            <multiselect
                                                v-model="selectedOptions"
                                                placeholder="Select Target Unit"
                                                label="name"
                                                track-by="name"
                                                :options="units"
                                                :multiple="false"
                                                @select="selectedUnit"
                                            >
                                            </multiselect>
                                        </div>
                                        <div class="col-md-4 mb-3">
                                            <label>Timesheet :</label>
                                            <select v-model="timesheet_id" class="form-control" >
                                                <option value="null">Select Timesheet</option>
                                                <option :value="item.id" v-for="item in timesheets">{{item.name}}
                                                    ( {{item.start_time}} - {{item.end_time || 'Non Shift'}} )
                                                </option>
                                            </select>
                                        </div>
                                        <div class="col-md-4 mb-3">
                                            <label>Employee Unit Level:</label>
                                            <multiselect
                                                v-model="selectedEmployeeLevel"
                                                placeholder="Select Employee Unit Level"
                                                label="name"
                                                track-by="name"
                                                :options="unitLevels"
                                                :multiple="false"
                                                @select="onSelectUnitLevelEmployee"
                                            >
                                            </multiselect>
                                        </div>
                                        <div class="col-md-4 mb-3">
                                            <label>Employee Unit :</label>
                                            <multiselect
                                                v-model="selectedEmployeeOptions"
                                                placeholder="Select Employee Unit"
                                                label="name"
                                                track-by="name"
                                                :options="unitsEmployee"
                                                :multiple="false"
                                                @select="selectedUnitEmployee"
                                            >
                                            </multiselect>
                                        </div>
                                        <div class="col-md-12 mb-3">
                                            <label>Assign To (Employees) :</label>
                                            <div ref="employeeTable"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="card-footer text-start">
                            <button class="btn btn-primary m-r-10" @click="saveSchedule">Save</button>
                            <button class="btn btn-secondary" @click="$router.push('/timesheet-assignment')">Back</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
<script>
import {TabulatorFull as Tabulator, PersistenceModule as Persistence} from "tabulator-tables";
import {useToast} from "vue-toastification";
import Datepicker from '@vuepic/vue-datepicker';

export default {
    components: {
        Datepicker
    },
    data() {
        return {
            employees: [],
            selectedEmployees: [],
            timesheets: [],
            day: this.$route.query.date,
            totalDays: [],
            periods: [],
            loading: false,
            period_id: 0,
            date: this.$route.query.date,
            timesheet_id: 0,
            workingArea: {},
            currentPage: 1,
            pageSize: 10,
            filterName: "",
            filterUnitId: "",
            units: [],
            unitsEmployee: [],
            selectedOptions: [],
            selectedEmployeeOptions: [],
            visibleOptions: [],
            table: null,
            lastDayOfCurrentMonth: null,
            selectedDate: 0,
            selectedEmployeeIds: [],
            selectedLevel: null,
            selectedEmployeeLevel: null,
            unitLevels: [
                {
                    name: 'Head Office',
                    value: 1
                },
                {
                    name: 'Operating Unit',
                    value: 2
                },
                {
                    name: 'Corporate',
                    value: 3
                },
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
            ]
        }
    },
    async mounted() {
        await this.getPeriods();
        await this.getTotalDays();
        this.getCurrentMonthEndDate();
    },
    methods: {
        onSelectUnitLevel(e) {
            this.selectedOptions = [];
            this.getUnit(e.value);
        },
        onSelectUnitLevelEmployee(e) {
            this.selectedEmployeeOptions = [];
            this.getEmployeeUnit(e.value);
        },
        handleDate(e) {
            const date = new Date(e);
            this.date = date;
            this.selectedDate = date.getDate();
        },
        getCurrentMonthEndDate() {
            const currentDate = new Date();
            const year = currentDate.getFullYear();
            const month = currentDate.getMonth();
            const firstDayOfNextMonth = new Date(year, month + 1, 1);
            return new Date(firstDayOfNextMonth.getTime() - 1);
        },
         selectedUnit() {
            this.getTimesheet(this.selectedOptions.id)
        },
        selectedUnitEmployee() {
            this.initializeEmployeeTable()
            this.table.setFilter('unit_id', "=", this.selectedOptions.relation_id);
        },
        async getUnit(value) {
            await this.$axios.get(`api/v1/admin/unit/related-unit?unit_level=${value}`)
                .then(response => {
                    this.units = response.data.data;
                }).catch(error => {
                    console.error(error);
                });
        },
        async getEmployeeUnit(value) {
            await this.$axios.get(`api/v1/admin/unit/related-unit?unit_level=${value}`)
                .then(response => {
                    this.unitsEmployee = response.data.data;
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
                    }).catch(error => {
                        console.error(error);
                    });
            } catch (error) {
                console.log(error);
            }
        },
        initializeEmployeeTable() {
            const ls = localStorage.getItem('my_app_token')
            this.table = new Tabulator(this.$refs.employeeTable, {
                ajaxURL: '/api/v1/admin/employee/paginated',
                layout: 'fitColumns',
                columns: [
                    {
                        formatter: "rowSelection",
                        hozAlign: "center",
                        width: 100,
                        headerSort: false,
                        titleFormatterParams: {
                            rowRange: "active"
                        },
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
                                wd.corporate,
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
                        "X-Unit-Relation-ID": this.$store.state.activeAdminUnit?.unit_relation_id ?? ''
                    },
                },
                ajaxParams: {
                    page: this.currentPage,
                    size: this.pageSize,
                },
                ajaxURLGenerator: (url, config, params) => {
                    const filters = {
                        unit_level: this.selectedLevel?.value ?? '',
                    }
                    params.filter.map((item) => {
                        if (item.field === 'name') this.filterName = item.value
                        if (item.field === 'unit_id') this.filterUnitId = item.value
                        if (item.field === 'unit_level') filters.unit_level = item.value
                    })
                    return `${url}?page=${params.page}&per_page=${params.size}&name=${this.filterName}&unit_id=${this.filterUnitId}`
                },
                ajaxResponse: function (url, params, response) {
                    return {
                        data: response.data.data,
                        last_page: response.data.last_page,
                    }
                },
                paginationSizeSelector: [10, 20, 50, 100],
                headerFilter: true,
                selectable: true,
                rowFormatter: (row) => {

                },
           });
            this.table.on("rowSelectionChanged", function(data, rows, selected, deselected)  {
                this.selectedEmployees = rows.map(row => row.getData().id);
                localStorage.setItem('selectedEmployees', JSON.stringify(this.selectedEmployees));
            })
            this.loading = false;
        },
        async saveSchedule() {
            const ls = JSON.parse(localStorage.getItem('selectedEmployees'));
            const queryPeriod = this.date
            const month = queryPeriod.getMonth() + 1;
            const year = queryPeriod.getFullYear();
            const monthNames = ["January", "February", "March", "April", "May", "June", "July", "August", "September", "October", "November", "December"];

            this.periods.forEach((item, index) => {
                const monthNumber = monthNames.indexOf(item.month) + 1;
                if (monthNumber === parseInt(month) && parseInt(item.year) === parseInt(year)) {
                    this.period_id = item.id;
                }
            });

            const timesheet = {}
            this.timesheets.forEach((item, index) => {
                if (item.id === this.timesheet_id) {
                    timesheet.id = item.id
                    timesheet.shift_type = item.shift_type
                }
            })

            const period = this.periods.find(item => item.id === this.period_id);
            await this.$axios.post(`/api/v1/admin/employee-timesheet/assign-schedule`, {
                employee_ids: ls,
                period_id: this.period_id,
                timesheet_id: timesheet.shift_type === 'shift' ? this.timesheet_id : timesheet.id,
                date: queryPeriod.getDate(),
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
