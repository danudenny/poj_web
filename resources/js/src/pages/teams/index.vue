<template>
    <div class="container-fluid">
        <Breadcrumbs main="Teams"/>

        <div class="container-fluid">
            <div class="email-wrap bookmark-wrap">
                <div class="row">
                    <div class="col-md-12">
                        <div class="card card-absolute">
                            <div class="card-header bg-primary">
                                <h5>Teams</h5>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-12">
                                        <div class="d-flex justify-content-between">
                                            <div class="d-flex justify-content-start col-md-8 column-gap-2">
                                                <div class="col-md-6">
                                                    <input
                                                        type="text"
                                                        class="form-control"
                                                        id="filter-name"
                                                        v-model="filterName"
                                                        @keyup="handleFilterName"
                                                        placeholder="Filter by name"
                                                    >
                                                </div>
                                                <div class="col-md-4">
                                                    <button class="btn btn-warning" @click="resetFilter">
                                                        <i class="fa fa-rotate-left"></i>
                                                        &nbsp; Reset Filter
                                                    </button>
                                                </div>
                                            </div>
                                            <div>
                                                <button class="btn btn-success" @click="createTeam">
                                                    <i class="fa fa-plus-circle"></i>
                                                    &nbsp; Create Teams
                                                </button>
                                            </div>
                                        </div>
                                        <hr>
                                        <div ref="teamTable"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <SmallModal
            :visible="isModalVisible"
            :title="modalTitle"
            @update:visible="isModalVisible = $event"
            @save="saveChanges"
        >
            <div class="row">
                <div class="col-md-12">
                    <div class="mb-2">
                        <label for="name">Team Name:</label>
                        <input type="text" class="form-control" id="name" v-model="team.name" required>
                    </div>
                    <div>
                        <label for="name">Description:</label>
                        <textarea class="form-control" id="description" v-model="team.description" rows="2" required></textarea>
                    </div>
                </div>
            </div>
        </SmallModal>
        <SmallModal
            :visible="isModalUpdateVisible"
            :title="modalUpdateTitle"
            @update:visible="isModalUpdateVisible = $event"
            @save="updateChanges"
        >
            <div class="row">
                <div class="col-md-12">
                    <div class="mb-2">
                        <label for="name">Team Name:</label>
                        <input type="text" class="form-control" id="name" v-model="team.name" required>
                    </div>
                    <div>
                        <label for="name">Description:</label>
                        <textarea class="form-control" id="description" v-model="team.description" rows="2" required></textarea>
                    </div>
                </div>
            </div>
        </SmallModal>
    </div>
</template>
<script>
import {TabulatorFull as Tabulator} from 'tabulator-tables';
import SmallModal from "../../components/small_modal_with_save.vue";
import {useToast} from "vue-toastification";
export default {
    components: {
        SmallModal
    },
    data() {
        return {
            table: null,
            currentPage: 1,
            pageSize: 10,
            filterName: '',
            isModalVisible: false,
            isModalUpdateVisible: false,
            modalTitle: 'Create new Team',
            modalUpdateTitle: 'Update Team',
            team: {
                name: '',
                description: '',
            },
        }
    },
    mounted() {
        this.initialTeamTable();
    },
    methods: {
        createTeam() {
            this.team.name = '';
            this.team.description = '';
            this.isModalVisible = true;
        },
        updateTeam(row) {
            this.isModalUpdateVisible = true;
            this.team = row.getData();
        },
        resetFilter() {
            this.filterName = ''
            this.table.clearFilter()
        },
        handleFilterName() {
            this.table.setFilter('name', 'like', this.filterName)
        },
        initialTeamTable() {
            const ls = localStorage.getItem('my_app_token');
            this.table = new Tabulator(this.$refs.teamTable, {
                paginationCounter:"rows",
                ajaxURL: '/api/v1/admin/team',
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
                ajaxURLGenerator: (url, config, params) => {
                    params.filter.map((item) => {
                        if (item.field === 'name') this.filterName = item.value
                    })
                    return `${url}?page=${params.page}&per_page=${params.size}&name=${this.filterName}`
                },
                layout: 'fitColumns',
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
                        hozAlign: 'center',
                    },
                    {
                        title: 'Description',
                        field: 'description',
                        hozAlign: 'center',
                        headerSort: false,
                        headerHozAlign: 'center',
                    },
                    {
                        title: 'Total Departments',
                        field: 'departments_count',
                        hozAlign: 'center',
                        headerSort: false,
                        headerHozAlign: 'center',
                        formatter: (cell) => {
                            const rowData = cell.getRow().getData();
                            if (rowData.departments_count > 0) {
                                return `<span class="badge badge-success">${rowData.departments_count}</span>`
                            } else {
                                return `<span class="badge badge-danger">${rowData.departments_count}</span>`
                            }
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
                filterMode:"remote",
                paginationSize: this.pageSize,
                paginationSizeSelector: [10, 20, 50, 100],
                headerFilter: true,
                paginationInitialPage:1,
                placeholder: 'No Data Available'
            })
        },
        viewDetailsFormatter(cell) {
            const rowData = cell.getRow().getData();
            return `
                <button class="button-icon button-warning" data-action="edit" data-row-id="${rowData.id}"><i data-action="edit" class="fa fa-pencil"></i> </button>
                <button class="button-icon button-danger" data-action="delete" data-row-id="${rowData.id}"><i data-action="delete" class="fa fa-trash"></i> </button>
             `;
        },
        handleActionButtonClick(e, cell) {
            const action = e.target.dataset.action
            const rowData = cell.getRow().getData();

            if (action === 'edit') {
                this.updateTeam(cell.getRow());
            } else if (action === 'delete') {
                this.basic_warning_alert(rowData.id);
            }
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
                    this.$axios.delete(`api/v1/admin/team/delete/${id}`)
                        .then(() => {
                            const pluck = this.table.getData().filter((item) => item.id !== id);
                            this.loading = true
                            this.table.setData(pluck);
                            this.loading = false
                            useToast().success("Data successfully deleted!");
                        })
                        .catch(error => {
                            useToast().error(error.response.data.message);
                        });
                }
            });
        },
        async saveChanges() {
            await this.$axios.post('/api/v1/admin/team/create', this.team)
                .then((response) => {
                    this.table.setData();
                    this.team.name = '';
                    this.team.description = '';
                    this.isModalVisible = false;
                    useToast().success(response.data.message);
                })
                .catch((err) => {
                    this.isModalVisible = true;
                    useToast().error(err.response.data.message);
                })
        },
        updateChanges() {
            this.$axios.put(`/api/v1/admin/team/update/${this.team.id}`, this.team)
                .then((response) => {
                    this.table.setData();
                    this.team.name = '';
                    this.team.description = '';
                    this.isModalUpdateVisible = false;
                    useToast().success(response.data.message);
                })
                .catch((err) => {
                    this.isModalUpdateVisible = true;
                    useToast().error(err.response.data.message);
                })
        }
    }
}
</script>
