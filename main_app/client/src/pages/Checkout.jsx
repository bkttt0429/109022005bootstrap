import React, { useState } from 'react';
import { Container, Row, Col, Form, Button, Card, ListGroup } from 'react-bootstrap';
import { useCart } from '../context/CartContext';
import { useAuth } from '../context/AuthContext';
import { useNavigate } from 'react-router-dom';
import axios from 'axios';
import { API_BASE_URL } from '../utils/apiConfig';
import Swal from 'sweetalert2';

export default function Checkout() {
    const { cart, clearCart } = useCart();
    const { user } = useAuth();
    const navigate = useNavigate();
    const [loading, setLoading] = useState(false);
    const [formData, setFormData] = useState({
        name: user?.name || '',
        email: user?.email || '',
        phone: '',
        address: ''
    });

    if (cart.count === 0) {
        return (
            <Container className="py-5 text-center">
                <h2>購物車是空的</h2>
                <Button variant="primary" onClick={() => navigate('/products')}>去逛逛</Button>
            </Container>
        );
    }

    const handleChange = (e) => {
        setFormData({ ...formData, [e.target.name]: e.target.value });
    };

    const handleSubmit = async (e) => {
        e.preventDefault();
        setLoading(true);

        try {
            const response = await axios.post(`${API_BASE_URL}/checkout_api.php`, {
                shipping_info: formData
            });

            if (response.data.success) {
                await Swal.fire({
                    icon: 'success',
                    title: '訂單已送出！',
                    text: `訂單編號: ${response.data.order_number}`,
                    confirmButtonText: '查看我的訂單'
                });
                clearCart();
                navigate('/dashboard/orders');
            }
        } catch (error) {
            Swal.fire({
                icon: 'error',
                title: '結帳失敗',
                text: error.response?.data?.error || '請稍後再試'
            });
        } finally {
            setLoading(false);
        }
    };

    return (
        <Container className="py-5">
            <h2 className="mb-4">結帳 (Checkout)</h2>
            <Row>
                <Col md={8}>
                    <Card className="shadow-sm border-0 mb-4">
                        <Card.Header className="bg-white fw-bold">收件資訊</Card.Header>
                        <Card.Body>
                            <Form onSubmit={handleSubmit}>
                                <Row>
                                    <Col md={6} className="mb-3">
                                        <Form.Label>收件人姓名</Form.Label>
                                        <Form.Control
                                            type="text"
                                            name="name"
                                            value={formData.name}
                                            onChange={handleChange}
                                            required
                                        />
                                    </Col>
                                    <Col md={6} className="mb-3">
                                        <Form.Label>Email</Form.Label>
                                        <Form.Control
                                            type="email"
                                            name="email"
                                            value={formData.email}
                                            disabled
                                        />
                                    </Col>
                                </Row>
                                <div className="mb-3">
                                    <Form.Label>聯絡電話</Form.Label>
                                    <Form.Control
                                        type="tel"
                                        name="phone"
                                        value={formData.phone}
                                        onChange={handleChange}
                                        required
                                        placeholder="0912-345-678"
                                    />
                                </div>
                                <div className="mb-3">
                                    <Form.Label>收件地址</Form.Label>
                                    <Form.Control
                                        as="textarea"
                                        rows={3}
                                        name="address"
                                        value={formData.address}
                                        onChange={handleChange}
                                        required
                                        placeholder="請輸入詳細地址..."
                                    />
                                </div>
                                <div className="mb-4">
                                    <Form.Label>付款方式</Form.Label>
                                    <Form.Select disabled>
                                        <option>貨到付款 (Cash on Delivery)</option>
                                    </Form.Select>
                                    <Form.Text className="text-muted">
                                        目前僅支援貨到付款。
                                    </Form.Text>
                                </div>

                                <div className="d-grid gap-2">
                                    <Button variant="primary" size="lg" type="submit" disabled={loading}>
                                        {loading ? '處理中...' : `確認下單 (NT$ ${cart.total.toLocaleString()})`}
                                    </Button>
                                    <Button variant="outline-secondary" onClick={() => navigate('/cart')}>
                                        返回購物車
                                    </Button>
                                </div>
                            </Form>
                        </Card.Body>
                    </Card>
                </Col>

                <Col md={4}>
                    <Card className="shadow-sm border-0">
                        <Card.Header className="bg-white fw-bold">訂單摘要</Card.Header>
                        <ListGroup variant="flush">
                            {cart.items.map(item => (
                                <ListGroup.Item key={item.id} className="d-flex justify-content-between align-items-center">
                                    <div className="d-flex align-items-center">
                                        <img
                                            src={item.image_url}
                                            alt={item.name}
                                            style={{ width: '40px', height: '40px', objectFit: 'cover', marginRight: '10px', borderRadius: '4px' }}
                                        />
                                        <div>
                                            <div className="small fw-bold text-truncate" style={{ maxWidth: '120px' }}>{item.name}</div>
                                            <div className="small text-muted">x {item.qty}</div>
                                        </div>
                                    </div>
                                    <span>NT$ {item.subtotal.toLocaleString()}</span>
                                </ListGroup.Item>
                            ))}
                            <ListGroup.Item className="d-flex justify-content-between fw-bold bg-light">
                                <span>總計</span>
                                <span className="text-primary">NT$ {cart.total.toLocaleString()}</span>
                            </ListGroup.Item>
                        </ListGroup>
                    </Card>
                </Col>
            </Row>
        </Container>
    );
}
