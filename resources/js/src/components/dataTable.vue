<template>
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
                <div class="card-body pagination-padding">
                    <nav aria-label="...">
                      <ul class="pagination pagination-sm pagination-primary">
                        <li class="page-item" :class="{'disabled' : page === 1}" @click="prevPage"><a class="page-link" href="javascript:void(0)" tabindex="-1">Prev</a></li>
                        <li class="page-item active"><a class="page-link" href="javascript:void(0)">{{ page }}</a></li>
    <!--                            <li class="page-item"><a class="page-link" href="javascript:void(0)">2 <span class="sr-only">(current)</span></a></li>-->
    <!--                            <li class="page-item"><a class="page-link" href="javascript:void(0)">3</a></li>-->
                        <li class="page-item" :class="{'disabled' : page === totalPages}">
                            <a class="page-link" href="javascript:void(0)" @click="nextPage">Next</a>
                        </li>
                      </ul>
                </nav>
                </div>
            </div>
        </div>
    </div>
</template>
<script>
import axios from 'axios';

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
        }
    },
    data() {
        return {
            items: [],
            page: 1,
            totalPages: 1,
        };
    },
    mounted() {
        this.fetchData();
    },
    methods: {
        fetchData() {
            axios
                .get(this.apiUrl, { params: { per_page: this.perPage, page: this.page } })
                .then(response => {
                    this.items = response.data.data.data;
                    this.totalPages = response.data.data.last_page;
                })
                .catch(error => {
                    console.error(error);
                });
        },
        nextPage() {
            if (this.page < this.totalPages) {
                this.page++;
                this.fetchData();
            }
        },
        prevPage() {
            if (this.page > 1) {
                this.page--;
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
                        this.$axios.delete(this.apiUrl+`/delete`, {params: {id: id}});
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
                    console.log(response)
                })
                .catch(error => {
                    console.error(error);
                });
        }
    },
};
</script>
