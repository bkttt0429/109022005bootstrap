import React from 'react';
import { Container, Table, Button, Card } from 'react-bootstrap';
import axios from 'axios';
import { toast } from 'react-hot-toast';
import { useNavigate, Link } from 'react-router-dom';
import Swal from 'sweetalert2';
import withReactContent from 'sweetalert2-react-content';
import { useAuth } from '../context/AuthContext';
import { useCart } from '../context/CartContext';
import { FaTrash } from 'react-icons/fa';

const MySwal = withReactContent(Swal);

export default function Cart() {
    const { cart, removeFromCart, clearCart } = useCart();
    const { user } = useAuth();
    const navigate = useNavigate();

    const handleCheckout = async () => {
        if (!user) {
            toast.error('請先登入');
            navigate('/signin');
            return;
        }

        const result = await MySwal.fire({
            title: '確定結帳？',
            text: `總金額: $${Number(cart.total).toLocaleString()}`,
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: '確認付款',
            cancelButtonText: '再逛逛'
        });

        if (result.isConfirmed) {
            try {
                // Show loading
                MySwal.fire({
                    title: '處理中...',
                    didOpen: () => {
                        MySwal.showLoading();
                    }
                });

                const response = await axios.post('./api/checkout_api.php', {
                    cartItems: cart.items,
                    total: cart.total,
                    userId: user.id || 1
                });

                if (response.data.success) {
                    clearCart();
                    MySwal.fire({
                        icon: 'success',
                        title: '訂單建立成功！',
                        text: `訂單編號: #${response.data.orderId}`,
                        timer: 2000,
                        showConfirmButton: false
                    }).then(() => {
                        navigate('/dashboard/orders');
                    });
                }
            } catch (error) {
                console.error(error);
                MySwal.fire({
                    icon: 'error',
                    title: '結帳失敗',
                    text: error.response?.data?.error || '請稍後再試'
                });
            }
        }
    };

    if (cart.items.length === 0) {
        return (
            <Container className="py-5 text-center">
                <h2>購物車是空的</h2>
                <p>快去選購喜歡的商品吧！</p>
                <Button as={Link} to="/products" variant="primary">前往購物</Button>
            </Container>
        );
    }

    return (
        <Container className="py-5">
            <h2 className="mb-4">購物車內容</h2>
            <Card className="shadow-sm border-0">
                <Card.Body>
                    <Table responsive hover className="align-middle">
                        <thead className="bg-light">
                            <tr>
                                <th>商品</th>
                                <th>單價</th>
                                <th>數量</th>
                                <th>小計</th>
                                <th>操作</th>
                            </tr>
                        </thead>
                        <tbody>
                            {cart.items.map(item => (
                                <tr key={item.id}>
                                    <td>
                                        <div className="d-flex align-items-center">
                                            <img src={item.image_url || 'https://via.placeholder.com/50'} alt={item.name} className="rounded me-3" style={{ width: '50px', height: '50px', objectFit: 'cover' }} />
                                            <span className="fw-bold">{item.name}</span>
                                        </div>
                                    </td>
                                    <td>${Number(item.price).toLocaleString()}</td>
                                    <td>{item.qty}</td>
                                    <td>${Number(item.subtotal).toLocaleString()}</td>
                                    <td>
                                        <Button variant="outline-danger" size="sm" onClick={() => removeFromCart(item.id)}>
                                            <FaTrash />
                                        </Button>
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </Table>
                </Card.Body>
                <Card.Footer className="bg-white border-0 py-3">
                    <div className="d-flex justify-content-between align-items-center">
                        <Button as={Link} to="/products" variant="outline-secondary">繼續購物</Button>
                        <div className="text-end">
                            <h4 className="fw-bold text-primary">總計: ${Number(cart.total).toLocaleString()}</h4>
                            <Button variant="success" size="lg" className="mt-2" onClick={() => navigate('/checkout')}>前往結帳</Button>
                        </div>
                    </div>
                </Card.Footer>
            </Card>
        </Container>
    );
}
