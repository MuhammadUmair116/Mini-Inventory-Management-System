import axios from 'axios';

window.axios = axios;

window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

const xsrfToken = document.cookie.match(/(?:^|;\s*)XSRF-TOKEN=([^;]*)/)?.[1];

if (xsrfToken) {
    window.axios.defaults.headers.common['X-XSRF-TOKEN'] = decodeURIComponent(xsrfToken);
}
