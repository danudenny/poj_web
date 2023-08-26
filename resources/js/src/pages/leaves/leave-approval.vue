<template>
    <div class="container-fluid">
        <Breadcrumbs main="Leave Approval"/>

        <div class="container-fluid">
            <div class="email-wrap bookmark-wrap">
                <div class="row">
                    <div class="col-md-12">
                        <div class="card card-absolute">
                            <div class="card-header bg-primary">
                                <h5>Leave Request Approval</h5>
                            </div>
                            <div class="card-body">
                                <div ref="leaveApprovalTable"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="modal fade" id="approvalModal" ref="approvalModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalCenter" aria-hidden="true">
            <VerticalModal title="Approval Modal" @save="onApproval()">
                <div class="row">
                    <div class="col-md-12">
                        <div class="mt-2">
                            <label for="status">Status:</label>
                            <select id="status" name="status" class="form-select" v-model="approval.status" required disabled>
                                <option value="approved" :selected="approval.status === 'approved' ? 'selected' : ''">Approve</option>
                                <option value="rejected" :selected="approval.status === 'rejected' ? 'selected' : ''">Reject</option>
                            </select>
                        </div>
                        <div class="mt-2" v-if="approval.status === 'rejected'">
                            <label for="name">Note:</label>
                            <input type="text" class="form-control" id="reason" v-model="approval.notes" required>
                        </div>
                    </div>
                </div>
            </VerticalModal>
        </div>
    </div>
</template>
<script>
import VerticalModal from "@components/modal/verticalModal.vue";
import {TabulatorFull as Tabulator} from 'tabulator-tables';
import moment from "moment";
import {useToast} from "vue-toastification";

export default {
    components: {
        VerticalModal,
    },
    data() {
        return {
            table: null,
            filterName: '',
            filterType: '',
            currentPage: 1,
            pageSize: 10,
            approval: {
                leave_request_id: null,
                status: null,
                notes: ""
            }
        }
    },
    mounted() {
        this.generateLeaveApprovalTable();
    },
    methods: {
        generateLeaveApprovalTable() {
            const ls = localStorage.getItem('my_app_token');
            this.table = new Tabulator(this.$refs.leaveApprovalTable ,{
                paginationCounter:"rows",
                ajaxURL: '/api/v1/admin/leave_request/approvals',
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
                        data: response.data.data,
                        last_page: response.data.last_page,
                    }
                },
                ajaxURLGenerator: (url, config, params) => {
                    params.filter.map((item) => {
                        if (item.field === 'leave_name') this.filterName = item.value
                        if (item.field === 'leave_type') this.filterType = item.value
                    })
                    return `${url}?page=${params.page}&per_page=${params.size}&append=is_can_approve,real_status&leave_name=${this.filterName}&leave_type=${this.filterType}`
                },
                layout: 'fitColumns',
                renderHorizontal:"virtual",
                height: '100%',
                columns: [
                    {
                        title: 'No',
                        field: '',
                        formatter: 'rownum',
                        hozAlign: 'center',
                        headerHozAlign: 'center',
                        headerSort: false,
                        width: 50,
                        frozen: true
                    },
                    {
                        title: 'Employee Name',
                        field: 'leave_request.employee.name',
                        headerHozAlign: 'center',
                        headerSort: false,
                        width: 250,
                        frozen: true,
                        formatter: function (cell) {
                            return `<span class="text-success" title="Go To Details"><b>${cell.getValue()}</b></span>`
                        },
                        cellClick: (e, cell) => {
                            this.$router.push({
                                name: 'leave-request-details',
                                params: {
                                    id: cell.getRow().getData().id
                                }
                            })
                        }
                    },
                    {
                        title: 'Unit',
                        field: 'leave_request.employee.last_unit.name',
                        headerHozAlign: 'center',
                        headerSort: false,
                    },
                    {
                        title: 'Date',
                        headerHozAlign: 'center',
                        headerSort: false,
                        columns: [
                            {
                                title: 'Start Date',
                                field: 'leave_request.start_date',
                                hozAlign: 'center',
                                headerHozAlign: 'center',
                                headerSort: false,
                                formatter: function (cell) {
                                    return moment(cell.getValue()).format('DD MMMM YYYY')
                                }
                            },
                            {
                                title: 'End Date',
                                field: 'leave_request.end_date',
                                hozAlign: 'center',
                                headerHozAlign: 'center',
                                headerSort: false,
                                formatter: function (cell) {
                                    return moment(cell.getValue()).format('DD MMMM YYYY')
                                }
                            },
                            {
                                title: 'Days',
                                field: 'leave_request.days',
                                hozAlign: 'center',
                                headerHozAlign: 'center',
                                headerSort: false,
                                formatter: function (cell) {
                                    return `<span class="badge badge-primary">${cell.getValue()}</span>`
                                }
                            },
                        ]
                    },
                    {
                        title: 'Leave Category',
                        field: 'leave_request.leave_type.leave_name',
                        headerHozAlign: 'center',
                        headerSort: false,
                    },
                    {
                        title: 'Leave Type',
                        field: 'leave_request.leave_type.leave_type',
                        hozAlign: 'center',
                        headerHozAlign: 'center',
                        headerSort: false,
                        formatter: function (cell) {
                            if (cell.getValue() === 'leave') {
                                return '<span class="badge badge-warning">Cuti</span>'
                            } else if (cell.getValue() === 'permit') {
                                return '<span class="badge badge-danger">Izin</span>'
                            }
                        }
                    },
                    {
                        title: 'Status',
                        field: 'real_status',
                        headerHozAlign: 'center',
                        headerSort: false,
                        formatter: function (cell) {
                            if (cell.getValue() === 'pending') {
                                return '<span class="badge badge-info">Pending</span>'
                            } else if (cell.getValue() === 'approved') {
                                return '<span class="badge badge-success">Approved</span>'
                            } else if (cell.getValue() === 'rejected') {
                                return '<span class="badge badge-danger">Rejected</span>'
                            } else {
                                return '<span class="badge badge-warning">Waiting Last Approval</span>'
                            }
                        }
                    },
                    {
                        title: 'Approval',
                        formatter: (cell, formatterParams, onRendered) => {
                            const rowData = cell.getRow().getData();
                            if (rowData.is_can_approve) {
                                return `
                                    <button class="button-icon button-success" data-bs-toggle="modal" data-bs-target="#approvalModal" data-action="approved" data-row-id="${rowData.leave_request.id}"><i data-action="approved" class="fa fa-check-circle"></i> </button>
                                    <button class="button-icon button-danger" data-bs-toggle="modal" data-bs-target="#approvalModal" data-action="rejected" data-row-id="${rowData.leave_request.id}"><i data-action="rejected" class="fa fa-minus-circle"></i> </button>
                                 `;
                            } else {
                                return '';
                            }
                        },
                        width: 150,
                        hozAlign: 'center',
                        sortable: false,
                        cellClick: (e, cell) => {
                            this.onApprovalButtonClicked(e, cell);
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
        onApprovalButtonClicked(e, cell) {
            const action = e.target.dataset.action
            const rowData = cell.getRow().getData();

            this.approval.leave_request_id = rowData.leave_request_id
            this.approval.status = action
        },
        onApproval() {
            this.$swal({
                icon: 'warning',
                title:"Are you sure want to do approval?",
                text:'Once doing approval, you will not be able to revert the status!',
                showCancelButton: true,
                confirmButtonText: 'Yes!',
                confirmButtonColor: '#126850',
                cancelButtonText: 'Cancel',
                cancelButtonColor: '#efefef',
            }).then((result)=>{
                if(result.value){
                    this.$axios.post(`api/v1/admin/leave_request/approval/${this.approval.leave_request_id}`, this.approval)
                        .then(() => {
                            this.generateLeaveApprovalTable()
                            useToast().success("Success to do approval!");
                        })
                        .catch(error => {
                            useToast().error(error.response.data.message);
                        });
                }
            });
        }
    }
}
</script>
