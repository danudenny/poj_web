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
                                        alt="looginpage"
                                        width="250"
                                    />
                                    <img class="img-fluid for-dark" src="../assets/images/logo/logo_dark.png" alt="looginpage" />
                                </a>
                            </div>
                            <div class="login-main">
                                <form class="theme-form" @submit.prevent="">
                                    <h4>Reset Your Password</h4>
                                    <div class="form-group">
                                        <label class="col-form-label">Enter Your Email Address</label>
                                        <div class="row">
                                            <div class="col-12">
                                                <input class="form-control" type="email" required="" placeholder="your@mail.com">
                                            </div>
                                            <div class="col-12">
                                                <div class="text-end">
                                                    <button class="btn btn-primary btn-block m-t-10" type="submit" @click.prevent="reset">Send</button>
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
export default {
    data() {
        return {
            active: true
        }
    },
    methods: {
        show() {
            this.active = !this.active
        },
        reset() {
            this.$router.push('/auth/reset_password')
        }
    }
}
</script>
