<template>
    <div class="container-fluid">
        <Breadcrumbs main="Department Edit"/>

        <div class="container-fluid">
            <div class="email-wrap bookmark-wrap">
                <div class="row">
                    <div class="col-md-7">
                        <div class="card card-absolute">
                            <div class="card-header bg-primary">
                                <h5>Department Edit</h5>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-12 d-flex row-gap-3 flex-column">
                                        <div>
                                            <label for="name">Name</label>
                                            <input type="text" class="form-control" id="name" v-model="department.department_name" placeholder="Name" disabled>
                                        </div>
                                        <div>
                                            <label for="unit">Unit</label>
                                            <input type="text" class="form-control" id="unit" v-model="department.unit_name" placeholder="Unit Name" disabled>
                                        </div>
                                        <div>
                                            <label for="total">Select Teams</label>
                                            <multiselect
                                                v-model="department.teams"
                                                :options="teams"
                                                label="name"
                                                track-by="id"
                                                placeholder="Select Team"
                                                :clear-on-select="false"
                                                :preserve-search="true"
                                                :multiple="true"
                                                :close-on-select="true"
                                            >
                                            </multiselect>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="card-footer d-flex justify-content-end column-gap-2">
                                <button class="btn btn-outline-danger" @click="this.$router.push('/management/department')">
                                    <i class="fa fa-rotate-left"></i>&nbsp; Back
                                </button>
                                <button class="btn btn-primary" @click="updateDepartment">
                                    <i class="fa fa-save"></i>&nbsp; Update
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

export default {
    data() {
        return {
            department: {
                department_name: '',
                unit_name: [],
                teams: []
            },
            loading: false,
            syncLoading: false,
            table: null,
            teams: [],
            selectedTeams: []
        }
    },
    async mounted() {
        await this.getDepartment();
        await this.getTeams()
    },
    methods: {
        async getDepartment() {
            this.loading = true;
            await this.$axios.get(`/api/v1/admin/department/view/${this.$route.params.id}/${this.$route.params.unit_id}`)
                .then(response => {
                    this.department = response.data.data;
                })
                .catch(error => {
                    console.error(error);
                })
                .finally(() => {
                    this.loading = false;
                });
        },
        async getTeams() {
            this.loading = true;
            await this.$axios.get(`/api/v1/admin/team?per_page=50`)
                .then(response => {
                    this.teams = response.data.data.data;
                })
                .catch(error => {
                    console.error(error);
                })
                .finally(() => {
                    this.loading = false;
                });
        },
        async updateDepartment() {
            this.loading = true;
            await this.$axios.post(`/api/v1/admin/department/assign-team/${this.$route.params.id}/${this.$route.params.unit_id}`, {
                teams: this.department.teams.map(team => team.id)
            })
                .then(response => {
                    useToast().success(response.data.message);
                    this.$router.push('/management/department');
                })
                .catch(error => {
                    useToast().error(error.response.data.message);
                    console.log(error);
                })
                .finally(() => {
                    this.loading = false;
                });
        }
    }
}

</script>
