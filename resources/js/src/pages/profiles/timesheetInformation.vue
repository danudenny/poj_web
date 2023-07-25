<template>
    <div class="tab-pane fade" id="pills-timesheet" role="tabpanel" aria-labelledby="pills-timesheet-tab">
        <div class="card mb-0">
            <div class="card-header d-flex bg-primary">
                <h5 class="mb-0">Timesheet Information</h5>
            </div>
            <div class="card-body p-4">
                <div class="taskadd">
                    <div class="row">
                        <div class="col-md-12 col-sm-12 my-3">
                            <div class="mb-2">
                                <h5>Daily Attendances</h5>
                            </div>
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
                                <tr v-for="(data, index) in profile.daily">
                                    <td class="text-center">{{data.date}}</td>
                                    <td class="text-center">{{data.start_time}}</td>
                                    <td class="text-center">{{data.end_time}}</td>
                                    <td class="text-center">{{data.name}}</td>
                                </tr>
                                </tbody>
                            </table>
                        </div>
                        <div class="col-md-12 col-sm-12 my-3">
                            <div class="mb-2">
                                <h5>Overtime Attendances</h5>
                            </div>
                            <table class="table table-striped">
                                <thead>
                                <tr>
                                    <th class="text-center">Date</th>
                                    <th class="text-center">Start Time</th>
                                    <th class="text-center">End Time</th>
                                </tr>
                                </thead>
                                <tbody>
                                <tr v-for="(data, index) in profile.overtime">
                                    <td class="text-center">{{data.date}}</td>
                                    <td class="text-center">{{data.start_time}}</td>
                                    <td class="text-center">{{data.end_time}}</td>
                                </tr>
                                </tbody>
                            </table>
                        </div>
                        <div class="col-md-12 col-sm-12 my-3">
                            <div class="mb-2">
                                <h5>Backup Attendances</h5>
                            </div>
                            <table class="table table-striped">
                                <thead>
                                <tr>
                                    <th class="text-center">Date</th>
                                    <th class="text-center">Start Time</th>
                                    <th class="text-center">End Time</th>
                                </tr>
                                </thead>
                                <tbody>
                                <tr v-for="(data, index) in profile.backup">
                                    <td class="text-center">{{data.date}}</td>
                                    <td class="text-center">{{data.start_time}}</td>
                                    <td class="text-center">{{data.end_time}}</td>
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
</style>
