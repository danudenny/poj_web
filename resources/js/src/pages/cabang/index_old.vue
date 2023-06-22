<template>
    <Breadcrumbs title="Working Area / Cabang" />

    <div class="row">
        <div class="col-sm-12 col-md-12">
            <div class="card card-absolute">
                <div class="card-header bg-primary">
                    <h5 class="text-white">Cabang</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped table-bordered .g-success">
                            <thead class="tbl-strip-thad-bdr">
                                <tr>
                                    <th scope="col">Id</th>
                                    <th scope="col">Name</th>
                                    <th scope="col">Code</th>
                                    <th scope="col">Area</th>
                                    <th scope="col">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="(item, index) in result" :key="index">
                                    <th scope="row">{{ index + 1 }}</th>
                                    <td>{{ item.name }}</td>
                                    <td>{{ item.code }}</td>
                                    <td>{{ item.area_id }}</td>
                                    <td></td>
                                </tr>
                            </tbody>
                        </table>
                        <hr>
                        <nav>
                            <ul class="pagination pagination-primary">
                                <li v-if="currentPage > 1" class="page-item">
                                    <a class="page-link" @click="currentPage -= 1" href="#">Previous</a>
                                </li>

                                <li class="page-item" v-for="pageNumber in renderedPages" :key="pageNumber" :class="{ active: pageNumber === currentPage }">
                                    <template v-if="pageNumber === '...'">
                                        <span class="page-link">...</span>
                                    </template>
                                    <template v-else>
                                        <a class="page-link" @click="currentPage = pageNumber" href="#">{{ pageNumber }}</a>
                                    </template>
                                </li>

                                <li class="page-item" v-if="currentPage < totalPages">
                                    <a class="page-link" @click="currentPage += 1" href="#">Next</a>
                                </li>
                            </ul>
                        </nav>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import axios from 'axios'
export default {
    data() {
        return {
            result: null,
            currentPage: 1,
            perPage: 15,
            totalItems: 0,
            totalPages: 0,
        }
    },
    async created() {
        await this.fetchDataCabang();
    },
    computed: {
        renderedPages() {
            const threshold = 4;
            const pageOffset = 2;
            const totalPages = this.totalPages;
            const currentPage = this.currentPage;

            if (totalPages <= threshold) {
                return Array.from({ length: totalPages }, (_, i) => i + 1);
            } else {
                const pages = [1];
                const leftOffset = Math.max(currentPage - pageOffset, 2);
                const rightOffset = Math.min(currentPage + pageOffset, totalPages - 1);

                if (leftOffset > 2) {
                    pages.push('...');
                }

                for (let i = leftOffset; i <= rightOffset; i++) {
                    pages.push(i);
                }

                if (rightOffset < totalPages - 1) {
                    pages.push('...');
                }

                pages.push(totalPages);
                return pages;
            }
        },
    },
    methods: {
        async fetchDataCabang() {
            await axios.get('api/v1/admin/cabang').then(response => {
                this.result = response.data.data.data
                this.totalItems = response.data.data.total;
                this.totalPages = Math.ceil(this.totalItems / this.perPage);
            })
        }
    }
}

</script>
