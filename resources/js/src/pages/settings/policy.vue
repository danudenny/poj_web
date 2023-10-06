<template>
    <div class="tab-pane fade" id="pills-policy" role="tabpanel" aria-labelledby="pills-policy-tab">
        <div class="card mb-0">
            <div class="card-header d-flex">
                <h5 class="mb-0">Privacy & Policy</h5>
            </div>
            <div class="card-body p-4 text-editor-body">
                <QuillEditor
                    v-model:content="this.content"
                    theme="snow"
                    :toolbar="'essential'"
                    :content="this.content"
                    contentType="html"
                />
            </div>
            <div class="card-footer d-flex">
                <div class="row">
                    <div class="col">
                        <div class="text-end">
                            <button class="btn btn-success me-3" @click="submitData">
                                <i data-feather="save" class="mr-2">
                                    <vue-feather type="save"></vue-feather>
                                </i>
                                Save
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import {useToast} from 'vue-toastification';
import { QuillEditor } from '@vueup/vue-quill'
import '@vueup/vue-quill/dist/vue-quill.snow.css';
export default {
    components: {
        QuillEditor,
    },
    data() {
        return {
            content: null
        };
    },
    mounted() {
    },
    created() {
        this.fetchExistingData();
    },
    methods: {
        fetchExistingData() {
            this.$axios.get('api/v1/admin/policy')
                .then(response => {
                    const result = response.data.data;
                    this.content = result.content;

                })
                .catch(error => {
                    console.error(error);
                });
        },
        submitData() {
            this.$axios.post('api/v1/admin/policy', {
                content: this.content
            })
                .then(response => {
                    useToast().success('Sukses update T&C');
                })
                .catch(error => {
                    useToast().error('Gagal update T&C');
                });
        }
    }
}
</script>

<style>

</style>
