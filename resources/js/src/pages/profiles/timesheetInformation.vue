<template>
    <div class="tab-pane fade" id="pills-timesheet" role="tabpanel" aria-labelledby="pills-timesheet-tab">
        <div class="card mb-0">
            <div class="card-header d-flex bg-primary">
                <h5 class="mb-0">Timesheet Information</h5>
            </div>
            <div class="card-body p-4">
                <div class="taskadd">
                    <div class="row">
                        <div class="col-md-12 col-sm-12">
                            <table class="table table-striped">
                                <thead>
                                <tr>
                                    <th class="text-center">Date</th>
                                    <th class="text-center">Start Time</th>
                                    <th class="text-center">End Time</th>
                                    <th class="text-center">Shift</th>
                                </tr>
                                </thead>
                                <tbody>
                                <tr v-for="(data, index) in schedules">
                                    <td class="text-center">{{fullDate}}</td>
                                    <td class="text-center">{{data.timesheet.start_time}}</td>
                                    <td class="text-center">{{data.timesheet.end_time}}</td>
                                    <td class="text-center">{{data.timesheet.name}}</td>
                                </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
export default {
    props: {
        profile: {
            type: Array,
            required: true
        }
    },
    data() {
        return {
            fullDate: null,
            month: null,
            schedules: null
        }
    },
    async mounted() {
        await this.getFullDate();
    },
    methods: {
        async getFullDate() {
            const date = new Date();
            const month = (date.getMonth() + 1).toString();
            this.month = month.replace(/^0+/, '');
            this.$axios.get(`/api/v1/admin/employee-timesheet/show-schedule?month=${this.month}&employee_id=${this.profile.employee_id}`)
                .then(response => {
                    this.schedules = response.data.data;
                    this.schedules.map(schedule => {
                        let date = schedule.date;
                        let year = schedule.period.year;
                        let month = schedule.period.month;
                        let dates = new Date(`${year}-${month}-${date}`);

                        let days = dates.getDate().toString().padStart(2, '0');
                        let months = (dates.getMonth() + 1).toString().padStart(2, '0');
                        let years = dates.getFullYear().toString();

                        this.fullDate = `${days}-${months}-${years}`;
                    });
                })
                .catch(error => {
                    console.log(error);
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
