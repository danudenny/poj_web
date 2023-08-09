<template>
    <div class="container-fluid">
        <Breadcrumbs main="Job Parent Assignment"/>

        <div class="container-fluid">
            <div class="email-wrap bookmark-wrap">
                <div class="row">
                    <div class="col-md-12">
                        <div class="card card-absolute">
                            <div class="card-header bg-primary">
                                <h5>Job List</h5>
                            </div>
                            <div class="card-body">
                                <div v-if="loading" class="text-center">
                                    <img src="../../assets/loader.gif" alt="loading" width="100">
                                </div>
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
                        <input type="text" class="form-control" v-model="selectedAssignedJob.unit.name" disabled required>
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
                        label="name"
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
                    name: null
                }
            },
            selectedParentJobAssignment: null,
            selectedParentJobUnit: {
                relation_id: null,
            },
            units: [],
            parentJobs: []
        }
    },
    async mounted() {
        this.initializeUnitJob();
        this.getUnitsData()
    },
    methods: {
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
        getParentJobData() {
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
                                field:"unit.name",
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
                                field:"parent.unit.name",
                                headerFilter:"input",
                                headerHozAlign:"center"
                            },
                        ],
                    },
                    {
                        title: '',
                        formatter: (cell) => {
                            return `<button class="button-icon button-success" data-bs-toggle="modal" data-bs-target="#jobParentAssignment"><i data-action="assign" class="fa fa-plus-square"></i> </button>`
                        },
                        width: 100,
                        headerSort: false,
                        hozAlign: 'center',
                        cellClick: this.handleActionButtonClick
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
        handleActionButtonClick(e, cell) {
            this.selectedAssignedJob = cell.getRow().getData()
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
                this.initializeUnitJob()
                this.parentJobs = []
                this.selectedAssignedJob = {
                    job: {
                        name: null
                    },
                    unit: {
                        name: null
                    }
                }
                this.selectedParentJobAssignment = null
                this.selectedParentJobUnit = {
                    relation_id: null
                }
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
    },
}
</script>

<style>
</style>
