<template>
    <Breadcrumbs main="Approval Create" />

    <div class="container-fluid">
        <div class="email-wrap bookmark-wrap">
            <div class="row">
                <div class="col-md-12">
                    <div class="card card-absolute">
                        <div class="card-header bg-primary">
                            <h5>Approval Create</h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-12">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label class="form-label" for="name">Title</label>
                                                <input class="form-control" id="title" type="text" v-model="approval.name" placeholder="Approval Title">
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label class="form-label" for="name">Approval Type</label>
                                                <multiselect
                                                    v-model="approval.approval_module_id"
                                                    placeholder="Select Attendance Type"
                                                    label="name"
                                                    track-by="id"
                                                    :options="approvalModules"
                                                    :multiple="false">
                                                </multiselect>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label>Unit Level :</label>
                                                <select class="form-select digits" v-model="selectedUnitLevel" @change="loadWorkingArea">
                                                    <option value="0">-- Select Unit Level --</option>
                                                    <option value="1">Head Office</option>
                                                    <option value="2">Regional</option>
                                                    <option value="3">Corporate</option>
                                                    <option value="4">Kanwil</option>
                                                    <option value="5">Area</option>
                                                    <option value="6">Cabang</option>
                                                    <option value="7">Outlet</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label>Working Area :</label>
                                            <multiselect
                                                v-model="selectedOptions"
                                                placeholder="Select Unit"
                                                label="name"
                                                track-by="name"
                                                :options="visibleOptions"
                                                :multiple="false"
                                                @select="loadEmployee"
                                            >
                                            </multiselect>
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
                            <button class="btn btn-primary m-r-10" @click="saveData">
                                <i class="fa fa-save"></i>&nbsp;Save
                            </button>
                            <button class="btn btn-secondary" @click="$router.push('/approval')">
                                <i class="fa fa-close"></i>&nbsp;Cancel
                            </button>
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
            loading: false,
            workingArea: {},
            currentPage: 1,
            pageSize: 10,
            filterName: "",
            filterUnitId: "",
            units: [],
            selectedOptions: 0,
            visibleOptions: [],
            table: null,
            selectedEmployeeIds: [],
            approval: {
                name: '',
                approval_module_id: null,
                unit_id: null,
                employee_ids: []
            },
            approvalModules: [],
            selectedUnitLevel: null,
        }
    },
    async mounted() {
        await this.getUnit();
        await this.getApprovalModules()
        // this.initializeEmployeeTable();
    },
    methods: {
        loadWorkingArea() {
            this.visibleOptions = this.units.filter(unit => unit.unit_level ===  parseInt(this.selectedUnitLevel));
        },
        loadEmployee(e) {
            this.approval.unit_id = e.id;
            this.initializeEmployeeTable();
        },
        async getApprovalModules() {
            await this.$axios.get('/api/v1/admin/approval-module')
                .then(response => {
                    this.approvalModules = response.data.data.data
                })
                .catch(error => {
                    console.error(error)
                })
        },
        async getUnit() {
            await this.$axios.get(`api/v1/admin/unit/related-unit`)
                .then(response => {
                    this.units = response.data.data;
                }).catch(error => {
                    console.error(error);
                });
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
                    this.units.filter(item => {
                        if (item.id === this.approval.unit_id) {
                            this.filterUnitId = item.relation_id;
                        }
                    });
                    params.filter.map((item) => {
                        if (item.field === 'name') this.filterName = item.value
                    })
                    return `${url}?page=${params.page}&per_page=${params.size}&name=${this.filterName}&last_unit_relation_id=${this.filterUnitId}`
                },
                ajaxResponse: function (url, params, response) {
                    return {
                        data: response.data.data,
                        last_page: response.data.last_page,
                    }
                },
                paginationSizeSelector: [10, 20, 50, 100],
                progressiveLoad: 'scroll',
                headerFilter: true,
                selectable: true,
                rowFormatter: (row) => {
                },
            });
            this.table.on("rowSelectionChanged", function(data, rows, selected, deselected)  {
                this.selectedEmployees = rows.map(row => row.getData().id);
                if (this.selectedEmployees.length > 5) {
                    rows[rows.length - 1].deselect();
                    useToast().error('Only 5 employees can be selected');
                }
                if (this.selectedEmployees.length === 0) {
                    useToast().error('Minimum 1 employee must be selected');
                    rows[rows.length - 1].select();
                }

                localStorage.setItem('selectedEmployees', JSON.stringify(this.selectedEmployees));
            })
            this.loading = false;
        },
        async saveData() {
            const ls = JSON.parse(localStorage.getItem('selectedEmployees'));
            await this.$axios.post(`/api/v1/admin/approval/create`, {
                approval_module_id: this.approval.approval_module_id.id,
                name: this.approval.name,
                is_active: true,
                unit_id: this.approval.unit_id,
                user_id: ls
            }).then(response => {
                localStorage.removeItem('selectedEmployees');
                useToast().success(response.data.message);
                this.$router.push('/approval');
            }).catch(error => {
                useToast().error(error.response.data.message);
            });
        }
    }
}
</script>
