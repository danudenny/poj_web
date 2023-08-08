<template>
    <div class="container-fluid">
        <Breadcrumbs main="Synchronization"/>

        <div class="container-fluid">
            <div class="email-wrap bookmark-wrap">
                <div class="row">
                    <div class="col-md-12">
                        <div class="card card-absolute">
                            <div class="card-header bg-primary">
                                <h5>Syncronization</h5>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-12">
                                        <table class="table table-striped">
                                            <thead>
                                            <tr>
                                                <th>Sync Name</th>
                                                <th>From</th>
                                                <th>To</th>
                                                <th>Action</th>
                                            </tr>
                                            </thead>
                                            <tbody>
                                                <tr class="text-center">
                                                    <td>Employee</td>
                                                    <td>ERP</td>
                                                    <td>Alakad Employee</td>
                                                    <td>
                                                        <button class="btn btn-warning"  :disabled="syncEmployeeLoading" type="button" @click="syncEmployeeAction">
                                                            <span v-if="syncEmployeeLoading">
                                                                <i  class="fa fa-spinner fa-spin"></i> Processing ... ({{ countdown }}s)
                                                            </span>
                                                            <span v-else>
                                                                <i class="fa fa-recycle"></i> &nbsp; Sync Data
                                                            </span>
                                                        </button>
                                                    </td>
                                                </tr>
                                                <tr class="text-center">
                                                    <td>User</td>
                                                    <td>Alakad Employee</td>
                                                    <td>Alakad User</td>
                                                    <td>
                                                        <button class="btn btn-warning"  :disabled="syncUserLoading" type="button" @click="syncUserAction">
                                                            <span v-if="syncUserLoading">
                                                                <i  class="fa fa-spinner fa-spin"></i> Processing ... ({{ countdown }}s)
                                                            </span>
                                                            <span v-else>
                                                                <i class="fa fa-recycle"></i> &nbsp; Sync Data
                                                            </span>
                                                        </button>
                                                    </td>
                                                </tr>
                                                <tr class="text-center">
                                                    <td>Corporate</td>
                                                    <td>ERP</td>
                                                    <td>Alakad Unit</td>
                                                    <td>
                                                        <button class="btn btn-warning"  :disabled="syncCorporateLoading" type="button" @click="syncCorporateAction">
                                                            <span v-if="syncCorporateLoading">
                                                                <i  class="fa fa-spinner fa-spin"></i> Processing ... ({{ countdown }}s)
                                                            </span>
                                                            <span v-else>
                                                                <i class="fa fa-recycle"></i> &nbsp; Sync Data
                                                            </span>
                                                        </button>
                                                    </td>
                                                </tr>
                                                <tr class="text-center">
                                                    <td>Kantor Wilayah</td>
                                                    <td>ERP</td>
                                                    <td>Alakad Unit</td>
                                                    <td>
                                                        <button class="btn btn-warning"  :disabled="syncKantorWilayahLoading" type="button" @click="syncKantorWilayahAction">
                                                            <span v-if="syncKantorWilayahLoading">
                                                                <i  class="fa fa-spinner fa-spin"></i> Processing ... ({{ countdown }}s)
                                                            </span>
                                                            <span v-else>
                                                                <i class="fa fa-recycle"></i> &nbsp; Sync Data
                                                            </span>
                                                        </button>
                                                    </td>
                                                </tr>
                                                <tr class="text-center">
                                                    <td>Area</td>
                                                    <td>ERP</td>
                                                    <td>Alakad Unit</td>
                                                    <td>
                                                        <button class="btn btn-warning"  :disabled="syncAreaLoading" type="button" @click="syncAreaAction">
                                                            <span v-if="syncAreaLoading">
                                                                <i  class="fa fa-spinner fa-spin"></i> Processing ... ({{ countdown }}s)
                                                            </span>
                                                            <span v-else>
                                                                <i class="fa fa-recycle"></i> &nbsp; Sync Data
                                                            </span>
                                                        </button>
                                                    </td>
                                                </tr>
                                                <tr class="text-center">
                                                    <td>Cabang</td>
                                                    <td>ERP</td>
                                                    <td>Alakad Unit</td>
                                                    <td>
                                                        <button class="btn btn-warning"  :disabled="syncCabangLoading" type="button" @click="syncCabangAction">
                                                            <span v-if="syncCabangLoading">
                                                                <i  class="fa fa-spinner fa-spin"></i> Processing ... ({{ countdown }}s)
                                                            </span>
                                                            <span v-else>
                                                                <i class="fa fa-recycle"></i> &nbsp; Sync Data
                                                            </span>
                                                        </button>
                                                    </td>
                                                </tr>
                                                <tr class="text-center">
                                                    <td>Outlet</td>
                                                    <td>ERP</td>
                                                    <td>Alakad Unit</td>
                                                    <td>
                                                        <button class="btn btn-warning"  :disabled="syncOutletLoading" type="button" @click="syncOutletAction">
                                                            <span v-if="syncOutletLoading">
                                                                <i  class="fa fa-spinner fa-spin"></i> Processing ... ({{ countdown }}s)
                                                            </span>
                                                            <span v-else>
                                                                <i class="fa fa-recycle"></i> &nbsp; Sync Data
                                                            </span>
                                                        </button>
                                                    </td>
                                                </tr>
                                                <tr class="text-center">
                                                    <td>Job</td>
                                                    <td>ERP</td>
                                                    <td>Alakad Job</td>
                                                    <td>
                                                        <button class="btn btn-warning"  :disabled="syncJobLoading" type="button" @click="syncJobAction">
                                                            <span v-if="syncJobLoading">
                                                                <i  class="fa fa-spinner fa-spin"></i> Processing ... ({{ countdown }}s)
                                                            </span>
                                                            <span v-else>
                                                                <i class="fa fa-recycle"></i> &nbsp; Sync Data
                                                            </span>
                                                        </button>
                                                    </td>
                                                </tr>
                                                <tr class="text-center">
                                                    <td>Department</td>
                                                    <td>ERP</td>
                                                    <td>Alakad Department</td>
                                                    <td>
                                                        <button class="btn btn-warning"  :disabled="syncDepartmentLoading" type="button" @click="syncDepartmentAction">
                                                            <span v-if="syncDepartmentLoading">
                                                                <i  class="fa fa-spinner fa-spin"></i> Processing ... ({{ countdown }}s)
                                                            </span>
                                                            <span v-else>
                                                                <i class="fa fa-recycle"></i> &nbsp; Sync Data
                                                            </span>
                                                        </button>
                                                    </td>
                                                </tr>
                                                <tr class="text-center">
                                                    <td>Partner</td>
                                                    <td>ERP</td>
                                                    <td>Alakad Partner</td>
                                                    <td>
                                                        <button class="btn btn-warning"  :disabled="syncPartnerLoading" type="button" @click="syncPartnerAction">
                                                            <span v-if="syncPartnerLoading">
                                                                <i  class="fa fa-spinner fa-spin"></i> Processing ... ({{ countdown }}s)
                                                            </span>
                                                            <span v-else>
                                                                <i class="fa fa-recycle"></i> &nbsp; Sync Data
                                                            </span>
                                                        </button>
                                                    </td>
                                                </tr>
                                            </tbody>
                                        </table>
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
import axios from "axios";
import {useToast} from "vue-toastification";

export default {
    data() {
        return {
            syncEmployeeLoading: false,
            syncUserLoading: false,
            syncCorporateLoading: false,
            syncKantorWilayahLoading: false,
            syncAreaLoading: false,
            syncCabangLoading: false,
            syncOutletLoading: false,
            syncJobLoading: false,
            syncDepartmentLoading: false,
            syncPartnerLoading: false,
            countdown: 0,
            interval: null,
        }
    },
    methods: {
        startCountdown() {
            this.countdown = 1;
            this.timerId = setInterval(() => {
                this.countdown++;
            }, 1000);
        },
        async syncEmployeeAction() {
            this.syncEmployeeLoading = true;
            this.loading = true
            this.startCountdown();

            await axios.create({
                baseURL: import.meta.env.VITE_SYNC_ODOO_URL,
            }).get('/sync-employee')
                .then(async (response) => {
                    if (await response.data.status === 201) {
                        this.syncEmployeeLoading = false;
                        this.loading = false;
                        useToast().success(response.data.message);
                    } else {
                        this.syncEmployeeLoading = false;
                        this.loading = false;
                        await this.getEmployees()
                        this.initializeEmployeesTable();
                        useToast().error(response.data.message);
                    }
                }).catch(async () => {
                    this.syncEmployeeLoading = false;
                    this.loading = false;
                    useToast().error("Failed to Sync Data! Check connection.");
                }).finally(() => {
                    this.syncEmployeeLoading = false;
                    clearInterval(this.timerId);
                });
        },
        async syncUserAction() {
            this.syncUserLoading = true;
            this.loading = true
            this.startCountdown();

            await this.$axios.get('/api/v1/admin/employee/sync-to-users')
                .then(async (response) => {
                    this.syncUserLoading = false;
                    this.loading = false;
                    useToast().success(response.data.message);
                }).catch(() => {
                    this.syncUserLoading = false;
                    useToast().error("Failed to Sync Data! Check connection.");
                }).finally(() => {
                    this.syncUserLoading = false;
                    clearInterval(this.timerId);
                });
        },
        async syncCorporateAction() {
            this.syncCorporateLoading = true;
            this.loading = true
            this.startCountdown();

            await axios.create({
                baseURL: import.meta.env.VITE_SYNC_ODOO_URL,
            }).get('/sync-corporates')
                .then(async (response) => {
                    if (await response.data.status === 201) {
                        this.syncCorporateLoading = false;
                        this.loading = false;
                        useToast().success(response.data.message);
                    } else {
                        this.syncCorporateLoading = false;
                        this.loading = false;
                        useToast().error(response.data.message);
                    }
                }).catch(async () => {
                    this.syncCorporateLoading = false;
                    this.loading = false;
                    useToast().error("Failed to Sync Data! Check connection.");
                }).finally(() => {
                    this.syncCorporateLoading = false;
                    clearInterval(this.timerId);
                });
        },
        async syncKantorWilayahAction() {
            this.syncKantorWilayahLoading = true;
            this.loading = true
            this.startCountdown();

            await axios.create({
                baseURL: import.meta.env.VITE_SYNC_ODOO_URL,
            }).get('/sync-kanwil')
                .then(async (response) => {
                    if (await response.data.status === 201) {
                        this.syncKantorWilayahLoading = false;
                        this.loading = false;
                        useToast().success(response.data.message);
                    } else {
                        this.syncKantorWilayahLoading = false;
                        this.loading = false;
                        useToast().error(response.data.message);
                    }
                }).catch(async () => {
                    this.syncKantorWilayahLoading = false;
                    this.loading = false;
                    useToast().error("Failed to Sync Data! Check connection.");
                }).finally(() => {
                    this.syncKantorWilayahLoading = false;
                    clearInterval(this.timerId);
                });
        },
        async syncAreaAction() {
            this.syncAreaLoading = true;
            this.loading = true
            this.startCountdown();

            await axios.create({
                baseURL: import.meta.env.VITE_SYNC_ODOO_URL,
            }).get('/sync-area')
                .then(async (response) => {
                    if (await response.data.status === 201) {
                        this.syncAreaLoading = false;
                        this.loading = false;
                        useToast().success(response.data.message);
                    } else {
                        this.syncAreaLoading = false;
                        this.loading = false;
                        useToast().error(response.data.message);
                    }
                }).catch(async () => {
                    this.syncAreaLoading = false;
                    this.loading = false;
                    useToast().error("Failed to Sync Data! Check connection.");
                }).finally(() => {
                    this.syncAreaLoading = false;
                    clearInterval(this.timerId);
                });
        },
        async syncCabangAction() {
            this.syncCabangLoading = true;
            this.loading = true
            this.startCountdown();

            await axios.create({
                baseURL: import.meta.env.VITE_SYNC_ODOO_URL,
            }).get('/sync-cabang')
                .then(async (response) => {
                    if (await response.data.status === 201) {
                        this.syncCabangLoading = false;
                        this.loading = false;
                        useToast().success(response.data.message);
                    } else {
                        this.syncCabangLoading = false;
                        this.loading = false;
                        useToast().error(response.data.message);
                    }
                }).catch(async () => {
                    this.syncCabangLoading = false;
                    this.loading = false;
                    useToast().error("Failed to Sync Data! Check connection.");
                }).finally(() => {
                    this.syncCabangLoading = false;
                    clearInterval(this.timerId);
                });
        },
        async syncOutletAction() {
            this.syncOutletLoading = true;
            this.loading = true
            this.startCountdown();

            await axios.create({
                baseURL: import.meta.env.VITE_SYNC_ODOO_URL,
            }).get('/sync-outlet')
                .then(async (response) => {
                    if (await response.data.status === 201) {
                        this.syncOutletLoading = false;
                        this.loading = false;
                        useToast().success(response.data.message);
                    } else {
                        this.syncOutletLoading = false;
                        this.loading = false;
                        useToast().error(response.data.message);
                    }
                }).catch(async () => {
                    this.syncOutletLoading = false;
                    this.loading = false;
                    useToast().error("Failed to Sync Data! Check connection.");
                }).finally(() => {
                    this.syncOutletLoading = false;
                    clearInterval(this.timerId);
                });
        },
        async syncJobAction() {
            this.syncJobLoading = true;
            this.loading = true
            this.startCountdown();

            await axios.create({
                baseURL: import.meta.env.VITE_SYNC_ODOO_URL,
            }).get('/sync-job')
                .then(async (response) => {
                    console.log(response)
                    if (await response.data.status === 201) {
                        await this.$axios.post('/api/v1/admin/job/insert-pivot')
                            .then(async (response) => {
                                console.log(response)
                                this.syncJobLoading = false;
                                this.loading = false;
                                useToast().success(response.data.message);
                            }).catch(async (err) => {
                                this.syncJobLoading = false;
                                this.loading = false;
                                useToast().error("Failed to Insert Job Has Unit");
                            }).finally(() => {
                                this.syncJobLoading = false;
                                clearInterval(this.timerId);
                            });
                    } else {
                        this.syncJobLoading = false;
                        this.loading = false;
                        useToast().error(response.data.message);
                    }
                }).catch(async (e) => {
                    this.syncJobLoading = false;
                    this.loading = false;
                    useToast().error("Failed to Sync Data! Check connection.");
                }).finally(() => {
                    this.syncJobLoading = false;
                    clearInterval(this.timerId);
                });
        },
        async syncDepartmentAction() {
            this.syncDepartmentLoading = true;
            this.loading = true
            this.startCountdown();

            await axios.create({
                baseURL: import.meta.env.VITE_SYNC_ODOO_URL,
            }).get('/sync-department')
                .then(async (response) => {
                    if (await response.data.status === 201) {
                        this.syncDepartmentLoading = false;
                        this.loading = false;
                        useToast().success(response.data.message);
                    } else {
                        this.syncDepartmentLoading = false;
                        this.loading = false;
                        useToast().error(response.data.message);
                    }
                }).catch(async () => {
                    this.syncDepartmentLoading = false;
                    this.loading = false;
                    useToast().error("Failed to Sync Data! Check connection.");
                }).finally(() => {
                    this.syncDepartmentLoading = false;
                    clearInterval(this.timerId);
                });
        },
        async syncPartnerAction() {
            this.syncPartnerLoading = true;
            this.loading = true
            this.startCountdown();

            await axios.create({
                baseURL: import.meta.env.VITE_SYNC_ODOO_URL,
            }).get('/sync-partner')
                .then(async (response) => {
                    if (await response.data.status === 201) {
                        this.syncPartnerLoading = false;
                        this.loading = false;
                        useToast().success(response.data.message);
                    } else {
                        this.syncPartnerLoading = false;
                        this.loading = false;
                        useToast().error(response.data.message);
                    }
                }).catch(async () => {
                    this.syncPartnerLoading = false;
                    this.loading = false;
                    useToast().error("Failed to Sync Data! Check connection.");
                }).finally(() => {
                    this.syncPartnerLoading = false;
                    clearInterval(this.timerId);
                });
        },
    }
}
</script>
