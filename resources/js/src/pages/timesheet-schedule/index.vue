<template>
    <Breadcrumbs title="Timesheet Assignment" />

    <div class="container-fluid">
        <div class="email-wrap bookmark-wrap">
            <div class="row">
                <div class="col-md-12">
                    <div class="card card-absolute">
                        <div class="card-header bg-primary">
                            <h5>Timesheet Assignment List</h5>
                        </div>
                        <div class="card-body">
                            <div class="d-flex justify-content-end">
                                <button class="btn btn-success" type="button" @click="createSchedule">
                                    <i class="fa fa-plus"></i> &nbsp; Create
                                </button>
                            </div>
                            <hr>
                            <div v-if="loading" class="text-center">
                                <img src="../../assets/loader.gif" alt="loading" width="100">
                            </div>
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th class="text-center">Date</th>
                                        <th class="text-center">Total Employees</th>
                                        <th class="text-center">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="(item, index) in this.schedules">
                                        <td class="text-center">{{index}}</td>
                                        <td class="text-center"><span class="badge badge-success">{{item.length}}</span></td>
                                        <td class="text-center">
                                            <button class="button-icon button-info" @click="viewData(index)">
                                                <i class="fa fa-eye"></i>
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
            date: 0,
        }
    },
    mounted() {
        this.getScheduleData()
    },
    methods: {
         viewData(e) {
            this.$store.dispatch('setData', this.schedules[e]);
            this.$router.push({
                name: 'timesheet-schedule-detail'
            })
        },
        getScheduleData() {
            this.loading = true
            this.$axios.get('/api/v1/admin/timesheet-schedule/get-schedule')
                .then((response) => {
                    this.schedules = response.data.data
                    this.loading = false
                })
                .catch((error) => {
                    console.log(error)
                    this.loading = false
                })
        },
        createSchedule() {
            this.$router.push({ name: 'timesheet-schedule-create' })
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
