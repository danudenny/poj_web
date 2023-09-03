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
                                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#assignAdminUnit" @click="onFormClicked">
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
                        <div class="d-flex justify-content-between">
                            <div>
                                <ul class="nav nav-pills nav-primary" id="pills-icontab" role="tablist">
                                    <li class="nav-item"><a class="nav-link active" id="pills-iconhome-tab" data-bs-toggle="pill" href="#pills-iconhome" role="tab" aria-controls="pills-iconhome" aria-selected="true" @click="onFormTypeSelected('general')"><i class="icofont icofont-info"></i>General</a></li>
                                </ul>
                            </div>
                        </div>
                        <div class="tab-content" id="pills-icontabContent">
                            <hr/>
                            <div class="tab-pane fade show active" id="pills-iconhome" role="tabpanel" aria-labelledby="pills-iconhome-tab">
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
            formType: "general",
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
            selectedUnit: null,
            selectedEmployee: null,
            selectedOperatingUnit: null,
            selectedOperatingUnitCorporate: null,
            selectedEmployeeOperatingUnit: null,
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
            operatingUnitDetailPagination: {
                currentPage: 1,
                pageSize: 10
            },
            employeeOperatingUnitPagination: {
                currentPage: 1,
                pageSize: 50,
                name: '',
                onSearch: false
            },
            units: [],
            employees: [],
            operatingUnits: [],
            operatingUnitCorporates: [],
            employeesOperatingUnit: [],
            selectedOperatingUnitDetails: [],
        }
    },
    async mounted() {
        this.generateAdminUnitTable()
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
                        headerFilter:"input",
                    },
                    {
                        title: 'Employee Work Email',
                        field: 'employee.work_email',
                        headerFilter:"input",
                    },
                    {
                        title: 'Unit',
                        field: 'unit.name',
                        headerFilter:"input",
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
                        employeeName: '',
                        workEmail: ''
                    }
                    params.filter.map((item) => {
                        if (item.field === 'employee.name') localFilter.employeeName = item.value
                        if (item.field === 'employee.work_email') localFilter.workEmail = item.value
                        if (item.field === 'unit.name') localFilter.unitName = item.value
                    })
                    return `${url}?page=${params.page}&per_page=${params.size}&size=${params.size}&unit_name=${localFilter.unitName}&employee_name=${localFilter.employeeName}&employee_email=${localFilter.workEmail}`
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
        generateOperatingUnitTable() {
            if (this.selectedOperatingUnit === null || this.selectedOperatingUnitCorporate === null) {
                return
            }

            const ls = localStorage.getItem('my_app_token')
            this.table = new Tabulator(this.$refs.operatingUnitDetailTable, {
                paginationCounter:"rows",
                ajaxURL: `/api/v1/admin/operating-unit/kanwils`,
                ajaxConfig: {
                    headers: {
                        Authorization: `Bearer ${ls}`,
                        "X-Unit-Relation-ID": this.$store.state.activeAdminUnit?.unit_relation_id ?? ''
                    },
                },
                ajaxParams: {
                    page: this.operatingUnitDetailPagination.currentPage,
                    size: this.operatingUnitDetailPagination.pageSize,
                },
                ajaxResponse: (url, params, response) => {
                    this.selectedOperatingUnitDetails = response.data.data

                    return {
                        data: response.data.data,
                        last_page: response.data.last_page,
                    }
                },
                ajaxURLGenerator: (url, config, params) => {
                    let localFilter = {
                        name: ''
                    }

                    return `${url}?page=${params.page}&per_page=${params.size}&name=${localFilter.name}&operating_unit_relation_id=${this.selectedOperatingUnit.relation_id}&corporate_relation_id=${this.selectedOperatingUnitCorporate.corporate_relation_id}`
                },
                layout: 'fitColumns',
                renderHorizontal:"virtual",
                height: '100%',
                groupBy: ['operating_unit_corporate_id'],
                progressiveLoad: 'scroll',
                responsiveLayout: true,
                groupStartOpen:true,
                groupHeader: function(value, count, data, group){
                    return data[0].operating_unit_corporate.corporate.name;
                },
                columns: [
                    {
                        title: 'No',
                        field: '',
                        formatter: 'rownum',
                        hozAlign: 'center',
                        headerHozAlign: 'center',
                        width: 100,
                        frozen: true,
                    },
                    {
                        title: 'Name',
                        field: 'kanwil.name',
                        headerFilter:"input",
                        headerHozAlign: 'center',
                    },
                ],
                paginationSize: this.pageSize,
                paginationSizeSelector: [10, 20, 50, 100],
                headerFilter: true,
                paginationInitialPage:1,
                placeholder: 'No Data Available',
            });
        },
        viewDetailsFormatter(cell, formatterParams, onRendered) {
            return `<button class="button-icon button-danger" data-bs-toggle="modal" data-bs-target="#deleteAdminUnit"><i class="fa fa-trash"></i></button>`;
        },
        getUnitsData() {
            this.$axios.get(`/api/v1/admin/unit/paginated?per_page=${this.unitPagination.pageSize}&page=${this.unitPagination.currentPage}&name=${this.unitPagination.name}&unit_level=4,5,6,7`)
                .then(response => {
                    this.units = response.data.data.data
                    this.unitPagination.onSearch = false
                })
                .catch(error => {
                    console.error(error);
                });
        },
        getOperatingUnitData() {
            this.$axios.get(`/api/v1/admin/unit/paginated?unit_level=2`)
                .then(response => {
                    this.operatingUnits = response.data.data
                })
                .catch(error => {
                    console.error(error);
                });
        },
        getOperatingUnitCorporateData() {
            if (this.selectedOperatingUnit === null) {
                return
            }

            this.$axios.get(`/api/v1/admin/operating-unit?operating_unit_relation_id=${this.selectedOperatingUnit.relation_id}`)
                .then(response => {
                    this.operatingUnitCorporates = response.data.data
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
        getEmployeesOperatingUnitData() {
            this.$axios.get(`/api/v1/admin/employee/paginated?per_page=${this.employeeOperatingUnitPagination.pageSize}&page=${this.employeeOperatingUnitPagination.currentPage}&name=${this.employeeOperatingUnitPagination.name}&is_operating_unit_user=1`)
                .then(response => {
                    this.employeesOperatingUnit = response.data.data.data
                    this.employeeOperatingUnitPagination.onSearch = false
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
            this.getEmployeesData()
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
            let payload = {
                employee_id: null,
                unit_relation_ids: []
            }

            if(this.formType === 'general') {
                payload.employee_id = this.selectedEmployee.id
                payload.unit_relation_ids.push(this.selectedUnit.relation_id)
            } else {
                payload.employee_id = this.selectedEmployeeOperatingUnit.id
                this.selectedOperatingUnitDetails.forEach(val => {
                    payload.unit_relation_ids.push(val.unit_relation_id)
                })
            }

            this.$axios.post(`/api/v1/admin/admin_unit/assign-multiple`, payload)
                .then(response => {
                    useToast().success("Success to add assign", { position: 'bottom-right' });
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
        onOperatingUnitSelected(val) {
            this.selectedOperatingUnitCorporate = null

            this.getOperatingUnitCorporateData()
            this.getEmployeesOperatingUnitData()
        },
        onOperatingUnitCorporateSelected(val) {
            this.generateOperatingUnitTable()
        },
        onEmployeeOperatingUnitSearchName(val) {
            this.employeeOperatingUnitPagination.name = val

            if (!this.employeeOperatingUnitPagination.onSearch) {
                this.employeeOperatingUnitPagination.onSearch = true
                setTimeout(() => {
                    this.getEmployeesOperatingUnitData()
                }, 1000)
            }
        },
        onFormClicked() {
            if(this.formType === 'general') {
                this.getUnitsData()
            } else {
                this.getOperatingUnitData()
            }
        },
        onFormTypeSelected(formType) {
            this.formType = formType

            if (formType === 'general') {
                this.getUnitsData()

                this.selectedOperatingUnit = null;
                this.selectedOperatingUnitCorporate = null;
                this.selectedEmployee = null;
                this.selectedOperatingUnitDetails = []
            } else {
                this.getOperatingUnitData()

                this.selectedUnit = null;
                this.selectedEmployee = null;
            }
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
