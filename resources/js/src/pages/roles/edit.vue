<template>
    <div class="container-fluid">
        <Breadcrumbs :main="$route.name"/>
        <div class="col-sm-12">
            <form class="card" @submit.prevent="updateRole">
                <div class="card-body">
                    <div class="row">
                        <div class="col-sm-6 col-md-6">
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
            roles: {},
            permissions: [],
            selectAll: false,
            selectedPermission: []
        }
    },
    async mounted() {
        await this.getRole();
        await this.getPermissions();
        this.initializePermissionsTable();
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
        async updateRole(){
            await axios.post(`/api/v1/admin/role/update`, {
                id: this.roles.id,
                name: this.roles.name,
                permission: this.roles.permission
            })
                .then(res => {
                    useToast().success(res.data.message , { position: 'bottom-right' });
                    this.$router.push('/management/roles');
                    console.log(res);
                })
                .catch(e => {
                    console.log(e);
                });

        },
        initializePermissionsTable() {
            const table = new Tabulator(this.$refs.permissionsTable, {
                data: this.permissions,
                layout: 'fitDataStretch',
                columns: [
                    {
                        formatter: "rowSelection",
                        titleFormatter: "rowSelection",
                        hozAlign: "center",
                        headerSort: false,
                        cellClick: function (e, cell) {
                            cell.getRow().toggleSelect();
                        },
                    },
                    {
                        title: 'Permission',
                        field: 'name'
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
                        console.log(row.select());
                    }
                }
            });
        },
    },
};
</script>
