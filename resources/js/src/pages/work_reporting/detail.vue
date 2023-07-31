<template>
    <div class="container-fluid">
        <Breadcrumbs main="Work Reporting Detail"/>

        <div class="container-fluid">
            <div class="email-wrap bookmark-wrap">
                <div class="row">
                    <div class="col-md-12">
                        <div class="card card-absolute">
                            <div class="card-header bg-primary">
                                <h5>Work Reporting Detail</h5>
                            </div>
                            <div class="card-body">
                                <div class="row mb-3">
                                    <div class="col-md-3">
                                        <label for="name">Date Time</label>
                                        <input type="text" class="form-control" id="name" v-model="workReporting.date" disabled>
                                    </div>
                                </div>
                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <label for="name">Title</label>
                                        <input type="text" class="form-control" id="name" v-model="workReporting.title" disabled>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="name">Job Type</label>
                                        <input type="text" class="form-control" id="name" v-model="workReporting.job_type" disabled>
                                    </div>
                                </div>
                                <div class="row mb-3">
                                    <div class="col-md-12">
                                        <label for="name">Description</label>
                                        <textarea type="text" class="form-control" id="name" v-model="workReporting.job_description" disabled></textarea>
                                    </div>
                                </div>
                                <div class="row mb-3">
                                    <div class="col-md-12">
                                        <label for="name">Image</label>
                                        <div>
                                            <div v-if="workReporting.image">
                                                <img :src="workReporting.image" alt="image" width="300">
                                            </div>
                                            <span v-else>No Image</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="card-footer">
                                <div class="d-flex justify-content-end">
                                    <button class="btn btn-warning" @click="this.$router.push('/work-reporting')">
                                        <i class="fa fa-arrow-left"></i> Back
                                    </button>
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
export default {
    data() {
        return {
            workReporting: {},
            loading: false
        }
    },
    async mounted() {
        await this.getWorkReporting();
    },
    methods: {
        async getWorkReporting() {
            this.loading = true;
            this.$axios
                .get(`/api/v1/admin/work-reporting/view/${this.$route.params.id}`)
                .then(response => {
                    this.loading = false;
                    this.workReporting = response.data.data;
                })
                .catch(error => {
                    console.error(error);
                });
        },
    }
}
</script>

<style scoped>

</style>

