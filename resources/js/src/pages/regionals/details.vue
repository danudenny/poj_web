<template>
    <div class="container-fluid">
        <Breadcrumbs title="Regional Detail"/>
        <div class="col-sm-12">
            <form class="card">
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-5">

                            <div class="mb-3">
                                <label class="form-label">Name</label>
                                <input class="form-control" type="text" v-model="item.name" disabled>
                            </div>
                        </div>
                        <hr>
                        <div class="col-md-12">
                            <div v-if="loading" class="text-center">
                                <img src="../../assets/loader.gif" alt="loading" width="100">
                            </div>
                            <div ref="regionalTable"></div>
                        </div>
                    </div>
                </div>
                <div class="card-footer text-end">
                    <button class="btn btn-secondary" @click="$router.push('/regionals')">Back</button>
                </div>
            </form>
        </div>
    </div>
</template>

<script>
import axios from 'axios';
import { useRoute } from 'vue-router';
import {TabulatorFull as Tabulator} from "tabulator-tables";
export default {
    data() {
        return {
            item: [],
            childs: [],
            loading: false,
        }
    },
    async mounted() {
        await this.getUnit();
        this.initializeRegionalTable();
    },
    methods: {
        async getUnit() {
            this.loading = true;
            await this.$axios.get(`/api/v1/admin/unit/view?id=${this.$route.params.id}`)
                .then(response => {
                    this.item = response.data.data;
                    this.childs = response.data.data.child;
                })
                .catch(error => {
                    console.error(error);
                });
        },
        initializeRegionalTable() {
            const table = new Tabulator(this.$refs.regionalTable, {
                data: this.childs,
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
                        headerFilter:"input",
                        formatter: function(row) {
                            return row.getData().name;
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
    }
};
</script>
