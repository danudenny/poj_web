<template>
    <div class="container-fluid">
        <Breadcrumbs :main="$route.name"/>
        <div class="col-sm-12">
            <form class="card" @submit.prevent="updateRole">
                <div class="card-body">
                    <div class="row">
                        <div class="col-sm-6 col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Role Level</label>
                                <select class="form-control" v-model="roles.role_level" id="level">
                                    <option value="superadmin" :selected="roles.role_level === 'superadmin'">Superadmin</option>
                                    <option value="admin" :selected="roles.role_level === 'admin'">Admin</option>
                                    <option value="staff" :selected="roles.role_level === 'staff'">User / Staff</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Role Name</label>
                                <input class="form-control" type="text" placeholder="Name" v-model="roles.name">
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
                    <button class="btn btn-primary m-r-10" type="submit">Update</button>
                    <button class="btn btn-secondary" @click="$router.push('/management/roles')">Cancel</button>
                </div>
            </form>
        </div>
    </div>
</template>

<script>
import {useRoute} from "vue-router";
import axios from "axios";
import {TabulatorFull as Tabulator} from 'tabulator-tables';
import {useToast} from "vue-toastification";

export default {
    data() {
        return {
            roles: {
                level: 'admin'
            },
            permissions: [],
            selectAll: false,
            selectedPermission: [],
            selectedIds: []
        }
    },
    async mounted() {
        await this.getRole();
        await this.getPermissions();
        await this.initializePermissionsTable();
    },
    methods: {
        async getPermissions() {
            await axios
                .get(`/api/v1/admin/permission?per_page=1000`)
                .then(response => {
                    this.permissions = response.data.data.data;
                })
                .catch(error => {
                    console.error(error);
                });
        },
        async getRole() {
            const route = useRoute();
            await axios
                .get(`/api/v1/admin/role/view?id=`+ route.params.id)
                .then(response => {
                    this.roles = response.data.data;
                })
                .catch(error => {
                    console.error(error);
                });
        },
        async initializePermissionsTable() {
            const table = await new Tabulator(this.$refs.permissionsTable, {
                data: this.permissions,
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
                pagination: 'local',
                paginationSize: 20,
                paginationSizeSelector: [10, 20, 50, 100],
                headerFilter: true,
                rowFormatter: (row) => {
                    const permission = row.getData();
                    const matchedPermissionIds = this.roles.permissions.map(p => p.id);
                    permission.allow = matchedPermissionIds.includes(permission.id);
                    if (permission.allow) {
                        row.getElement().classList.add("highlight");
                        row.select();
                    }
                },
            });
            table.on("rowSelectionChanged", function(data, rows, selected, deselected)  {
                this.selectedPermission = rows.map(row => row.getData().id);
                localStorage.setItem('selectedPermission', JSON.stringify(this.selectedPermission));
            })
        },
        async updateRole(){
            const getData = JSON.parse(localStorage.getItem('selectedPermission'))
            await this.$axios.post(`/api/v1/admin/role/update`, {
                id: this.roles.id,
                name: this.roles.name,
                is_active: this.roles.is_active,
                role_level: this.roles.role_level,
                permission: getData
            })
            .then(res => {
                useToast().success(res.data.message , { position: 'bottom-right' });
                this.$router.push('/management/roles')
            })
            .catch(e => {
                console.log(e);
            });

        },
    },
};
</script>
