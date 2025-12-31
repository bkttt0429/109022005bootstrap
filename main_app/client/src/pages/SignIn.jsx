import React, { useState } from 'react';
import { Container, Card, Form, Button, Alert } from 'react-bootstrap';
import { useNavigate } from 'react-router-dom';
import { useAuth } from '../context/AuthContext';
import { GoogleLogin } from '@react-oauth/google';
import axios from 'axios';
import { API_BASE_URL } from '../utils/apiConfig';

export default function SignIn() {
    const [email, setEmail] = useState('');
    const [password, setPassword] = useState('');
    const [error, setError] = useState('');
    const { login } = useAuth();
    const navigate = useNavigate();

    const handleSubmit = async (e) => {
        e.preventDefault();
        setError('');
        const res = await login(email, password);
        if (res.success) {
            navigate('/dashboard');
        } else {
            setError(res.error || 'Login failed');
        }
    };

    const handleGoogleSuccess = async (credentialResponse) => {
        console.log("Google response received", credentialResponse);
        try {
            const res = await axios.post(`${API_BASE_URL}/auth_google.php`, { token: credentialResponse.credential });
            console.log("API response", res.data);
            if (res.data.success) {
                const token = res.data.token;
                if (token) {
                    localStorage.setItem('authToken', token);
                    axios.defaults.headers.common['Authorization'] = `Bearer ${token}`;
                }
                setTimeout(() => {
                    // Use href to ensure we go to the right place
                    const baseUrl = window.location.href.split('#')[0];
                    window.location.href = baseUrl + '#/dashboard';
                    window.location.reload();
                }, 500);
            } else {
                setError('Google Login Failed: ' + (res.data.error || ''));
            }
        } catch (err) {
            console.error("Auth error", err);
            setError('Google Login Error: ' + (err.response?.data?.error || err.message));
        }
    };

    return (
        <Container className="py-5 d-flex justify-content-center align-items-center" style={{ minHeight: '80vh' }}>
            <Card className="shadow-lg border-0" style={{ width: '100%', maxWidth: '400px' }}>
                <Card.Body className="p-4">
                    <h3 className="text-center mb-4 fw-bold text-primary">管理員登入</h3>
                    {error && <Alert variant="danger">{error}</Alert>}

                    <div className="d-flex justify-content-center mb-3">
                        <GoogleLogin
                            onSuccess={handleGoogleSuccess}
                            onError={() => {
                                setError('Google Login Failed');
                            }}
                            ux_mode="popup"
                        />
                    </div>

                    <Form onSubmit={handleSubmit}>
                        <Form.Group className="mb-3" controlId="formBasicEmail">
                            <Form.Label>Email address</Form.Label>
                            <Form.Control
                                type="email"
                                placeholder="Enter email"
                                value={email}
                                onChange={(e) => setEmail(e.target.value)}
                                required
                            />
                        </Form.Group>

                        <Form.Group className="mb-3" controlId="formBasicPassword">
                            <Form.Label>Password</Form.Label>
                            <Form.Control
                                type="password"
                                placeholder="Password"
                                value={password}
                                onChange={(e) => setPassword(e.target.value)}
                                required
                            />
                        </Form.Group>
                        <div className="d-grid gap-2">
                            <Button variant="primary" type="submit" size="lg">
                                登入
                            </Button>
                        </div>
                        <div className="text-center mt-3 text-muted small">
                            Demo: admin@example.com / admin
                        </div>
                        <div className="text-center mt-3">
                            <span className="text-muted">還沒有帳號？ </span>
                            <a href="#/signup" className="text-decoration-none fw-bold">立即註冊</a>
                        </div>
                    </Form>
                </Card.Body>
            </Card>
        </Container>
    );
}
