import axios from 'axios';
window.axios = axios;

window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

// Ensure CSRF token is sent with requests
// axios.defaults.withCredentials = true;
// axios.defaults.withXSRFToken = true;
