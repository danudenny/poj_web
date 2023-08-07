<template>
  <div>
    <div class="d-flex justify-content-end mb-2">
      <button @click="openModal" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#assignOperatingUnitModal"><i class="fa fa-plus" /> &nbsp; Assign</button>
    </div>
    <div ref="employeesTable"></div>
  </div>
  <div class="modal fade" id="assignOperatingUnitModal" ref="assignOperatingUnitModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalCenter" aria-hidden="true">
    <form @submit.prevent="onAssignOperatingUnit()">
      <VerticalModal title="Assign Operating Unit">
        <div class="row">
          <div class="col-md-12">
            <multiselect
                v-model="selectedCorporate"
                placeholder="Select Unit"
                label="name"
                track-by="id"
                :options="corporates"
                :multiple="false"
                :required="true"
                @select="onCorporateSelected"
            >
            </multiselect>
            <br/>
            <div ref="kanwilTable"></div>
          </div>
        </div>
      </VerticalModal>
    </form>
  </div>
</template>
<script>
import {useToast} from "vue-toastification";
import Modal from "../../components/modal.vue";
import {TabulatorFull as Tabulator} from "tabulator-tables";
import VerticalModal from "@components/modal/verticalModal.vue";
export default {
  props: {
    id: {
      type: Number,
      required: true
    }
  },
  components: {
    Modal,
    VerticalModal
  },
  data() {
    return {
      isModalVisible: false,
      selectedCorporate: {
        relation_id: ''
      },
      corporates: [],
      pagination: {
        currentPage: 1,
        pageSize: 10
      },
      kanwilPagination: {
        currentPage: 1,
        pageSize: 10
      },
      selectedKanwil: []
    }
  },
  mounted() {
    this.generateOperatingUnitTable()
    this.getCorporate()
  },
  methods: {
    generateOperatingUnitTable() {
      const ls = localStorage.getItem('my_app_token')
      this.table = new Tabulator(this.$refs.employeesTable, {
        paginationCounter:"rows",
        ajaxURL: `/api/v1/admin/operating-unit/kanwils`,
        ajaxConfig: {
          headers: {
            Authorization: `Bearer ${ls}`,
            "X-Unit-Relation-ID": this.$store.state.activeAdminUnit?.unit_relation_id ?? ''
          },
        },
        ajaxParams: {
          page: this.pagination.currentPage,
          size: this.pagination.pageSize,
        },
        ajaxResponse: function (url, params, response) {
          return {
            data: response.data.data,
            last_page: response.data.last_page,
          }
        },
        ajaxURLGenerator: (url, config, params) => {
          let localFilter = {
            name: ''
          }
          params.filter.map((item) => {
            if (item.field === 'kanwil.name') localFilter.name = item.value
          })
          return `${url}?page=${params.page}&per_page=${params.size}&name=${localFilter.name}&representative_office_id${this.id}`
        },
        layout: 'fitColumns',
        renderHorizontal:"virtual",
        height: '100%',
        groupBy: ['operating_unit_corporate_id'],
        progressiveLoad: 'scroll',
        responsiveLayout: true,
        groupStartOpen:true,
        groupHeader: function(value, count, data, group){
          return data[0].operating_unit_corporate.corporate.name;
        },
        columns: [
          {
            title: 'No',
            field: '',
            formatter: 'rownum',
            hozAlign: 'center',
            headerHozAlign: 'center',
            width: 100,
            frozen: true,
          },
          {
            title: 'Name',
            field: 'kanwil.name',
            headerFilter:"input",
            headerHozAlign: 'center',
          },
          {
            title: '',
            formatter: this.viewDetailsFormatter,
            width: 100,
            hozAlign: 'center',
            cellClick: (e, cell) => {
              this.onDelete(cell.getRow().getData().id);
            }
          },
        ],
        filterMode:"remote",
        paginationSize: this.pageSize,
        paginationSizeSelector: [10, 20, 50, 100],
        headerFilter: true,
        paginationInitialPage:1,
        placeholder: 'No Data Available',
      });
      this.loading = false;
    },
    getCorporate() {
      this.$axios.get(`api/v1/admin/unit/paginated?unit_level=3`)
          .then((resp) => {
            this.corporates = resp.data.data
          })
          .catch(error => {
            useToast().error(error.response.data.message, { position: 'bottom-right' });
          });
    },
    generateKanwilTable() {
      const ls = localStorage.getItem('my_app_token')
      const table = new Tabulator(this.$refs.kanwilTable, {
        ajaxURL: '/api/v1/admin/operating-unit/available-kanwil',
        layout: 'fitColumns',
        columns: [
          {
            formatter: "rowSelection",
            hozAlign: "center",
            width: 10,
            headerSort: false,
            titleFormatterParams: {
              rowRange: "active"
            },
            cellClick: function (e, cell) {
              cell.getRow()
            },
          },
          {
            title: 'Kanwil Name',
            field: 'name'
          }
        ],
        pagination: true,
        paginationMode: 'remote',
        responsiveLayout: true,
        filterMode:"remote",
        paginationSize: this.kanwilPagination.pageSize,
        ajaxConfig: {
          headers: {
            Authorization: `Bearer ${ls}`,
            "X-Unit-Relation-ID": this.$store.state.activeAdminUnit?.unit_relation_id ?? ''
          },
        },
        ajaxParams: {
          page: this.kanwilPagination.currentPage,
          size: this.kanwilPagination.pageSize,
        },
        ajaxURLGenerator: (url, config, params) => {
          return `${url}?page=${params.page}&per_page=${params.size}&parent_relation_id=${this.selectedCorporate.relation_id}`
        },
        ajaxResponse: function (url, params, response) {
          return {
            data: response.data.data,
            last_page: response.data.last_page,
          }
        },
        paginationSizeSelector: [10, 20, 50, 100],
        headerFilter: true,
        rowFormatter: (row) => {
          if (this.selectedKanwil.includes(row.getData().relation_id)) {
            row.select()
          }
        },
      });
      table.on("rowSelectionChanged", (data, rows, selected, deselected) => {
        if(selected.length > 0) {
          this.selectedKanwil.push(selected[0].getData().relation_id)
        }
        if (deselected.length > 0) {
          let deselectedID = deselected[0].getData().relation_id
          this.selectedKanwil = this.selectedKanwil.filter((val) => {
            return deselectedID !== val
          })
        }
      })
    },
    viewDetailsFormatter(cell, formatterParams, onRendered) {
      return `<button class="button-icon button-danger" data-id="${cell.getRow().getData().id}"><i class="fa fa-trash"></i> </button>`;
    },
    onDelete(id) {
      this.$swal({
        icon: 'warning',
        title:"Delete Data?",
        text:'Once deleted, you will not be able to recover the data!',
        showCancelButton: true,
        confirmButtonText: 'Yes, delete it!',
        confirmButtonColor: '#e64942',
        cancelButtonText: 'Cancel',
        cancelButtonColor: '#efefef',
      }).then((result)=>{
        if(result.value){
          this.$axios.delete(`api/v1/admin/operating-unit/remove/${id}`)
              .then(() => {
                useToast().success("Data successfully deleted!", { position: 'bottom-right' });
                this.generateOperatingUnitTable()
              })
              .catch(error => {
                useToast().error(error.response.data.message, { position: 'bottom-right' });
              });
        }
      });
    },
    openModal() {
    },
    onCorporateSelected() {
      this.generateKanwilTable()
    },
    onAssignOperatingUnit() {
      this.$axios.post(`api/v1/admin/operating-unit/assign`, {
            representative_office_id: this.id,
            corporates: [{
              unit_relation_id: this.selectedCorporate.relation_id,
              kanwils: this.selectedKanwil
            }]
          })
          .then(() => {
            useToast().success("Data successfully added!", { position: 'bottom-right' });
            this.generateOperatingUnitTable()
            this.selectedCorporate = {
              relation_id: ''
            };
            this.selectedKanwil = []
            this.generateKanwilTable()
          })
          .catch(error => {
            useToast().error(error.response.data.message, { position: 'bottom-right' });
          });
    }
  }
}
</script>


<style>

.button-group {
  float: right !important;
}

.tabulator-group {
  overflow: hidden !important;
}
</style>
