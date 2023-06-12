<template>
    <DataTableHeader
        :isCreate="false"
    >
    </DataTableHeader>

    <DataTable
        :apiUrl="`/api/v1/admin/employee`"
        :perPage="10"
        :columns="tableColumns"
        :detail="`/management/employees/`"
        :isEdit="false"
        :isDelete="false"
    >
    </DataTable>
</template>

<script>

import DataTable from "../../components/dataTable.vue";
import DataTableHeader from "../../components/dataTableHeader.vue";

export default {
    components: {
        DataTable,
        DataTableHeader,
    },
    data() {
        return {
            tableColumns: [
                { key: 'id', label: 'Id' },
                { key: 'name', label: 'Name' },
                { key: 'status', label: 'Status' },
                { key: 'gender', label: 'Gender' },
                { key: 'mobile_phone', label: 'Phone' },
                { key: 'work_email', label: 'Email' },
                { key: 'company.name', label: 'Comp' },
            ],
        };
    },
};
</script>
