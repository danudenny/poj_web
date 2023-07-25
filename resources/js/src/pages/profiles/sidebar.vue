<template>
    <div class="col-xl-4 box-col-6">
        <div class="md-sidebar"><a class="btn btn-primary md-sidebar-toggle" @click="collapse()">bookmark filter</a>
            <div class="md-sidebar-aside job-left-aside custom-scrollbar" :class="filtered ? 'open' : ''">
                <div class="email-left-aside">
                    <div class="card">
                        <div class="card-body">
                            <div class="email-app-sidebar left-bookmark task-sidebar">
                                <div class="media">
                                    <div class="media-size-email">
                                        <img
                                            class="me-3 rounded-circle"
                                            src="@/assets/images/user/user.png"
                                            alt="" />
                                    </div>
                                    <div class="media-body">
                                        <h6 class="f-w-600">{{employeeData.name}}</h6>
                                        <p>{{employeeData.work_email}}</p>
                                    </div>
                                </div>
                                <hr>
                                <ul class="nav main-menu" role="tablist">
                                   <li>
                                        <a class="pills-link active" id="pills-app-tab" data-bs-toggle="pill" href="#pills-app" role="tab"
                                           aria-controls="pills-app" aria-selected="true">
                                            <span class="title" @click="active('pills-app-tab')">
                                                <i data-feather="user">
                                                    <vue-feather type="user"></vue-feather>
                                                </i>
                                                Basic Information
                                            </span>
                                        </a>
                                    </li>
                                    <li>
                                        <a class="pills-link" id="pills-company-tab" data-bs-toggle="pill" href="#pills-company" role="tab"
                                           aria-controls="pills-company" aria-selected="true">
                                            <span class="title" @click="active('pills-company-tab')">
                                                <i data-feather="briefcase">
                                                    <vue-feather type="briefcase"></vue-feather>
                                                </i>
                                                Work Information
                                            </span>
                                        </a>
                                    </li>
                                    <li>
                                        <a class="pills-link" id="pills-timesheet-tab" data-bs-toggle="pill" href="#pills-timesheet" role="tab"
                                           aria-controls="pills-timesheet" aria-selected="true">
                                            <span class="title" @click="active('pills-timesheet-tab')">
                                                <i data-feather="watch">
                                                    <vue-feather type="watch"></vue-feather>
                                                </i>
                                                Timesheet Information
                                            </span>
                                        </a>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-8 col-md-12 box-col-12">
        <div class="email-right-aside bookmark-tabcontent">
            <div class="card email-body radius-left">
                <div class="ps-0">
                    <div class="tab-content">
                        <basicInformation v-if="!isLoading" :employee="employeeData"/>
                        <companyInformation v-if="!isLoading" :employee="employeeData"/>
                        <timesheetInformation v-if="!isLoading" :profile="schedules"/>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import basicInformation from "./basicInformation.vue";
import companyInformation from "./companyInformation.vue";
import timesheetInformation from "./timesheetInformation.vue";

export default {
    components: {
        basicInformation,
        companyInformation,
        timesheetInformation
    },
    data() {
        return {
            filtered: false,
            employeeData: {},
            isLoading: true,
            profile: {},
            schedules: []
        }
    },
    async created() {
        await this.getProfile();
        // await this.fetchExistingData();
        // await this.getTimesheet();
    },
    methods: {
        async getTimesheet() {
            this.$axios.get(`/api/v1/admin/employee-timesheet/show-schedule?employee_id=${this.profile.employee_id}`)
                .then(response => {
                    this.schedules = response.data.data;
                })
                .catch(error => {
                    console.log(error);
                });

            // return this.fullDate;
            return this.schedules;
        },
        active(tabId) {
            document.getElementById(tabId).classList.add('active');
        },
        collapse() {
            this.filtered = !this.filtered
        },
        async getProfile() {
            await this.$axios.get('api/v1/admin/user/profile').then(response => {
                this.profile = response.data.data
                this.employeeData = response.data.data.employee
                this.schedules = response.data.data.time_schedules
                this.isLoading = false;
            }).catch(error => {
                console.error(error);
            });
        },
        async fetchExistingData() {
            await this.$axios.get(`api/v1/admin/employee/view/${this.profile.employee_id}`)
                .then(response => {
                    this.employeeData = response.data.data.employee

                    this.isLoading = false;
                })
                .catch(error => {
                    console.error(error);
                    this.isLoading = false;
                });
        },
    },
}
</script>

<style scoped>
.pills-link.active {
    background-color: #0A5640;
    transition: background-color 0.3s ease-in;
}
.pills-link.active > span.title {
    color: #fff;
}

.pills-link.active > i {
    color: #fff;
}
</style>
