<template>
    <DataTable
        :apiUrl="`/api/v1/admin/employee`"
        :perPage="10"
        :columns="tableColumns"
        :detail="`/management/employees/`"
        :isEdit="false"
        :isDelete="false"
        :isCreate="false"
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
                { key: 'id', label: 'Id' },
                { key: 'name', label: 'Name' },
                { key: 'status', label: 'Status' },
                { key: 'gender', label: 'Gender' },
                { key: 'mobile_phone', label: 'Phone' },
                { key: 'work_email', label: 'Email' },
                { key: 'company.name', label: 'Comp' },
            ],
            tableFilter: [
                { key: 'name', label: 'Name', type: 'text' },
                { key: 'work_email', label: 'Work Email', type: 'text' },
                { key: 'odoo_employee_id', label: 'Employee ID', type: 'text' },

            ],
        };
    },
};
</script>
