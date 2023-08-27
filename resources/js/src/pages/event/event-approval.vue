<template>
    <div class="container-fluid">
        <Breadcrumbs main="Event Approval"/>

        <div class="container-fluid">
            <div class="email-wrap bookmark-wrap">
                <div class="row">
                    <div class="col-md-12">
                        <div class="card card-absolute">
                            <div class="card-header bg-primary">
                                <h5>Event Approval</h5>
                            </div>
                            <div class="card-body">
                                <div ref="eventApprovalTable"></div>
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
            pagination: {
                pageSize: 10,
                currentPage: 1
            },
            approval: {
                event_id: null,
                status: null,
                notes: ""
            }
        }
    },
    mounted() {
        this.generateEventApprovalTable()
    },
    methods: {
        generateEventApprovalTable() {
            const ls = localStorage.getItem('my_app_token');
            this.table = new Tabulator(this.$refs.eventApprovalTable ,{
                paginationCounter:"rows",
                ajaxURL: '/api/v1/admin/event/approvals',
                ajaxConfig: {
                    headers: {
                        Authorization: `Bearer ${ls}`,
                        "X-Unit-Relation-ID": this.$store.state.activeAdminUnit?.unit_relation_id ?? '',
                        "X-Selected-Role": this.$store.state.currentRole,
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
                    return `${url}?page=${params.page}&per_page=${params.size}`
                },
                layout: 'fitData',
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
                        width: 50
                    },
                    {
                        title: 'Requestor Name',
                        field: 'event.requestor_employee.name',
                        hozAlign: 'center',
                        headerHozAlign: 'center',
                        headerSort: false,
                    },
                    {
                        title: 'Approver Name',
                        field: 'employee.name',
                        hozAlign: 'center',
                        headerHozAlign: 'center',
                        headerSort: false,
                    },
                    {
                        title: 'Title',
                        field: 'event.title',
                        hozAlign: 'center',
                        headerHozAlign: 'center',
                        headerSort: false,
                    },
                    {
                        title: 'Need Presence?',
                        field: 'event.is_need_absence',
                        hozAlign: 'center',
                        headerHozAlign: 'center',
                        headerSort: false,
                        formatter: function (cell) {
                            console.log()
                            if (cell.getValue()) {
                                return `<button class="button-icon button-success"><i class="fa fa-check-circle"></i> </button>`
                            } else {
                                return `<button class="button-icon button-danger"><i class="fa fa-minus-circle"></i> </button>`
                            }
                        }
                    },
                    {
                        title: 'Location',
                        field: 'event.latitude',
                        hozAlign: 'center',
                        headerHozAlign: 'center',
                        headerSort: false,
                        formatter: function (cell) {
                            const event = cell.getRow().getData().event
                            return `<a href="https://maps.google.com/maps?q=${event.latitude},${event.longitude}&z=4" target="_blank"><button class="button-icon button-success"><i data-action="approved" class="fa fa-map"></i> </button></a>`
                        }
                    },
                    {
                        title: 'Event Schedule',
                        field: 'event.event_repeat_description',
                        hozAlign: 'center',
                        headerHozAlign: 'center',
                        headerSort: false,
                    },
                    {
                        title: 'Event Type',
                        field: 'event.event_type',
                        hozAlign: 'center',
                        headerHozAlign: 'center',
                        headerSort: false,
                        formatter: function (cell) {
                            if (cell.getValue() === 'anggaran') {
                                return `<span class="badge badge-primary">Anggaran</span>`
                            } else {
                                return `<span class="badge badge-secondary">Non Anggaran</span>`
                            }
                        }
                    },
                    {
                        title: 'Event Type',
                        field: 'event.location_type',
                        hozAlign: 'center',
                        headerHozAlign: 'center',
                        headerSort: false,
                        formatter: function (cell) {
                            if (cell.getValue() === 'external') {
                                return `<span class="badge badge-primary">External</span>`
                            } else {
                                return `<span class="badge badge-secondary">Internal</span>`
                            }
                        }
                    },
                    {
                        title: 'Status Event',
                        field: 'event.last_status',
                        headerHozAlign: 'center',
                        headerSort: false,
                        formatter: function (cell) {
                            if (cell.getValue() === 'pending') {
                                return '<span class="badge badge-info">Pending</span>'
                            } else if (cell.getValue() === 'approve') {
                                return '<span class="badge badge-success">Approved</span>'
                            } else if (cell.getValue() === 'reject') {
                                return '<span class="badge badge-danger">Rejected</span>'
                            } else {
                                return '<span class="badge badge-warning">Waiting Last Approval</span>'
                            }
                        }
                    },
                    {
                        title: 'Status Approval',
                        field: 'real_status',
                        headerHozAlign: 'center',
                        headerSort: false,
                        formatter: function (cell) {
                            let value = cell.getValue()
                            if (value === 'pending') {
                                return '<span class="badge badge-info">Pending</span>'
                            } else if (value === 'approved') {
                                return '<span class="badge badge-success">Approved</span>'
                            } else if (value === 'rejected') {
                                return '<span class="badge badge-danger">Rejected</span>'
                            } else {
                                return '<span class="badge badge-warning">Waiting Last Approval</span>'
                            }
                        }
                    },
                    {
                        title: 'Action',
                        formatter: (cell, formatterParams, onRendered) => {
                            const rowData = cell.getRow().getData().event;
                            if (rowData.is_can_approve) {
                                return `
                                    <button class="button-icon button-warning" data-action="view" data-row-id="${rowData.id}"><i data-action="view" class="fa fa-eye"></i> </button>
                                    <button class="button-icon button-success" data-bs-toggle="modal" data-bs-target="#approvalModal" data-action="approved" data-row-id="${rowData.id}"><i data-action="approved" class="fa fa-check-circle"></i> </button>
                                    <button class="button-icon button-danger" data-bs-toggle="modal" data-bs-target="#approvalModal" data-action="rejected" data-row-id="${rowData.id}"><i data-action="rejected" class="fa fa-minus-circle"></i> </button>
                                 `;
                            } else {
                                return '<button class="button-icon button-warning" data-action="view" data-row-id="${rowData.id}"><i data-action="view" class="fa fa-eye"></i> </button>';
                            }
                        },
                        width: 150,
                        hozAlign: 'center',
                        sortable: false,
                        cellClick: this.onActionButtonClicked
                    },
                ],
                placeholder: 'No Data Available',
                pagination: true,
                paginationMode: 'remote',
                filterMode:"remote",
                paginationSize: this.pagination.pageSize,
                paginationSizeSelector: [10, 20, 50, 100],
                headerFilter: true,
                paginationInitialPage:1,
            })
        },
        onActionButtonClicked(e, cell) {
            const action = e.target.dataset.action
            const rowData = cell.getRow().getData();
            const eventID = rowData.event_id

            if (action === 'view') {
                this.$router.push({name: 'event_request_detail', params: {id: eventID}});
            } else {
                this.approval.event_id = eventID
                this.approval.status = action
            }
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
                    this.$axios.post(`api/v1/admin/event/approval/${this.approval.event_id}`, this.approval)
                        .then(() => {
                            this.generateEventApprovalTable()
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
