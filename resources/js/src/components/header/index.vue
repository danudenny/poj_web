<template>
  <div class="header-wrapper row m-0">
    <div class="nav-right col-xxl-7 col-xl-6 col-md-7 col-8 pull-right right-header p-0 ms-auto">
      <ul class="nav-menus">
        <SwitchRole />
        <Notifications />
        <Profile />
      </ul>
    </div>
  </div>
</template>
<script>
import Notifications from './notifications.vue';
import Profile from './profile.vue';
import SwitchRole from './switch_role.vue';

export default {
  components: {
    Notifications,
    Profile,
    SwitchRole
  },
};
</script>
