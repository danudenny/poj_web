<template>
    <div class="modal-overlay" v-if="visible" @click="closeModal">
        <div class="modal-container" @click.stop>
            <div class="modal-header">
                <h5 class="modal-title">{{ title }}</h5>
            </div>
            <hr>
            <div class="modal-content">
                <slot></slot>
                <hr>
                <div class="d-flex justify-content-end column-gap-2">
                    <button class="btn btn-secondary" @click="closeModal">
                        <i class="fa fa-close"></i>&nbsp;Close</button>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
export default {
    props: {
        visible: {
            type: Boolean,
            default: false,
        },
        title: {
            type: String,
            required: true
        }
    },
    methods: {
        saveChanges() {
            this.$emit('save');
        },
        closeModal() {
            this.$emit('update:visible', false);
        },
    },
};
</script>

<style>
.modal-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0, 0, 0, 0.5);
    display: flex;
    justify-content: center;
    align-items: center;
    z-index: 9999; /* Ensure the overlay is above other elements on the page */
}

.modal-container {
    background-color: #fff;
    border-radius: 8px;
    width: 400px;
    padding: 20px;
    max-width: 90%; /* Set a maximum width for the modal */
    max-height: 90%; /* Set a maximum height for the modal */
    overflow-y: auto; /* Enable vertical scrolling if the content exceeds the modal height */
}

.modal-content {
    /* Add your custom styles for the modal content here */
}
</style>

