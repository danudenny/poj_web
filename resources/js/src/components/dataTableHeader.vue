<template>
    <div class="col-md-12 project-list">
        <div class="card" v-if="isFilter === false">
            <div class="row">
                <div class="col-md-6 d-flex">
                    <button class="btn btn-xs btn-secondary" @click="filterCheck()"> <vue-feather class="mt-1 mb-sm-1" type="filter" title="Filter Data"> </vue-feather></button>
                </div>
                <div class="col-md-6" v-if="isCreate">
                    <div class="form-group mb-0 me-0"></div><router-link class="btn btn-primary" :to="create"> <vue-feather class="me-1" type="plus-square"> </vue-feather>Create</router-link>
                </div>
            </div>
        </div>
        <form class="card" v-else>
            <div class="card-body">
                <div class="row">
                    <div v-for="filter in filters" :key="filter.key" class="col-md-4">
                        <div class="mb-1" v-if="filter.type === 'text'">
                            <input class="form-control" type="text" :placeholder="filter.label" v-if="filter.type === 'text'">
                        </div>
                        <div class="mb-1" v-else>
                            <select class="form-control">
                                <option disabled selected>Status</option>
                                <option>Active</option>
                                <option>In-Active</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card-footer text-start">
                <button class="btn btn-secondary m-l-10" @click="filterCheck()"> <vue-feather type="x" title="Filter Data"> </vue-feather></button>
                <button class="btn btn-primary"> <vue-feather type="search"> </vue-feather>Filter</button>
            </div>
        </form>
    </div>
</template>

<script>
export default {
    data() {
        return {
            isFilter: false,
        }
    },
    props: {
        create: {
            type: String,
            required: false,
        },
        isCreate: {
            type: Boolean,
            required: false,
            default: true,
        },
        filters: {
            type: Array,
            required: true,
        },
    },
    methods: {
        filterCheck() {
            if (this.isFilter) {
                this.isFilter = false;
            } else {
                this.isFilter = true;
            }
        },
    },
}
</script>
