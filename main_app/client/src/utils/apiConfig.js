// detect if we are in development mode
const isDev = import.meta.env.DEV;

// For development, use the vite proxy '/api'
// For production, use the absolute path relative to the domain root for XAMPP stability
const BASE = isDev
    ? '/api'
    : window.location.pathname.substring(0, window.location.pathname.indexOf('/main_app/')) + '/main_app/api';

export const API_BASE_URL = BASE;
export const API_V1_URL = `${BASE}/v1`;

export default {
    API_BASE_URL,
    API_V1_URL
};
