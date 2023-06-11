<template>
  <div class="page-title">
    <div class="row">
      <div class="col-6">
        <h3>{{main}}</h3>
      </div>
      <div class="col-6">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><router-link :to="{ path: '/' }">
                <vue-feather type="home"></vue-feather>
            </router-link></li>
            <li class="breadcrumb-item" v-if="main">{{main}}</li>
            <li class="breadcrumb-item active" v-if="title">{{title}}</li>
        </ol>
      </div>
    </div>
  </div>

</template>

<script>

  export default {
    components: {},
    props: {
      title: {
        default: ''
      },
      main: {
        default: ''
      }
    }
  };
</script>
