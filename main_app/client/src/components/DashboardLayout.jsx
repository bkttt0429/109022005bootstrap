import React from 'react';
import { Outlet } from 'react-router-dom';
import Sidebar from './Sidebar';
import { useTheme } from '../context/ThemeContext';

export default function DashboardLayout() {
    const { theme } = useTheme();

    return (
        <div className={`d-flex ${theme === 'dark' ? 'bg-dark' : 'bg-light'}`} style={{ minHeight: '100vh' }}>
            <Sidebar />
            <div className="flex-grow-1 p-4" style={{ marginLeft: '250px' }}>
                <Outlet />
            </div>
        </div>
    );
}
