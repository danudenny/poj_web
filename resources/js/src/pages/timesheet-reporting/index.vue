<template>
    <div class="container-fluid">
        <Breadcrumbs main="Timesheet Reporting"/>

        <div class="container-fluid">
            <div class="email-wrap bookmark-wrap">
                <div class="row">
                    <div class="col-md-12">
                        <div class="card card-absolute">
                            <div class="card-header bg-primary">
                                <h5>Timesheet Reporting</h5>
                            </div>
                            <div class="card-body">
                                <div class="d-flex justify-content-end mb-2">
                                    <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#createTimesheetReporting">
                                        <i class="fa fa-plus-circle" /> &nbsp; Create Timesheet Reporting
                                    </button>
                                </div>
                                <div ref="listTimesheetReporting"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="modal fade" id="createTimesheetReporting" ref="createTimesheetReporting" tabindex="-1" role="dialog" aria-labelledby="exampleModalCenter" aria-hidden="true">
            <VerticalModal title="Create Timesheet Reporting" @save="onCreate()">
                <div class="row">
                    <div class="col-md-12">
                        <div class="mt-2">
                            <label for="date">Start Date:</label>
                            <input type="date" class="form-control" id="date" v-model="createTimesheetReportingPayload.start_date" required>
                        </div>
                    </div>
                    <div class="col-md-12">
                        <div class="mt-2">
                            <label for="date">End Date:</label>
                            <input type="date" class="form-control" id="date" v-model="createTimesheetReportingPayload.end_date" required>
                        </div>
                    </div>
                    <div class="col-md-12">
                        <label>Unit :</label>
                        <multiselect
                            v-model="selectedUnit"
                            placeholder="Select Unit"
                            label="name_with_corporate"
                            track-by="relation_id"
                            :options="units"
                            :multiple="false"
                            @select="onSelectedUnit"
                            @search-change="onSearchUnitName"
                        >
                        </multiselect>
                    </div>
                </div>
            </VerticalModal>
        </div>
    </div>
</template>
<script>
import {TabulatorFull as Tabulator} from 'tabulator-tables';
import {useToast} from "vue-toastification";
import VerticalModal from "@components/modal/verticalModal.vue";
import Modal from "@components/modal.vue";
import moment from "moment/moment";

export default {
    components: {VerticalModal, Modal},
    data() {
        return {
            createTimesheetReportingPayload: {
                start_date: null,
                end_date: null,
                unit_relation_id: null
            },
            pagination: {
                currentPage: 1,
                pageSize: 10
            },
            unitPagination: {
                limit: 20,
                isOnSearch: true,
                name: ''
            },
            selectedUnit: null,
            units: []
        }
    },
    mounted() {
        this.generateTimesheetReportingTable()
        this.getUnits()
    },
    methods: {
        getUnits() {
            this.$axios.get(`api/v1/admin/unit/paginated?per_page=${this.unitPagination.limit}&name=${this.unitPagination.name}&unit_level=4,5,6,7&append=name_with_corporate`)
                .then(response => {
                    this.units = response.data.data.data;
                    this.unitPagination.isOnSearch = false
                }).catch(error => {
                    console.error(error);
                });
        },
        generateTimesheetReportingTable() {
            const ls = localStorage.getItem('my_app_token');
            this.table = new Tabulator(this.$refs.listTimesheetReporting ,{
                paginationCounter:"rows",
                ajaxURL: '/api/v1/admin/timesheet-report',
                ajaxConfig: {
                    headers: {
                        Authorization: `Bearer ${ls}`,
                        "X-Unit-Relation-ID": this.$store.state.activeAdminUnit?.unit_relation_id ?? '',
                        "X-Selected-Role": this.$store.state.currentRole,
                    }
                },
                ajaxParams: {
                    page: this.pagination.currentPage,
                    size: this.pagination.pageSize,
                },
                ajaxResponse: function (url, params, response) {
                    return {
                        data: response.data.data,
                        last_page: response.data.last_page,
                    }
                },
                ajaxURLGenerator: (url, config, params) => {
                    return `${url}?page=${params.page}&per_page=${params.size}`
                },
                layout: 'fitColumns',
                renderHorizontal:"virtual",
                height: '100%',
                columns: [
                    {
                        title: 'No',
                        field: '',
                        formatter: 'rownum',
                        hozAlign: 'center',
                        headerHozAlign: 'center',
                        headerSort: false,
                        width: 50,
                        frozen: true
                    },
                    {
                        title: 'Unit Name',
                        field: 'unit.name',
                        headerHozAlign: 'center',
                        hozAlign: 'center',
                        headerSort: false,
                    },
                    {
                        title: 'Status',
                        field: 'status',
                        headerHozAlign: 'center',
                        hozAlign: 'center',
                        headerSort: false,
                        formatter: function (cell) {
                            let value = cell.getValue()

                            if (value === 'pending') {
                                return `<span class="badge badge-warning">Pending</span>`
                            } else {
                                return `<span class="badge badge-success">Synced to ERP</span>`
                            }
                        }
                    },
                    {
                        title: 'Period',
                        headerHozAlign: 'center',
                        headerSort: false,
                        columns: [
                            {
                                title: 'Start Date',
                                field: 'start_date',
                                hozAlign: 'center',
                                headerHozAlign: 'center',
                                headerSort: false,
                            },
                            {
                                title: 'End Date',
                                field: 'end_date',
                                hozAlign: 'center',
                                headerHozAlign: 'center',
                                headerSort: false,
                            },
                        ]
                    },
                    {
                        title: 'Sync',
                        headerHozAlign: 'center',
                        headerSort: false,
                        columns: [
                            {
                                title: 'Last Sync At',
                                field: 'last_sync_with_client_timezone',
                                headerHozAlign: 'center',
                                hozAlign: 'center',
                                headerSort: false,
                            },
                            {
                                title: 'Last Sync By',
                                field: 'last_sync_by',
                                headerHozAlign: 'center',
                                hozAlign: 'center',
                                headerSort: false,
                            },
                        ]
                    },
                    {
                        title: 'Last Send',
                        headerHozAlign: 'center',
                        headerSort: false,
                        columns: [
                            {
                                title: 'Last Send At',
                                field: 'last_sent_with_client_timezone',
                                headerHozAlign: 'center',
                                hozAlign: 'center',
                                headerSort: false,
                            },
                            {
                                title: 'Last Send By',
                                field: 'last_sent_by',
                                headerHozAlign: 'center',
                                hozAlign: 'center',
                                headerSort: false,
                            },
                        ]
                    },
                    {
                        title: 'Action',
                        hozAlign: 'center',
                        headerHozAlign: 'center',
                        headerSort: false,
                        formatter: (cell, formatterParams, onRendered) => {
                            const rowData = cell.getRow().getData();
                            return `
                                    <button class="button-icon button-success" data-action="view" data-row-id="${rowData.id}"><i data-action="view" class="fa fa-eye"></i> </button>
                                 `;
                        },
                        width: 150,
                        sortable: false,
                        cellClick: (e, cell) => {
                            const action = e.target.dataset.action
                            const rowData = cell.getRow().getData();

                            if (action === 'view') {
                                this.$router.push({
                                    name: 'Detail Timesheet Reporting',
                                    params: { id: rowData.id },
                                })
                            }
                        }
                    },
                ],
                placeholder: 'No Data Available',
                pagination: true,
                paginationMode: 'remote',
                filterMode:"remote",
                paginationSize: this.pagination.pageSize,
                paginationSizeSelector: [10, 20, 50, 100],
                headerFilter: true,
                paginationInitialPage:1,
            })
        },
        onSelectedUnit() {
            this.createTimesheetReportingPayload.unit_relation_id = this.selectedUnit.relation_id
        },
        onSearchUnitName(val) {
            this.unitPagination.name = val

            if (!this.unitPagination.isOnSearch) {
                this.unitPagination.isOnSearch = true
                setTimeout(() => {
                    this.getUnits()
                }, 1000)
            }
        },
        onCreate() {
            this.$swal({
                icon: 'warning',
                title:"Are you sure want to add timesheet report?",
                text:'Please re-check the date range for timesheet report before create report!',
                showCancelButton: true,
                confirmButtonText: 'Yes!',
                confirmButtonColor: '#126850',
                cancelButtonText: 'Cancel',
                cancelButtonColor: '#efefef',
            }).then((result)=>{
                if(result.isConfirmed){
                    this.$axios.post(`api/v1/admin/timesheet-report`, this.createTimesheetReportingPayload)
                        .then(() => {
                            useToast().success("Success to create timesheet report!");
                            this.generateTimesheetReportingTable()
                        })
                        .catch(error => {
                            useToast().error(error.response.data.message);
                        });
                }
            });
        },
    }
}
</script>
