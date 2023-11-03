<template>
    <div className="container-fluid">
        <Breadcrumbs main="Area"/>

        <div className="container-fluid">
            <div className="email-wrap bookmark-wrap">
                <div className="row">
                    <div className="col-md-12">
                        <div className="card card-absolute">
                            <div className="card-header bg-primary">
                                <h5>Area List</h5>
                            </div>
                            <div className="card-body">
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
export default {
    data() {
        return {
            corporates: [],
            loading: false,
            syncLoading: false,
            table: null,
            countdown: 0,
            timerId: null,
            pageSize: 10,
            pagination: {
                pageSize: 10,
                currentPage: 1
            }
        }
    },
    async mounted() {
        this.initializeCorporateTable();
    },
    methods: {
        initializeCorporateTable() {
            const ls = localStorage.getItem('my_app_token');

            this.table = new Tabulator(this.$refs.corporateTable, {
                ajaxURL: '/api/v1/admin/unit/paginated',
                ajaxConfig: {
                    headers: {
                        Authorization: `Bearer ${ls}`,
                        "X-Selected-Role": this.$store.state.currentRole,
                        "X-Unit-Relation-ID": this.$store.state.activeAdminUnit?.unit_relation_id ?? ''
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
	                let localFilter = {
		                name: ''
	                }

	                params.filter.map((item) => {
		                if (item.field === 'name') localFilter.name = item.value
	                })

	                return `${url}?page=${params.page}&per_page=${params.size}&append=total_child&unit_level=5&name=${localFilter.name}`
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
                        field: 'formatted_name',
                        headerFilter: "input"
                    },
                    {
                        title: 'Jumlah Cabang',
                        field: 'total_child',
                        hozAlign: 'center',
                        headerHozAlign: 'center',
                        formatter: function(cell) {
                            const val = cell.getValue()
                            return `<span class="badge badge-${val === 0 ? 'danger': 'success'}">${val}</span>`
                        }
                    },
                    {
                        title: '',
                        formatter: this.viewDetailsFormatter,
                        width: 70,
                        hozAlign: 'center',
                        sortable: false,
                        cellClick: (e, cell) => {
                            this.viewData(cell.getRow().getData());
                        }
                    },
                ],
                placeholder: 'No Data Available',
                pagination: true,
                paginationMode: 'remote',
                filterMode:"remote",
                paginationSize: this.pageSize,
                paginationSizeSelector: [10, 20, 50, 100],
                headerFilter: true,
                paginationInitialPage:1,
            });
            this.loading = false
        },
        viewDetailsFormatter(cell) {
            return `<button title="Detail" class="button-icon button-success" data-id="${cell.getRow().getData()}"><i class="fa fa-eye"></i> </button>`;
        },
        viewData(data) {
            this.$router.push({
                name: 'Area Detail',
                params: {id: data.id},
                query: {
                    unit_id: data.relation_id
                }
            })
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
