import { createApp } from 'vue'
import App from './App.vue'
import router from './router';
import store from './store'
import 'bootstrap/dist/js/bootstrap.bundle'
import './assets/scss/app.scss'
import VueFeather from 'vue-feather';
// import { createI18n } from 'vue-i18n'
import en from './locales/en.json';
import pt from './locales/fr.json';
 import fr from './locales/pt.json';
import es from './locales/es.json';
import { defaultLocale, localeOptions } from './constants/config';
import Breadcrumbs from './components/bread_crumbs.vue';
import VueSweetalert2 from 'vue-sweetalert2';
import 'sweetalert2/dist/sweetalert2.min.css';
import Multiselect from 'vue-multiselect';
import "vue-multiselect/dist/vue-multiselect.css";
import axios from 'axios';

const messages = { en: en, es: es, pt: pt, fr: fr};
const locale = (localStorage.getItem('currentLanguage') && localeOptions.filter(x => x.id === localStorage.getItem('currentLanguage')).length > 0) ? localStorage.getItem('currentLanguage') : defaultLocale;
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

const app = createApp(App)
    .use(router)
    .use(store)
    .use(VueSweetalert2)
    .component('multiselect', Multiselect)
    .component(VueFeather.name, VueFeather)
    .component('Breadcrumbs', Breadcrumbs);
app.config.globalProperties.$axios = axios;
app.mount('#app');
