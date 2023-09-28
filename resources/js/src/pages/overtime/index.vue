<template>
    <div class="container-fluid">
        <Breadcrumbs main="Overtime Request"/>

        <div class="container-fluid">
            <div class="email-wrap bookmark-wrap">
                <div class="row">
                    <div class="col-md-12">
                        <div class="card card-absolute">
                            <div class="card-header bg-primary">
                                <h5>Lembur Pegawai</h5>
                            </div>
                            <div class="card-body">
                                <div class="d-flex justify-content-end mb-2">
                                    <button v-if="this.$store.state.permissions?.includes('overtime-request-create')" class="btn btn-primary" type="button" @click="$router.push('/attendance/overtime/create')">
                                        <i class="fa fa-plus" /> &nbsp;Buat Lembur
                                    </button>
                                </div>
                                <div v-if="loading" class="text-center">
                                    <img src="../../assets/loader.gif" alt="loading" width="100">
                                </div>
                                <div ref="overtimeTable"></div>
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
            pageSize: 10,
            currentPage: 1,
            loading: false,
        }
    },
    async mounted() {
        this.generateOvertimeTable();
    },
    methods: {
        generateOvertimeTable() {
            const ls = localStorage.getItem('my_app_token')
            const table = new Tabulator(this.$refs.overtimeTable, {
                ajaxURL: '/api/v1/admin/overtime',
                layout: 'fitColumns',
                columns: [
                    {
                        title: 'No',
                        field: '',
                        formatter: 'rownum',
                        width: 100
                    },
                    {
                        title: 'Nama Requestor',
                        field: 'requestor_employee.name',
                        headerFilter:"input"
                    },
                    {
                        title: 'Status',
                        field: 'last_status',
                        headerFilter:"input"
                    },
                    {
                        title: 'Nama Unit',
                        field: 'unit.name',
                        headerFilter:"input"
                    },
                    {
                        title: 'Waktu Mulai',
                        field: 'start_date',
                    },
                    {
                        title: 'Waktu Selesai',
                        field: 'end_date',
                    },
                    {
                        title: 'Berkas',
                        formatter: (cell, formatterParams, onRendered) => {
                            if (cell.getRow().getData().image_url) {
                                return `<a target="_blank" class="button-icon button-success p-2 mt-3" href="${cell.getRow().getData().image_url}"><i class="fa fa-file"></i> </a>`;
                            } else {
                                return "-"
                            }
                        }
                    },
                    {
                        title: '',
                        formatter: this.viewDetailsFormatter,
                        width: 120,
                        hozAlign: 'center',
                        sortable: false,
                        cellClick: (e, cell) => {
                            this.handleActionButtonClick(e, cell);
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
                        "X-Unit-Relation-ID": this.$store.state.activeAdminUnit?.unit_relation_id ?? '',
                        "X-Selected-Role": this.$store.state.currentRole,
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
            const rowData = cell.getRow().getData();
            if (this.$store.state.permissions?.includes('overtime-request-delete')) {
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
                    name: 'Detail Overtime',
                    params: { id: rowData.id },
                })
            } else if (action === 'delete') {
                this.basic_warning_alert(rowData.id);
            }
        },
        basic_warning_alert:function(id){
            this.$swal({
                icon: 'warning',
                title:"Apakah Anda Ingin Menghapus Data?",
                text:'Setelah Anda Menghapus, Data Tidak Akan Bisa Dikembalikan!',
                showCancelButton: true,
                confirmButtonText: 'Ya, Hapus!',
                confirmButtonColor: '#e64942',
                cancelButtonText: 'Batal',
                cancelButtonColor: '#efefef',
            }).then((result)=>{
                if(result.value){
                    this.$axios.delete(`api/v1/admin/overtime/delete/${id}`)
                        .then(() => {
                            this.generateOvertimeTable()
                            useToast().success("Data successfully deleted!", { position: 'bottom-right' });
                        })
                        .catch(error => {
                            useToast().error(error.response.data.message, { position: 'bottom-right' });
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
