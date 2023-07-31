<template>
    <div class="container-fluid">
        <Breadcrumbs main="Leave Master"/>

        <div class="container-fluid">
            <div class="email-wrap bookmark-wrap">
                <div class="row">
                    <div class="col-md-12">
                        <div class="card card-absolute">
                            <div class="card-header bg-primary">
                                <h5>Leave Master</h5>
                            </div>
                            <div class="card-body">
                                <div class="d-flex justify-content-end mb-2">
                                    <button @click="openModal" class="btn btn-success">
                                        <i class="fa fa-plus-circle" /> &nbsp; Create Master Leave
                                    </button>
                                </div>
                                <div ref="leaveMasterTable"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div>
        <Modal :visible="isModalVisible" @save="saveChanges" :title="modalTitle" @update:visible="isModalVisible = $event">
            <div class="row">
                <div class="col-md-12">
                    <div>
                        <label for="name">Name:</label>
                        <input type="text" class="form-control" id="name" v-model="leave.leave_name" required>
                    </div>

                    <div>
                        <label for="name">Code:</label>
                        <input type="text" class="form-control" id="name" v-model="leave.leave_code" required>
                    </div>

                    <div class="mt-3">
                        <label for="name">Type :</label>
                        <select id="status" class="form-select" v-model="leave.leave_type" required>
                            <option value='sick'>Izin</option>
                            <option value='leave'>Cuti</option>
                        </select>
                    </div>
                </div>
            </div>
        </Modal>
    </div>
</template>
<script>
import {TabulatorFull as Tabulator} from 'tabulator-tables';
import {useToast} from "vue-toastification";
import Modal from "@components/modal.vue";

export default {
    components: {Modal},
    data() {
        return {
            modalTitle: 'Create Master Leave',
            table: null,
            filterName: '',
            filterType: '',
            currentPage: 1,
            pageSize: 10,
            isModalVisible: false,
            leave: {
                leave_name: '',
                leave_type: '',
                leave_code: '',
            }
        }
    },
    mounted() {
        this.masterLeaveTable();
    },
    methods: {
        openModal() {
            this.modalTitle = 'Create Master Leave'
            this.isModalVisible = true
        },
        masterLeaveTable() {
            const ls = localStorage.getItem('my_app_token');
            this.table = new Tabulator(this.$refs.leaveMasterTable ,{
                paginationCounter:"rows",
                ajaxURL: '/api/v1/admin/master_leave',
                ajaxConfig: {
                    headers: {
                        Authorization: `Bearer ${ls}`,
                    }
                },
                ajaxParams: {
                    page: this.currentPage,
                    size: this.pageSize,
                },
                ajaxResponse: function (url, params, response) {
                    return {
                        data: response.data,
                        last_page: response.last_page,
                    }
                },
                ajaxURLGenerator: (url, config, params) => {
                    params.filter.map((item) => {
                        if (item.field === 'leave_name') this.filterName = item.value
                        if (item.field === 'leave_type') this.filterType = item.value
                    })
                    return `${url}?page=${params.page}&per_page=${params.size}&leave_name=${this.filterName}&leave_type=${this.filterType}`
                },
                layout: 'fitColumns',
                columns: [
                    {
                        title: 'No',
                        field: '',
                        formatter: 'rownum',
                        hozAlign: 'center',
                        headerHozAlign: 'center',
                        headerSort: false,
                        width: 50,
                    },
                    {
                        title: 'Leave Name',
                        field: 'leave_name',
                        headerFilter: true,
                        headerHozAlign: 'center',
                        headerSort: false,
                    },
                    {
                        title: 'Leave Type',
                        field: 'leave_type',
                        headerFilter: true,
                        hozAlign: 'center',
                        headerHozAlign: 'center',
                        headerSort: false,
                        formatter: function (cell) {
                            if (cell.getValue() === 'leave') {
                                return '<span class="badge badge-warning">Cuti</span>'
                            } else if (cell.getValue() === 'sick') {
                                return '<span class="badge badge-danger">Izin</span>'
                            }
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
            })
        },
        saveChanges() {
            this.$axios.post('/api/v1/admin/master_leave/create', this.leave)
                .then(() => {
                    this.isModalVisible = false;
                    useToast().success('Master Leave Created Successfully');
                    this.table.setData();
                })
                .catch(() => {
                    useToast().error('Something went wrong');
                })
        }
    }
}
</script>
