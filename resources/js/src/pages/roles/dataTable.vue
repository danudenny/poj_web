<template>
    <DataTable
        :apiUrl="`/api/v1/admin/role`"
        :perPage="10"
        :columns="tableColumns"
        :detail="`/management/roles/`"
        :create="`/management/roles/create`"
        :filters="tableFilter"
    >
    </DataTable>
</template>
<script >

import DataTable from "@/components/dataTable.vue";

export default {
    components: {
        DataTable,
    },
    data() {
        return {
            tableColumns: [
                { key: 'no', label: 'No' },
                { key: 'name', label: 'Name' },
                { key: 'status', label: 'Status' },
                { key: 'is_active', label: 'Is Active' },
            ],
            tableFilter: [
                { key: 'name', label: 'Name', type: 'text' },
                { key: 'is_active', label: 'Is Active', type: 'selectbox' },
            ],
        };
    },
};
</script>
