import axios from 'axios';
window.axios = axios;

window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
// Read the CSRF token injected in the main Blade and send it automatically on AJAX requests.
const tokenMeta = document.querySelector('meta[name="csrf-token"]')
if (tokenMeta) {
	window.axios.defaults.headers.common['X-CSRF-TOKEN'] = tokenMeta.getAttribute('content')
}
// Send cookies on cross-site requests (useful if app served on a different host/port)
window.axios.defaults.withCredentials = true;

// If the XSRF-TOKEN cookie exists (set by Laravel), also set X-XSRF-TOKEN header for libraries that expect it
function readCookie(name) {
	const match = document.cookie.match(new RegExp('(^|; )' + name + '=([^;]*)'))
	return match ? decodeURIComponent(match[2]) : null
}
const xsrf = readCookie('XSRF-TOKEN')
if (xsrf && !window.axios.defaults.headers.common['X-XSRF-TOKEN']) {
	window.axios.defaults.headers.common['X-XSRF-TOKEN'] = xsrf
}

// Also set X-XSRF-TOKEN from the meta tag if cookie is not present
if (!window.axios.defaults.headers.common['X-XSRF-TOKEN'] && tokenMeta) {
    window.axios.defaults.headers.common['X-XSRF-TOKEN'] = tokenMeta.getAttribute('content')
}
