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
                                    <option>-- Select Type -- </option>
                                    <option value="superadmin">Superadmin</option>
                                    <option value="admin">Admin</option>
                                    <option value="staff">User / Staff</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-sm-6 col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Role Name</label>
                                <input class="form-control" type="text" placeholder="Name" v-model="role.name">
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
                                <template v-for="(groupAbilities, groupName) in groupedData" :key="groupName">
                                    <tr class="text-center">
                                        <td><b>{{ groupName }}</b></td>
                                        <td v-if="hasAbility(groupAbilities, 'read')">
                                            <input type="checkbox" :id="`read-${groupName}`" :value="getAbilityId(groupAbilities, 'read')" v-model="selectedAbilities">
                                            <label :for="`read-${groupName}`"></label>
                                        </td>
                                        <td v-else>
                                            <input type="checkbox" disabled>
                                        </td>
                                        <td v-if="hasAbility(groupAbilities, 'create')">
                                            <input type="checkbox" :id="`create-${groupName}`" :value="getAbilityId(groupAbilities, 'create')" v-model="selectedAbilities">
                                            <label :for="`create-${groupName}`"></label>
                                        </td>
                                        <td v-else>
                                            <input type="checkbox" disabled>
                                        </td>
                                        <td v-if="hasAbility(groupAbilities, 'update')">
                                            <input type="checkbox" :id="`update-${groupName}`" :value="getAbilityId(groupAbilities, 'update')" v-model="selectedAbilities">
                                            <label :for="`update-${groupName}`"></label>
                                        </td>
                                        <td v-else>
                                            <input type="checkbox" disabled>
                                        </td>
                                        <td v-if="hasAbility(groupAbilities, 'delete')">
                                            <input type="checkbox" :id="`delete-${groupName}`" :value="getAbilityId(groupAbilities, 'delete')" v-model="selectedAbilities">
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
            currentPage: 1,
            pageSize: 20,
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
        await this.getPermissions();
    },
    watch: {
        selectedAbilities: {
            handler() {
                localStorage.setItem('selectedPermission', JSON.stringify(this.selectedAbilities));
            },
            deep: true
        }
    },
    methods: {
        getAbilityId(groupAbilities, abilityName) {
            const ability = groupAbilities.find(item => item.ability === abilityName);
            return ability ? ability.id : null;
        },
        hasAbility(groupAbilities, ability) {
            return groupAbilities.some(item => item.ability === ability);
        },
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
                .get(`/api/v1/admin/permission?limit=200`)
                .then(response => {
                    this.permissions = response.data.data.data;
                })
                .catch(error => {
                    console.error(error);
                });
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
                    useToast().error(e.response.data.message);
                    console.error(e);
                });
        }
    },
};
</script>
