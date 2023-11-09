<template>

    <div class="container-fluid">
        <Breadcrumbs main="Dashboard"/>
    </div>

</template>

<script>
export default {
    mounted() {
        console.log("incoming")
    },
    unmounted() {
        console.log("destroy")
    }
}
</script>
