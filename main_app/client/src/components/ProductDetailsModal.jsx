import React from 'react';
import { Modal, Button, Badge } from 'react-bootstrap';
import { Swiper, SwiperSlide } from 'swiper/react';
import { Navigation, Pagination, EffectCoverflow } from 'swiper/modules';
import { FaShoppingCart } from 'react-icons/fa';
import { useCart } from '../context/CartContext';
import ProductReviews from './ProductReviews';

// Import Swiper styles
import 'swiper/css';
import 'swiper/css/navigation';
import 'swiper/css/pagination';
import 'swiper/css/effect-coverflow';

export default function ProductDetailsModal({ show, onHide, product }) {
    const { addToCart } = useCart();

    if (!product) return null;

    // Simulate multiple images if only one exists (for Swiper demo)
    const images = [
        product.image_url || 'https://via.placeholder.com/300',
        'https://via.placeholder.com/300/0d6efd/ffffff?text=Angle+2',
        'https://via.placeholder.com/300/0dcaf0/ffffff?text=Detail',
    ];

    return (
        <Modal show={show} onHide={onHide} size="lg" centered>
            <Modal.Header closeButton className="border-0">
                <Modal.Title>{product.name}</Modal.Title>
            </Modal.Header>
            <Modal.Body>
                <div className="row">
                    <div className="col-md-6 mb-3 mb-md-0">
                        <Swiper
                            effect={'coverflow'}
                            grabCursor={true}
                            centeredSlides={true}
                            slidesPerView={'auto'}
                            coverflowEffect={{
                                rotate: 50,
                                stretch: 0,
                                depth: 100,
                                modifier: 1,
                                slideShadows: true,
                            }}
                            pagination={true}
                            modules={[EffectCoverflow, Pagination]}
                            className="mySwiper"
                            style={{ height: '300px' }}
                        >
                            {images.map((img, index) => (
                                <SwiperSlide key={index} style={{ width: '300px', backgroundPosition: 'center', backgroundSize: 'cover' }}>
                                    <img src={img} alt={`Slide ${index}`} style={{ display: 'block', width: '100%', height: '100%', objectFit: 'cover', borderRadius: '10px' }} />
                                </SwiperSlide>
                            ))}
                        </Swiper>
                        <p className="text-center text-muted mt-2 small">左右滑動查看更多角度 (Swiper Effect)</p>
                    </div>
                    <div className="col-md-6 d-flex flex-column justify-content-between">
                        <div>
                            <Badge bg="secondary" className="mb-2">{product.category || '一般商品'}</Badge>
                            <h3 className="fw-bold text-primary">NT$ {product.price}</h3>
                            <p className="text-muted">{product.description || '這是一項優質商品，採用高品質材料製成，適合各種場合使用。現在購買享有優惠。'}</p>

                            <hr />

                            <div className="d-flex justify-content-between align-items-center mb-2">
                                <span>庫存狀況:</span>
                                <Badge bg={product.stock_quantity > 0 ? 'success' : 'danger'}>
                                    {product.stock_quantity > 0 ? `現貨 (${product.stock_quantity})` : '缺貨'}
                                </Badge>
                            </div>
                            <div className="d-flex justify-content-between align-items-center">
                                <span>SKU:</span>
                                <span className="text-monospace text-muted">{product.sku || 'N/A'}</span>
                            </div>
                        </div>

                        <div className="d-grid gap-2 mt-4">
                            <Button variant="primary" size="lg" onClick={() => { addToCart(product.id); onHide(); }}>
                                <FaShoppingCart className="me-2" /> 加入購物車
                            </Button>
                        </div>
                    </div>
                </div>
                <hr className="my-4" />
                <ProductReviews productId={product.id} />
            </Modal.Body>
        </Modal >
    );
}
