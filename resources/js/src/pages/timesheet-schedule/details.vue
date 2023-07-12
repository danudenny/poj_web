<template>
    <Breadcrumbs title="Timesheet Assignment Detail" />

    <div class="container-fluid">
        <div class="email-wrap bookmark-wrap">
            <div class="row">
                <div class="col-md-12">
                    <div class="card card-absolute">
                        <div class="card-header bg-primary">
                            <h5>Timesheet Assignment Detail</h5>
                        </div>
                        <div class="card-body">
                            <div class="d-flex justify-content-end">
                                <button class="btn btn-warning" @click="this.$router.push('/timesheet-assign')">
                                    <i class="fa fa-arrow-left"></i> Back
                                </button>
                            </div>
                            <hr>
                            <table class="table table-striped">
                                <thead>
                                <tr>
                                    <th class="text-center">No</th>
                                    <th class="text-center">Name</th>
                                    <th class="text-center">Start Time</th>
                                    <th class="text-center">End Time</th>
                                    <th class="text-center">Shift</th>
                                    <th class="text-center">Action</th>
                                </tr>
                                </thead>
                                <tbody>
                                <tr v-for="(data, index) in schedules">
                                    <td class="text-center">{{index + 1}}</td>
                                    <td>{{data.employee.name}}</td>
                                    <td class="text-center">{{data.timesheet.start_time}}</td>
                                    <td class="text-center">{{data.timesheet.end_time}}</td>
                                    <td class="text-center">{{data.timesheet.name}}</td>
                                    <td class="text-center">
                                        <button type="button" class="button-icon button-success">
                                            <i class="fa fa-pencil"></i>
                                        </button>
                                        <button type="button" class="button-icon button-danger">
                                            <i class="fa fa-trash"></i>
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
</template>

<script>
export default {
    data() {
        return {
            loading: false,
            schedules: [],
        }
    },
    computed: {
        receivedData() {
            return this.$store.getters.getData;
        }
    },
    mounted() {
        localStorage.setItem('scheduleData', JSON.stringify(this.receivedData));
        this.schedules = JSON.parse(localStorage.getItem('scheduleData'));
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
