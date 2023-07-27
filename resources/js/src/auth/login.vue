<template>
  <div>

    <div class="container-fluid">
      <div class="row ">
        <div class="col-12 p-0">
          <div class="login-card">
            <div>
              <div>
                <a class="logo">
                  <img
                      class="img-fluid for-light"
                      src="http://alakad.optimajasa.co.id:9000/att-poj-bucket/logo/Logo_Aplikasi_Absen-2-2-removebg-preview (1).png"
                      alt="looginpage"
                      width="150"
                  />
                  <img class="img-fluid for-dark" src="../assets/images/logo/logo_dark.png" alt="looginpage" />
                </a>
              </div>
              <div class="login-main">
                <form class="theme-form" @submit.prevent="signin">
                  <h4>Sign in to account</h4>
                  <p>Enter your email & password to login</p>
                  <div class="form-group">
                    <label class="col-form-label">Email Address</label>
                    <input class="form-control" type="email" required="" placeholder="Test@gmail.com"
                      v-model="email">
                    <!-- <span class="validate-error" v-if="!user.email.value || !validEmail(user.email.value)">{{
                      user.email.errormsg }}</span> -->

                  </div>
                  <div class="form-group">
                    <label class="col-form-label">Password</label>
                    <div class="form-input position-relative">
                      <input class="form-control" :type="active?'password':'text'" name="password" required=""
                        placeholder="*********" v-model="password">
                      <!-- <span class="validate-error" v-if="user.password.value.length < 7">{{ user.password.errormsg
                      }}</span> -->

                      <div class="show-hide"><span :class="active?'show':'hide'" @click.prevent="show"> </span></div>
                    </div>
                  </div>
                  <div class="form-group mb-0">
                    <div class="checkbox p-0">
                      <input id="checkbox1" type="checkbox">
                      <label class="text-muted" for="checkbox1">Remember password</label>
                    </div><router-link class="link" to="/auth/forget_password"> Forgot password?</router-link>
                    <div class="text-end mt-3">
                      <button class="btn btn-primary btn-block w-100" type="submit">Sign
                        in</button>

                    </div>
                  </div>

                </form>

              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

  </div>
</template>

<script>

import { mapActions,mapState,mapGetters } from "vuex";

export default {
  name: 'login',
  data() {
    // return {

    //   result: { email: '', password: '' },

    //   user: {
    //     email: {
    //       value: 'test@admin.com',
    //       errormsg: ''
    //     },
    //     password: {
    //       value: 'test@123456',
    //       errormsg: ''
    //     }
    //   }
    // };
    return {
        email: '',
        password: '',
        active: true,
    };
  },

//   created() {

//   },
  methods: {
    ...mapActions(['login']),
    async signin() {
        try {
            await this.login({
                email: this.email,
                password: this.password
            });
            this.$router.push('/');
        } catch (error) {
            console.log(error);
        }
    },
    show() {
       this.active = !this.active
    },
    // login() {
    //   if (!this.user.password.value || this.user.password.value.length < 7) {
    //     this.user.password.errormsg = 'min length 7'
    //   } else { this.user.password.errormsg = '' }

    //   if (!this.user.email.value) {
    //     this.user.email.errormsg = 'empty not allowed'
    //   } else if (!this.validEmail(this.user.email.value)) {
    //     this.user.email.errormsg = 'Valid email required.'
    //   }
    //   else {
    //     this.user.email.errormsg = ''
    //   }
    //   if (!this.user.email.errormsg && !this.user.password.errormsg && this.user.email.value != 'test@admin.com' || this.user.password.value != 'test@123456') {
    //     alert("wrong credenstials")
    //   }
    //   if (!this.user.email.errormsg && !this.user.password.errormsg && this.user.email.value == 'test@admin.com' && this.user.password.value == 'test@123456') {

    //     this.result = { email: this.user.email.value, password: this.user.password.value }

    //     localStorage.setItem('User', JSON.stringify({ email: this.user.email.value, useer: true }))
    //     this.logging = true
    //     this.$router.push('/')


    //   }
    // },
    // validEmail: function (email) {
    //   const re = /^(([^<>()[\]\\.,;:\s@"]+(\.[^<>()[\]\\.,;:\s@"]+)*)|(".+"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/
    //   return re.test(email)
    // }
  },
};
</script>
