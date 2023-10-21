<template>
    <div class="container-fluid">
        <Breadcrumbs main="User Detail"/>
        <div class="col-sm-12">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <ul class="nav nav-pills nav-primary" id="pills-icontab" role="tablist">
                                <li class="nav-item"><a class="nav-link active" id="pills-iconhome-tab" data-bs-toggle="pill" href="#pills-iconhome" role="tab" aria-controls="pills-iconhome" aria-selected="true"><i class="icofont icofont-info"></i>User Information</a></li>
                                <li class="nav-item"><a class="nav-link" id="pills-operating-unit-tab" data-bs-toggle="pill" href="#pills-operating-unit" role="tab" aria-controls="pills-operating-unit" aria-selected="false" v-if="this.item.is_in_representative_unit"><i class="icofont icofont-tools"></i>Operating Unit</a></li>
                                <li class="nav-item"><a class="nav-link" id="pills-central-unit-tab" data-bs-toggle="pill" href="#pills-central-unit" role="tab" aria-controls="pills-central-unit" aria-selected="false" v-if="this.item.is_in_central_unit"><i class="icofont icofont-tools"></i>Central Operating Unit</a></li>
                            </ul>
                        </div>
                    </div>
                    <div class="tab-content" id="pills-icontabContent">
                        <hr>
                        <div class="tab-pane fade show active" id="pills-iconhome" role="tabpanel" aria-labelledby="pills-iconhome-tab">
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="row">
                                        <div v-if="item.avatar" class="col-md-6">
                                            <div class="mb-3">
                                                <p>Foto Profil</p>
                                                <img :src="item.avatar" class="b-r-10" style="width: 100%"/>
                                            </div>
                                        </div>
                                        <div v-if="item.face_initial_url" class="col-md-6">
                                            <div class="mb-3">
                                                <p>Foto Inisial</p>
                                                <img :src="item.face_initial_url" class="b-r-10" style="width: 100%"/>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-5">

                                    <div class="mb-3">
                                        <label class="form-label">Name</label>
                                        <input class="form-control" type="text" placeholder="Name" v-model="item.name" disabled>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Email address</label>
                                        <input class="form-control" type="email" placeholder="Email" v-model="item.email" disabled>
                                    </div>
                                    <div class="mb-3">
                                        <div class="mb-2">
                                            <label class="col-form-label">Roles</label>
                                            <ul>
                                                <li v-for="role in item.roles" class="badge badge-primary">{{ role.name }}</li>
                                            </ul>
                                        </div>
                                    </div>
                                    <div class="mb-3">
                                        <div class="mb-2">
                                            <label class="col-form-label">Allowed Operating Unit</label>
                                            <ul>
                                                <li v-for="operatingUnit in item.allowed_operating_units" class="badge badge-primary">{{ operatingUnit.name }}</li>
                                            </ul>
                                        </div>
                                    </div>
                                    <div class="mb-3" v-if="this.$store.state.permissions?.includes('face-recognition-create')">
                                        <button class="btn btn-secondary" @click="this.resetInitialFace"><i class="fa fa-cog"></i> Reset Initial Face</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="tab-pane fade show" id="pills-operating-unit" role="tabpanel" aria-labelledby="pills-operating-unit">
                            <OperatingUnit :id="this.$route.params.id" :unit_id="this.item.employee.last_unit.relation_id"/>
                        </div>
                        <div class="tab-pane fade show" id="pills-central-unit" role="tabpanel" aria-labelledby="pills-operating-unit">
                            <CentralUnit :id="this.$route.params.id" :unit_id="this.item.employee.last_unit.relation_id"/>
                        </div>
                    </div>
                </div>
                <div class="card-footer text-end">
                    <button class="btn btn-secondary" @click="$router.push('/management/users')"><i class="fa fa-arrow-left"></i> Back</button> &nbsp;
                    <button class="btn btn-primary" @click="this.$router.push({
                        name: 'user-edit',
                        params: {
                            id: this.$route.params.id
                        },
                    })"><i class="fa fa-pencil"></i> Edit</button>
                </div>
            </div>
        </div>
    </div>
</template>

<script>

import axios from 'axios';
import { useRoute } from 'vue-router';
import OperatingUnit from "./operating-unit.vue";
import CentralUnit from "./central-unit.vue";
import {useToast} from "vue-toastification";

export default {
    components: {OperatingUnit, CentralUnit},
    data() {
        return {
            item: {
                avatar: null,
                face_initial_url: null,
                is_in_representative_unit: false,
                is_in_central_unit: false,
                employee: {
                    last_unit: {
                        relation_id: 0
                    },
                    department: {}
                }
            },
            fallbackImageUrl: '',
            didLoad: true,
        }
    },
    mounted() {
        this.getUser();
    },
    methods: {
        getUser() {
            const route = useRoute();
            axios
                .get(`/api/v1/admin/user/view?id=`+ route.params.id)
                .then(response => {
                    this.item = response.data.data;
                })
                .catch(error => {
                    console.error(error);
                });
        },
        resetInitialFace() {
            this.$swal({
                icon: 'warning',
                title:"Apakah Anda Ingin Mereset Data Wajah?",
                text:'Setelah proses reset berhasil, maka data tidak dapat dikembalikan!',
                showCancelButton: true,
                confirmButtonText: 'Ya, reset!',
                confirmButtonColor: '#126850',
                cancelButtonText: 'Batal',
                cancelButtonColor: '#efefef',
            }).then((result)=>{
                if(result.value){
                    this.$axios.post(`api/v1/admin/user/reset_initial_face/${this.$route.params.id}`)
                        .then(async (res) => {
                            useToast().success('Sukses mereset data wajah' , {position: 'bottom-right'});
                        })
                        .catch(error => {
                            useToast().error(error.response.data.message , { position: 'bottom-right' });
                        });
                }
            });
        }
    }
};
</script>
