<template>
    <nav>
        <ul class="pagination pagination-primary">
            <li v-if="currentPage > 1" class="page-item">
                <a class="page-link" @click="updateCurrentPage(currentPage - 1)" href="#">Previous</a>
            </li>

            <li class="page-item" v-for="pageNumber in renderedPages" :key="pageNumber" :class="{ active: pageNumber === currentPage }">
                <template v-if="pageNumber === '...'">
                    <span class="page-link">...</span>
                </template>
                <template v-else>
                    <a class="page-link" @click="updateCurrentPage(pageNumber)" href="#">{{ pageNumber }}</a>
                </template>
            </li>

            <li class="page-item" v-if="currentPage < totalPages">
                <a class="page-link" @click="updateCurrentPage(currentPage + 1)" href="#">Next</a>
            </li>
        </ul>
    </nav>
</template>

<script>
export default {
    props: {
        currentPage: {
            type: Number,
            required: true
        },
        totalPages: {
            type: Number,
            required: true
        },
        updateCurrentPage: {
            type: Function,
            required: true
        }
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
};
</script>
