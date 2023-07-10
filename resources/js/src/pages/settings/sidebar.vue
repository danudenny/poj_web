<template>
    <div class="col-xl-3 box-col-6">
        <div class="md-sidebar"><a class="btn btn-primary md-sidebar-toggle" @click="collapse()">bookmark filter</a>
            <div class="md-sidebar-aside job-left-aside custom-scrollbar" :class="filtered ? 'open' : ''">
                <div class="email-left-aside">
                    <div class="card">
                        <div class="card-body">
                            <div class="email-app-sidebar left-bookmark task-sidebar">
                                <ul class="nav main-menu" role="tablist">
                                    <li class="nav-item"><span class="main-title">Settings Menu</span></li>
                                    <li>
                                        <a class="pills-link active" id="pills-app-tab" data-bs-toggle="pill" href="#pills-app" role="tab"
                                           aria-controls="pills-app" aria-selected="true">
                                            <span class="title" @click="active('pills-app-tab')">
                                                <i data-feather="command">
                                                    <vue-feather type="command"></vue-feather>
                                                </i>
                                                App Setting
                                            </span>
                                        </a>
                                    </li>
                                    <li>
                                        <a class="pills-link" id="pills-created-tab" data-bs-toggle="pill" href="#pills-mail" role="tab"
                                           aria-controls="pills-created" aria-selected="true">
                                            <span class="title" @click="active('pills-created-tab')">
                                                <i data-feather="mail">
                                                    <vue-feather type="mail"></vue-feather>
                                                </i>
                                                Email Setting
                                            </span>
                                        </a>
                                    </li>
                                    <li>
                                        <a class="pills-link" id="pills-storage-tab" data-bs-toggle="pill" href="#pills-storage" role="tab"
                                           aria-controls="pills-storage" aria-selected="true">
                                            <span class="title" @click="active('pills-storage-tab')">
                                                <i data-feather="database">
                                                    <vue-feather type="database"></vue-feather>
                                                </i>
                                                Storage Setting
                                            </span>
                                        </a>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-9 col-md-12 box-col-12">
        <div class="email-right-aside bookmark-tabcontent">
            <div class="card email-body radius-left">
                <div class="ps-0">
                    <div class="tab-content">
                        <appSettings />
                        <storageSettings />
                        <mailSettings />
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
<script>
import storageSettings from './storageSetting.vue'
import appSettings from './appSetting.vue'
import mailSettings from './mailSetting.vue'
export default {
    components: {
        storageSettings,
        appSettings,
        mailSettings
    },
    data() {
        return {
            filtered: false,
        }
    },
    methods: {
        active(tabId) {
            document.getElementById(tabId).classList.add('active');
        },
        collapse() {
            this.filtered = !this.filtered
        }
    },
}
</script>

<style scoped>
.pills-link.active {
    background-color: #0A5640;
    transition: background-color 0.3s ease-in;
}
.pills-link.active > span.title {
    color: #fff;
}

.pills-link.active > i {
    color: #fff;
}
</style>
