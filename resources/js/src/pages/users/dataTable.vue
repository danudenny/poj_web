<template>
    <div class="col-sm-12">
    <div class="card">
      <!-- <div class="card-header">

      </div> -->
      <div class="table-responsive signal-table">
        <table class="table table-hover">
          <thead>
            <tr>
              <th scope="col">Name</th>
              <th scope="col">Status</th>
              <th scope="col">Email</th>
              <th scope="col">Role</th>
              <th scope="col">Actions</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="(item, index) in users" :key="index">
              <th scope="row">{{ item.nik }}</th>
              <td>{{ item.name }}</td>
              <td>{{ item.email }}</td>
              <td>{{ item.role }}</td>
              <td class="btn-showcase">
                <router-link to="/management/users/detail">
                  <button class="btn btn-xs btn-outline-info" type="button" title="detail"><i class="fa fa-eye"></i></button>
                </router-link>
                <router-link to="/management/users/edit">
                  <button class="btn btn-xs btn-outline-warning" title="update"><i class="fa fa-edit"></i></button>
                </router-link>
                <button v-on:click="advanced_danger_alert" class="btn btn-xs btn-outline-danger" title="delete"><i class="fa fa-trash"></i></button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <div class="card-body pagination-padding">
        <nav aria-label="...">
          <ul class="pagination pagination-sm pagination-primary">
            <li class="page-item disabled"><a class="page-link" href="javascript:void(0)" tabindex="-1">Previous</a></li>
            <li class="page-item active"><a class="page-link" href="javascript:void(0)">1</a></li>
            <li class="page-item"><a class="page-link" href="javascript:void(0)">2 <span class="sr-only">(current)</span></a></li>
            <li class="page-item"><a class="page-link" href="javascript:void(0)">3</a></li>
            <li class="page-item"><a class="page-link" href="javascript:void(0)">Next</a></li>
          </ul>
        </nav>
      </div>
      <!-- <div class="pagination">
        <button @click="prevPage" :disabled="page === 1">Previous</button>
        <span>{{ page }}</span>
        <button @click="nextPage" :disabled="page === totalPages">Next</button>
      </div> -->

    </div>
  </div>
  </template>
  <script >

  import { mapState } from 'vuex';
  import getImage from "@/mixins/getImage"
  export default {
    data() {
      return {
        data: [],
        page: 1,
        totalPages: 1,
      };
    },
    mixins: [getImage],
    computed: {
      ...mapState({
        users: state => state.bootsrap.users
      })
    },
    methods: {
      nextPage() {
        if (this.page < this.totalPages) {
          this.page++;
          //fetch data
        }
      },
      prevPage() {
        if (this.page > 1) {
          this.page--;
          //fetch data
        }
      },
      advanced_danger_alert: function() {
        this.$swal({
          text:'Are you sure, you want to do this?',
          showCancelButton: true,
          confirmButtonText: 'Yes, delete it!',
          confirmButtonColor: '#4466f2',
          cancelButtonText: 'Cancel',
          cancelButtonColor: '#efefef',
          reverseButtons: true
        })
      }
    },
  }
  </script>
