<template>
    <Breadcrumbs main="Approvals / Approval" />

    <div class="container-fluid">
        <div class="email-wrap bookmark-wrap">
            <div class="row">
                <div class="col-md-12">
                    <div class="card card-absolute">
                        <div class="card-header bg-primary">
                            <h5>Approval List</h5>
                        </div>
                        <div class="card-body">
                            <div class="d-flex justify-content-end">
                                <button class="btn btn-primary" type="button" @click="createData">
                                    <i class="fa fa-plus"></i> &nbsp; Create
                                </button>
                            </div>
                            <hr>
                            <div class="d-flex justify-content-start row mb-3" v-if="false">
                                <div class="col-md-4">
                                    <multiselect
                                        v-model="selectedWorkingArea"
                                        placeholder="Select Working Area"
                                        label="name"
                                        track-by="id"
                                        :options="workingArea"
                                        :multiple="false"
                                        :close-on-select="true"
                                        @select="onSelectWorkingArea"
                                        >
                                    </multiselect>
                                </div>
                                <div class="col-md-4">
                                    <multiselect
                                        v-model="selectedApprovalModule"
                                        placeholder="Select Approval Module"
                                        label="name"
                                        track-by="id"
                                        :options="approvalModule"
                                        :multiple="false"
                                        :close-on-select="true"
                                        @select="onSelectApprovalModule"
                                    >
                                    </multiselect>
                                </div>
                                <div class="col-md-4">
                                    <button class="btn btn-warning" type="button" @click="resetFilter">
                                        <i class="fa fa-refresh"></i> &nbsp; Reset Filter
                                    </button>
                                </div>
                            </div>
                            <div v-if="loading" class="text-center">
                                <img src="../../assets/loader.gif" alt="loading" width="100">
                            </div>
                            <div ref="approvalTable"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import {useToast} from "vue-toastification"
import VerticalModal from "@components/modal/verticalModal.vue";
import {TabulatorFull as Tabulator} from 'tabulator-tables';

export default {
    components: {
        VerticalModal
    },
    data() {
        return {
            approvals: [],
            table: null,
            loading: false,
            currentPage: 1,
            pageSize: 10,
            filterName: '',
            filterWorkingArea: '',
            filterApprovalModule: '',
            workingArea: [],
            selectedWorkingArea: [],
            selectedApprovalModule: [],
            approvalModule: [],
        }
    },
    async mounted() {
        this.initApprovalTable();
        await this.getWorkingArea();
        await this.getApprovalModule()
    },
    methods: {
        initApprovalTable() {
            const ls = localStorage.getItem('my_app_token')
            this.table = new Tabulator(this.$refs.approvalTable, {
                paginationCounter:"rows",
                ajaxURL: '/api/v1/admin/approval',
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
                ajaxResponse: function (url, params, response) {
                    return {
                        data: response.data.data,
                        last_page: response.data.last_page,
                    }
                },
                layout: 'fitColumns',
                renderHorizontal:"virtual",
                height: '100%',
                frozenColumn:2,
                columns: [
                    {
                        title: 'No',
                        field: '',
                        formatter: 'rownum',
                        hozAlign: 'center',
                        headerSort: false,
                        headerHozAlign: 'center',
                        width: 50,
                    },
                    {
                        title: 'Name',
                        field: 'name',
                        headerSort: false,
                        headerHozAlign: 'center',
                    },
                    {
                        title: 'Approval Module',
                        field: 'approval_module.name',
                        clearable:true,
                        hozAlign: 'center',
                        headerSort: false,
                        headerHozAlign: 'center',
                    },
                    {
                        title: 'Units',
                        field: 'unit.name',
                        hozAlign: 'center',
                        headerSort: false,
                        headerHozAlign: 'center',
                    },
                    {
                        title: 'Levels',
                        field: '',
                        headerHozAlign: 'center',
                        hozAlign: 'center',
                        width: 70,
                        headerSort: false,
                        formatter: function(cell){
                            let data = cell.getRow().getData().total_approval_user;
                            return `<span class="badge badge-primary">${data}</span> `;;
                        }
                    },
                    {
                        title: '',
                        formatter: this.viewDetailsFormatter,
                        width: 150,
                        headerSort: false,
                        hozAlign: 'center',
                        cellClick: (e, cell) => {
                            this.handleActionButtonClick(e, cell);
                        }
                    },
                ],
                pagination: true,
                paginationMode: 'remote',
                filterMode:"remote",
                paginationSize: this.pageSize,
                paginationSizeSelector: [10, 20, 50, 100],
                headerFilter: true,
                paginationInitialPage:1,
                placeholder: 'No Data Available'
            });
            this.loading = false;
        },
        viewDetailsFormatter(cell) {
            const rowData = cell.getRow().getData();
            return `
                <button class="button-icon button-success" data-action="view" data-row-id="${rowData.id}"><i data-action="view" class="fa fa-eye"></i> </button>
                <button class="button-icon button-warning" data-action="edit" data-row-id="${rowData.id}"><i data-action="edit" class="fa fa-pencil"></i> </button>
                <button class="button-icon button-danger" data-action="delete" data-row-id="${rowData.id}"><i data-action="delete" class="fa fa-trash"></i> </button>
             `;
        },
        handleActionButtonClick(e, cell) {
            const action = e.target.dataset.action
            const rowData = cell.getRow().getData();

            if (action === 'edit') {
                this.$router.push({
                    name: 'approval_edit',
                    params: { id: rowData.id },
                    query: { unit_id: rowData.unit.id }
                })
            } else if (action === 'view') {
                this.$router.push({
                    name: 'approval_details',
                    params: { id: rowData.id },
                })
            } else if (action === 'delete') {
                this.basic_warning_alert(rowData.id);
            }
        },
        async getWorkingArea() {
            await this.$axios.get('api/v1/admin/unit/related-unit')
                .then(response => {
                    this.workingArea = response.data.data;
                })
                .catch(error => {
                    console.log(error);
                });
        },
        async getApprovalModule() {
            await this.$axios.get('api/v1/admin/approval-module')
                .then(response => {
                    this.approvalModule = response.data.data.data;
                })
                .catch(error => {
                    console.log(error);
                });
        },
        onSelectWorkingArea() {
            this.table.setData(`/api/v1/admin/approval?unit_id=${this.selectedWorkingArea.id}&approval_module_id=${this.selectedApprovalModule.id || ''}`);
        },
        onSelectApprovalModule() {
            this.table.setData(`/api/v1/admin/approval?unit_id=${this.selectedWorkingArea.id || ''}&approval_module_id=${this.selectedApprovalModule.id}`);
        },
        resetFilter() {
            this.selectedWorkingArea = [];
            this.selectedApprovalModule = [];
            this.table.setData(`/api/v1/admin/approval`);
        },
        createData() {
            this.$router.push({ path: '/approval/create' })
        },
        redrawTable() {
            this.$nextTick(() => {
                this.table.redraw(true);
            });
        },
        basic_warning_alert:function(id){
            this.$swal({
                icon: 'warning',
                title:"Delete Data?",
                text:'Once deleted, you will not be able to recover the data!',
                showCancelButton: true,
                confirmButtonText: 'Yes, delete it!',
                confirmButtonColor: '#e64942',
                cancelButtonText: 'Cancel',
                cancelButtonColor: '#efefef',
            }).then((result)=>{
                if(result.value){
                    this.$axios.delete(`api/v1/admin/approval/delete/${id}`)
                        .then(() => {
                            const pluck = this.table.getData().filter((item) => item.id !== id);
                            this.loading = true
                            this.table.setData(pluck);
                            this.redrawTable();
                            this.loading = false
                            useToast().success("Data successfully deleted!");
                        })
                        .catch(error => {
                            useToast().error(error.response.message);
                        });
                }
            });
        },

    }
}
</script>

<style scoped>
.table thead th {
    text-align: center;
    font-weight: bold;
    background-color: #0A5640;
    color: #fff;
}

.table tbody td:last-child {
    text-align: center;
}
.table tbody td:first-child {
    text-align: center;
}

.badge {
    font-size: 12px;
    padding: 5px 10px;
    border-radius: 20px;
    font-weight: 500;
    text-transform: uppercase;
    letter-spacing: 1px;
    margin: 0 5px;
}

.badge-success {
    background-color: #28a745;
    color: #fff
}

.badge-danger {
    background-color: #dc3545;
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

.button-success:hover {
    background-color: #218838;
    color: #fff
}

.button-danger {
    background-color: #dc3545;
    color: #fff
}

.button-danger:hover {
    background-color: #c82333;
    color: #fff
}

.button-info {
    background-color: #17a2b8;
    color: #fff
}

.button-info:hover {
    background-color: #138496;
    color: #fff
}
</style>
