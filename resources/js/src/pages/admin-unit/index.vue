<template>
    <div class="container-fluid">
        <Breadcrumbs main=""/>

        <div class="container-fluid">
            <div class="email-wrap bookmark-wrap">
                <div class="row">
                    <div class="col-md-12">
                        <div class="card card-absolute">
                            <div class="card-header bg-primary">
                                <h5>List Admin Unit</h5>
                            </div>
                            <div class="card-body">
                                <div class="d-flex justify-content-end mb-2">
                                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#assignAdminUnit">
                                        <i class="fa fa-plus" /> &nbsp;Assign Admin
                                    </button>
                                </div>
                                <div v-if="loading" class="text-center">
                                    <img src="../../assets/loader.gif" alt="loading" width="100">
                                </div>
                                <div ref="adminUnitTable"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="modal fade" id="deleteAdminUnit" ref="deleteAdminUnit" tabindex="-1" role="dialog" aria-labelledby="exampleModalCenter" aria-hidden="true">
        <VerticalModal title="Delete Admin Unit?" @save="onAdminUnitUpdate()">
            <div class="row">
                <div class="col-md-12">
                    <p>Are you sure want to delete {{ this.selectedAdminUnit.employee.name }} from {{ this.selectedAdminUnit.unit.name }}?</p>
                </div>
            </div>
        </VerticalModal>
    </div>
    <div class="modal fade" id="assignAdminUnit" ref="assignAdminUnit" tabindex="-1" role="dialog" aria-labelledby="exampleModalCenter" aria-hidden="true">
        <VerticalModal title="Assign Admin Unit" @save="onCreateAdminUnit()">
            <div class="row">
                <div class="col-md-12">
                    <div class="row">
                        <div class="col-md-12">
                            <label for="name">Select Unit</label>
                            <multiselect
                                v-model="selectedUnit"
                                placeholder="Select Unit"
                                label="name"
                                track-by="id"
                                :options="units"
                                :multiple="false"
                                :required="true"
                                @select="onUnitSelected"
                                @search-change="onUnitSearchName"
                            >
                            </multiselect>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-12">
                            <label for="name">Select Employee</label>
                            <multiselect
                                v-model="selectedEmployee"
                                placeholder="Select Employee"
                                label="name"
                                track-by="id"
                                :options="employees"
                                :multiple="false"
                                :required="true"
                                @select="onEmployeeSelected"
                                @search-change="onEmployeeSearchName"
                            >
                            </multiselect>
                        </div>
                    </div>
                </div>
            </div>
        </VerticalModal>
    </div>
</template>

<script>
import {TabulatorFull as Tabulator} from 'tabulator-tables';
import VerticalModal from "@components/modal/verticalModal.vue";
import {useToast} from "vue-toastification";

export default {
    components: {
        VerticalModal
    },
    data() {
        return {
            pageSize: 10,
            currentPage: 1,
            loading: false,
            adminUnit: {
                unit_relation_id: null,
                employee_id: null
            },
            selectedAdminUnit: {
                id: null,
                unit: {
                    name: null
                },
                employee: {
                    name: null
                }
            },
            selectedUnit: {
                id: null,
                relation_id: null
            },
            selectedEmployee: {
                id: null,
            },
            unitPagination: {
                currentPage: 1,
                pageSize: 50,
                name: ''
            },
            employeePagination: {
                currentPage: 1,
                pageSize: 50,
                name: ''
            },
            units: [],
            employees: []
        }
    },
    async mounted() {
        this.generateAdminUnitTable()
        this.getUnitsData()
        this.getEmployeesData()
    },
    methods: {
        generateAdminUnitTable() {
            const ls = localStorage.getItem('my_app_token')
            const table = new Tabulator(this.$refs.adminUnitTable, {
                ajaxURL: '/api/v1/admin/admin_unit',
                layout: 'fitColumns',
                columns: [
                    {
                        title: 'No',
                        field: '',
                        formatter: 'rownum',
                        width: 100
                    },
                    {
                        title: 'Employee Name',
                        field: 'employee.name',
                    },
                    {
                        title: 'Employee Work Email',
                        field: 'employee.work_email',
                    },
                    {
                        title: 'Unit',
                        field: 'unit.name',
                    },
                    {
                        title: '',
                        formatter: this.viewDetailsFormatter,
                        width: 70,
                        hozAlign: 'center',
                        sortable: false,
                        cellClick: (e, cell) => {
                            this.selectedAdminUnit = cell.getRow().getData()
                        }
                    },
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
                    let localFilter = {
                        unitName: '',
                        lastStatus: '',
                        requestorName: ''
                    }
                    params.filter.map((item) => {
                        if (item.field === 'unit.name') localFilter.unitName = item.value
                        if (item.field === 'last_status') localFilter.lastStatus = item.value
                        if (item.field === 'requestor_employee.name') localFilter.requestorName = item.value
                    })
                    return `${url}?page=${params.page}&per_page=${params.size}&size=${params.size}&unit_name=${localFilter.unitName}&last_status=${localFilter.lastStatus}&requestor_name=${localFilter.requestorName}`
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
        },
        viewDetailsFormatter(cell, formatterParams, onRendered) {
            return `<button class="button-icon button-danger" data-bs-toggle="modal" data-bs-target="#deleteAdminUnit"><i class="fa fa-trash"></i></button>`;
        },
        getUnitsData() {
            this.$axios.get(`/api/v1/admin/unit/paginated?per_page=${this.unitPagination.pageSize}&page=${this.unitPagination.currentPage}&name=${this.unitPagination.name}`)
                .then(response => {
                    this.units = response.data.data.data
                    this.unitPagination.onSearch = false
                })
                .catch(error => {
                    console.error(error);
                });
        },
        getEmployeesData() {
            this.$axios.get(`/api/v1/admin/employee/paginated?per_page=${this.employeePagination.pageSize}&page=${this.employeePagination.currentPage}&name=${this.employeePagination.name}`)
                .then(response => {
                    this.employees = response.data.data.data
                    this.employeePagination.onSearch = false
                })
                .catch(error => {
                    console.error(error);
                });
        },
        onAdminUnitUpdate() {
            if (this.selectedAdminUnit.id === null) {
                return
            }

            this.$axios.delete(`/api/v1/admin/admin_unit/remove/${this.selectedAdminUnit.id}`)
                .then(response => {
                    useToast().success("Success to delete " + this.selectedAdminUnit.employee.name + " from " + this.selectedAdminUnit.unit.name, { position: 'bottom-right' });
                    this.generateAdminUnitTable()
                })
                .catch(error => {
                    if(error.response.data.message instanceof Object) {
                        for (const key in error.response.data.message) {
                            useToast().error(error.response.data.message[key][0], { position: 'bottom-right' });
                        }
                    } else {
                        useToast().error(error.response.data.message , { position: 'bottom-right' });
                    }
                    this.isEdit = false
                });
        },
        onUnitSearchName(val) {
            this.unitPagination.name = val

            if (!this.unitPagination.onSearch) {
                this.unitPagination.onSearch = true
                setTimeout(() => {
                    this.getUnitsData()
                }, 1000)
            }
        },
        onUnitSelected(val) {
            this.adminUnit.unit_relation_id = this.selectedUnit.relation_id
        },
        onEmployeeSearchName(val) {
            this.employeePagination.name = val

            if (!this.employeePagination.onSearch) {
                this.employeePagination.onSearch = true
                setTimeout(() => {
                    this.getEmployeesData()
                }, 1000)
            }
        },
        onEmployeeSelected(val) {
            this.adminUnit.employee_id = this.selectedEmployee.id
        },
        onCreateAdminUnit() {
            this.$axios.post(`/api/v1/admin/admin_unit/create`, this.adminUnit)
                .then(response => {
                    useToast().success("Success to add " + this.selectedEmployee.name + " to " + this.selectedUnit.name, { position: 'bottom-right' });
                    this.generateAdminUnitTable()
                })
                .catch(error => {
                    if(error.response.data.message instanceof Object) {
                        for (const key in error.response.data.message) {
                            useToast().error(error.response.data.message[key][0], { position: 'bottom-right' });
                        }
                    } else {
                        useToast().error(error.response.data.message , { position: 'bottom-right' });
                    }
                    this.isEdit = false
                });
        }
    }
}
</script>

<style>
.tabulator .tabulator-header .tabulator-col {
    background-color: #0A5640 !important;
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
</style>
