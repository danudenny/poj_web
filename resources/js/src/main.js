import { createApp } from 'vue'
import App from './App.vue'
import router from './router';
import store from './store'
import 'bootstrap/dist/js/bootstrap.bundle'
import VueDatePicker from '@vuepic/vue-datepicker';
import './assets/scss/app.scss'
import VueFeather from 'vue-feather';
import Toast from "vue-toastification";
import "vue-toastification/dist/index.css";

import Breadcrumbs from './components/bread_crumbs.vue';
import VueSweetalert2 from 'vue-sweetalert2';
import 'sweetalert2/dist/sweetalert2.min.css';
import Multiselect from 'vue-multiselect';
import "vue-multiselect/dist/vue-multiselect.css";
import axios from 'axios';
import 'tabulator-tables/dist/css/tabulator.min.css';
import '@vuepic/vue-datepicker/dist/main.css';

const token = localStorage.getItem(store.TOKEN_STORAGE_KEY);
if (token) {
    store.commit('setToken', token);
}

axios.interceptors.request.use(config => {
    const token = localStorage.getItem('my_app_token');
    if (token) {
        config.headers['Authorization'] = `Bearer ${token}`;
    }
    return config;
}, error => {
    return Promise.reject(error);
});

axios.interceptors.request.use(
    (config) => {
        const selectedRole = JSON.parse(localStorage.getItem('USER_ROLES'));
        if (selectedRole) {
            config.headers['X-Selected-Role'] = selectedRole;
        }
        return config;
    },
    (error) => {
        return Promise.reject(error);
    }
);

axios.defaults.baseURL = import.meta.env.VITE_API_URL;

const app = createApp(App)
    .use(router)
    .use(store)
    .use(Toast)
    .use(VueSweetalert2)
    .component('multiselect', Multiselect)
    .component(VueFeather.name, VueFeather)
    .component('VueDatePicker', VueDatePicker)
    .component('Breadcrumbs', Breadcrumbs);

app.config.globalProperties.$axios = axios;
app.mount('#app');
