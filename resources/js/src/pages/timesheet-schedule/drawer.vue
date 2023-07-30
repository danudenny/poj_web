<template>
    <div class="drawer" :class="{ open: isOpen }">
        <slot></slot>
    </div>
</template>

<script>
export default {
    data() {
        return {
            isOpen: false,
        };
    },
};
</script>

<style>
.drawer {
    position: fixed;
    top: 0;
    right: -300px;
    width: 300px;
    height: 100%;
    background-color: #f0f0f0;
    transition: right 0.3s ease-in-out;
    z-index: 999;
}

.drawer.open {
    right: 0;
}
</style>
