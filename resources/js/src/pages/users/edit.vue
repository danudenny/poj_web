<template>
    <div class="container-fluid">
        <Breadcrumbs main="Edit User"/>
        <div class="col-sm-12">
            <form class="card" @submit.prevent="updateUser">

                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Name</label>
                                <input disabled class="form-control" type="text" placeholder="Name" v-model="user.name">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Email address</label>
                                <input disabled class="form-control" type="email" placeholder="Email" v-model="user.email">
                            </div>
                            <div class="mb-3" v-if="user.employee.last_unit">
                                <label class="form-label">Working Unit</label>
                                <input disabled class="form-control" type="text" placeholder="-" v-model="user.employee.last_unit.name">
                            </div>
                            <div class="mb-3">
                                <div role="alert" class="text-danger">
                                    <strong><i class="fa fa-warning"></i> Kosongkan Password Jika Tidak Ingin Mengganti! </strong>
                                </div>
                                <label class="form-label">Password</label>
                                <input class="form-control" type="password" placeholder="Password" v-model="user.password">
                            </div>
                            <div class="mb-3">
                                <div class="mb-2">
                                    <label class="col-form-label">Roles</label>
                                    <multiselect
                                        v-model="user.roles"
                                        placeholder="Select Roles"
                                        label="name"
                                        track-by="id"
                                        :options="roles"
                                        :multiple="true"
                                        :taggable="false">
                                    </multiselect>
                                </div>
                            </div>
                            <div class="mb-3">
                                <div class="mb-2">
                                    <label class="col-form-label">Allowed Operating Unit</label>
                                    <multiselect
                                        v-model="user.allowed_operating_units"
                                        placeholder="Select Allowed Operating Units"
                                        label="name"
                                        track-by="id"
                                        :options="operatingUnits"
                                        :multiple="true"
                                        :taggable="false">
                                    </multiselect>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6" v-if="false">
                            <div class="mb-3" v-if="user.employee.department">
                                <label class="form-label">Department</label>
                                <input disabled class="form-control" type="text" placeholder="-" v-model="user.employee.department.name">
                            </div>
                            <div class="mb-3">
                                <label class="col-form-label">Team of Department</label>
                                <multiselect
                                    v-model="user.employee.team"
                                    placeholder="Select Team"
                                    label="name"
                                    track-by="id"
                                    :options="department.teams"
                                    :multiple="false"
                                    @select="onSelectTeam"
                                >
                                </multiselect>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card-footer text-start">
                    <div class="btn btn-secondary" @click="$router.go(-1)"><i class="fa fa-close"></i> Cancel</div> &nbsp;
                    <button v-if="!isProcess" class="btn btn-primary m-r-10" type="submit"><i class="fa fa-save"></i> Update</button>
                    <button v-if="isProcess" class="btn btn-primary m-r-10 disabled" disabled>...</button>
                </div>
            </form>
        </div>
    </div>
</template>

<script>
import {useRoute} from "vue-router";
import axios from "axios";
import {useToast} from "vue-toastification";

export default {
    data() {
        return {
            user: {
                id: null,
                name: null,
                email: null,
                username: null,
                password: null,
                employee_id: null,
                employee: {
                    last_unit: {
                        name: ''
                    },
                    department: {
                        name: '',
                    },
                    team: {}
                },
                roles: [],
                allowed_operating_units: []
            },
            roles: [],
            operatingUnits: [],
            users: {},
            department: {
                teams: []
            },
            selectedTeam: null,
            isProcess: false
        }
    },
    async mounted() {
        await this.getUser();
        await this.getRoles();
        await this.getTeams();
        this.getOperatingUnits()
    },
    methods: {
        onSelectTeam(e) {
            this.selectedTeam = this.department.teams.find(team => team.id === e.id);
        },
        async getTeams() {
            return
            await this.$axios.get(`/api/v1/admin/department/view/${this.$route.query.dept_id}/${this.$route.query.unit_id}`)
                .then(res => {
                    this.department = res.data.data;
                })
                .catch(e => {
                    console.log(e);
                });
        },
        async getOperatingUnits() {
            await this.$axios.get(`/api/v1/admin/unit/operating-unit`)
                .then(res => {
                    this.operatingUnits = res.data.data;
                })
                .catch(e => {
                    console.log(e);
                });
        },
        async getUser() {
            const route = useRoute();
            await axios
                .get(`/api/v1/admin/user/view?id=`+ route.params.id)
                .then(response => {
                   this.user = response.data.data;
                    this.user.id = response.data.data.id;
                    this.user.name = response.data.data.name;
                    this.user.username = response.data.data.username;
                    this.user.employee_id = response.data.data.employee_id;
                    this.user.email = response.data.data.email;
                    this.user.roles = response.data.data.roles;
                    this.user.employee = response.data.data.employee;
                })
                .catch(error => {
                    console.error(error);
                });
        },
        async getRoles() {
            await this.$axios.get(`/api/v1/admin/role`)
                .then(res => {
                    this.roles = res.data.data;
                })
                .catch(e => {
                    console.log(e);
                });
        },
        async updateUser() {
            let id = this.user.id;
            let name = this.user.name;
            let username = this.user.username;
            let email = this.user.email;
            let password = this.user.password;
            let roles = this.user.roles.map(value => value.id);
            let team = this.selectedTeam;
            let employee_id = this.user.employee_id;
            let allowedOperatingUnits = this.user.allowed_operating_units.map(value => value.relation_id)

            this.isProcess = true

            await this.$axios.post(`/api/v1/admin/user/update`, {
                id: id,
                name: name,
                username: username,
                email: email,
                password: password,
                roles: roles,
                team_id: team?.id,
                employee_id: employee_id,
                allowed_operating_units: allowedOperatingUnits
            })
                .then(res => {
                    this.isProcess = false
                    useToast().success(res.data.message );
                    this.$router.go(-1);
                })
                .catch(e => {
                    this.isProcess = false
                    useToast().error(e.response.data.message);
                });
        },

    },
};
</script>
