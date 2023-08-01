<template>
    <div class="container-fluid">
        <Breadcrumbs main="Backup"/>

        <div class="container-fluid">
            <div class="email-wrap bookmark-wrap">
                <div class="row">
                    <div class="col-md-12">
                        <div class="card card-absolute">
                            <div class="card-header bg-primary">
                                <h5>Backup List</h5>
                            </div>
                            <div class="card-body">
                                <div class="d-flex justify-content-end mb-2">
                                    <button class="btn btn-warning" type="button" @click="$router.push('/attendance/create-backup')">
                                        <i class="fa fa-recycle" /> &nbsp; Assign Backup
                                    </button>
                                </div>
                                <div v-if="loading" class="text-center">
                                    <img src="../../assets/loader.gif" alt="loading" width="100">
                                </div>
                                <div ref="backupTable"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import {TabulatorFull as Tabulator} from 'tabulator-tables';

export default {
    data() {
        return {
            backups: [],
            loading: false,
        }
    },
    async mounted() {
        await this.getDepartments();
        this.initializeDepartmentTable();
    },
    methods: {
        async getDepartments() {
            this.loading = true;
            await this.$axios.get(`/api/v1/admin/backup`)
                .then(response => {
                    this.backups = response.data.data;
                    console.log(this.backups)
                })
                .catch(error => {
                    console.error(error);
                });
        },
        initializeDepartmentTable() {
            const table = new Tabulator(this.$refs.backupTable, {
                paginationCounter:"rows",
                data: this.backups,
                layout: 'fitData',
                renderHorizontal:"virtual",
                height: '100%',
                columns: [
                    {
                        title: 'No',
                        field: '',
                        formatter: 'rownum',
                        width: 70,
                        frozen: true,
                    },
                    {
                        title: 'Requestor Name',
                        field: 'requestor_employee.name',
                        headerFilter:"input",
                        frozen: true,
                        width: 200,
                        formatter: function (cell, formatterParams, onRendered) {
                            return `<span class="text-danger"><b>${cell.getValue()}</b></span>`;
                        },
                        cellClick: (e, cell) => {
                            this.viewData(cell.getRow().getData().id);
                        }
                    },
                    {
                        title: 'Status',
                        field: 'status',
                        headerFilter:"input"
                    },
                    {
                        title: 'Request Type',
                        field: 'request_type',
                        headerFilter:"input"
                    },
                    {
                        title: 'Shift Type',
                        field: 'shift_type',
                        headerFilter:"input",
                        hozAlign:"center"
                    },
                    {
                        title:"Assigned",
                        headerHozAlign:"center",
                        columns:[
                            {
                                title:"From",
                                field:"unit.name",
                                headerHozAlign:"center",
                                formatter: function (cell, formatterParams, onRendered) {
                                    return `<span><i class="fa fa-arrow-left text-warning"></i>&nbsp; ${cell.getValue()}</span>`;
                                },
                            },
                            {
                                title:"To",
                                field:"source_unit.name",
                                headerHozAlign:"center",
                                formatter: function (cell, formatterParams, onRendered) {
                                    return `<span><i class="fa fa-arrow-right text-success"></i>&nbsp; ${cell.getValue()}</span>`;
                                },
                            },
                        ],
                    },
                    {
                        title: 'Job',
                        field: 'job.name',
                        headerHozAlign:"center",
                        hozAlign:"center",
                        headerFilter:"input"
                    },
                    {
                        title: 'Start Date',
                        field: 'start_date',
                        headerHozAlign:"center",
                        hozAlign:"center",
                        headerFilter:"date",
                        formatter: function (cell, formatterParams, onRendered) {
                            const dateValue = new Date(cell.getRow().getData().start_date);
                            const dateFormatter  = new Intl.DateTimeFormat('id-ID', {
                                year: 'numeric',
                                month: 'long',
                                day: 'numeric',
                            });
                            const formattedDate = dateFormatter .format(dateValue);
                            return `<span>${formattedDate}</span>`;
                        },
                    },
                    {
                        title: 'End Date',
                        field: 'end_date',
                        headerHozAlign:"center",
                        hozAlign:"center",
                        headerFilter:"date",
                        formatter: function (cell, formatterParams, onRendered) {
                            const dateValue = new Date(cell.getRow().getData().end_date);
                            const dateFormatter  = new Intl.DateTimeFormat('id-ID', {
                                year: 'numeric',
                                month: 'long',
                                day: 'numeric',
                            });
                            const formattedDate = dateFormatter .format(dateValue);
                            return `<span>${formattedDate}</span>`;
                        },
                    },
                    {
                        title: 'Duration',
                        field: 'duration',
                        headerHozAlign:"center",
                        hozAlign:"center",
                        formatter: function (cell, formatterParams, onRendered) {
                            return `<span>${cell.getValue()} days</span>`;
                        },
                    },
                    {
                        title: 'File',
                        formatter: (cell, formatterParams, onRendered) => {
                            if (cell.getRow().getData().file_url) {
                                return `<a target="_blank" class="button-icon button-success p-2" href="${cell.getRow().getData().file_url}"><i class="fa fa-file"></i> </a>`;
                            } else {
                                return "-"
                            }
                        }
                    },
                    {
                        title: 'Status',
                        field: 'status',
                        headerHozAlign:"center",
                        hozAlign:"center",
                        formatter: function (cell, formatterParams, onRendered) {
                            if (cell.getValue() === 'assigned') {
                                return `<span class="badge badge-warning">${cell.getValue()}</span>`;
                            } else if (cell.getValue() === 'approved') {
                                return `<span class="badge badge-success">${cell.getValue()}</span>`;
                            } else if (cell.getValue() === 'rejected') {
                                return `<span class="badge badge-danger">${cell.getValue()}</span>`;
                            }
                        },
                    },
                    {
                        title: 'Created At',
                        field: 'created_at',
                        headerFilter:"input",
                        formatter: function (cell, formatterParams, onRendered) {
                            const dateValue = new Date(cell.getRow().getData().created_at);
                            const dateFormatter  = new Intl.DateTimeFormat('id-ID', {
                                year: 'numeric',
                                month: 'long',
                                day: 'numeric',
                                hour: '2-digit',
                                minute: '2-digit',
                                second: '2-digit',
                            });
                            const formattedDate = dateFormatter .format(dateValue);
                            return `<span class="text-success"><b>${formattedDate}</b></span>`;
                        },
                    },
                    {
                        title: '',
                        formatter: this.viewDetailsFormatter,
                        width: 70,
                        hozAlign: 'center',
                        sortable: false,
                        cellClick: (e, cell) => {
                            this.viewData(cell.getRow().getData().id);
                        }
                    },
                ],
                pagination: 'local',
                paginationSize: 10,
                paginationSizeSelector: [10, 20, 50, 100],
                headerFilter: true,
                paginationInitialPage:1,
                placeholder: 'No Data Available',
                headerSortElement:"",
                rowFormatter: (row) => {
                    //
                }
            });
            this.loading = false
        },
        viewDetailsFormatter(cell, formatterParams, onRendered) {
            return `<button class="button-icon button-success" data-id="${cell.getRow().getData().id}"><i class="fa fa-eye"></i> </button>`;
        },
        viewData(id) {
            this.$router.push({name: 'Detail Backup', params: {id}});
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
