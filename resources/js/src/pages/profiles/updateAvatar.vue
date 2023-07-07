<template>
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Change Profile Image</h5>
                <button class="btn-close" type="button" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div>
                    <div size="120" class="user mb-5">
                        <img :src="cropedImage === '' ? avatars : cropedImage" class="profile-img">
                        <i class="icon fa fa-upload" @click="$refs.FileInput.click()"></i>
                        <input ref="FileInput" type="file" accept="image/jpeg,image/jpg,image/png" style="display: none;" @change="onFileSelect" />
                    </div>
                    <v-dialog v-model="dialog" width="500">
                        <v-card>
                            <v-card-text>
                                <VueCropper v-show="selectedFile" ref="cropper" :src="selectedFile" :crop="cropImage" alt="Source Image"></VueCropper>
                            </v-card-text>
                        </v-card>
                    </v-dialog>
                </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" type="button" data-bs-dismiss="modal" @click="onCancel">Cancel</button>
                <button class="btn btn-primary" type="button" :disabled="!isCrop" @click="saveImage()" data-bs-dismiss="modal">Save Change</button>
            </div>
        </div>
    </div>
    </div>
</template>

<script>
import { mapState } from 'vuex'
import axios from 'axios'
import VueCropper from 'vue-cropperjs';
import 'cropperjs/dist/cropper.css';
import { useToast } from "vue-toastification";
export default {
    components: {
        VueCropper,
    },
    props: ['image_name', 'avatars', 'user_id'],
    data() {
        return {
            mime_type: '',
            cropedImage: '',
            autoCrop: false,
            selectedFile: '',
            image: '',
            dialog: false,
            files: '',
            isCrop: false,
        }
    },
    computed: {
        ...mapState(['user', 'avatar']),
    },
    mounted() {

    },
    methods: {
        async saveImage() {
            this.cropedImage = this.$refs.cropper.getCroppedCanvas().toDataURL()
            await this.$refs.cropper.getCroppedCanvas().toBlob((blob) => {

                const formData = new FormData();
                let reader = new FileReader();
                reader.readAsDataURL(blob);
                let userId = this.user_id;
                let mimeType = this.mime_type;

                reader.onloadend = function() {
                    let base64data = reader.result;
                    //console.log(base64data);

                    formData.append('avatar', base64data)
                    formData.append('name', 'avatar-' + new Date().getTime())
                    formData.append('mime', mimeType)
                    axios
                        .post('/api/v1/admin/user/' + userId + '/avatar', formData)
                        .then((response) => {
                            console.log(response);
                            localStorage.setItem('USER_AVATAR', response.data.data.avatar);
                            useToast().success(response.data.message , { position: 'bottom-right' });
                            window.location.reload()
                        })
                        .catch(function (error) {
                            console.log(error)
                            useToast().error(error.response.data.message , { position: 'bottom-right' });
                        })
                }

            })
        },
        onFileSelect(e) {
            const file = e.target.files[0]
            this.mime_type = file.type
            console.log(this.mime_type)
            if (typeof FileReader === 'function') {
                this.dialog = true
                const reader = new FileReader()
                reader.onload = (event) => {
                    this.selectedFile = event.target.result
                    this.$refs.cropper.replace(event.target.result)
                }
                reader.readAsDataURL(file)

            } else {
                useToast().error('Sorry, System not supported', { position: 'bottom-right' });
            }
        },
        cropImage() {
            this.isCrop = true;
            this.image = this.$refs.cropper.getData();
            this.cropedImage = this.$refs.cropper.getCroppedCanvas().toDataURL();
        },
        onCancel() {
            this.$refs.cropper.clear();
            this.image = '';
            this.selectedFile = '';
            this.cropedImage = '';
            this.isCrop = false;
        },
    },
};
</script>
<style scoped>
.user {
    width: 140px;
    height: 140px;
    border-radius: 100%;
    border: 3px solid #2e7d32;
    position: relative;
}
.profile-img {
    height: 100%;
    width: 100%;
    border-radius: 50%;
}
.icon {
    position: absolute;
    top: 10px;
    right: 0;
    background: #e2e2e2;
    border-radius: 100%;
    width: 30px;
    height: 30px;
    line-height: 30px;
    vertical-align: middle;
    text-align: center;
    color: #298a08;
    font-size: 17px;
    cursor: pointer;
}
</style>
