<template>
    <div className="container-fluid">
        <Breadcrumbs title="Outlet"/>

        <div className="container-fluid">
            <div className="email-wrap bookmark-wrap">
                <div className="row">
                    <div className="col-md-12">
                        <div className="card card-absolute">
                            <div className="card-header bg-primary">
                                <h5>Outlet List</h5>
                            </div>
                            <div className="card-body">
                                <div className="d-flex justify-content-end mb-2">
                                    <button className="btn btn-warning" :disable="syncLoading" type="button" @click="syncFromERP">
                                        <span v-if="syncLoading">
                                            <i  class="fa fa-spinner fa-spin"></i> Processing ... ({{ progress }}s)
                                        </span>
                                        <span v-else>
                                            <i class="fa fa-recycle"></i> &nbsp; Sync From ERP
                                        </span>
                                    </button>
                                </div>
                                <div v-if="loading" className="text-center">
                                    <img src="../../assets/loader.gif" alt="loading" width="100">
                                </div>
                                <div ref="outletTable"></div>
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
import {useToast} from 'vue-toastification';
import axios from 'axios';
export default {
    data() {
        return {
            outlets: [],
            loading: false,
            syncLoading: false,
            table: null,
            progress: 0,
            timerId: null
        }
    },
    async mounted() {
        await this.getOutlet();
        this.initializeOutletTable();
    },
    methods: {
        startProgress() {
            this.progress = 0;
            this.timerId = setInterval(() => {
                this.progress++;
            }, 1000);
        },
        async getOutlet() {
            this.loading = true;
            await this.$axios.get(`/api/v1/admin/unit?unit_level=7`)
                .then(response => {
                    this.outlets = response.data.data;
                })
                .catch(error => {
                    console.error(error);
                });
        },
        initializeOutletTable() {
            this.table = new Tabulator(this.$refs.outletTable, {
                data: this.outlets,
                layout: 'fitColumns',
                columns: [
                    {
                        title: 'No',
                        field: '',
                        formatter: 'rownum',
                        width: 100
                    },
                    {
                        title: 'Name',
                        field: 'name',
                        headerFilter: "input"
                    },
                    {
                        title: 'Jumlah Subsidiary',
                        field: '',
                        hozAlign: 'center',
                        headerHozAlign: 'center',
                        formatter: function(value) {
                            return `<span class="badge badge-${value.getData().child.length === 0 ? 'danger': 'success'}">${value.getData().child.length}</span>`
                        }
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
                paginationInitialPage: 1,
                rowFormatter: (row) => {
                    //
                }
            });
            this.loading = false
        },
        viewDetailsFormatter(cell, formatterParams, onRendered) {
            return `<button title="Detail" class="button-icon button-success" data-id="${cell.getRow().getData().id}"><i class="fa fa-eye"></i> </button>`;
        },
        viewData(id) {
            this.$router.push({name: 'Outlet Detail', params: {id}});
        },
        async syncFromERP() {
            this.syncLoading = true;
            this.loading = true;
            this.startProgress();
            this.table.destroy();

            await axios.create({
                baseURL: import.meta.env.VITE_SYNC_ODOO_URL,
            }).get('/sync-outlet')
                .then(async (response) => {
                    if (await response.data.status === 201) {
                        this.syncLoading = false;
                        this.loading = false;
                        await this.getOutlet()
                        this.initializeOutletTable()
                        useToast().success(response.data.message);
                    } else {
                        this.syncLoading = false;
                        useToast().error(response.data.message);
                    }
                }).catch(error => {
                    this.syncLoading = false;
                    useToast().error("Failed to Sync Data! Check if connection are stable");
                }).finally(() => {
                    this.syncLoading = false;
                    this.progress = 100;
                    clearInterval(this.timerId);
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
