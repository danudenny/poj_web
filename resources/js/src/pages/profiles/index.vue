<template>
    <div class="container-fluid">
        <Breadcrumbs main="Profiles"/>
        <div class="container-fluid">
            <div class="email-wrap bookmark-wrap">
                <div class="row">
                    <sidebarEmployee />
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import sidebarEmployee from './sidebar.vue'

export default {
    components: {
        sidebarEmployee
    },
};
</script>
