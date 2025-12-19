import React from 'react';
import { Navbar, Container, Nav, Badge, Button } from 'react-bootstrap';
import { Link, useNavigate } from 'react-router-dom';
import { FaCube, FaShoppingCart, FaUser, FaSun, FaMoon } from 'react-icons/fa';
import { useAuth } from '../context/AuthContext';
import { useCart } from '../context/CartContext';
import { useTheme } from '../context/ThemeContext';

export default function Header() {
    const { user, logout } = useAuth();
    const { cart } = useCart();
    const { theme, toggleTheme } = useTheme();
    const navigate = useNavigate();

    const handleLogout = () => {
        logout();
        navigate('/');
    };

    return (
        <Navbar expand="lg" className="fixed-top navbar-glass" data-bs-theme={theme}>
            <Container>
                <Navbar.Brand as={Link} to="/" className="fw-bold text-primary">
                    <FaCube className="me-2" />LUXE Shop
                </Navbar.Brand>
                <Navbar.Toggle aria-controls="basic-navbar-nav" />
                <Navbar.Collapse id="basic-navbar-nav">
                    <Nav className="me-auto">
                        <Nav.Link as={Link} to="/">首頁</Nav.Link>
                        <Nav.Link as={Link} to="/products">商品目錄</Nav.Link>
                        {user && user.role === 'admin' && <Nav.Link as={Link} to="/dashboard">ERP後台</Nav.Link>}
                    </Nav>
                    <Nav className="align-items-center">
                        <Button variant="link" onClick={toggleTheme} className="text-decoration-none me-3" size="sm">
                            {theme === 'light' ? <FaMoon size={18} className="text-secondary" /> : <FaSun size={18} className="text-warning" />}
                        </Button>

                        <Nav.Link as={Link} to="/cart" className="position-relative me-3">
                            <FaShoppingCart size={20} />
                            {cart.count > 0 && (
                                <Badge bg="danger" pill className="position-absolute start-100 translate-middle" style={{ top: '5px', fontSize: '0.6rem' }}>
                                    {cart.count}
                                </Badge>
                            )}
                        </Nav.Link>

                        {user ? (
                            <div className="d-flex align-items-center">
                                <span className="me-2 text-muted small"><FaUser className="me-1" />{user.email}</span>
                                <Button variant="outline-danger" size="sm" onClick={handleLogout}>登出</Button>
                            </div>
                        ) : (
                            <Button as={Link} to="/signin" variant="primary" size="sm">登入</Button>
                        )}
                    </Nav>
                </Navbar.Collapse>
            </Container>
        </Navbar>
    );
}
