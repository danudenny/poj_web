<template>
    <div className="container-fluid">
        <Breadcrumbs main="Corporate"/>

        <div className="container-fluid">
            <div className="email-wrap bookmark-wrap">
                <div className="row">
                    <div className="col-md-12">
                        <div className="card card-absolute">
                            <div className="card-header bg-primary">
                                <h5>Corporate List</h5>
                            </div>
                            <div className="card-body">
<!--                                <div className="d-flex justify-content-end mb-2">-->
<!--                                    <button className="btn btn-warning" :disable="syncLoading" type="button" @click="syncFromERP">-->
<!--                                        <span v-if="syncLoading">-->
<!--                                            <i  class="fa fa-spinner fa-spin"></i> Processing ... ({{ countdown }}s)-->
<!--                                        </span>-->
<!--                                        <span v-else>-->
<!--                                            <i class="fa fa-recycle"></i> &nbsp; Sync From ERP-->
<!--                                        </span>-->
<!--                                    </button>-->
<!--                                </div>-->
                                <div v-if="loading" className="text-center">
                                    <img src="../../assets/loader.gif" alt="loading" width="100">
                                </div>
                                <div ref="corporateTable"></div>
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
            corporates: [],
            loading: false,
            syncLoading: false,
            table: null,
            countdown: 0,
            timerId: null,
            totalEmployee: null
        }
    },
    async mounted() {
        await this.getCorporate();
        this.initializeCorporateTable();
    },
    methods: {
        startCountdown() {
            this.countdown = 1;
            this.timerId = setInterval(() => {
                this.countdown++;
            }, 1000);
        },
        async getCorporate() {
            this.loading = true;
            await this.$axios.get(`/api/v1/admin/unit?unit_level=3`)
                .then(response => {
                    this.corporates = response.data.data;
                })
                .catch(error => {
                    console.error(error);
                });
        },
        initializeCorporateTable() {
            this.table = new Tabulator(this.$refs.corporateTable, {
                data: this.corporates,
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
                        title: 'Jumlah Kanwil',
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
            this.$router.push({name: 'Corporate Detail', params: {id}});
        },
        async syncFromERP() {
            this.syncLoading = true;
            this.loading = true;
            this.startCountdown();
            this.table.destroy();

            await axios.create({
                baseURL: import.meta.env.VITE_SYNC_ODOO_URL,
            }).get('/sync-corporates')
                .then(async (response) => {
                    if (await response.data.status === 201) {
                        this.syncLoading = false;
                        this.loading = false;
                        await this.getCorporate()
                        this.initializeCorporateTable()
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
