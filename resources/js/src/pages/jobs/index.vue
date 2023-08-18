<template>
    <div class="container-fluid">
        <Breadcrumbs main="Job List"/>

        <div class="container-fluid">
            <div class="email-wrap bookmark-wrap">
                <div class="row">
                    <div class="col-md-12">
                        <div class="card card-absolute">
                            <div class="card-header bg-primary">
                                <h5>Job List</h5>
                            </div>
                            <div class="card-body">
                                <div v-if="loading" class="text-center">
                                    <img src="../../assets/loader.gif" alt="loading" width="100">
                                </div>
                                <div class="d-flex justify-content-start column-gap-2 my-3">
                                    <div class="col-md-4">
                                        <multiselect
                                            v-model="selectedUnitLevel"
                                            placeholder="Select Unit Level"
                                            label="name"
                                            track-by="id"
                                            :options="unitLevels"
                                            :multiple="false"
                                            @select="onSelectUnitLevel">
                                        </multiselect>
                                    </div>
                                    <div class="col-md-5">
                                        <multiselect
                                            v-model="selectedUnit"
                                            placeholder="Select Unit"
                                            label="name"
                                            track-by="id"
                                            :options="units"
                                            :multiple="false"
                                            @select="onSelectUnit"
                                            @search-change="onSearchUnit"
                                        >
                                        </multiselect>
                                    </div>
                                    <div>
                                        <button class="btn btn-primary" @click="resetFilter">
                                            <i class="fa fa-rotate-left"></i>&nbsp;Reset Filter
                                        </button>
                                    </div>
                                </div>
                                <div ref="jobTable"></div>
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
            jobs: [],
            loading: false,
            syncLoading: false,
            table: null,
            countdown: 0,
            timerId: null,
            currentPage: 1,
            pageSize: 10,
            filterName: '',
            selectedUnitLevel: null,
            unitLevels: [
                {
                    id: 1,
                    name: 'Head Office'
                },
                {
                    id: 2,
                    name: 'Operating Unit'
                },
                {
                    id: 3,
                    name: 'Corporate'
                },
                {
                    id: 4,
                    name: 'Kantor Wilayah'
                },
                {
                    id: 5,
                    name: 'Area'
                },
                {
                    id: 6,
                    name: 'Cabang'
                },
                {
                    id: 7,
                    name: 'Outlet'
                },
            ],
            selectedUnit: null,
            units: [],
            searchUnitName: '',
        }
    },
    async mounted() {
        this.initializeDepartmentTable();
    },
    methods: {
        resetFilter() {
            this.initializeDepartmentTable();
            this.selectedUnit = null;
            this.selectedUnitLevel = null;
            this.searchUnitName = '';
        },
        onSearchUnit(e) {
            this.searchUnitName = e;
        },
        onSelectUnitLevel(e) {
            this.selectedUnitLevel = this.unitLevels.find(unitLevel => unitLevel.id === e.id);
            this.getUnits();
        },
        onSelectUnit(e) {
            this.selectedUnit = this.units.find(unit => unit.parent_relation_id === e.parent_relation_id);
            this.table.setFilter('unit_id', '=', this.selectedUnit.parent_relation_id);
        },
        async getUnits() {
            const ls = localStorage.getItem('USER_ROLES');
            await this.$axios.get(`/api/v1/admin/unit?unit_level=${this.selectedUnitLevel.id}&name=${this.searchUnitName}`, {
                headers: {'X-Selected-Role': ls}
            })
                .then(res => {
                    this.units = res.data.data;
                })
                .catch(e => {
                    console.log(e);
                });
        },
        startCountdown() {
            this.countdown = 1;
            this.timerId = setInterval(() => {
                this.countdown++;
            }, 1000);
        },
        initializeDepartmentTable() {
            const ls = localStorage.getItem('my_app_token');
            this.table = new Tabulator(this.$refs.jobTable, {
                paginationCounter:"rows",
                ajaxURL: '/api/v1/admin/job',
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
                    const filters = {
                        unit_id: this.selectedUnit?.parent_relation_id ?? '',
                    }

                    params.filter.map((item) => {
                        if (item.field === 'job_name') this.filterName = item.value
                        if (item.field === 'unit_id') filters.unit_id = item.value
                    })

                    return `${url}?flat=true&page=${params.page}&per_page=${params.size}&name=${this.filterName}&unit_id=${filters.unit_id}`
                },
                layout: 'fitColumns',
                columns: [
                    {
                        title: 'No',
                        field: '',
                        formatter: 'rownum',
                        width: 70,
                        hozAlign: 'center',
                        headerHozAlign: 'center',
                    },
                    {
                        title: 'Name',
                        field: 'job_name',
                        headerFilter:"input",
                        headerHozAlign: 'center',
                        headerSort: false,
                    },
                    {
                        title: 'Unit',
                        field: 'unit_name',
                        headerFilter:"input",
                        headerHozAlign: 'center',
                        headerSort: false,
                        hozAlign: 'center',
                    },
                    {
                        title: 'Roles',
                        field: 'roles',
                        hozAlign: 'center',
                        headerHozAlign: 'center',
                        headerSort: false,
                        formatter: function (cell) {
                            const roles = cell.getValue();
                            let html = '';
                            roles.forEach((role) => {
                                html += `<span class="badge badge-success">${role.name}</span> `;
                            });
                            return html;
                        }
                    },
                    {
                        title: '',
                        formatter: this.viewDetailsFormatter,
                        width: 100,
                        headerSort: false,
                        hozAlign: 'center',
                        cellClick: (e, cell) => {
                            this.handleActionButtonClick(e, cell);
                        }
                    },
                ],
                pagination: true,
                paginationMode: 'remote',
                paginationSize: this.pageSize,
                paginationSizeSelector: [10, 20, 50, 100],
                headerFilter: true,
                paginationInitialPage:1,
                rowFormatter: (row) => {
                    //
                },
                placeholder:"No Data Available",
            });
            this.loading = false
        },
        viewDetailsFormatter(cell) {
            const rowData = cell.getRow().getData();
            return `
                <button class="button-icon button-success" data-action="view" data-row-id="${rowData.id}"><i data-action="view" class="fa fa-eye"></i> </button>
                <button class="button-icon button-warning" data-action="edit" data-row-id="${rowData.id}"><i data-action="edit" class="fa fa-pencil"></i> </button>
             `;
        },
        handleActionButtonClick(e, cell) {
            const action = e.target.dataset.action
            const rowData = cell.getRow().getData();

            if (action === 'edit') {
                this.$router.push({
                    name: 'job-edit',
                    params: { id: rowData.job_id }
                })
            } else if (action === 'view') {
                this.$router.push({
                    name: 'approval_details',
                    params: { id: rowData.id },
                })
            }
        },
    },
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
