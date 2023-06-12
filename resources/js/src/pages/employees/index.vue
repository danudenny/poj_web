<template>
    <div class="container-fluid">
        <Breadcrumbs main="Employee Management"/>

        <div class="container-fluid basic_table">
            <div class="row">
                <dataTable/>
            </div>
        </div>
    </div>
</template>

<script>
import dataTable from './dataTable.vue'

export default {
    components: {
        dataTable,
    }
}
</script>
