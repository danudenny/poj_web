<template>
    <Breadcrumbs main="Timesheet Assignment" />

    <div class="container-fluid">
        <div class="email-wrap bookmark-wrap">
            <div class="row">
                <div class="col-md-12">
                    <div class="card card-absolute">
                        <div class="card-header bg-primary">
                            <h5>Timesheet Assignment List</h5>
                        </div>
                        <div class="card-body">
                            <calendar-view
                                :show-date="showDate"
                                :enable-date-selection="true"
                                class="theme-default"
                                @click-date="clickedDate"
                                @click-item="toggleDrawer"
                                title="Create New Assignment"
                                :items="items"
                            >
                                <template #header="{ headerProps }">
                                    <calendar-view-header
                                        :header-props="headerProps"
                                        @input="setShowDate"
                                        style="background-color: #0A5640; color: white;"
                                    />
                                </template>
                            </calendar-view>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div v-if="isOpen" class="drawer-overlay" @click="closeDrawer">
            <div class="drawer" @click.stop>
                <div class="row">
                    <div class="d-flex column-gap-2 m-2">
                        <button class="btn btn-primary" @click="closeDrawer">
                            <i class="fa fa-times-circle"></i>&nbsp;Close</button>
                        <button class="btn btn-warning" @click="editTimesheet">
                            <i class="fa fa-pencil"></i>&nbsp;Edit</button>
                    </div>
                    <div class="col-md-7">
                        <table class="table table-striped table-responsive">
                            <thead>
                            <tr>
                                <th>No</th>
                                <th>Name</th>
                                <th>Days</th>
                                <th>Unit</th>
                            </tr>
                            </thead>
                            <tbody>
                            <tr v-for="(item, index) in clickedSchedule">
                                <td>{{ index + 1 }}</td>
                                <td>
                                   <div>
                                         <div class="d-flex justify-content-start column-gap-2 avatar avatar-sm">
                                            <img :src="`https://ui-avatars.com/api/?name=${item.employee.name}&background=0A5640&color=fff&length=2&rounded=false&size=32`" alt="avatar" class="avatar-img rounded">

                                             <div class="d-flex flex-column">
                                                 <span>{{ item.employee.name }}</span>
                                                 <small class="text-danger">
                                                     <b>{{ item.timesheet.start_time}} - {{item.timesheet.end_time}}</b>
                                                     <span class="badge badge-primary">{{item.timesheet.shift_type}}</span>
                                                 </small>
                                             </div>
                                         </div>
                                   </div>
                                </td>
                                <td>
                                    <span v-if="item.timesheet.shift_type === 'non_shift'" class="badge badge-danger" v-for="day in item.timesheet.days">
                                        {{day}}
                                    </span>
                                </td>
                                <td>{{item.employee.last_unit.name}}</td>
                            </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import {CalendarView, CalendarViewHeader} from "vue-simple-calendar"
import {useToast} from "vue-toastification";
import "../../../../../node_modules/vue-simple-calendar/dist/style.css"
import "../../../../../node_modules/vue-simple-calendar/dist/css/default.css"

export default {
    components: {
        CalendarView,
        CalendarViewHeader,
    },
    data() {
        return {
            loading: false,
            schedules: [],
            date: 0,
            showDate: new Date(),
            isOpen: false,
            items: [
                {
                    id: '',
                    startDate: '',
                    title: '',
                }
            ],
            clickedSchedule: [],
            scheduleInfo: []
        }
    },
    mounted() {
        this.getScheduleData()
    },
    methods: {
        editTimesheet() {
            const date = this.clickedSchedule[0].date
            const year = this.clickedSchedule[0].period.year
            const month = this.clickedSchedule[0].period.month

            const dateObj = new Date(year, month - 1, date)
            const dateStr = dateObj.toLocaleDateString()
            this.$router.push({
                name: 'timesheet-schedule-edit',
                query: {
                    date: dateStr
                }
            })
        },
        toggleDrawer(item) {
            this.schedules.filter((schedule) => {
                if (schedule.date === item.startDate.getDate()) {
                    this.clickedSchedule.push(schedule)
                }
            });
            this.isOpen = !this.isOpen;
        },
        closeDrawer() {
            this.clickedSchedule = []
            this.isOpen = false;
            document.removeEventListener("click", this.closeOnOutsideClick);
        },
        closeOnOutsideClick(event) {
            const drawerElement = this.$el.querySelector(".drawer");
            if (drawerElement && !drawerElement.contains(event.target)) {
                this.closeDrawer();
            }
        },
        thisMonth(d) {
            const t = new Date()
            return new Date(t.getFullYear(), t.getMonth(), d)
        },
        clickedDate(date) {
            if (date.toLocaleDateString() < new Date().toLocaleDateString()) {
                return useToast().error('You cannot create schedule in the past');
            }
            this.$router.push({
                name: 'timesheet-schedule-create',
                query: {
                    date: date
                }
            })
        },
        setShowDate(d) {
            this.showDate = d;
        },
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
                    const groupedItems = this.schedules.reduce((result, schedule) => {
                        const day = schedule.date;
                        const monthNumber = schedule.period.month;
                        const year = schedule.period.year;
                        const dateObject = new Date(Date.UTC(year, monthNumber - 1, day, 5, 0, 0)); // Setting time to 5:00 AM (GMT+0)

                        const key = `${year}-${monthNumber}-${day}`;
                        if (!result[key]) {
                            result[key] = {
                                id: schedule.id,
                                startDate: dateObject,
                                title: 'Lihat Data',
                            };
                        }

                        return result;
                    }, {});

                    this.items = Object.values(groupedItems);
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
    font-size: 9px;
    padding: 5px 10px;
    border-radius: 5px;
    font-weight: 400;
    text-transform: capitalize;
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
.drawer-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0, 0, 0, 0.3);
    z-index: 1999;
}

.drawer {
    position: relative;
    top: 0;
    right: -800px;
    height: 100%;
    width: 950px;
    background-color: #f0f0f0;
    transition: right 0.5s ease-in-out;
    z-index: 1000;
    overflow-y: auto;
    scrollbar-width: thin;
}

.drawer.open {
    right: 0;
}
.past .offset2 .span1 .cv-item {
    background-color: #218838 !important;
}
.card-absolute .card-body {
    height: 550px !important;
}
</style>
