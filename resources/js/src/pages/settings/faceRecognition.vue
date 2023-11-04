<template>
    <div class="tab-pane fade" id="pills-face" role="tabpanel" aria-labelledby="pills-face-tab">
        <div class="card mb-0">
            <div class="card-header d-flex">
                <h5 class="mb-0">Face Recognition</h5>
            </div>
            <div class="card-body p-4 text-editor-body">
                <div class="row">
                    <div class="col-sm-6">
                        <div class="mb-3">
                            <label class="form-label">Percentage (%)</label>
                            <div class="input-group">
                                <input autocomplete="off" class="form-control" type="number" v-model="faceRecognitionPercentageValue" placeholder="" aria-label="">
                            </div>
                        </div>
                    </div>
                </div>
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
export default {
    components: {
    },
    data() {
        return {
            key: 'FACE_RECOGNITION_PERCENTAGE',
            faceRecognitionPercentageID: null,
            faceRecognitionPercentageValue: null
        };
    },
    created() {
        this.fetchExistingData()
    },
    methods: {
        fetchExistingData() {
            this.$axios.get('api/v1/admin/setting')
                .then(response => {
                    const result = response.data.data;

                    result.filter(entry => {
                        if (entry.key === this.key) {
                            this.faceRecognitionPercentageID = entry.id
                            this.faceRecognitionPercentageValue = entry.value;
                        }
                    });

                })
                .catch(error => {
                    console.error(error);
                });
        },
        submitData() {
            this.$axios.put(`api/v1/admin/setting/update/${this.faceRecognitionPercentageID}`, {
                key: this.key,
                value: this.faceRecognitionPercentageValue
            })
                .then(() => {
                    useToast().success('Data successfully saved!');
                    window.location.reload();
                })
                .catch((error) => {
                    useToast().error('Data failed to update');
                    console.error(error);
                });
        }
    }
}
</script>

<style>

</style>
