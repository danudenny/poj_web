<template>
    <DataTable
        :apiUrl="`/api/v1/admin/permission`"
        :perPage="10"
        :columns="tableColumns"
        :detail="`/management/permissions/`"
        :create="`/management/roles/create`"
        :filters="tableFilter"
    >
    </DataTable>
</template>
<script>

import DataTable from "@/components/dataTable.vue";

export default {
    components: {
        DataTable,
    },
    data() {
        return {
            tableColumns: [
                { key: 'name', label: 'Name' },
            ],
            tableFilter: [
                { key: 'name', label: 'Name', type: 'text' }
            ],
        };
    },
};
</script>
