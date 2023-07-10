<template>
    <div class="tab-pane fade active show" id="pills-app" role="tabpanel" aria-labelledby="pills-app-tab">
        <div class="card mb-0">
            <div class="card-header d-flex">
                <h5 class="mb-0">App Settings</h5>
            </div>
            <div class="card-body p-4">
                <div class="taskadd">
                    <div class="row">
                        <div class="col">
                            <div class="mb-3">
                                <label>App Name</label>
                                <input class="form-control" v-model="appName" type="text" placeholder="">
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col">
                            <div class="mb-3">
                                <label>App Logo</label>
                                <input class="form-control" @change="handleLogoUpload" type="file" accept="image/*" placeholder="">
                                <div class="mt-2">
                                    <img v-if="appLogoURL" :src="appLogoURL" alt="App Logo" style="width: 40%"/>
                                </div>
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
import axios from "axios";
export default {
    data() {
        return {
            appNameId: 0,
            appLogoId: 0,
            appName: '',
            appLogoFile: null,
            appLogoURL: '',
        };
    },
    created() {
        this.fetchExistingData();
    },
    methods: {
        fetchExistingData() {
            this.$axios.get('api/v1/admin/setting?limit=100')
                .then(response => {
                    const result = response.data.data;

                    result.filter(entry => {
                        if (entry.key === 'app_name') {
                            this.appNameId += entry.id;
                            this.appName = entry.value;
                        }
                        if (entry.key === 'app_logo') {
                            this.appLogoId += entry.id;
                            this.appLogoURL = entry.value;
                        }
                    });

                })
                .catch(error => {
                    console.error(error);
                });
        },
        handleLogoUpload(event) {
            this.appLogoFile = event.target.files[0];
            const reader = new FileReader();
            reader.onload = () => {
                this.appLogoURL = reader.result;
            };

            if (this.appLogoFile) {
                reader.readAsDataURL(this.appLogoFile);
            }
        },
        async submitData() {
            if (this.appLogoFile) {
                await this.uploadImage()
                    .then((logoURL) => {
                        this.sendDataToAPI(this.appName, logoURL);
                    })
                    .catch((error) => {
                        console.error(error);
                    });
            } else {
                this.sendDataToAPI(this.appName, this.appLogoURL);
            }

        },
        async uploadImage() {
            return new Promise(async (resolve, reject) => {
                const formData = new FormData();
                formData.append('file', this.appLogoFile);

                await axios.post('api/v1/upload-files', formData)
                    .then((response) => {
                        const logoURL = response.data.path;
                        resolve(logoURL);
                    })
                    .catch((error) => {
                        reject(error);
                    });
            });
        },
        sendDataToAPI(appName, appLogoURL) {
            const data = [
                { id: this.appNameId, key: 'app_name', value: appName },
                { id: this.appLogoId, key: 'app_logo', value: appLogoURL }
            ];

            axios.put('api/v1/admin/setting/bulk-update', data)
                .then((response) => {
                    this.basic_success_alert();
                    console.log(response);
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
    }
}
</script>

<style scoped>
.dropzone__item-thumbnail  {
    width: 100%;
    height: 100%;
}

.dropzone__item-thumbnail > img {
    width: 100%;
}
</style>
