import React from 'react';
import { useQuery } from '@tanstack/react-query';
import axios from 'axios';
import { API_BASE_URL } from '../utils/apiConfig';
import { Card, Row, Col, Placeholder, Button } from 'react-bootstrap';
import { useAuth } from '../context/AuthContext';
import { useCart } from '../context/CartContext';
import { Link } from 'react-router-dom';
import { motion } from 'framer-motion';

export default function RecommendedProducts({ mode = 'auto' }) {
    const { user } = useAuth();
    const { addToCart } = useCart();

    const isTopSelling = mode === 'top';

    const { data: recommendations, isLoading } = useQuery({
        queryKey: ['recommendations', user?.id, mode],
        queryFn: async () => {
            const userId = user?.id || 0;
            const typeParam = isTopSelling ? 'top' : 'auto';
            const res = await axios.get(`${API_BASE_URL}/recommendations.php?user_id=${userId}&limit=4&type=${typeParam}`);
            return Array.isArray(res.data) ? res.data : [];
        },
        staleTime: 60 * 1000,
    });

    if (isLoading) {
        return (
            <Row xs={1} md={2} lg={4} className="g-4 mb-5">
                {[1, 2, 3, 4].map((i) => (
                    <Col key={i}>
                        <Card className="h-100 border-0 shadow-sm">
                            <Placeholder as={Card.Img} variant="top" height={200} />
                            <Card.Body>
                                <Placeholder as={Card.Title} animation="glow">
                                    <Placeholder xs={8} />
                                </Placeholder>
                                <Placeholder as={Card.Text} animation="glow">
                                    <Placeholder xs={4} />
                                </Placeholder>
                            </Card.Body>
                        </Card>
                    </Col>
                ))}
            </Row>
        );
    }

    if (!recommendations || recommendations.length === 0) return null;

    const title = isTopSelling
        ? '熱銷商品 (Best Sellers)'
        : (user ? `為您推薦 (Recommended for ${user.name || 'Valued Customer'})` : '熱銷推薦 (Top Selling)');

    return (
        <div className="mb-5">
            <h3 className="fw-bold mb-4 border-start border-4 border-primary ps-3">
                {title}
            </h3>
            <Row xs={1} md={2} lg={4} className="g-4">
                {recommendations.map((product, index) => (
                    <Col key={product.id}>
                        <motion.div
                            initial={{ opacity: 0, y: 20 }}
                            animate={{ opacity: 1, y: 0 }}
                            transition={{ delay: index * 0.1 }}
                        >
                            <Card className="h-100 shadow-sm border-0 hover-lift">
                                <div className="position-relative overflow-hidden" style={{ height: '200px' }}>
                                    <Card.Img
                                        variant="top"
                                        src={product.image_url}
                                        className="h-100 w-100 object-fit-cover transition-transform"
                                        alt={product.name}
                                    />
                                    {/* Quick Add Overlay */}
                                    <div className="position-absolute top-0 end-0 p-2">
                                        <Button
                                            variant="light"
                                            size="sm"
                                            className="rounded-circle shadow-sm"
                                            onClick={() => addToCart(product)}
                                            title="Add to Cart"
                                        >
                                            <i className="bi bi-cart-plus-fill text-primary"></i> +
                                        </Button>
                                    </div>
                                </div>
                                <Card.Body>
                                    <Card.Title className="h6 text-truncate" title={product.name}>
                                        {product.name}
                                    </Card.Title>
                                    <div className="d-flex justify-content-between align-items-center">
                                        <span className="fw-bold text-primary">${Number(product.price).toLocaleString()}</span>
                                        <Link to="/products" className="small text-muted text-decoration-none">
                                            查看詳情
                                        </Link>
                                    </div>
                                    {/* Show Sold Count for Top Selling */}
                                    {isTopSelling && product.sold && (
                                        <div className="mt-2 small text-muted">
                                            <i className="bi bi-fire text-danger me-1"></i>
                                            已售出 {product.sold} 件
                                        </div>
                                    )}
                                </Card.Body>
                            </Card>
                        </motion.div>
                    </Col>
                ))}
            </Row>
        </div>
    );
}
