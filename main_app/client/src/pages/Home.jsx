import React from 'react';
import { Container, Row, Col, Card, Button } from 'react-bootstrap';
import { Link } from 'react-router-dom';
import { FaShoppingBag, FaRobot, FaShippingFast } from 'react-icons/fa';
import { motion } from 'framer-motion';
import { useAuth } from '../context/AuthContext';
import RecommendedProducts from '../components/RecommendedProducts';

export default function Home() {
    const { user } = useAuth();

    return (
        <motion.div
            className="home-page"
            initial={{ opacity: 0 }}
            animate={{ opacity: 1 }}
            exit={{ opacity: 0 }}
            transition={{ duration: 0.5 }}
        >
            {/* Hero Section */}
            <header className="hero-section text-center">
                <Container>
                    <motion.div
                        initial={{ y: -50, opacity: 0 }}
                        animate={{ y: 0, opacity: 1 }}
                        transition={{ delay: 0.2, type: "spring", stiffness: 120 }}
                    >
                        <h1 className="display-4 fw-bold mb-3">探索專屬於您的精品購物空間</h1>
                        <p className="lead mb-4">極致購物、智慧推薦、尊榮體驗 — 您的理想生活從這裡開始</p>
                        <div className="d-flex gap-3 justify-content-center">
                            <Link to="/products" className="btn btn-light btn-lg px-4 text-primary fw-bold shadow-sm">立即購物</Link>
                            {user && user.role === 'admin' && (
                                <Link to="/dashboard" className="btn btn-outline-light btn-lg px-4">管理後台</Link>
                            )}
                        </div>
                    </motion.div>
                </Container>
            </header>

            {/* Features Section */}
            <Container className="mb-5">
                <Row xs={1} lg={3} className="g-4 py-5">
                    {[
                        { icon: <FaShoppingBag size={48} />, title: "嚴選優質商品", text: "我們為您挑選全球頂尖品質的商品，確保每一件都符合您的品味。" },
                        { icon: <FaRobot size={48} />, title: "AI 智慧導購", text: "搭載先進 AI 技術，根據您的偏好即時推薦最適合的夢幻清單。" },
                        { icon: <FaShippingFast size={48} />, title: "極速配送服務", text: "全台快速到貨，完善的物流後勤讓您的期待不再被耽擱。" }
                    ].map((feature, index) => (
                        <Col key={index}>
                            <motion.div
                                initial={{ opacity: 0, y: 50 }}
                                whileInView={{ opacity: 1, y: 0 }}
                                viewport={{ once: true }}
                                transition={{ delay: index * 0.2 }}
                            >
                                <Card className="h-100 border-0 shadow-sm text-center p-4 custom-card">
                                    <Card.Body>
                                        <div className="text-gold mb-3">
                                            {feature.icon}
                                        </div>
                                        <h3 className="fs-4 fw-bold">{feature.title}</h3>
                                        <p className="text-muted small">{feature.text}</p>
                                    </Card.Body>
                                </Card>
                            </motion.div>
                        </Col>
                    ))}
                </Row>

                {/* Recommendation Section */}
                <RecommendedProducts />
            </Container>
        </motion.div >
    );
}
