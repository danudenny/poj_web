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
                        <div class="col-sm-12">
                            <table class="table table-bordered table-striped">
                                <thead>
                                <tr>
                                    <th>Permission Name</th>
                                    <th>Read</th>
                                    <th>Create</th>
                                    <th>Update</th>
                                    <th>Delete</th>
                                </tr>
                                </thead>
                                <tbody>
                                <template v-for="(groupAbilities, groupName) in groupedData" :key="groupName" >
                                    <tr class="text-center">
                                        <td><b>{{ groupName }}</b></td>
                                        <td v-if="hasAbility(groupAbilities, 'read')">
                                            <input
                                                type="checkbox"
                                                :id="`read-${groupName}`"
                                                :value="getAbilityId(groupAbilities, 'read')"
                                                v-model="selectedAbilities"
                                            >
                                            <label :for="`read-${groupName}`"></label>
                                        </td>
                                        <td v-else>
                                            <input type="checkbox" disabled>
                                        </td>
                                        <td v-if="hasAbility(groupAbilities, 'create')">
                                            <input
                                                type="checkbox"
                                                :id="`create-${groupName}`"
                                                :value="getAbilityId(groupAbilities, 'create')"
                                                v-model="selectedAbilities"
                                            >
                                            <label :for="`create-${groupName}`"></label>
                                        </td>
                                        <td v-else>
                                            <input type="checkbox" disabled>
                                        </td>
                                        <td v-if="hasAbility(groupAbilities, 'update')">
                                            <input
                                                type="checkbox"
                                                :id="`update-${groupName}`"
                                                :value="getAbilityId(groupAbilities, 'update')"
                                                v-model="selectedAbilities"
                                            >
                                            <label :for="`update-${groupName}`"></label>
                                        </td>
                                        <td v-else>
                                            <input type="checkbox" disabled>
                                        </td>
                                        <td v-if="hasAbility(groupAbilities, 'delete')">
                                            <input
                                                type="checkbox"
                                                :id="`delete-${groupName}`"
                                                :value="getAbilityId(groupAbilities, 'delete')"
                                                v-model="selectedAbilities"
                                            >
                                            <label :for="`delete-${groupName}`"></label>
                                        </td>
                                        <td v-else>
                                            <input type="checkbox" disabled>
                                        </td>
                                    </tr>
                                </template>
                                </tbody>
                            </table>
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
            selectedIds: [],
            currentPage: 1,
            pageSize: 20,
            table: null,
            selectedPermissionNames: [],
            selectedAbilities: []
        }
    },
    computed: {
        groupedData() {
            const grouped = {};
            this.permissions.forEach(item => {
                if (!grouped[item.group]) {
                    grouped[item.group] = [];
                }
                grouped[item.group].push(item);
            });
            return grouped;
        }
    },
    async mounted() {
        await this.getRole();
        await this.getPermissions();
    },
    watch: {
        selectedAbilities: {
            handler(newValue) {
                const permissionNames = this.getPermissionNamesFromIds(newValue);
                localStorage.setItem('USER_PERMISSION_TEMP', JSON.stringify(permissionNames));
                localStorage.setItem('selectedPermission', JSON.stringify(newValue));
            },
            deep: true
        }
    },
    methods: {
        getAbilityId(groupAbilities, abilityName) {
            const ability = groupAbilities.find(item => item.ability === abilityName);
            return ability ? ability.id : null;
        },
        hasAbility(groupAbilities, abilityName) {
            return groupAbilities.some(item => item.ability === abilityName);
        },
        async getPermissions() {
            await axios
                .get(`/api/v1/admin/permission?limit=200`)
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
                    if (this.roles.permissions && this.roles.permissions.length > 0) {
                        this.selectedAbilities = this.roles.permissions.map(permission => permission.id);
                        const permissionName = this.roles.permissions.map(permission => permission.name);
                        localStorage.setItem('USER_PERMISSION_TEMP', JSON.stringify(permissionName));
                    }
                })
                .catch(error => {
                    console.error(error);
                });
        },
        getPermissionNamesFromIds(permissionIds) {
            const permissionNames = permissionIds.map(permissionId => {
                const permission = this.permissions.find(item => item.id === permissionId);
                return permission ? permission.name.toLowerCase() : null;
            });

            return permissionNames.filter(name => name !== null);
        },
        async updateRole(){
            const getPermissionTemp = JSON.parse(localStorage.getItem('USER_PERMISSION_TEMP'))
            if (this.roles.name === JSON.parse(localStorage.getItem('USER_ROLES'))) {
                localStorage.setItem('USER_PERMISSIONS', JSON.stringify(getPermissionTemp));
            }
            await this.$axios.post(`/api/v1/admin/role/update`, {
                id: this.roles.id,
                name: this.roles.name,
                is_active: this.roles.is_active,
                role_level: this.roles.role_level,
                permission: this.selectedAbilities
            })
            .then(async (res) => {
                useToast().success(res.data.message);
                this.$router.push('/management/roles');
                if (this.roles.name === JSON.parse(localStorage.getItem('USER_ROLES'))) {
                    localStorage.removeItem('USER_PERMISSION_TEMP');
                    localStorage.removeItem('selectedPermission');
                    setTimeout(() => {
                        window.location.reload();
                    }, 1000);
                }
            })
            .catch(e => {
                console.error(e);
            });

        },
    },
};
</script>
