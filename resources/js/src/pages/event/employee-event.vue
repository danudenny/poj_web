<template>
    <div class="container-fluid">
        <Breadcrumbs main="Employee Event"/>

        <div class="container-fluid">
            <div class="email-wrap bookmark-wrap">
                <div class="row">
                    <div class="col-md-12">
                        <div class="card card-absolute">
                            <div class="card-header bg-primary">
                                <h5>Employee Event</h5>
                            </div>
                            <div class="card-body">
                                <div class="d-flex justify-content-end mb-2">
                                </div>
                                <div v-if="loading" class="text-center">
                                    <img src="../../assets/loader.gif" alt="loading" width="100">
                                </div>
                                <div ref="employeeEvents"></div>
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

export default {
    data() {
        return {
            employeeEvents: [],
            loading: false,
        }
    },
    async mounted() {
        await this.getEmployeeEvents();
        this.initializeEventTable();
    },
    methods: {
        async getEmployeeEvents() {
            this.loading = true;
            await this.$axios.get(`/api/v1/admin/event/employee-event`)
                .then(response => {
                    console.log(response.data.data)
                    this.employeeEvents = response.data.data;
                })
                .catch(error => {
                    console.error(error);
                });
        },
        initializeEventTable() {
            const table = new Tabulator(this.$refs.employeeEvents, {
                data: this.employeeEvents,
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
                        field: 'employee.name',
                        headerFilter:"input"
                    },
                    {
                        title: 'Event Name',
                        field: 'event.title',
                        headerFilter:"input"
                    },
                    {
                        title: 'Date',
                        field: 'event_date',
                        headerFilter:"input"
                    },
                    {
                        title: 'Time',
                        field: 'event_time',
                        headerFilter:"input"
                    }
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
        viewDetailsFormatter(cell, formatterParams, onRendered) {
            return `<button class="button-icon button-success" data-id="${cell.getRow().getData().id}"><i class="fa fa-eye"></i> </button>`;
        },
        viewData(id) {
            this.$router.push({name: 'event_request_detail', params: {id}});
        }
    }
}
</script>

<style>
.tabulator .tabulator-header .tabulator-col {
    background-color: #0A5640 !important;
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
</style>
