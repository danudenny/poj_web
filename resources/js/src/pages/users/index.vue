<template xmlns="http://www.w3.org/1999/html">

    <div class="container-fluid">
        <Breadcrumbs main="Users Management"/>

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
