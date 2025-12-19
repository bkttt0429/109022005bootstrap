import React, { useEffect, useState } from 'react';
import { Container, Row, Col, Card, Button, Spinner } from 'react-bootstrap';
import axios from 'axios';
import { API_BASE_URL } from '../utils/apiConfig';
import { useCart } from '../context/CartContext';
import { motion } from 'framer-motion';
import Skeleton from 'react-loading-skeleton';
import 'react-loading-skeleton/dist/skeleton.css';
import Swal from 'sweetalert2';

import ProductDetailsModal from '../components/ProductDetailsModal';
import RecommendedProducts from '../components/RecommendedProducts';

export default function Products() {
    const [products, setProducts] = useState([]);
    const [loading, setLoading] = useState(true);
    const { addToCart } = useCart();
    // Modal State
    const [modalShow, setModalShow] = useState(false);
    const [selectedProduct, setSelectedProduct] = useState(null);

    useEffect(() => {
        axios.get(`${API_BASE_URL}/products_api.php`)
            .then(res => {
                setProducts(res.data);
                setLoading(false);
            })
            .catch(err => {
                console.error("Error fetching products", err);
                setLoading(false);
            });
    }, []);

    const handleOpenModal = (product) => {
        setSelectedProduct(product);
        setModalShow(true);
    };

    if (loading) {
        return (
            <Container className="py-5">
                <div className="mb-5 pb-4 border-bottom">
                    <Skeleton height={200} className="mb-3" />
                </div>
                <h2 className="mb-4"><Skeleton width={200} /></h2>
                <Row xs={1} md={2} lg={4} className="g-4">
                    {Array(8).fill(0).map((_, i) => (
                        <Col key={i}>
                            <Card className="h-100 shadow-sm border-0">
                                <Skeleton height={200} />
                                <Card.Body>
                                    <Skeleton count={2} />
                                    <div className="d-flex justify-content-between mt-3">
                                        <Skeleton width={80} />
                                        <Skeleton width={80} />
                                    </div>
                                </Card.Body>
                            </Card>
                        </Col>
                    ))}
                </Row>
            </Container>
        );
    }

    const handleAddToCart = (product) => {
        addToCart(product.id);
        const Toast = Swal.mixin({
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 1500,
            timerProgressBar: true,
            didOpen: (toast) => {
                toast.addEventListener('mouseenter', Swal.stopTimer)
                toast.addEventListener('mouseleave', Swal.resumeTimer)
            }
        });

        Toast.fire({
            icon: 'success',
            title: `${product.name} 已加入購物車`
        });
    };

    return (
        <Container className="py-5">
            <div className="mb-5 pb-4 border-bottom">
                <RecommendedProducts mode="top" />
                <RecommendedProducts mode="auto" />
            </div>

            <h2 className="mb-4 text-primary fw-bold"><i className="fa-solid fa-store me-2"></i>商品目錄</h2>
            <Row xs={1} md={2} lg={4} className="g-4">
                {products.map((product, index) => (
                    <Col key={product.id}>
                        <motion.div
                            initial={{ opacity: 0, scale: 0.9 }}
                            animate={{ opacity: 1, scale: 1 }}
                            transition={{ duration: 0.3, delay: index * 0.1 }}
                            whileHover={{ y: -10 }}
                        >
                            <Card className="h-100 shadow-sm border-0 product-card" onClick={() => handleOpenModal(product)} style={{ cursor: 'pointer' }}>
                                <div style={{ height: '200px', overflow: 'hidden' }}>
                                    <Card.Img variant="top" src={product.image_url || 'https://via.placeholder.com/300'} style={{ objectFit: 'cover', height: '100%', width: '100%' }} />
                                </div>
                                <Card.Body className="d-flex flex-column">
                                    <Card.Title className="fw-bold text-truncate" title={product.name}>{product.name}</Card.Title>
                                    <Card.Text className="text-muted small flex-grow-1" style={{ minHeight: '3em' }}>
                                        {product.description ? product.description.substring(0, 50) + '...' : '暫無描述'}
                                    </Card.Text>
                                    <div className="d-flex justify-content-between align-items-center mt-3">
                                        <span className="h5 mb-0 text-primary">NT$ {Number(product.price).toLocaleString()}</span>
                                        <Button variant="outline-primary" size="sm" onClick={(e) => { e.stopPropagation(); handleAddToCart(product); }}>
                                            加入購物車
                                        </Button>
                                    </div>
                                </Card.Body>
                            </Card>
                        </motion.div>
                    </Col>
                ))}
            </Row>



            <ProductDetailsModal
                show={modalShow}
                onHide={() => setModalShow(false)}
                product={selectedProduct}
            />
        </Container>
    );
}
