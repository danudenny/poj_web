<template>
    <div class="container-fluid">
        <Breadcrumbs title="User Detail"/>
        <div class="col-sm-12">
            <form class="card">
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4">
                            <div class="mb-3">
                                <pre>{{item.avatar}}</pre>
                                <img :src="item.avatar" />
                            </div>
                        </div>
                        <div class="col-md-5">

                            <div class="mb-3">
                                <label class="form-label">Name</label>
                                <input class="form-control" type="text" placeholder="Name" v-model="item.name" disabled>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Username</label>
                                <input class="form-control" type="text" placeholder="Username" v-model="item.username" disabled>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Email address</label>
                                <input class="form-control" type="email" placeholder="Email" v-model="item.email" disabled>
                            </div>
                            <div class="mb-3">
                                <div class="mb-2">
                                    <label class="col-form-label">Roles</label>
                                    <ul>
                                        <li v-for="role in item.roles" class="badge badge-primary">{{ role.name }}</li>
                                    </ul>
                                </div>
                            </div>
                            <div class="mb-3">
                                <div class="mb-2">
                                    <b class="text-danger w-700">Active Sessions</b>
                                    <ul>
                                        <li v-show="item.is_normal_checkin">
                                            <span class="badge badge-primary">Attendance Check-In</span>
                                        </li>
                                        <li v-show="item.is_backup_checkin">
                                            <span class="badge badge-primary">Backup Check-In</span>
                                        </li>
                                        <li v-show="item.is_event_checkin">
                                            <span class="badge badge-primary">Event Check-In</span>
                                        </li>
                                        <li v-show="item.is_overtime_checkin">
                                            <span class="badge badge-primary">Overtime Check-In</span>
                                        </li>
                                        <li v-show="item.is_longshift_checkin">
                                            <span class="badge badge-primary">Longshift Check-In</span>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card-footer text-end">
                    <button class="btn btn-secondary" @click="$router.push('/management/users')">Back</button>
                </div>
            </form>
        </div>
    </div>
</template>

<script>

import axios from 'axios';
import { useRoute } from 'vue-router';
export default {
    data() {
        return {
            item: [],
            fallbackImageUrl: '',
            didLoad: true,
        }
    },
    mounted() {
        this.getUser();
    },
    methods: {
        getUser() {
            const route = useRoute();
            axios
                .get(`/api/v1/admin/user/view?id=`+ route.params.id)
                .then(response => {
                    this.item = response.data.data;
                })
                .catch(error => {
                    console.error(error);
                });
        }
    }
};
</script>
