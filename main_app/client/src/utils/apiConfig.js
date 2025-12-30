// detect if we are in development mode
const isDev = import.meta.env.DEV;

// For development, use the vite proxy '/api'
// For production, use the absolute path relative to the domain root for XAMPP stability
export const API_BASE_URL = isDev
    ? '/api'
    : window.location.pathname.substring(0, window.location.pathname.indexOf('/main_app/')) + '/main_app/api';

export default {
    API_BASE_URL
};
