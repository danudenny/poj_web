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
        <form class="card" v-else @submit.prevent="fetchData()">
            <div class="card-body">
                <div class="row">
                    <div v-for="filter in filters" :key="filter.key" class="col-md-4">
                        <div class="mb-1" v-if="filter.type === 'text'">
                            <input class="form-control" v-model="this.params[filter.key]" type="text" :placeholder="filter.label" v-if="filter.type === 'text'">
                        </div>
                        <div class="mb-1" v-else>
                            <select class="form-select" v-model="this.params[filter.key]">
                                <option value="">All</option>
                                <option value="1">Active</option>
                                <option value="0">In-Active</option>
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
    <div class="col-sm-12">
        <div class="card">
            <div class="table-responsive signal-table">
                <table class="table table-hover">
                    <thead>
                    <tr>
                        <th scope="col" v-for="column in columns" :key="column.key">{{ column.label }}</th>
                        <th scope="col">Actions</th>
                    </tr>
                    </thead>
                    <tbody>
                    <tr v-for="item in items" :key="item.id">
                        <td v-for="column in columns" :key="column.key" :class="column.key">
                            <div v-if="column.key === 'status'">
                                <span v-if="item[column.key] === 'In Active'" class="badge badge-danger">{{ item[column.key] }}</span>
                                <span v-else-if="item[column.key] === 'Active'" class="badge badge-success">{{ item[column.key] }}</span>
                                <span v-else>{{ item[column.key] }}</span>
                            </div>

                            <div class="media-body icon-state switch-outline" v-else-if="column.key === 'is_active'">
                                <label class="switch">
                                    <input type="checkbox" :checked="item[column.key] === 1" @click="updateIsActive(item.id)">
                                    <span class="switch-state bg-primary"></span>
                                </label>
                            </div>

                            <div v-else-if="column.key === 'roles'">
                                <ul>
                                    <li v-for="role in item[column.key]" class="badge badge-secondary">{{ role.name }}</li>
                                </ul>
                            </div>

                            <div v-else>
                                {{ item[column.key] }}
                            </div>
                        </td>
                          <td class="btn-showcase">
                            <router-link :to="detail+`detail/`+item.id">
                              <button class="btn btn-xs btn-outline-info" type="button" title="detail"><i class="fa fa-eye"></i></button>
                            </router-link>
                            <router-link v-if="isEdit" :to="detail+`edit/`+item.id">
                              <button class="btn btn-xs btn-outline-warning" title="update"><i class="fa fa-edit"></i></button>
                            </router-link>
                            <button v-if="isDelete" @click="deleteData(item.id)" class="btn btn-xs btn-outline-danger" title="delete"><i class="fa fa-trash"></i></button>
                        </td>
                    </tr>
                    </tbody>
                </table>
                <div class="row row-cols-sm-6 theme-form form-control-sm mt-5 form-bottom">
                    <div class="mb-3 d-flex">
                        <select v-model="params.per_page" class="form-select form-control-sm" @change="fetchData()">
                            <option v-for="option in options" :value="option" :key="option">{{option}}</option>
                        </select>
                    </div>
                    <div class="mb-3 d-flex">
                        <ul class="pagination pagination-sm pagination-primary">
                            <li class="page-item" :class="{'disabled' : this.params.page === 1}" @click="prevPage"><a class="page-link" href="javascript:void(0)" tabindex="-1"> &lt;&lt; </a></li>
                            <li class="page-item active"><a class="page-link" href="javascript:void(0)">{{ this.params.page }}</a></li>
                            <!--                            <li class="page-item"><a class="page-link" href="javascript:void(0)">2 <span class="sr-only">(current)</span></a></li>-->
                            <!--                            <li class="page-item"><a class="page-link" href="javascript:void(0)">3</a></li>-->
                            <li class="page-item" :class="{'disabled' : this.params.page === this.params.totalPages}">
                                <a class="page-link" href="javascript:void(0)" @click="nextPage"> &gt;&gt; </a>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
<script>
import axios from 'axios';
import {useToast} from "vue-toastification";

export default {
    props: {
        apiUrl: {
            type: String,
            required: true,
        },
        perPage: {
            type: Number,
            default: 10,
        },
        columns: {
            type: Array,
            required: true,
        },
        detail: {
            type: String,
            required: false,
        },
        isEdit: {
            type: Boolean,
            required: false,
            default: true,
        },
        isDelete: {
            type: Boolean,
            required: false,
            default: true,
        },
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
    data() {
        return {
            items: [],
            totalItems: 0,
            isFilter: false,
            params : {
                page: 1,
                per_page: this.$props.perPage,
                totalPages: 1,
                name: null,
                is_active: "",
                email: null,
                username: null
            },
            options: [10, 25, 50],
        };
    },
    mounted() {
        this.fetchData();
    },
    computed: {
        totalPages() {
            return Math.ceil(this.totalItems / this.params.per_page)
        },
    },
    methods: {
        fetchData() {
            axios
                .get(this.apiUrl, { params: this.params })
                .then(response => {
                    console.log(response);
                    console.log(response.data.data.current_page);
                    this.items = response.data.data.data;
                    this.totalItems = response.data.data.total;
                    this.params.page = response.data.data.current_page;
                    this.params.totalPages = response.data.data.last_page;
                })
                .catch(error => {
                    console.error(error);
                });
        },
        nextPage() {
            if (this.params.page < this.params.totalPages) {
                this.params.page++;
                this.fetchData();
            }
        },
        prevPage() {
            if (this.params.page > 1) {
                this.params.page--;
                this.fetchData();
            }
        },
        async deleteData(id) {
            try {
                this.$swal({
                    text:'Are you sure, you want to do this?',
                    showCancelButton: true,
                    confirmButtonText: 'Yes, delete it!',
                    confirmButtonColor: '#ff0101',
                    cancelButtonText: 'Cancel',
                    cancelButtonColor: '#efefef',
                    reverseButtons: true
                }).then((result) => {
                    if (result.isConfirmed) {
                        this.$axios.delete(this.apiUrl+`/delete`, {params: {id: id}}).then(res => {
                            useToast().success(res.data.message , { position: 'bottom-right' });
                        }).catch(err => {
                            useToast().error(err.response.data.message , { position: 'bottom-right' });
                        });
                        this.items = this.items.filter((item) => item.id !== id);
                    }
                });

            } catch (e) {
                console.log(e);
            }
        },
        updateIsActive(id) {
            axios
                .post(this.apiUrl+`/toggle-status`, {id: id} )
                .then(response => {
                    useToast().success(response.data.message , { position: 'bottom-right' });
                    console.log(response)
                })
                .catch(error => {
                    useToast().error(error.response.data.message , { position: 'bottom-right' });
                    console.error(error);
                });
        },
        filterCheck() {
            if (this.isFilter) {
                this.isFilter = false;
            } else {
                this.isFilter = true;
            }
        },
    },
};
</script>
