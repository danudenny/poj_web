<template>
    <div>
        <div ref="employeesTable"></div>
    </div>
</template>

<script>
import {TabulatorFull as Tabulator} from 'tabulator-tables';
import {useToast} from 'vue-toastification';
import axios from "axios";

export default {
    props: {
        id: {
            type: Number,
            required: true
        },
    },
    data() {
        return {
            table: null,
            loading: false,
            syncLoading: false,
            countdown: 0,
            currentPage: 1,
            pageSize: 10,
            timerId: null,
            kanwil: [],
            cabang: [],
            outlet: [],
            area: [],
            filterName: '',
            filterEmail: '',
            filterKanwil: '',
            filterArea: '',
            filterCabang: '',
            filterOutlet: '',
            filterJob: '',
        }
    },
    async mounted() {
        this.initializeEmployeesTable();
    },
    unmounted() {
        this.table.destroy()
        this.table = null
    },
    methods: {
        initializeEmployeesTable() {
            const ls = localStorage.getItem('my_app_token')
            this.table = new Tabulator(this.$refs.employeesTable, {
                paginationCounter:"rows",
                ajaxURL: `/api/v1/admin/employee/paginated`,
                ajaxConfig: {
                    headers: {
                        Authorization: `Bearer ${ls}`,
	                    "X-Selected-Role": this.$store.state.currentRole,
                        "X-Unit-Relation-ID": this.$store.state.activeAdminUnit?.unit_relation_id ?? ''
                    },
                },
                ajaxParams: {
                    page: this.currentPage,
                    size: this.pageSize,
                },
                ajaxResponse: function (url, params, response) {
                    return {
                        data: response.data.data,
                        last_page: response.data.last_page,
                    }
                },
                ajaxURLGenerator: (url, config, params) => {
                    let localFilter = {
                        employeeName: '',
                        jobName: '',
                        workEmail: '',
                        employeeCategory: '',
                        outletName: '',
                        cabangName: ''
                    }
                    params.filter.map((item) => {
                        if (item.field === 'name') localFilter.employeeName = item.value
                        if (item.field === 'job.name') localFilter.jobName = item.value
                        if (item.field === 'work_email') localFilter.workEmail = item.value
                        if (item.field === 'employee_category') localFilter.employeeCategory = item.value
                        if (item.field === 'outlet.name') localFilter.outletName = item.value
                        if (item.field === 'cabang.name') localFilter.cabangName = item.value
                    })

	                return `${url}?page=${params.page}&per_page=${params.size}&append=unit_outlet,unit_cabang,unit_area,unit_kanwil,unit_corporate&unit_relation_id=${this.id}&job_name=${localFilter.jobName}&employee_category=${localFilter.employeeCategory}&name=${localFilter.employeeName}&work_email=${localFilter.workEmail}&outlet_name=${localFilter.outletName}&cabang_name=${localFilter.cabangName}`
                },
                layout: 'fitDataFill',
                renderHorizontal:"virtual",
                height: '100%',
                groupBy: ['outlet.name'],
                progressiveLoad: 'scroll',
                groupStartOpen:false,
                frozenColumn:2,
                columns: [
                    {
                        title: 'No',
                        field: '',
                        formatter: 'rownum',
                        hozAlign: 'center',
                        headerHozAlign: 'center',
                        frozen: true,
                    },
                    {
                        title: 'Name',
                        field: 'name',
                        headerFilter:"input",
                        width: 200,
                        frozen: true,
                        headerHozAlign: 'center',
                    },
                    {
                        title: 'Jobs',
                        field: 'job.name',
                        headerFilter: "input",
                        hozAlign: 'center',
                        headerHozAlign: 'center',
                    },
                    {
                        title: 'Email',
                        field: 'work_email',
                        headerFilter:"input",
                        headerHozAlign: 'center',
                    },
                    {
                        title: 'Employee Type',
                        field: 'employee_category',
                        headerFilter:"input",
                        headerHozAlign: 'center',
                        hozAlign: 'center',
                        formatter: function (cell, formatterParams, onRendered) {
                            const arr =  cell.getValue().split("_");

                            for (let i = 0; i < arr.length; i++) {
                                arr[i] = arr[i].charAt(0).toUpperCase() + arr[i].slice(1);
                            }
                            return cell.getValue() ? arr.join(" ") : '<i class="fa fa-times text-danger"></i>'
                        },
                    },
	                {
		                title: 'Cabang',
		                field: 'cabang.name',
                        headerFilter:"input",
		                hozAlign: 'center',
		                headerHozAlign: 'center',
		                formatter: function (cell, formatterParams, onRendered) {
			                return cell.getValue() ? cell.getValue() : '<i class="fa fa-times text-danger"></i>'
		                },
	                },
                    {
                        title: 'Outlet',
                        field: 'outlet.name',
                        headerFilter:"input",
                        hozAlign: 'center',
                        headerHozAlign: 'center',
                        formatter: function (cell, formatterParams, onRendered) {
                            return cell.getValue() ? cell.getValue() : '<i class="fa fa-times text-danger"></i>'
                        },
                    },
                    {
                        title: '',
                        formatter: this.viewDetailsFormatter,
                        width: 100,
                        hozAlign: 'center',
                        cellClick: (e, cell) => {
                            this.viewData(cell.getRow().getData().id);
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
        viewDetailsFormatter(cell, formatterParams, onRendered) {
            return `<button class="button-icon button-success" data-id="${cell.getRow().getData().id}"><i class="fa fa-eye"></i> </button>`;
        },
        viewData(id) {
            this.$router.push({name: 'employee_detail', params: {id}});
        },
    }
}
</script>
