<template>
  <router-link to="/">
      <div v-if="loading" class="spinner-border text-primary" role="status">
          <span class="sr-only">Loading...</span>
      </div>
    <img
      style="height: 49px;"
      class="img-fluid for-light"
      :src="logo"
      alt=""
      width="160"
      @error="errorImage"
    />
  </router-link>
</template>
<script>
import mainLogo from '../../assets/images/logo_main.png'

  export default {
    name: 'Logo',
      data() {
          return {
              logo: '',
              loading: false,
              errorImages: ''
          }
      },
      async mounted() {
          await this.getLogo();
      },
      methods: {
          async errorImage() {
              this.logo = mainLogo;
          },
          async getLogo() {
              this.loading = true
              await this.$axios.get('/api/v1/admin/setting')
                  .then((response) => {
                      response.data.data.map((item) => {
                          if (item.key === 'app_logo') {
                              this.logo = item.value;
                              this.loading = false
                          }
                      })
                  })
          },
      }
  };
</script>


