<template>
  <div class="page-title">
    <div class="row">
      <div class="col-6">
        <h3>{{main}}</h3>
      </div>
      <div class="col-6">
        <ol class="breadcrumb">
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
        default: 'home'
      },
      main: {
        default: ''
      }
    }
  };
</script>
