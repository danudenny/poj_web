<template>
    <DataTableHeader
        :create="`/management/users/create`"
    >
    </DataTableHeader>
    <DataTable
        :apiUrl="`/api/v1/admin/user`"
        :perPage="10"
        :columns="tableColumns"
        :detail="`/management/users/`"
    >
    </DataTable>
</template>

<script>

import DataTable from "@/components/dataTable.vue";
import DataTableHeader from "@/components/dataTableHeader.vue";

export default {
  components: {
      DataTableHeader,
      DataTable,
  },
  data() {
      return {
          tableColumns: [
              { key: 'name', label: 'Name' },
              { key: 'username', label: 'Username' },
              { key: 'email', label: 'Email' },
              { key: 'roles', label: 'Role' },
          ],
      };
  },
};
</script>
