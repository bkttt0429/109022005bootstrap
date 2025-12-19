/**
 * Centralized API configuration to handle paths in both development and production.
 * In development (Vite), we use the '/api' proxy.
 * In production (dist folder), we use relative path '../../api' to point to 'main_app/api'.
 */

const isDev = import.meta.env.DEV;

// For development, use the proxy path. 
// For production, use the relative path from the 'dist' folder to the 'api' folder.
export const API_BASE_URL = isDev ? '/api' : './api';

export default {
    API_BASE_URL
};
