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
                                    <button v-if="this.$store.state.permissions?.includes('backup-request-create')" class="btn btn-warning" type="button" @click="$router.push('/attendance/create-backup')">
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
import {useToast} from "vue-toastification";

export default {
    data() {
        return {
            backups: [],
            loading: false,
            table: null
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
                })
                .catch(error => {
                    console.error(error);
                });
        },
        initializeDepartmentTable() {
            this.table = new Tabulator(this.$refs.backupTable, {
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
                        width: 150,
                        hozAlign: 'center',
                        sortable: false,
                        cellClick: (e, cell) => {
                            this.handleActionButtonClick(e, cell);
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
            const rowData = cell.getRow().getData();
            if (this.$store.state.permissions?.includes('backup-request-delete')) {
                return `
                    <button class="button-icon button-success" data-action="view" data-row-id="${rowData.id}"><i data-action="view" class="fa fa-eye"></i> </button>
                    <button class="button-icon button-danger" data-action="delete" data-row-id="${rowData.id}"><i data-action="delete" class="fa fa-trash"></i> </button>
                 `;
            } else {
                return `
                    <button class="button-icon button-success" data-action="view" data-row-id="${rowData.id}"><i data-action="view" class="fa fa-eye"></i> </button>
                 `;
            }
        },
        handleActionButtonClick(e, cell) {
            const action = e.target.dataset.action
            const rowData = cell.getRow().getData();

            if (action === 'view') {
                this.$router.push({
                    name: 'Detail Backup',
                    params: { id: rowData.id },
                })
            } else if (action === 'delete') {
                this.basic_warning_alert(rowData.id);
            }
        },
        viewData(id) {
            this.$router.push({name: 'Detail Backup', params: {id}});
        },
        redrawTable() {
            this.$nextTick(() => {
                this.table.redraw(true);
            });
        },
        basic_warning_alert:function(id){
            this.$swal({
                icon: 'warning',
                title:"Delete Data?",
                text:'Once deleted, you will not be able to recover the data!',
                showCancelButton: true,
                confirmButtonText: 'Yes, delete it!',
                confirmButtonColor: '#e64942',
                cancelButtonText: 'Cancel',
                cancelButtonColor: '#efefef',
            }).then((result)=>{
                if(result.value){
                    this.$axios.delete(`api/v1/admin/backup/delete/${id}`)
                        .then(() => {
                            const pluck = this.table.getData().filter((item) => item.id !== id);
                            this.loading = true
                            this.table.setData(pluck);
                            this.redrawTable();
                            this.loading = false
                            useToast().success("Data successfully deleted!");
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
