<template>
    <li class="profile-nav onhover-dropdown pe-0 py-0">
      <div class="media profile-media">
        <img class="b-r-10" src="@/assets/images/dashboard/profile.png" alt="" />
        <div class="media-body">
          <span>Acoder Dev</span>
          <p class="mb-0 font-roboto">
            Admin <i class="middle fa fa-angle-down"></i>
          </p>
        </div>
      </div>
      <ul class="profile-dropdown onhover-show-div">
        <li>
          <router-link to="/">
            <vue-feather type="user"></vue-feather><span>Account </span>
          </router-link>
        </li>
        <!-- <li>
          <vue-feather type="mail"></vue-feather><span>Inbox</span>
        </li> -->
        <!-- <li>
          <vue-feather type="file-text"></vue-feather><span>Taskboard</span>
        </li> -->
        <li>
          <router-link to="/">
            <vue-feather type="settings"></vue-feather><span>Settings</span>
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
    methods: {
        ...mapActions(['logout']),
        async handleLogout() {
            try {
                await this.logout();
                this.$router.push('/auth/login');
            } catch (error) {
                console.log('Logout error:', error);
            }
        },
    },
  };
  </script>
