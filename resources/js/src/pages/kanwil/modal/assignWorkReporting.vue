<template>
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title" id="myLargeModalLabel">{{modalTitle}}</h4>
                <button class="btn-close" type="button" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <slot></slot>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    Close
                </button>
                <button type="button" class="btn btn-primary" @click="saveChanges">
                    Save changes
                </button>
            </div>
        </div>
    </div>
</template>

<script>
export default {
    props: {
        modalTitle: {
            type: String,
            default: 'Modal title'
        }
    },
    methods: {
        saveChanges() {
            this.$emit('save');
        }
    }
}
</script>
