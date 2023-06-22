<template>
    <DataTable
        :apiUrl="`/api/v1/admin/unit?level_name=Kanwil`"
        :perPage="10"
        :columns="tableColumns"
        :detail="`/kanwils/`"
        :isEdit="false"
        :isDelete="false"
        :isCreate="false"
        :isSync="true"
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
                { key: 'no', label: 'No' },
                { key: 'name', label: 'Name' },
                { key: 'create_date', label: 'Create Date' },
                { key: 'write_date', label: 'Write Date' },
            ],
            tableFilter: [
                { key: 'name', label: 'Name', type: 'text' },
            ],
        };
    },
};
</script>
