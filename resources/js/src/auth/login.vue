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
                      :src="logo"
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
    return {
        email: '',
        password: '',
        active: true,
        logo: ''
    };
  },
    async mounted() {
        await this.getLogo();
    },
    methods: {
      async getLogo() {
          await this.$axios.get('/api/v1/admin/setting')
              .then((response) => {
                  response.data.data.map((item) => {
                      if (item.key === 'app_logo') {
                          this.logo = item.value;
                          console.log(this.logo)
                      }
                  })
              })
      },
    ...mapActions(['login']),
    async signin() {
        try {
            await this.login({
                email: this.email,
                password: this.password
            });
            let exist = localStorage.getItem("my_app_token")
            if (exist != null) {
                window.location.href = "/"
            }
        } catch (error) {
            console.log(error);
        }
    },
    show() {
       this.active = !this.active
    },
  },
};
</script>
