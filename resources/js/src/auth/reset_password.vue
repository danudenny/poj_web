<template>
<div>
    <div class="page-wrapper">
        <div class="container-fluid p-0">
            <div class="row">
                <div class="col-12">
                    <div class="login-card">
                        <div>
                            <div>
                                <a class="logo">
                                    <img
                                        class="img-fluid for-light"
                                        src="../assets/images/logo_square.png"
                                        alt="loginpage"
                                        width="150"
                                    />
                                    <img class="img-fluid for-dark" src="../assets/images/logo/logo_dark.png" alt="looginpage" />
                                </a>
                            </div>
                            <div class="login-main ">
                                <form class="theme-form" @submit.prevent="">
                                    <h4>Create Your Password</h4>
                                    <div class="form-group">
                                        <label class="col-form-label">New Password</label>
                                        <div class="form-input position-relative">

                                            <input class="form-control" :type="active?'password':'text'" v-model="password" required="" placeholder="*********" />
                                            <div class="show-hide"><span :class="active?'show':'hide'" @click.prevent="show"> </span></div>
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label class="col-form-label">Retype Password</label>
                                        <input class="form-control" type="password" v-model="confirmationPassword" required="" placeholder="*********">
                                    </div>
                                    <div class="form-group mb-0">
                                        <div class="checkbox p-0">
                                            <input id="checkbox1" type="checkbox">
                                            <label class="text-muted" for="checkbox1">Remember password</label>
                                        </div>
                                        <button class="btn btn-primary btn-block" type="submit" @click.prevent="resetPassword">Done </button>
                                    </div>

                                </form>
                            </div>
                        </div>
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
            active: true,
            password: '',
            token: this.$route.query.token,
            confirmationPassword: '',
        }
    },
    methods: {
        show() {
            this.active = !this.active
        },
        async resetPassword() {
            console.log(this.$route.query.token)
            if (this.password !== this.confirmationPassword) {
                this.warning_alert_state("Password and confirmation password does not match")
            }
            await axios.post('/api/v1/auth/reset_password', {
                token: this.token,
                password: this.password,
                confirmationPassword: this.confirmationPassword
            })
                .then(response => {
                    this.basic_success_alert("Password has been reset successfully")
                    this.$router.push('login')
                    console.log(response.data)
                })
                .catch(error => {
                    this.warning_alert_state("Failed to reset password")
                    console.error(error)
                });
        },
        basic_success_alert:function(message){
            this.$swal({
                icon: 'success',
                title:'Success',
                text: message,
                type:'success'
            });
        },
        warning_alert_state: function (message) {
            this.$swal({
                icon: "error",
                title: "Failed!",
                text: message,
                type: "error",
            });
        },
    }
}
</script>
