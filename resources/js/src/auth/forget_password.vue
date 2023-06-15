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
                                        src="http://192.168.100.73:9000/att-poj-bucket/logo/logo_poj-removebg-preview.png"
                                        alt="loginpage"
                                        width="250"
                                    />
                                    <img class="img-fluid for-dark" src="../assets/images/logo/logo_dark.png" alt="looginpage" />
                                </a>
                            </div>
                            <div class="login-main">
                                <form class="theme-form" @submit.prevent="sendResetLink">
                                    <h4>Reset Your Password</h4>
                                    <div class="form-group">
                                        <label class="col-form-label">Enter Your Email Address</label>
                                        <div class="row">
                                            <div class="col-12">
                                                <input class="form-control" type="email" v-model="email" required="" placeholder="your@mail.com">
                                            </div>
                                            <div class="col-12">
                                                <div class="text-end">
                                                    <button class="btn btn-primary btn-block m-t-10" type="submit">Send Link</button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <p class="mt-4 mb-0 text-center">Already have an password? <router-link class="ms-2" tag="a" to="/auth/login">
                                            Login
                                        </router-link>
                                    </p>
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
            email: ''
        }
    },
    methods: {
        async sendResetLink() {
            await axios.post('/api/v1/auth/forget_password', { email: this.email })
                .then(response => {
                    this.basic_success_alert()
                    console.log(response.data)
                })
                .catch(error => {
                    this.warning_alert_state()
                    console.error(error)
                });
        },
        basic_success_alert:function(){
            this.$swal({
                icon: 'success',
                title:'Success',
                text:'Password Reset Link already send via email!',
                type:'success'
            });
        },
        warning_alert_state: function () {
            this.$swal({
                icon: "error",
                title: "Failed!",
                text: "Email Not Found!",
                type: "error",
            });
        },
        show() {
            this.active = !this.active
        },
        reset() {
            this.$router.push('/auth/reset_password')
        }
    }
}
</script>
