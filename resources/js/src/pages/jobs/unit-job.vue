<template>
    <div class="container-fluid">
        <Breadcrumbs main="Job Parent Assignment"/>

        <div class="container-fluid">
            <div class="email-wrap bookmark-wrap">
                <div class="row">
                    <div class="col-md-12">
                        <div class="card card-absolute">
                            <div class="card-header bg-primary">
                                <h5>Unit Job List</h5>
                            </div>
                            <div class="card-body">
                                <div v-if="loading" class="text-center">
                                    <img src="../../assets/loader.gif" alt="loading" width="100">
                                </div>
	                            <button class="btn btn-success" type="button" data-bs-toggle="modal" data-bs-target="#jobUnitAssignment">
		                            <i class="fa fa-recycle" /> &nbsp; Assign Job to Unit
	                            </button>
                                <br/>
                                <br/>
                                <div ref="jobTable"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="modal fade" id="jobParentAssignment" ref="jobParentAssignment" tabindex="-1" role="dialog" aria-labelledby="exampleModalCenter" aria-hidden="true">
        <VerticalModal title="Assign Parent Job" @save="onJobParentAssigned()">
            <div class="row">
                <div class="col-md-12">
                    <div class="mt-2">
                        <label for="name">Current Unit Job</label>
                        <input type="text" class="form-control" v-model="selectedAssignedJob.unit.formatted_name" disabled required>
                    </div>
                    <div class="mt-2">
                        <label for="name">Curret Job Name</label>
                        <input type="text" class="form-control" v-model="selectedAssignedJob.job.name" disabled required>
                    </div>

                    <p align="center" class="mt-4" style="font-size: 20px;color: #0A5640">
                        <span><i data-action="assign" class="fa fa-arrow-down"></i></span>
                    </p>

                    <label for="name">Select Unit Parent Job</label>
                    <multiselect
                        v-model="selectedParentJobUnit"
                        placeholder="Select Unit Parent Job"
                        label="formatted_name"
                        track-by="id"
                        :options="units"
                        :multiple="false"
                        :required="true"
                        @select="onUnitSelected"
                        @search-change="onUnitSearchName"
                    >
                    </multiselect>

                    <br/>

                    <div v-if="this.selectedParentJobUnit.relation_id != null">
                        <label for="name">Select Parent Job</label>
                        <multiselect
                            v-model="selectedParentJobAssignment"
                            placeholder="Select Parent Job"
                            label="job_name"
                            track-by="id"
                            :options="parentJobs"
                            :multiple="false"
                            :required="true"
                            @search-change="onParentJobSearchName"
                        >
                        </multiselect>
                    </div>
                </div>
            </div>
        </VerticalModal>
    </div>

    <div class="modal fade" id="jobUnitAssignment" ref="jobUnitAssignment" tabindex="-1" role="dialog" aria-labelledby="exampleModalCenter" aria-hidden="true">
        <VerticalModal title="Assign Job to Unit" @save="onAssignJobUnit()">
            <div class="row">
                <div class="col-md-12">
                    <label for="name">Select Unit</label>
                    <multiselect
                        v-model="selectedJobUnit"
                        placeholder="Select Unit"
                        label="formatted_name"
                        track-by="relation_id"
                        :options="jobUnits"
                        :multiple="false"
                        :required="true"
                        @select="onJobUnitSelected"
                        @search-change="onJobUnitSearchName"
                    >
                    </multiselect>

                    <br/>

                    <label for="name">Job</label>
                    <multiselect
                        v-model="selectedJob"
                        placeholder="Select Job"
                        label="name"
                        track-by="id"
                        :options="jobs"
                        :multiple="false"
                        :required="true"
                        @search-change="onJobSearchName"
                    >
                    </multiselect>
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
            loading: false,
            syncLoading: false,
            jobPagination: {
                currentPage: 1,
                pageSize: 10,
            },
            unitPagination: {
                currentPage: 1,
                pageSize: 20,
                onSearch: true,
                name: ''
            },
            parentJobPagination: {
                currentPage: 1,
                pageSize: 20,
                onSearch: true,
                name: ''
            },
            selectedAssignedJob: {
                job: {
                    name: null
                },
                unit: {
                    relation_id: null,
                    name: null
                }
            },
            selectedParentJobAssignment: null,
            selectedParentJobUnit: {
                relation_id: null,
            },
            units: [],
            parentJobs: [],
            jobUnitPagination: {
                currentPage: 1,
                pageSize: 20,
                onSearch: true,
                name: ''
            },
            selectedJobUnit: null,
            jobUnits: [],
            jobMasterPagination: {
                currentPage: 1,
                pageSize: 20,
                onSearch: true,
                name: ''
            },
            selectedJob: null,
            jobs: []
        }
    },
    async mounted() {
        this.initializeUnitJob();
        this.getJobUnitsData()
        this.getMasterJobData()
    },
    methods: {
        getUnitsData() {
            if (this.selectedAssignedJob.unit.relation_id === null) {
                return
            }

            this.$axios.get(`/api/v1/admin/unit/paginated?per_page=${this.unitPagination.pageSize}&page=${this.unitPagination.currentPage}&name=${this.unitPagination.name}`)
                .then(response => {
                    this.units = response.data.data.data
                    this.unitPagination.onSearch = false
                })
                .catch(error => {
                    console.error(error);
                });
        },
        async getParentJobData() {
            if (this.selectedParentJobUnit.relation_id === null) {
                return
            }

            this.$axios.get(`/api/v1/admin/unit-job?per_page=${this.parentJobPagination.pageSize}&page=${this.parentJobPagination.currentPage}&job_name=${this.parentJobPagination.name}&unit_relation_id=${this.selectedParentJobUnit.relation_id}`)
                .then(response => {
                    this.parentJobs = response.data.data.data
                    this.parentJobPagination.onSearch = false
                })
                .catch(error => {
                    console.error(error);
                });
        },
        async getJobUnitsData() {
            this.$axios.get(`/api/v1/admin/unit/paginated?per_page=${this.jobUnitPagination.pageSize}&page=${this.jobUnitPagination.currentPage}&name=${this.jobUnitPagination.name}`)
                .then(response => {
                    this.jobUnits = response.data.data.data
                    this.jobUnitPagination.onSearch = false
                })
                .catch(error => {
                    console.error(error);
                });
        },
        async getMasterJobData() {
            this.$axios.get(`/api/v1/admin/job/list/master-job?per_page=${this.jobMasterPagination.pageSize}&page=${this.jobMasterPagination.currentPage}&name=${this.jobMasterPagination.name}`)
                .then(response => {
                    this.jobs = response.data.data.data
                    this.jobMasterPagination.onSearch = false
                })
                .catch(error => {
                    console.error(error);
                });
        },
        initializeUnitJob() {
            const ls = localStorage.getItem('my_app_token');
            this.table = new Tabulator(this.$refs.jobTable, {
                paginationCounter:"rows",
                ajaxURL: '/api/v1/admin/unit-job',
                filterMode:"remote",
                ajaxConfig: {
                    headers: {
                        Authorization: `Bearer ${ls}`,
                        "X-Unit-Relation-ID": this.$store.state.activeAdminUnit?.unit_relation_id ?? ''
                    },
                },
                ajaxParams: {
                    page: this.jobPagination.currentPage,
                    size: this.jobPagination.pageSize,
                },
                ajaxResponse: function (url, params, response) {
                    return {
                        data: response.data.data,
                        last_page: response.data.last_page,
                    }
                },
                ajaxURLGenerator: (url, config, params) => {
                    let localFilter = {
                        jobName: '',
                        unitName: '',
                        parentJobName: '',
                        parentUnitName: ''
                    }

                    params.filter.map((item) => {
                        if (item.field === 'unit.name') localFilter.unitName = item.value
                        if (item.field === 'job.name') localFilter.jobName = item.value
                        if (item.field === 'parent.job.name') localFilter.parentJobName = item.value
                        if (item.field === 'parent.unit.name') localFilter.parentUnitName = item.value
                    })

                    return `${url}?page=${params.page}&per_page=${params.size}&unit_name=${localFilter.unitName}&job_name=${localFilter.jobName}&parent_unit_name=${localFilter.parentUnitName}&parent_job_name=${localFilter.parentJobName}`
                },
                layout: 'fitColumns',
                columns: [
                    {
                        title: 'No',
                        field: '',
                        formatter: 'rownum',
                        width: 70,
                        hozAlign: 'center',
                        headerHozAlign: 'center',
                    },
                    {
                        title:"Current",
                        headerHozAlign:"center",
                        columns:[
                            {
                                title:"Job Name",
                                field:"job.name",
                                headerFilter:"input",
                                headerHozAlign:"center"
                            },
                            {
                                title:"Unit Name",
                                field:"unit.formatted_name",
                                headerFilter:"input",
                                headerHozAlign:"center"
                            },
                        ],
                    },
                    {
                        title:"Parent",
                        headerHozAlign:"center",
                        columns:[
                            {
                                title:"Job Name",
                                field:"parent.job.name",
                                headerFilter:"input",
                                headerHozAlign:"center"
                            },
                            {
                                title:"Unit Name",
                                field:"parent.unit.formatted_name",
                                headerFilter:"input",
                                headerHozAlign:"center"
                            },
                        ],
                    },
                    {
                        title: '',
                        formatter: this.viewDetailsFormatter,
                        width: 100,
                        headerSort: false,
                        hozAlign: 'center',
                        cellClick: (e, cell) => {
                            this.handleActionButtonClick(e, cell);
                        }
                    },
                ],
                pagination: true,
                paginationMode: 'remote',
                paginationSize: this.jobPagination.pageSize,
                paginationSizeSelector: [10, 20, 50, 100],
                headerFilter: true,
                paginationInitialPage:1,
                rowFormatter: (row) => {
                    //
                },
                placeholder:"No Data Available",
            });
            this.loading = false
        },
        viewDetailsFormatter(cell) {
            const rowData = cell.getRow().getData();
            return `
                <button class="button-icon button-success" data-action="view" data-row-id="${rowData.id}"><i data-action="view" class="fa fa-eye"></i> </button>
                <button class="button-icon button-success" data-action="assign" data-bs-toggle="modal" data-bs-target="#jobParentAssignment"><i data-action="assign" class="fa fa-plus-square"></i> </button>
             `;
        },
        handleActionButtonClick(e, cell) {
            const action = e.target.dataset.action
            const rowData = cell.getRow().getData();

            if (action === 'view') {
                this.$router.push({
                    name: 'unit-job-canvas',
                    params: { id: rowData.unit_relation_id },
                })
            } else if (action === 'assign') {
                this.selectedAssignedJob = rowData
                this.getUnitsData()
            }
        },
        onJobParentAssigned() {
            if (this.selectedAssignedJob === null || this.selectedParentJobAssignment === null) {
                return
            }

            this.$axios.post(`/api/v1/admin/unit-job/assign`, {
                unit_has_job_id: this.selectedAssignedJob.id,
                parent_unit_has_job_id: this.selectedParentJobAssignment.id
            }).then(response => {
                useToast().success("Success to create data", { position: 'bottom-right' });
                this.parentJobs = []
                this.selectedAssignedJob = {
                    job: {
                        name: null
                    },
                    unit: {
                        relation_id: null,
                        name: null
                    }
                }
                this.selectedParentJobAssignment = null
                this.selectedParentJobUnit = {
                    relation_id: null
                }
                this.initializeUnitJob()
            }).catch(error => {
                if(error.response.data.message instanceof Object) {
                    for (const key in error.response.data.message) {
                        useToast().error(error.response.data.message[key][0], { position: 'bottom-right' });
                    }
                } else {
                    useToast().error(error.response.data.message , { position: 'bottom-right' });
                }
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
            this.getParentJobData()
        },
        onParentJobSearchName(val) {
            this.parentJobPagination.name = val

            if (!this.parentJobPagination.onSearch) {
                this.parentJobPagination.onSearch = true
                setTimeout(() => {
                    this.getParentJobData()
                }, 1000)
            }
        },
        onJobUnitSelected(val) {

        },
        onJobUnitSearchName(val) {
            this.jobUnitPagination.name = val

            if (!this.jobUnitPagination.onSearch) {
                this.jobUnitPagination.onSearch = true
                setTimeout(() => {
                    this.getJobUnitsData()
                }, 1000)
            }
        },
        onJobSearchName(val) {
            this.jobMasterPagination.name = val

            if (!this.jobMasterPagination.onSearch) {
                this.jobMasterPagination.onSearch = true
                setTimeout(() => {
                    this.getMasterJobData()
                }, 1000)
            }
        },
        onAssignJobUnit() {
            if (this.selectedJobUnit === null || this.selectedJob === null) {
                useToast().error("Field is required", { position: 'bottom-right' });
            }

            this.$axios.post(`/api/v1/admin/unit-job/create`, {
                unit_relation_id: this.selectedJobUnit.relation_id,
                odoo_job_id: this.selectedJob.odoo_job_id
            }).then(response => {
                useToast().success("Success to create data", { position: 'bottom-right' });
                this.initializeUnitJob()
            }).catch(error => {
                if(error.response.data.message instanceof Object) {
                    for (const key in error.response.data.message) {
                        useToast().error(error.response.data.message[key][0], { position: 'bottom-right' });
                    }
                } else {
                    useToast().error(error.response.data.message , { position: 'bottom-right' });
                }
            });
        }
    },
}
</script>

<style>
</style>
