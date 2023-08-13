<template>
    <div class="tab-pane fade" id="pills-storage" role="tabpanel" aria-labelledby="pills-storage-tab">
        <div class="card mb-0">
            <div class="card-header d-flex">
                <h5 class="mb-0">Storage Settings</h5>
            </div>
            <div class="card-body p-4">
                <div class="taskadd">
                    <div class="alert alert-warning" role="alert">
                        <h4 class="alert-heading">Perhatian!</h4>
                        <p class="text-white">Kredensial bersifat rahasia dan hanya bisa dilihat oleh pihak internal. Jangan memberikan kredensial dibawah kepada pihak diluar internal PT Pesonna Optima Jasa.</p>
                    </div>
                    <div class="row">
                        <div class="col-sm-6">
                            <div class="mb-3">
                                <label class="form-label">Access Key</label>
                                <div class="input-group">
                                    <input autocomplete="off" class="form-control" :type="accessKeyInputType" v-model="accessKey" placeholder="" aria-label="">
                                    <span class="input-group-text access-key-toggle-icon" @click="toggleAccessKeyVisibility">
                                        <i :data-feather="accessKeyToggleIcon">
                                            <vue-feather :type="accessKeyToggleIcon"></vue-feather>
                                        </i>
                                    </span>
                                </div>
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="mb-3">
                                <label class="form-label">Secret Key</label>
                                <div class="input-group">
                                    <input autocomplete="off" class="form-control" :type="secretKeyInputType" v-model="secretKey" placeholder="" aria-label="">
                                    <span class="input-group-text" @click="toggleSecretKeyVisibility">
                                        <i :data-feather="secretKeyToggleIcon">
                                            <vue-feather :type="secretKeyToggleIcon"></vue-feather>
                                        </i>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-sm-6">
                            <div class="mb-3">
                                <label>Full URL</label>
                                <input class="form-control" v-model="fullUrl" type="text" placeholder="">
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="mb-3">
                                <label>API Endpoint</label>
                                <input class="form-control" v-model="apiEndpoint" type="text" placeholder="">
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-sm-6">
                            <div class="mb-3">
                                <label>Bucket Name</label>
                                <input class="form-control" v-model="bucketName" type="text" placeholder="">
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
    data() {
        return {
            showAccessKey: false,
            showSecretKey: false,
            accessKey: '',
            accessKeyId: 0,
            secretKey: '',
            secretKeyId: 0,
            fullUrl: '',
            fullUrlId: 0,
            apiEndpoint: '',
            apiEndpointId: 0,
            bucketName: '',
            bucketNameId: 0
        };
    },
    created() {
        this.fetchExistingData();
    },
    computed: {
        accessKeyInputType() {
            return this.showAccessKey ? 'text' : 'password';
        },
        accessKeyToggleIcon() {
            return this.showAccessKey ? 'eye' : 'eye-off';
        },
        secretKeyInputType() {
            return this.showSecretKey ? 'text' : 'password';
        },
        secretKeyToggleIcon() {
            return this.showSecretKey ? 'eye' : 'eye-off';
        }
    },
    methods: {
        fetchExistingData() {
            this.$axios.get('api/v1/admin/setting')
                .then(response => {
                    const result = response.data.data;

                    result.filter(entry => {
                        if (entry.key === 'MINIO_KEY') {
                            this.accessKeyId += entry.id;
                            this.accessKey = entry.value;
                        }
                        if (entry.key === 'MINIO_SECRET') {
                            this.secretKeyId += entry.id;
                            this.secretKey = entry.value;
                        }
                        if (entry.key === 'MINIO_URL') {
                            this.fullUrlId += entry.id;
                            this.fullUrl = entry.value;
                        }
                        if (entry.key === 'MINIO_ENDPOINT') {
                            this.apiEndpointId += entry.id;
                            this.apiEndpoint = entry.value;
                        }
                        if (entry.key === 'MINIO_BUCKET') {
                            this.bucketNameId += entry.id;
                            this.bucketName = entry.value;
                        }
                    });

                })
                .catch(error => {
                    console.error(error);
                });
        },
        async submitData() {
            await this.sendDataToAPI(
                this.accessKey,
                this.secretKey,
                this.fullUrl,
                this.apiEndpoint,
                this.bucketName
            );
        },
        sendDataToAPI(accessKey, secretKey, fullUrl, apiEndpoint, bucketName) {
            const data = [
                { id: this.accessKeyId, key: 'MINIO_KEY', value: accessKey },
                { id: this.secretKeyId, key: 'MINIO_SECRET', value: secretKey },
                { id: this.fullUrlId, key: 'MINIO_URL', value: fullUrl },
                { id: this.apiEndpointId, key: 'MINIO_ENDPOINT', value: apiEndpoint },
                { id: this.bucketNameId, key: 'MINIO_BUCKET', value: bucketName }
            ];

           this.$axios.put('api/v1/admin/setting/bulk-update', data)
                .then(() => {
                    useToast().success('Data successfully saved!');
                    window.location.reload();
                })
                .catch((error) => {
                    this.warning_alert_state();
                    console.error(error);
                });
        },
        basic_success_alert:function(){
            this.$swal({
                icon: 'success',
                title:'Success',
                text:'Data successfully saved!',
                type:'success'
            });
        },
        warning_alert_state: function () {
            this.$swal({
                icon: "error",
                title: "Failed!",
                text: "Failed save data!",
                type: "error",
            });
        },
        toggleAccessKeyVisibility() {
            this.showAccessKey = !this.showAccessKey;
        },
        toggleSecretKeyVisibility() {
            this.showSecretKey = !this.showSecretKey;
        }
    }
}
</script>
