<template>
    <div class="container-fluid">
        <Breadcrumbs main="User Detail"/>
        <div class="col-sm-12">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <ul class="nav nav-pills nav-primary" id="pills-icontab" role="tablist">
                                <li class="nav-item"><a class="nav-link active" id="pills-iconhome-tab" data-bs-toggle="pill" href="#pills-iconhome" role="tab" aria-controls="pills-iconhome" aria-selected="true"><i class="icofont icofont-info"></i>User Information</a></li>
                                <li class="nav-item"><a class="nav-link" id="pills-operating-unit-tab" data-bs-toggle="pill" href="#pills-operating-unit" role="tab" aria-controls="pills-operating-unit" aria-selected="false" v-if="this.item.is_in_representative_unit"><i class="icofont icofont-tools"></i>Operating Unit</a></li>
                            </ul>
                        </div>
                    </div>
                    <div class="tab-content" id="pills-icontabContent">
                        <hr>
                        <div class="tab-pane fade show active" id="pills-iconhome" role="tabpanel" aria-labelledby="pills-iconhome-tab">
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
                        <div class="tab-pane fade show" id="pills-operating-unit" role="tabpanel" aria-labelledby="pills-operating-unit">
                            <OperatingUnit :id="this.$route.params.id" :unit_id="this.item.employee.last_unit.relation_id"/>
                        </div>
                    </div>
                </div>
                <div class="card-footer text-end">
                    <button class="btn btn-secondary" @click="$router.push('/management/users')">Back</button>
                </div>
            </div>
        </div>
    </div>
</template>

<script>

import axios from 'axios';
import { useRoute } from 'vue-router';
import OperatingUnit from "./operating-unit.vue";

export default {
    components: {OperatingUnit},
    data() {
        return {
            item: {
                is_in_representative_unit: false,
                employee: {
                    last_unit: {
                        relation_id: 0
                    }
                }
            },
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
