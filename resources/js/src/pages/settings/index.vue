<template>
    <Breadcrumbs main="General Settings" />

    <div class="container-fluid">
        <div class="email-wrap bookmark-wrap">
            <div class="row">
                <sidebarSetting />
            </div>
        </div>
    </div>
</template>

<script>
import sidebarSetting from './sidebar.vue'
export default {
    components: {
        sidebarSetting
    },
}
</script>
