// import './bootstrap';
import { createApp } from 'vue/dist/vue.esm-bundler.js';
import SampleComponent from './components/SampleComponent.vue';

createApp({
    components: {
        SampleComponent
    }
}).mount('#app');
