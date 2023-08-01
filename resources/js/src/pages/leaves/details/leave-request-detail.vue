<template>
    <div class="container-fluid">
        <Breadcrumbs main="Leave Request Detail"/>

        <div class="container-fluid">
            <div class="email-wrap bookmark-wrap">
                <div class="row">
                    <div class="col-md-8">
                        <div class="card card-absolute">
                            <div class="card-header bg-primary">
                                <h5>Leave Request Detail</h5>
                            </div>
                            <div class="card-body">
                                <div v-if="loading" class="text-center">
                                    <img src="../../../assets/loader.gif" alt="loading" width="100">
                                </div>
                                <div class="row d-flex row-gap-3" >
                                    <div class="col-md-12">
                                        <label>Employee Name</label>
                                        <p class="text-success" style="font-weight: 700;">{{leaveData?.employee?.name}} ({{leaveData.employee?.last_unit?.name}})</p>
                                    </div>
                                    <div class="col-md-5">
                                        <label>Start Date</label>
                                        <p class="text-success" style="font-weight: 700;">{{leaveData?.start_date}}</p>
                                    </div>
                                    <div class="col-md-5">
                                        <label>End Date</label>
                                        <p class="text-success" style="font-weight: 700;">{{leaveData?.end_date}}</p>
                                    </div>
                                    <div class="col-md-2">
                                        <label>Days</label>
                                        <p class="text-success" style="font-weight: 700;">{{leaveData?.days}}</p>
                                    </div>
                                    <div class="col-md-5">
                                        <label>Leave Type</label>
                                        <p class="text-success" style="font-weight: 700;">{{leaveData.leave_type?.leave_type === 'permit' ? 'Izin' : 'Cuti'}}</p>
                                    </div>
                                    <div class="col-md-7">
                                        <label>Leave Category</label>
                                        <p class="text-success" style="font-weight: 700;">{{leaveData.leave_type?.leave_name}}</p>
                                    </div>
                                    <div class="col-md-12">
                                        <label>Status</label>
                                        <p class="text-success" style="font-weight: 700;">{{leaveData?.last_status}}</p>
                                    </div>
                                    <div class="col-md-12">
                                        <label>Leave Reason</label>
                                        <p class="text-success" style="font-weight: 700;">{{leaveData?.reason}}</p>
                                    </div>
                                </div>
                            </div>
                            <div class="card-footer d-flex justify-content-end">
                                <button class="btn btn-warning" @click="$router.push('/leave-request')">
                                    <i class="fa fa-rotate-left"></i>&nbsp; Back
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card card-absolute">
                            <div class="card-header bg-primary">
                                <h5>Tracking</h5>
                            </div>
                            <div class="card-body">
                                <div class="row"  v-for="item in leaveData.leave_history">
                                    <div :class="['card', 'p-4', getStatusClass(item.status)]">
                                        <div class="card-content">
                                            <span>Status : {{ item.status.toUpperCase() }}</span><br>
                                            <span>Created at : {{ moment(item.created_at).format('DD MMMM YYYY') }}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
<script>

import moment from "moment";

export default {
    computed: {
        moment() {
            return moment
        }
    },
    data() {
        return {
            leaveData: {},
            permitType: '',
            loading: false
        }
    },
    async mounted() {
        await this.getData();
    },
    methods: {
        getStatusClass(status) {
            if (status === 'on process') {
                return 'bg-primary';
            } else if (status === 'approved') {
                return 'bg-success';
            } else if (status === 'rejected') {
                return 'bg-danger';
            }
        },
        async getData() {
            this.loading = true;
            await this.$axios.get(`api/v1/admin/leave_request/view/${this.$route.params.id}`)
                .then((response) => {
                    this.leaveData = response.data.data;
                    this.loading = false;
                })
                .catch((error) => {
                    console.error(error);
                })
        }
    }
}
</script>
