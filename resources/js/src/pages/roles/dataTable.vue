<template>
    <DataTableHeader
        :create="`/management/roles/create`"
    >
    </DataTableHeader>
    <DataTable
        :apiUrl="`/api/v1/admin/role`"
        :perPage="10"
        :columns="tableColumns"
        :detail="`/management/roles/`"
    >
    </DataTable>
</template>
<script >

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
                { key: 'status', label: 'Status' },
                { key: 'is_active', label: 'Is Active' },
            ],
        };
    },
};
</script>
