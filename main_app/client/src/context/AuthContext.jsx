import React, { createContext, useContext, useState, useEffect } from 'react';
import axios from 'axios';
import { API_BASE_URL } from '../utils/apiConfig';

const AuthContext = createContext();

export function useAuth() {
    return useContext(AuthContext);
}

export function AuthProvider({ children }) {
    const [user, setUser] = useState(null);
    const [loading, setLoading] = useState(true);

    useEffect(() => {
        // Initialize token from storage
        const token = localStorage.getItem('authToken');
        if (token) {
            axios.defaults.headers.common['Authorization'] = `Bearer ${token}`;
        }

        const checkStatus = async () => {
            try {
                // Use centralized base URL
                const res = await axios.get(`${API_BASE_URL}/auth_api.php`);
                if (res.data.loggedIn) {
                    setUser(res.data.user);
                }
            } catch (err) {
                console.error("Auth check failed", err);
                // If check fails (e.g. 401), clear token
                localStorage.removeItem('authToken');
                delete axios.defaults.headers.common['Authorization'];
            } finally {
                setLoading(false);
            }
        };
        checkStatus();
    }, []);

    const login = async (email, password) => {
        try {
            // Use centralized base URL
            const res = await axios.post(`${API_BASE_URL}/auth_api.php`, { email, password });
            if (res.data.success) {
                setUser(res.data.user);

                // Store Token
                const token = res.data.token;
                if (token) {
                    localStorage.setItem('authToken', token);
                    axios.defaults.headers.common['Authorization'] = `Bearer ${token}`;
                }

                return { success: true };
            } else {
                return { success: false, error: res.data.error };
            }
        } catch (err) {
            return { success: false, error: 'Network error' };
        }
    };

    const logout = async () => {
        try {
            await axios.post(`${API_BASE_URL}/logout_api.php`);
        } catch (err) {
            console.error("Logout API failed", err);
        }
        setUser(null);
        localStorage.removeItem('authToken');
        delete axios.defaults.headers.common['Authorization'];
    };

    return (
        <AuthContext.Provider value={{ user, login, logout, loading }}>
            {!loading && children}
        </AuthContext.Provider>
    );
}
