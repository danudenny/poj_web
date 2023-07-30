<template>
  <li class="onhover-dropdown">
    <div>
      <vue-feather type="bell" @click="notification_open()"></vue-feather
      >
      <span class="badge rounded-pill badge-secondary"></span>
    </div>
    <div
      class="onhover-show-div notification-dropdown"
      :class="{ active: notification }"
    >
      <h6 class="f-18 mb-0 dropdown-title bg-primary">Notitications</h6>
      <ul>
        <li><a class="f-w-700" href="#">Check all</a></li>
      </ul>
    </div>
  </li>
</template>

<script>

  export default {
    name: 'Notifications',
    data() {
      return {
        notification: false,
      };
    },
    methods: {
      notification_open() {
        this.notification = !this.notification;
      },
    },
  };
</script>
