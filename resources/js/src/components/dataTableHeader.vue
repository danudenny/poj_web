<template>
    <div class="col-md-12 project-list">
        <div class="card">
            <div class="row">
                <div class="col-md-6 d-flex">
                    <button class="btn btn-xs btn-secondary"> <vue-feather type="filter" title="Filter Data"> </vue-feather></button>
                </div>
                <div class="col-md-6">
                    <div class="form-group mb-0 me-0"></div><router-link class="btn btn-primary" :to="create"> <vue-feather class="me-1" type="plus-square"> </vue-feather>Create</router-link>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
export default {
    props: {
        create: {
            type: String,
            required: false,
        }
    },
}
</script>
