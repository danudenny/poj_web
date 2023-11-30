<template>
    <div class="container-fluid">
        <Breadcrumbs main="Create Timesheet Reporting"/>

        <div class="container-fluid">
            <div class="email-wrap bookmark-wrap">
                <div class="row">
                    <div class="col-md-12">
                        <div class="card card-absolute">
                            <div class="card-header bg-primary">
                                <h5>Create Timesheet Reporting</h5>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-12">
                                        <div class="mt-4">
                                            <label for="date">Start Date:</label>
                                            <input type="date" class="form-control" id="date" v-model="createTimesheetReportingPayload.start_date" required>
                                        </div>
                                    </div>
                                    <div class="col-md-12">
                                        <div class="mt-4 mb-4">
                                            <label for="date">End Date:</label>
                                            <input type="date" class="form-control" id="date" v-model="createTimesheetReportingPayload.end_date" required>
                                        </div>
                                    </div>
                                    <div class="col-md-12">
                                        <label>Unit :</label>
                                        <multiselect
                                            v-model="selectedUnit"
                                            placeholder="Select Unit"
                                            label="name_with_corporate"
                                            track-by="relation_id"
                                            :options="units"
                                            :multiple="false"
                                            @select="onSelectedUnit"
                                            @search-change="onSearchUnitName"
                                        >
                                        </multiselect>
                                    </div>
                                </div>
                            </div>
                            <div class="card-footer text-start">
                                <button class="btn btn-secondary" @click="$router.go(-1)" :disabled="isOnCreateProcess"><i class="fa fa-arrow-left"></i> Back</button> &nbsp;
                                <button class="btn btn-success" @click="onCreate" :disabled="isOnCreateProcess">
                                    <span v-if="!isOnCreateProcess"><i class="fa fa-save"></i> Save</span>
                                    <span v-else>Creating & Syncing Data ({{this.countdown}}s)</span>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
<script>
import {TabulatorFull as Tabulator} from 'tabulator-tables';
import {useToast} from "vue-toastification";
import VerticalModal from "@components/modal/verticalModal.vue";
import Modal from "@components/modal.vue";
import moment from "moment/moment";

export default {
    components: {VerticalModal, Modal},
    data() {
        return {
            createTimesheetReportingPayload: {
                start_date: null,
                end_date: null,
                unit_relation_id: null
            },
            pagination: {
                currentPage: 1,
                pageSize: 10
            },
            unitPagination: {
                limit: 20,
                isOnSearch: true,
                name: ''
            },
            selectedUnit: null,
            units: [],
            isOnCreateProcess: false,
            countdown: 0,
            timerInterval: null
        }
    },
    mounted() {
        this.getUnits()
    },
    methods: {
        getUnits() {
            this.$axios.get(`api/v1/admin/unit/paginated?per_page=${this.unitPagination.limit}&name=${this.unitPagination.name}&unit_level=3,4,5,6,7&append=name_with_corporate`)
                .then(response => {
                    this.units = response.data.data.data;
                    this.unitPagination.isOnSearch = false
                }).catch(error => {
                console.error(error);
            });
        },
        onSelectedUnit() {
            this.createTimesheetReportingPayload.unit_relation_id = this.selectedUnit.relation_id
        },
        onSearchUnitName(val) {
            this.unitPagination.name = val

            if (!this.unitPagination.isOnSearch) {
                this.unitPagination.isOnSearch = true
                setTimeout(() => {
                    this.getUnits()
                }, 1000)
            }
        },
        onStartTimer() {
            this.countdown = 0
            this.timerInterval = this.timerId = setInterval(() => {
                this.countdown++;
            }, 1000);
        },
        onKillTimer() {
            clearInterval(this.timerInterval)
            this.timerInterval = null;
            this.countdown = null;
        },
        onCreate() {
            this.$swal({
                icon: 'warning',
                title:"Are you sure want to add timesheet report?",
                text:'Please re-check the date range for timesheet report before create report!',
                showCancelButton: true,
                confirmButtonText: 'Yes!',
                confirmButtonColor: '#126850',
                cancelButtonText: 'Cancel',
                cancelButtonColor: '#efefef',
            }).then((result)=>{
                if(result.isConfirmed){
                    this.isOnCreateProcess = true
                    this.onStartTimer()
                    this.$axios.post(`api/v1/admin/timesheet-report`, this.createTimesheetReportingPayload)
                        .then(() => {
                            this.onKillTimer()
                            this.isOnCreateProcess = false
                            useToast().success("Success to create timesheet report!");
                            this.$router.push({
                                name: 'Timesheet Reporting',
                            })
                        })
                        .catch(error => {
                            this.onKillTimer()
                            this.isOnCreateProcess = false
                            useToast().error(error.response.data.message);
                        });
                }
            });
        },
    }
}
</script>
