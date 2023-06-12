<template>
    <DataTableHeader
        :create="`/management/permissions/create`"
    >
    </DataTableHeader>
    <DataTable
        :apiUrl="`/api/v1/admin/permission`"
        :perPage="10"
        :columns="tableColumns"
        :detail="`/management/permissions/`"
    >
    </DataTable>
</template>
<script>

import DataTable from "@/components/dataTable.vue";
import DataTableHeader from "@/components/dataTableHeader.vue";

export default {
    components: {
        DataTable,
        DataTableHeader,
    },
    data() {
        return {
            tableColumns: [
                { key: 'name', label: 'Name' },
            ],
        };
    },
};
</script>
