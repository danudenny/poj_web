<template>
    <li class="profile-nav onhover-dropdown pe-0 py-0">
      <div class="media profile-media" style="width: 250px">
        <img class="b-r-10 img-40" :src="avatars" alt="avatar" />
        <div class="media-body">
          <span style="font-size: 13px"><b>{{ user.name }}</b></span>
          <p class="mb-0 text-warning" style="font-size: 12px">
            {{ user.last_units.name}} <i class="middle fa fa-angle-down"></i>
          </p>
        </div>
      </div>
      <ul class="profile-dropdown onhover-show-div">
        <li>
          <router-link to="/profile">
            <vue-feather type="user"></vue-feather><span>Profile</span>
          </router-link>
        </li>
        <li>
          <a @click="handleLogout">
            <vue-feather type="log-in"></vue-feather><span>Log out</span>
          </a>
        </li>
      </ul>
    </li>
  </template>

  <script>
  import {mapActions} from "vuex";

  export default {
    name: 'Profile',
    data() {
        return {
            user: {
                name: '',
                email: '',
                last_units: {
                    name: '',
                }
            },
            avatars: '',
            profileImg: '',
        }
    },
    async mounted() {
      await this.getUser;
      await this.getUserAvatar;
      this.avatars = `https://ui-avatars.com/api/?name=${this.user.name}&background=0A5640&color=fff&length=2&rounded=false&size=32`
    },
    methods: {
        ...mapActions(['logout']),
        async handleLogout() {
            try {
                await this.logout();
                this.$router.push('/auth/login');
                window.location.reload()
            } catch (error) {
                console.log('Logout error:', error);
            }
        },

    },
      computed: {
          getUser() {
              const getUser = localStorage.getItem('USER_STORAGE_KEY');
              this.user = JSON.parse(getUser)
              return this.user
          },
          getUserAvatar() {
              this.profileImg = localStorage.getItem('USER_AVATAR');
              return this.profileImg;
          },
      }
  };
  </script>
