<template>
    <div class="container-fluid">
        <Breadcrumbs main="Daftar Approval Backup"/>

        <div class="container-fluid">
            <div class="email-wrap bookmark-wrap">
                <div class="row">
                    <div class="col-md-12">
                        <div class="card card-absolute">
                            <div class="card-header bg-primary">
                                <h5>Daftar Approval Backup</h5>
                            </div>
                            <div class="card-body">
                                <div class="d-flex justify-content-end mb-2">

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
            await this.$axios.get(`/api/v1/admin/backup/list-approval`)
                .then(response => {
                    this.backups = response.data.data;
                })
                .catch(error => {
                    console.error(error);
                });
        },
        initializeDepartmentTable() {
            const table = new Tabulator(this.$refs.backupTable, {
                data: this.backups,
                layout: 'fitData',
                columns: [
                    {
                        title: 'No',
                        field: '',
                        formatter: 'rownum',
                        width: 100
                    },
                    {
                        title: 'Nama Requestor',
                        field: 'backup.requestor_employee.name',
                        headerFilter:"input"
                    },
                    {
                        title: 'Nama Approver',
                        field: 'employee.name',
                        headerFilter:"input"
                    },
                    {
                        title: 'Status',
                        field: 'real_status',
                        headerFilter:"input",
                        formatter: function (cell, formatterParams, onRendered) {
                            let val = cell.getValue()

                            if (val === 'approved') {
                                return `<span class="badge badge-success">Disetujui</span>`
                            } else if (val === 'rejected') {
                                return `<span class="badge badge-danger">Ditolak</span>`
                            } else if (val === 'pending') {
                                return `<span class="badge badge-info">Pending</span>`
                            } else {
                                return `<span class="badge badge-warning">${val}</span>`
                            }
                        },
                    },
                    {
                        title: 'Tipe Request',
                        field: 'backup.request_type',
                        headerFilter:"input"
                    },
                    {
                        title: 'Tipe Shift',
                        field: 'backup.shift_type',
                        headerFilter:"input"
                    },
                    {
                        title:"Unit",
                        headerHozAlign:"center",
                        columns:[
                            {
                                title:"Dari",
                                field:"backup.source_unit.name",
                                headerHozAlign:"center",
                                formatter: function (cell, formatterParams, onRendered) {
                                    return `<span><i class="fa fa-arrow-left text-warning"></i>&nbsp; <span class="badge badge-warning">${cell.getValue()}</span></span>`;
                                },
                            },
                            {
                                title:"Ke",
                                field:"backup.unit.name",
                                headerHozAlign:"center",
                                formatter: function (cell, formatterParams, onRendered) {
                                    return `<span><span class="badge badge-success">${cell.getValue()}</span> &nbsp; <i class="fa fa-arrow-right text-success"></i></span>`;
                                },
                            },
                        ],
                    },
                    {
                        title: 'Tanggal Mulai',
                        field: 'backup.start_date',
                        headerFilter:"date"
                    },
                    {
                        title: 'Tanggal Selesai',
                        field: 'backup.end_date',
                        headerFilter:"date"
                    },
                    {
                        title: 'Durasi',
                        field: 'backup.duration',
                        formatter: function (cell, formatterParams, onRendered) {
                            return `<span class="badge badge-success">${cell.getValue()} hari</span>`;
                        },
                    },
                    {
                        title: '',
                        formatter: this.viewDetailsFormatter,
                        width: 70,
                        hozAlign: 'center',
                        sortable: false,
                        cellClick: (e, cell) => {
                            this.viewData(cell.getRow().getData().backup.id);
                        }
                    },
                ],
                pagination: 'local',
                paginationSize: 10,
                paginationSizeSelector: [10, 20, 50, 100],
                headerFilter: true,
                paginationInitialPage:1,
                rowFormatter: (row) => {
                    //
                }
            });
            this.loading = false
        },
        viewDetailsFormatter(cell, formatterParams, onRendered) {
            return `<button class="button-icon button-success" data-id="${cell.getRow().getData().id}"><i class="fa fa-arrow-right"></i> </button>`;
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
