<template>
    <div class="tab-pane fade" id="pills-mail" role="tabpanel" aria-labelledby="pills-mail-tab">
        <div class="card mb-0">
            <div class="card-header d-flex">
                <h5 class="mb-0">Mail Settings</h5>
            </div>
            <div class="card-body p-4">
                <div class="taskadd">
                    <div class="row">
                        <div class="col-sm-4">
                            <div class="mb-3">
                                <label>Email Type</label>
                                <input class="form-control" v-model="emailType" type="text" placeholder="">
                            </div>
                        </div>
                        <div class="col-sm-4">
                            <div class="mb-3">
                                <label>Email Host</label>
                                <input class="form-control" v-model="emailHost" type="text" placeholder="">
                            </div>
                        </div>
                        <div class="col-sm-4">
                            <div class="mb-3">
                                <label>Email Port</label>
                                <input class="form-control" v-model="emailPort" type="text" placeholder="">
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-sm-6">
                            <div class="mb-3">
                                <label>Email Username</label>
                                <input class="form-control" v-model="emailUsername" type="text" placeholder="">
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="mb-3">
                                <label>Email Password</label>
                                <input class="form-control" v-model="emailPassword" type="password" placeholder="">
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-sm-6">
                            <div class="mb-3">
                                <label>Email Encryption</label>
                                <input class="form-control" v-model="emailEncryption" type="text" placeholder="">
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="mb-3">
                                <label>Email Address</label>
                                <input class="form-control" v-model="emailAddress" type="text" placeholder="">
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
            emailType: '',
            emailHost: '',
            emailPort: '',
            emailUsername: '',
            emailPassword: '',
            emailEncryption: '',
            emailAddress: '',
            emailTypeId: 0,
            emailHostId: 0,
            emailPortId: 0,
            emailUsernameId: 0,
            emailPasswordId: 0,
            emailEncryptionId: 0,
            emailAddressId: 0,
        };
    },
    created() {
        this.fetchExistingData();
    },
    methods: {
        fetchExistingData() {
            axios.get('api/v1/admin/setting?limit=100')
                .then(response => {
                    const result = response.data.data.data;

                    console.log(result)

                    result.filter(entry => {
                        if (entry.key === 'MAIL_TYPE') {
                            this.emailTypeId += entry.id;
                            this.emailType = entry.value;
                        }
                        if (entry.key === 'MAIL_HOST') {
                            this.emailHostId += entry.id;
                            this.emailHost = entry.value;
                        }
                        if (entry.key === 'MAIL_PORT') {
                            this.emailPortId += entry.id;
                            this.emailPort = entry.value;
                        }
                        if (entry.key === 'MAIL_USERNAME') {
                            this.emailUsernameId += entry.id;
                            this.emailUsername = entry.value;
                        }
                        if (entry.key === 'MAIL_PASSWORD') {
                            this.emailPasswordId += entry.id;
                            this.emailPassword = entry.value;
                        }
                        if (entry.key === 'MAIL_ENCRYPTION') {
                            this.emailEncryptionId += entry.id;
                            this.emailEncryption = entry.value;
                        }
                        if (entry.key === 'MAIL_ADDRESS') {
                            this.emailAddressId += entry.id;
                            this.emailAddress = entry.value;
                        }
                    });

                })
                .catch(error => {
                    console.error(error);
                });
        },
        async submitData() {
            await this.sendDataToAPI(
                this.emailType,
                this.emailHost,
                this.emailPort,
                this.emailUsername,
                this.emailPassword,
                this.emailEncryption,
                this.emailAddress,
            );
        },
        sendDataToAPI(emailType, emailHost, emailPort, emailUsername, emailPassword, emailEncryption, emailAddress) {
            const data = [
                { id: this.emailTypeId, key: 'MAIL_TYPE', value: emailType },
                { id: this.emailHostId, key: 'MAIL_HOST', value: emailHost },
                { id: this.emailPortId, key: 'MAIL_PORT', value: emailPort },
                { id: this.emailUsernameId, key: 'MAIL_USERNAME', value: emailUsername },
                { id: this.emailPasswordId, key: 'MAIL_PASSWORD', value: emailPassword },
                { id: this.emailEncryptionId, key: 'MAIL_ENCRYPTION', value: emailEncryption },
                { id: this.emailAddressId, key: 'MAIL_ADDRESS', value: emailAddress }
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
