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
                            label="corporate_name"
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
    <button id="onDeleteOperatingUnitUser" style="display:none" @click="this.onDelete"></button>
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
        },
        unit_id: {
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
                id: ''
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
            selectedKanwil: null,
        }
    },
    mounted() {
        this.generateOperatingUnitTable()
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
                    return `${url}?page=${params.page}&per_page=${params.size}&name=${localFilter.name}&user_id=${this.id}`
                },
                layout: 'fitColumns',
                renderHorizontal:"virtual",
                height: '100%',
                groupBy: ['operating_unit_corporate_id'],
                progressiveLoad: 'scroll',
                responsiveLayout: true,
                groupStartOpen:true,
                groupHeader: (value, count, data, group) => {
                    console.log()
                    return data[0].operating_unit_corporate.corporate.name + `<button class="button-icon button-danger button-group" onclick="document.getElementById('onDeleteOperatingUnitUser').setAttribute('data-id-operating-unit', ${data[0].operating_unit_corporate.id});document.getElementById('onDeleteOperatingUnitUser').click()" data-action="delete" data-row-id="${data[0].operating_unit_corporate.id}"><i class="fa fa-trash"></i> </button>`;
                },
                headerClick: (e, group) => {
                    console.log(e)
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
            this.$axios.get(`api/v1/admin/operating-unit/corporates?representative_unit_id=${this.unit_id}`)
                .then((resp) => {
                    this.corporates = resp.data.data
                })
                .catch(error => {
                    useToast().error(error.response.data.message, { position: 'bottom-right' });
                });
        },
        openModal() {
            this.getCorporate()
        },
        onDelete(e) {
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
                    this.$axios.post(`api/v1/admin/operating-unit/remove-user`, {
                        user_id: this.id,
                        operating_unit_corporate_id: e.target.dataset.idOperatingUnit
                    })
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
        onCorporateSelected() {
        },
        onAssignOperatingUnit() {
            this.$axios.post(`api/v1/admin/operating-unit/assign-user`, {
                user_id: this.id,
                operating_unit_corporate_id: this.selectedCorporate.id
            })
                .then(() => {
                    useToast().success("Data successfully added!", { position: 'bottom-right' });
                    this.generateOperatingUnitTable()
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
