<template>
    <div class="container-fluid">
        <Breadcrumbs main="Edit Job"/>

        <div class="container-fluid">
            <div class="email-wrap bookmark-wrap">
                <div class="row">
                    <div class="col-md-7">
                        <div class="card card-absolute">
                            <div class="card-header bg-primary">
                                <h5>{{job.name}}</h5>
                            </div>
                            <div class="card-body">
                                <div v-if="loading" class="text-center">
                                    <img src="../../assets/loader.gif" alt="loading" width="100">
                                </div>
                                <div class="row">
                                    <div class="col-md-12">
                                        <div class="mb-2">
                                            <label class="col-form-label">Roles</label>
                                            <multiselect
                                                v-model="job.roles"
                                                placeholder="Select Roles"
                                                label="name"
                                                track-by="id"
                                                :options="roles"
                                                :multiple="true"
                                                :taggable="false">
                                            </multiselect>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="card-footer">
                                <div class="d-flex justify-content-end column-gap-2">
                                    <button class="btn btn-success" @click="updateJob">
                                        <i class="fa fa-save"></i> Save
                                    </button>
                                    <button class="btn btn-dark" @click="this.$router.push('/management/job')">
                                        <i class="fa fa-rotate-left"></i> Back
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-5">
                        <div class="card card-absolute">
                            <div class="card-header bg-primary">
                                <h5>Total Units: <span class="badge badge-secondary">{{job.units ? job.units.length : 0}}</span></h5>
                            </div>
                            <div class="card-body">
                                <div v-if="loading" class="text-center">
                                    <img src="../../assets/loader.gif" alt="loading" width="100">
                                </div>
                                <div class="row">
                                    <div class="col-md-12">
                                        <span v-for="unit in job.units" :key="unit.id" class="badge badge-danger">
                                            {{unit.name}}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import {useToast} from 'vue-toastification';

export default {
    data() {
        return {
            job: {},
            loading: false,
            roles: []
        }
    },
    mounted() {
        this.getJob();
        this.getRoles();
    },
    methods: {
        async getRoles() {
            await this.$axios.get(`/api/v1/admin/role`)
                .then(res => {
                    this.roles = res.data.data;
                })
                .catch(e => {
                    console.log(e);
                });
        },
        async getJob() {
            this.loading = true;
            try {
                this.$axios.get(`/api/v1/admin/job/view/${this.$route.params.id}`)
                    .then((response) => {
                        this.job = response.data.data;
                    });
            } catch (e) {
                console.log(e);
            }
            this.loading = false;
        },
        async updateJob() {
            let roles = this.job.roles.map(value => value.id);

            await this.$axios.put(`/api/v1/admin/job/assign-roles/${this.$route.params.id}`, {
                roles: roles
            })
                .then(res => {
                    useToast().success(res.data.message);
                    this.$router.push('/management/job');
                })
                .catch(e => {
                    useToast().error(e.response.data.message);
                    console.error(e);
                });
        },
    }
}

</script>
