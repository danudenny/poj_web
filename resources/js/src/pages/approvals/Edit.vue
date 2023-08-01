<template>
    <Breadcrumbs main="Approval Edit" />

    <div class="container-fluid">
        <div class="email-wrap bookmark-wrap">
            <div class="row">
                <div class="col-md-12">
                    <div class="card card-absolute">
                        <div class="card-header bg-primary">
                            <h5>Approval Edit</h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-12">
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label>Unit :</label>
                                            <multiselect
                                                v-model="selectedOptions"
                                                placeholder="Loading ..."
                                                disabled
                                                label="name"
                                                track-by="name"
                                                :options="units"
                                                :multiple="false"                                            >
                                            </multiselect>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label>Approval Type :</label>
                                            <multiselect
                                                v-model="selectedApprovalModule"
                                                placeholder="Loading ..."
                                                disabled
                                                label="name"
                                                track-by="name"
                                                :options="approvalModules"
                                                :multiple="false"                                            >
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
import {TabulatorFull as Tabulator} from "tabulator-tables";
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
            selectedOptions: [],
            selectedApprovalModule: [],
            visibleOptions: [],
            table: null,
            selectedEmployeeIds: [],
            approvalModules: []
        }
    },
    async mounted() {
        await this.getUnit();
        this.initializeEmployeeTable();
        await this.getApprovals()
        await this.getApprovalModules();
    },
    methods: {
        async getApprovalModules() {
            await this.$axios.get('/api/v1/admin/approval-module')
                .then(response => {
                    this.approvalModules = response.data.data.data
                    //get approval module from this.approvals
                    this.approvalModules.filter(item => {
                        if (item.id === this.approvals.approval_module_id) {
                            this.selectedApprovalModule = item;
                        }
                    });


                })
                .catch(error => {
                    console.error(error)
                })
        },
        async getUnit() {
            await this.$axios.get(`api/v1/admin/unit/related-unit`)
                .then(response => {
                    this.units = response.data.data;
                    const unitId = parseInt(this.$route.query.unit_id);
                    this.units.filter(item => {
                        if (item.id === unitId) {
                            this.selectedOptions = item;
                        }
                    });
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
                    const unitId = parseInt(this.$route.query.unit_id);
                    this.units.filter(item => {
                        if (item.id === unitId) {
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
                headerFilter: true,
                selectable: true,
                rowFormatter: (row) => {
                    let employees = row.getData();
                    const unitId = parseInt(this.$route.query.unit_id);
                    this.approvals.users.filter(item => {
                        if (item.last_unit.id === unitId) {
                            if (item.id === employees.id) {
                                row.select();
                            }
                        }
                    });
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
                    rows[0].select();
                }
                localStorage.setItem('selectedEmployees', JSON.stringify(this.selectedEmployees));
            })
            this.loading = false;
        },
        async getApprovals() {
            const id = parseInt(this.$route.params.id);
            await this.$axios.get(`/api/v1/admin/approval/view/${id}`)
                .then(response => {
                    this.approvals = response.data.data;
                })
                .catch(error => {
                    console.error(error);
                });
        },
        async saveData() {
            const ls = JSON.parse(localStorage.getItem('selectedEmployees'));
            await this.$axios.put(`/api/v1/admin/approval/update/${this.$route.params.id}`, {
                approval_module_id: this.approvals.approval_module_id,
                name: this.approvals.name,
                is_active: true,
                unit_id: parseInt(this.$route.query.unit_id),
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
