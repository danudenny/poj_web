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
                          :model-value="employeeTimesheet.real_date"
                          :enable-time-picker="false"
                          :min-date="new Date()"
                          auto-apply
                          :disabled="true"
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
                          @search-change="onUnitSearchName"
                      >
                      </multiselect>
                    </div>
                    <div class="col-md-4 mb-3">
                      <label>Timesheet :</label>
                      <select v-model="timesheet_id" class="form-control" >
                        <option value="null">Select Timesheet</option>
                        <option :value="item.id" v-for="item in timesheets">{{item.name}}
                          ( {{item.start_time}} - {{item.end_time}} | {{ item.shift_type }} )
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
                          :disabled="true"
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
                          :disabled="true"
                      >
                      </multiselect>
                    </div>
                    <div class="col-md-4 mb-3">
                      <label>Employee :</label>
                      <multiselect
                          v-model="selectedEmployee"
                          placeholder="Selected Employee"
                          label="name"
                          track-by="name"
                          :options="optionSelectedEmployees"
                          :multiple="false"
                          :disabled="true"
                      >
                      </multiselect>
                    </div>
                  </div>
                </div>
              </div>
            </div>
            <div class="card-footer text-start">
              <button class="btn btn-secondary" @click="$router.push('/timesheet-assignment')"><i data-action="view" class="fa fa-arrow-left"></i> Back</button>&nbsp;
              <button class="btn btn-primary m-r-10" @click="saveSchedule"> <i data-action="view" class="fa fa-save"></i> Save</button>
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
      employeeTimesheet: {
        real_date: null,
        unit: {
          id: null,
          name: null,
          unit_level: null
        }
      },
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
      selectedEmployee: null,
      optionSelectedEmployees: [],
      targetUnitPagination: {
        limit: 10,
        isOnSearch: true,
        name: ''
      },
      unitLevels: [
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
    this.getDetailEmployeeTimesheet()
  },
  methods: {
    getDetailEmployeeTimesheet() {
      this.$axios.get(`api/v1/admin/employee-timesheet/view-employee-schedule/${this.$route.params.id}`)
          .then(response => {
            this.employeeTimesheet = response.data.data;

            this.unitLevels.forEach((val) => {
              if (this.employeeTimesheet.unit.unit_level === val.value) {
                this.selectedLevel = val
              }
            })

            this.selectedOptions = this.employeeTimesheet.unit
            this.targetUnitPagination.name = this.selectedOptions.name
            this.getUnit();

            this.getTimesheet(this.selectedOptions.id)
            this.timesheet_id = this.employeeTimesheet.timesheet_id

            this.unitLevels.forEach((val) => {
              if (this.employeeTimesheet.employee.last_unit.unit_level === val.value) {
                this.selectedEmployeeLevel = val
              }
            })

            this.selectedEmployeeOptions = this.employeeTimesheet.employee.last_unit
            this.selectedEmployee = this.employeeTimesheet.employee
          }).catch(error => {
        console.error(error);
      });
    },
    onSelectUnitLevel(e) {
      this.selectedOptions = [];
      this.timesheet_id = null;
      this.timesheets = []
      this.getUnit();
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
      this.timesheet_id = null;
      this.timesheets = []
      this.getTimesheet(this.selectedOptions.id)
    },
    selectedUnitEmployee() {
      this.initializeEmployeeTable()
      this.table.setFilter('unit_id', "=", this.selectedOptions.relation_id);
    },
    async getUnit(value) {
      if (this.selectedLevel == null) {
        return
      }

      await this.$axios.get(`api/v1/admin/unit/paginated?unit_level=${this.selectedLevel.value}&per_page=${this.targetUnitPagination.limit}&name=${this.targetUnitPagination.name}`)
          .then(response => {
            this.units = response.data.data.data;
            this.targetUnitPagination.isOnSearch = false
          }).catch(error => {
            console.error(error);
          });
    },
    onUnitSearchName(val) {
      this.targetUnitPagination.name = val

      if (!this.targetUnitPagination.isOnSearch) {
        this.targetUnitPagination.isOnSearch = true
        setTimeout(() => {
          this.getUnit()
        }, 1000)
      }
    },
    async getEmployeeUnit(value) {
      await this.$axios.get(`api/v1/admin/unit/paginated?unit_level=${value}&per_page=20`)
          .then(response => {
            this.unitsEmployee = response.data.data.data;
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
              this.timesheets = this.processDataTimesheet(response.data.data.data);
            }).catch(error => {
              console.error(error);
            });
      } catch (error) {
        console.log(error);
      }
    },
    processDataTimesheet(datas) {
      if(this.employeeTimesheet.real_date === null) {
        return
      }

      let daysName = ["Sunday", "Monday", "Tuesday", "Wednesday", "Thursday", "Friday", "Saturday"]
      let currDate = new Date(this.employeeTimesheet.real_date)
      let currDay = daysName[currDate.getDay()]

      let respondedData = [];

      datas.forEach((value) => {
        if (value.start_time === null && value.end_time === null) {
          value.timesheet_days.forEach((day) => {
            if (day.day === currDay) {
              respondedData.push({
                id: value.id,
                name: value.name,
                start_time: day.start_time,
                end_time: day.end_time,
                shift_type: value.shift_type === 'non_shift' ? 'Non Shift' : 'Shift',
              })
            }
          })
        } else {
          respondedData.push({
            id: value.id,
            name: value.name,
            start_time: value.start_time,
            end_time: value.end_time,
            shift_type: value.shift_type === 'non_shift' ? 'Non Shift' : 'Shift',
          })
        }
      })

      return respondedData
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
      let payload = {
        timesheet_date: this.employeeTimesheet.real_date,
        timesheet: {
          id: this.timesheet_id,
          start_time: null,
          end_time: null
        },
        unit_relation_id: this.selectedOptions?.relation_id ?? null
      }

      this.timesheets.forEach((val) => {
        if (val.id === payload.timesheet.id) {
          payload.timesheet.start_time = val.start_time + ":00"
          payload.timesheet.end_time = val.end_time + ":00"
        }
      })

      this.$swal({
        icon: 'warning',
        title:"Are Sure You Want to Update the Data?",
        showCancelButton: true,
        confirmButtonText: 'Yes, update it!',
        confirmButtonColor: '#126850',
        cancelButtonText: 'Cancel',
        cancelButtonColor: '#efefef',
      }).then((result)=>{
        if(result.value){
          this.$axios.post(`api/v1/admin/employee-timesheet/update-employee-schedule/${this.$route.params.id}`, payload)
              .then(() => {
                useToast().success("Data successfully updated!");
                this.getDetailEmployeeTimesheet()
              })
              .catch(error => {
                useToast().error(error.response.data.message);
              });
        }
      });
    }
  }
}
</script>
