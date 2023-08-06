<template>
    <div class="container-fluid">
        <Breadcrumbs main="Permission Management"/>

        <div class="container-fluid">
            <div class="email-wrap bookmark-wrap">
                <div class="row">
                    <div class="col-md-12">
                        <div class="card card-absolute">
                            <div class="card-header bg-primary">
                                <h5>Permission List</h5>
                            </div>
                            <div class="card-body">
                                <div class="d-flex justify-content-end mb-2">
                                    <button class="btn btn-success btn-sm" @click="createPermission">
                                        <i class="fa fa-plus-circle"></i> Create
                                    </button>
                                </div>
                                <div v-if="loading" class="text-center">
                                    <img src="../../assets/loader.gif" alt="loading" width="100">
                                </div>
                                <div ref="permissionsTable"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>

import {TabulatorFull as Tabulator} from "tabulator-tables";

export default {
    data() {
        return {
            table: null,
            loading: false,
            currentPage: 1,
            pageSize: 10,
            roles: [],
            selectedPermission: [],
        }
    },
    async mounted() {
        await this.initializePermissionsTable();
    },
    methods: {
        async initializePermissionsTable() {
            const ls = localStorage.getItem('my_app_token');
            this.table = await new Tabulator(this.$refs.permissionsTable, {
                ajaxURL: '/api/v1/admin/permission?limit=20',
                ajaxConfig: {
                    headers: {
                        Authorization: `Bearer ${ls}`,
                        "X-Unit-Relation-ID": this.$store.state.activeAdminUnit?.unit_relation_id ?? ''
                    },
                },
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
                layout: 'fitColumns',
                columns: [
                    {
                        title: 'No',
                        field: '',
                        formatter: 'rownum',
                        width: 100,
                    },
                    {
                        title: 'Permission',
                        field: 'name',
                        headerFilter:"input"
                    }
                ],
                paginationMode: 'remote',
                progressiveLoad: 'scroll',
                height: 650,
                paginationSize: 20,
                paginationSizeSelector: [10, 20, 50, 100],
                headerFilter: true,
                rowFormatter: (row) => {

                },
            });
        },
        createPermission() {
            this.$router.push({name: 'permission-create'});
        },
    }
}
</script>
