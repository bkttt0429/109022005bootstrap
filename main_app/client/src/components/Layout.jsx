import React from 'react';
import { Outlet } from 'react-router-dom';
import Header from './Header';
import Footer from './Footer';

export default function Layout() {
    return (
        <div className="d-flex flex-column min-vh-100">
            <Header />
            <div className="flex-grow-1" style={{ marginTop: '80px' }}>
                <Outlet />
            </div>
            <Footer />
        </div>
    );
}
