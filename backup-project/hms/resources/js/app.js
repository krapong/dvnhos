import { createApp } from 'vue';
import axios from 'axios';
import HospitalMealApp from './components/HospitalMealApp.vue';

axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
axios.defaults.withCredentials = true;

const assetScript = document.querySelector('script[src*="/build/assets/"]');
const assetPath = assetScript ? new URL(assetScript.src).pathname : '';
const appBasePath = assetPath.replace(/\/build\/assets\/.*$/, '');

if (appBasePath && appBasePath !== '/') {
  axios.defaults.baseURL = appBasePath;
}

const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
if (csrfToken) {
  axios.defaults.headers.common['X-CSRF-TOKEN'] = csrfToken;
}

axios.interceptors.response.use(
  response => response,
  error => {
    if (error.response && error.response.status === 419) {
      window.location.reload();
    }
    return Promise.reject(error);
  }
);

createApp(HospitalMealApp).mount('#app');
