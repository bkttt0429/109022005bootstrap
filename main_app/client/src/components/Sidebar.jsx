import React from 'react';
import { Nav } from 'react-bootstrap';
import { Link, useLocation } from 'react-router-dom';
import { FaChartLine, FaBox, FaShoppingBag, FaRobot, FaCog, FaSignOutAlt, FaMoneyBillWave } from 'react-icons/fa';
import { useAuth } from '../context/AuthContext';
import { useTheme } from '../context/ThemeContext';

export default function Sidebar() {
    const location = useLocation();
    const { logout } = useAuth();
    const { theme } = useTheme();

    const isActive = (path) => location.pathname === path;

    const navItems = [
        { path: '/dashboard', label: '總覽', icon: <FaChartLine /> },
        { path: '/dashboard/products', label: '商品管理', icon: <FaBox /> },
        { path: '/dashboard/orders', label: '訂單管理', icon: <FaShoppingBag /> },
        { path: '/dashboard/kanban', label: '訂單看板', icon: <FaChartLine /> },
        { path: '/dashboard/chat', label: 'RAG 助理', icon: <FaRobot /> },
        { path: '/dashboard/accounting', label: '會計系統', icon: <FaMoneyBillWave /> },
    ];

    return (
        <div className={`sidebar d-flex flex-column p-3 h-100 ${theme === 'dark' ? 'bg-dark text-white border-end border-secondary' : 'bg-white text-dark border-end'}`} style={{ width: '250px', minHeight: '100vh', position: 'fixed', top: 0, left: 0, overflowY: 'auto' }}>
            <Link to="/" className="d-flex align-items-center mb-3 mb-md-0 me-md-auto text-decoration-none fs-4 fw-bold p-2">
                <span className={theme === 'dark' ? 'text-white' : 'text-primary'}>ERP System</span>
            </Link>
            <hr />
            <Nav className="flex-column mb-auto">
                {navItems.map((item) => (
                    <Nav.Item key={item.path} className="mb-1">
                        <Nav.Link
                            as={Link}
                            to={item.path}
                            className={`d-flex align-items-center rounded ${isActive(item.path)
                                ? 'active bg-primary text-white'
                                : theme === 'dark' ? 'text-secondary hover-light' : 'text-dark hover-bg-light'
                                }`}
                        >
                            <span className="me-2">{item.icon}</span>
                            {item.label}
                        </Nav.Link>
                    </Nav.Item>
                ))}
            </Nav>
            <hr />
            <div className="dropdown">
                <Nav.Link onClick={logout} className={`d-flex align-items-center ${theme === 'dark' ? 'text-danger' : 'text-danger'}`} style={{ cursor: 'pointer' }}>
                    <FaSignOutAlt className="me-2" />
                    登出
                </Nav.Link>
            </div>
        </div>
    );
}
