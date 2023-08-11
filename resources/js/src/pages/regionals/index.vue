<template>
    <div class="container-fluid">
        <Breadcrumbs main="Kantor Perwakilan"/>

        <div class="container-fluid">
            <div class="email-wrap bookmark-wrap">
                <div class="row">
                    <div class="col-md-12">
                        <div class="card card-absolute">
                            <div class="card-header bg-primary">
                                <h5>Kantor Perwakilan</h5>
                            </div>
                            <div class="card-body">
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
            loading: false,
            currentPage: 1,
            pageSize: 10,
            filterName: '',
        }
    },
    async mounted() {
        this.initializeRegionalTable();
    },
    methods: {
        initializeRegionalTable() {
            const ls = localStorage.getItem('my_app_token');
            const table = new Tabulator(this.$refs.regionalTable, {
                ajaxURL: '/api/v1/admin/unit/paginated',
                ajaxConfig: {
                    headers: {
                        Authorization: `Bearer ${ls}`,
                        "X-Unit-Relation-ID": this.$store.state.activeAdminUnit?.unit_relation_id ?? ''
                    },
                },
                filterMode:"remote",
                ajaxParams: {
                    page: this.currentPage,
                    size: this.pageSize,
                },
                ajaxResponse: function (url, params, response) {
                    return {
                        data: response.data.data,
                        last_page: response.data.last_page,
                    }
                },
                ajaxURLGenerator: (url, config, params) => {
                    let localFilter = {
                        name: ''
                    }

                    params.filter.map((item) => {
                        if (item.field === 'name') localFilter.name = item.value
                    })

                    return `${url}?page=${params.page}&per_page=${params.size}&name=${localFilter.name}&unit_level=2&append=total_managed_operating_unit`
                },
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
                        title: 'Total Unit',
                        field: 'total_managed_operating_unit',
                        width: 120,
                        formatter: (cell) => {
                            let data = cell.getRow().getData().total_managed_operating_unit;
                            if (data === 0) {
                                return `<span class="badge badge-danger">${data}</span> `;;
                            } else {
                                return `<span class="badge badge-primary">${data}</span> `;;
                            }
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
                pagination: true,
                paginationMode: 'remote',
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
