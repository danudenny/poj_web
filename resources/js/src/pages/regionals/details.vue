<template>
    <div class="container-fluid">
        <Breadcrumbs :title="$route.name"/>
        <div class="col-sm-12">
            <div class="card">
                <div className="card-header bg-primary">
                    <div class="d-flex justify-content-between">
                        <h5>{{item.name}}</h5>
                        <button class="btn btn-sm btn-outline-warning" @click="$router.push('/kanwil')">
                            <i class="icofont icofont-double-left"></i>&nbsp;Back
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <ul class="nav nav-pills nav-primary" id="pills-icontab" role="tablist">
                                <li class="nav-item"><a class="nav-link active" id="pills-iconhome-tab" data-bs-toggle="pill" href="#pills-iconhome" role="tab" aria-controls="pills-iconhome" aria-selected="true"><i class="icofont icofont-info"></i>Basic Information</a></li>
                                <li class="nav-item"><a class="nav-link" id="pills-operating-unit-tab" data-bs-toggle="pill" href="#pills-operating-unit" role="tab" aria-controls="pills-operating-unit" aria-selected="false"><i class="icofont icofont-tools"></i>Operating Unit</a></li>
                              <li class="nav-item"><a class="nav-link" id="pills-employee-tab" data-bs-toggle="pill" href="#pills-employee" role="tab" aria-controls="pills-employee" aria-selected="false"><i class="icofont icofont-users"></i>Employee</a></li>
                            </ul>
                        </div>
                    </div>
                    <div class="tab-content" id="pills-icontabContent">
                        <hr>
                        <div class="tab-pane fade show active" id="pills-iconhome" role="tabpanel" aria-labelledby="pills-iconhome-tab">
                            <div>
                                <button type="button" v-if="!editing" class="btn btn-warning" @click="onEditData">
                                    <i class="fa fa-pencil-square"></i> Edit Data
                                </button>
                                <div v-else class="d-flex justify-content-end column-gap-2">
                                    <button type="button" class="btn btn-success" @click="onSaveData">
                                        <i class="fa fa-save"></i> Save Data
                                    </button>
                                    <button type="button" class="btn btn-danger" @click="onCloseEdit">
                                        <i class="fa fa-times-circle"></i> Cancel Edit
                                    </button>
                                </div>
                            </div>
                            <hr>
                            <div class="row">
                                <div class="col-md-12">
                                    <div class="mb-3">
                                        <label class="form-label">Name</label>
                                        <input class="form-control" type="text" v-model="item.name" :disabled="!editing">
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="tab-pane fade show" id="pills-operating-unit" role="tabpanel" aria-labelledby="pills-operating-unit">
                            <OperatingUnit :id="this.$route.params.id"/>
                        </div>
                        <div class="tab-pane fade" id="pills-employee" role="tabpanel" aria-labelledby="pills-employee-tab">
                            <Employee :id="this.$route.params.id"/>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import {TabulatorFull as Tabulator} from "tabulator-tables";
import OperatingUnit from "./operating-unit.vue";
import Employee from "./employee.vue";

export default {
    components: {Employee, OperatingUnit},
    data() {
        return {
            item: [],
            childs: [],
            loading: false,
            editing: false
        }
    },
    async mounted() {
        await this.getKantorPerwakilan();
        this.initializeRegionalTable();
    },
    methods: {
        async getKantorPerwakilan() {
            this.loading = true;
            await this.$axios.get(`/api/v1/admin/unit/detail/${this.$route.params.id}`)
                .then(response => {
                    this.item = response.data.data;
                    console.log(this.item)
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
        onEditData() {
            this.editing = !this.editing
        },
        onSaveData() {
            this.onEditData()
        },
        onCloseEdit() {
            this.onEditData()
        }
    }
};
</script>
