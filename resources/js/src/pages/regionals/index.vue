<template>
    <div class="container-fluid">
        <Breadcrumbs title="Regional"/>

        <div class="container-fluid">
            <div class="email-wrap bookmark-wrap">
                <div class="row">
                    <div class="col-md-12">
                        <div class="card card-absolute">
                            <div class="card-header bg-primary">
                                <h5>Regional List</h5>
                            </div>
                            <div class="card-body">
<!--                                <div class="d-flex justify-content-end mb-2">-->
<!--                                    <button class="btn btn-warning" type="button" data-bs-toggle="modal"-->
<!--                                            data-bs-target="#exampleModalCenter">-->
<!--                                        <i class="fa fa-recycle" /> &nbsp; Sync From ERP-->
<!--                                    </button>-->
<!--                                </div>-->
                                <div v-if="loading" class="text-center">
                                    <img src="../../assets/loader.gif" alt="loading" width="100">
                                </div>
                                <div ref="regionalTable"></div>
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
            regionals: [],
            loading: false,
        }
    },
    async mounted() {
        await this.getRegionals();
        this.initializeRegionalTable();
    },
    methods: {
        async getRegionals() {
            this.loading = true;
            await this.$axios.get(`/api/v1/admin/unit?level_name=Regional`)
                .then(response => {
                    this.regionals = response.data.data;
                })
                .catch(error => {
                    console.error(error);
                });
        },
        initializeRegionalTable() {
            const table = new Tabulator(this.$refs.regionalTable, {
                data: this.regionals,
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
                        headerFilter:"input"
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
            this.$router.push({name: 'regional_detail', params: {id}});
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
