<template>
    <div class="container-fluid">
        <Breadcrumbs :main="$route.name"/>
        <div class="col-sm-12">
            <form class="card" @submit.prevent="addRole">
                <div class="card-body">
                    <div class="row">
                        <div class="col-sm-6 col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Role Level</label>
                                <select class="form-control" v-model="role.level">
                                    <option value="superadmin">Superadmin</option>
                                    <option value="admin">Admin</option>
                                    <option value="staff">User / Staff</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Role Name</label>
                                <input class="form-control" type="text" placeholder="Name" v-model="role.name">
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-sm-6">
                            <div ref="permissionsTable"></div>
                        </div>
                    </div>
                </div>

                <div class="card-footer text-start">
                    <button class="btn btn-primary m-r-10" type="submit">Save</button>
                    <button class="btn btn-secondary" @click="$router.push('/management/roles')">Cancel</button>
                </div>
            </form>
        </div>
    </div>
</template>

<script>
import axios from "axios";
import {useToast} from "vue-toastification";
import {TabulatorFull as Tabulator} from 'tabulator-tables';

export default {
    data() {
        return {
            role: {
                id: null,
                name: null,
                level: null,
            },
            permissions: [],
            selectAll: false,
            selectedPermission: [],
            selectedIds: [],
            table: null,
            currentPage: 1,
            pageSize: 20,
        }
    },
    async mounted() {
        await this.getPermissions();
        await this.initializePermissionsTable();
    },
    methods: {
        toggleSelectAll: function () {
            this.selectAll = !this.selectAll;
            this.role.permissions = [];
            if (this.selectAll) {
                for (let key in this.permissions) {
                    this.role.permissions.push(this.permissions[key]);
                }
            }
        },
        updateCheckall: function(){
            this.selectAll = this.role.permissions.length === this.permissions.length;
        },
        async getPermissions() {
            await axios
                .get(`/api/v1/admin/permission?limit=10`)
                .then(response => {
                    this.permissions = response.data.data.data;
                })
                .catch(error => {
                    console.error(error);
                });
        },
        async initializePermissionsTable() {
            const ls = localStorage.getItem('my_app_token');
            this.table = await new Tabulator(this.$refs.permissionsTable, {
                ajaxURL: `/api/v1/admin/permission?limit=10`,
                ajaxConfig: {
                    headers: {
                        Authorization: `Bearer ${ls}`,
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
                layout: 'fitDataStretch',
                columns: [
                    {
                        formatter: "rowSelection",
                        titleFormatter: "rowSelection",
                        hozAlign: "center",
                        headerSort: false,
                        cellClick: function (e, cell) {
                            cell.getRow()
                        },
                    },
                    {
                        title: 'Permission',
                        field: 'name',
                        headerFilter:"input"
                    }
                ],
                pagination: true,
                paginationMode: 'remote',
                paginationSize: this.pageSize,
                paginationSizeSelector: [10, 20, 50, 100],
                headerFilter: true,
                rowFormatter: (row) => {

                },
            });
            this.table.on("rowSelectionChanged", function(data, rows, selected, deselected)  {
                this.selectedPermission = rows.map(row => row.getData().id);
                localStorage.setItem('selectedPermission', JSON.stringify(this.selectedPermission));
            })
        },
        async addRole() {
            let id = this.role.id;
            let name = this.role.name;
            let role_level = this.role.level;

            let ls = JSON.parse(localStorage.getItem('selectedPermission'));

            await this.$axios.post(`/api/v1/admin/role/save`, {
                id: id,
                name: name,
                role_level: role_level,
                permission: ls
            })
                .then(res => {
                    useToast().success(res.data.message , {
                        position: 'bottom-right'
                    });
                    this.$router.push('/management/roles');
                })
                .catch(e => {
                    useToast().error(e.response.data.message , { position: 'bottom-right' });
                    console.log(e);
                });
        },
        async isChecked(permission) {
            return this.role.permissions.map(val => val.id === permission.id) !== false;
        },
    },
};
</script>
