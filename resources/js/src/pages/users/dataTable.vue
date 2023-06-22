<template>
    <DataTable
        :apiUrl="`/api/v1/admin/user`"
        :perPage="10"
        :columns="tableColumns"
        :detail="`/management/users/`"
        :create="`/management/users/create`"
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
              { key: 'email', label: 'Email' },
              { key: 'roles', label: 'Role' },
              { key: 'is_active', label: 'Is Active' },
          ],
          tableFilter: [
              { key: 'name', label: 'Name', type: 'text' },
              { key: 'email', label: 'Email', type: 'text' },
              { key: 'is_active', label: 'Is Active', type: 'selectbox' },
          ],
      };
  },
};
</script>
