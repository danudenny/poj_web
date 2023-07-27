<template>
    <li class="onhover-dropdown">
        <div class="notification-box">
            <vue-feather type="database" @click="listUnitOpen()"></vue-feather
            >
            <span class="badge rounded-pill badge-secondary"></span>
        </div>
        <div
            class="onhover-show-div notification-dropdown"
            :class="{ active: isUnitOpen }"
        >
            <h6 class="f-18 mb-0 dropdown-title bg-primary">Switch Unit</h6>
            <div>
                <div
                    v-for="(item, index) in adminUnits"
                    :class="'admin-units' + (this.$store.state.activeAdminUnit?.unit_relation_id === item.unit_relation_id ? ' admin-units-active' : '')"
                    @click="onChangeActiveUnit(item)"
                >
                    {{ item.name }}
                </div>
            </div>
        </div>
    </li>
</template>

<script>

export default {
    name: 'Notifications',
    data() {
        return {
            isUnitOpen: false,
            adminUnits: []
        };
    },
    async mounted() {
        this.adminUnits = await this.$store.getters.adminUnits
        this.$store.getters.activeAdminUnit
    },
    methods: {
        listUnitOpen() {
            this.isUnitOpen = !this.isUnitOpen;
        },
        onChangeActiveUnit(data) {
            this.$store.state.activeAdminUnit = data
            this.$store.commit('setActiveAdminUnit', data)
        }
    },
};
</script>

<style>
.admin-units {
    cursor: pointer;
    margin: 10px;
    padding: 10px;
}
.admin-units:hover {
    background-color: #e6e6e6;
    font-weight: bold;
}

.admin-units-active {
    background-color: #126850;
    color: white;
    font-weight: bold;
}
</style>
