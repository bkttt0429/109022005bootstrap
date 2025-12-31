import React, { useState, useEffect } from 'react';
import { Container, Row, Col, Card, Table, Badge, Button, ProgressBar, Spinner, Modal, Form } from 'react-bootstrap';
import { FaBoxes, FaTruckLoading, FaExclamationTriangle, FaCheckCircle, FaHistory, FaArrowUp, FaTimes } from 'react-icons/fa';
import axios from 'axios';
import { API_BASE_URL, API_V1_URL } from '../../utils/apiConfig';
import { toast } from 'react-hot-toast';

export default function Inventory() {
    const [products, setProducts] = useState([]);
    const [loading, setLoading] = useState(true);
    const [showInboundModal, setShowInboundModal] = useState(false);
    const [selectedProduct, setSelectedProduct] = useState(null);
    const [inboundQty, setInboundQty] = useState(0);
    const [isSubmitting, setIsSubmitting] = useState(false);

    const fetchInventory = async () => {
        try {
            const res = await axios.get(`${API_V1_URL}/inventory`);
            // Ensure data is an array before setting state
            setProducts(Array.isArray(res.data) ? res.data : []);
        } catch (err) {
            console.error("Failed to fetch inventory", err);
            toast.error("無法載入進貨數據");
            setProducts([]); // Set empty array on error
        } finally {
            setLoading(false);
        }
    };

    useEffect(() => {
        fetchInventory();
    }, []);

    const handleTriggerRestock = async (productId) => {
        try {
            await axios.post(`${API_V1_URL}/inventory/trigger-restock`, { product_id: productId });
            toast.success("已觸發 n8n 自動補貨流程！(等待自動更新)");

            // Wait 3 seconds for n8n to finish processing, then refresh
            setTimeout(() => {
                fetchInventory();
                toast.success("庫存已同步！");
            }, 3000);

        } catch (err) {
            toast.error("觸發補貨失敗");
        }
    };

    const handleInbound = async (e) => {
        e.preventDefault();
        if (inboundQty <= 0) return;

        setIsSubmitting(true);
        try {
            await axios.post(`${API_V1_URL}/inventory/inbound`, {
                product_id: selectedProduct.id,
                quantity: parseInt(inboundQty),
                reason: 'MANUAL_RESTOCK'
            });
            toast.success("進貨紀錄已更新");
            setShowInboundModal(false);
            fetchInventory();
        } catch (err) {
            toast.error("更新失敗");
        } finally {
            setIsSubmitting(false);
        }
    };

    const getStatusBadge = (status) => {
        switch (status) {
            case 'Critical': return <Badge bg="danger">極高優先級</Badge>;
            case 'Warning': return <Badge bg="warning" text="dark">警告</Badge>;
            case 'Out of Stock': return <Badge bg="dark">缺貨</Badge>;
            default: return <Badge bg="success">良好</Badge>;
        }
    };

    if (loading) return (
        <div className="text-center py-5">
            <Spinner animation="border" variant="primary" />
        </div>
    );

    return (
        <Container fluid className="py-4">
            <div className="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h2 className="fw-bold mb-1">進貨與庫存管理</h2>
                    <p className="text-muted">基於銷售速度與前置天數的智能優先級系統</p>
                </div>
                <Button variant="primary" onClick={() => fetchInventory()} className="shadow-sm">
                    <FaHistory className="me-2" /> 重新整理
                </Button>
            </div>

            <Row className="mb-4">
                <Col md={4}>
                    <Card className="border-0 shadow-sm bg-danger bg-opacity-10">
                        <Card.Body className="d-flex align-items-center">
                            <div className="p-3 bg-danger rounded-circle text-white me-3">
                                <FaExclamationTriangle size={24} />
                            </div>
                            <div>
                                <h6 className="text-danger mb-0">急需補貨項目</h6>
                                <h3 className="fw-bold mb-0">{products.filter(p => p.stock_status === 'Critical' || p.stock_status === 'Out of Stock').length}</h3>
                            </div>
                        </Card.Body>
                    </Card>
                </Col>
                <Col md={4}>
                    <Card className="border-0 shadow-sm bg-primary bg-opacity-10">
                        <Card.Body className="d-flex align-items-center">
                            <div className="p-3 bg-primary rounded-circle text-white me-3">
                                <FaTruckLoading size={24} />
                            </div>
                            <div>
                                <h6 className="text-primary mb-0">平均前置天數</h6>
                                <h3 className="fw-bold mb-0">~3.5 天</h3>
                            </div>
                        </Card.Body>
                    </Card>
                </Col>
                <Col md={4}>
                    <Card className="border-0 shadow-sm bg-success bg-opacity-10">
                        <Card.Body className="d-flex align-items-center">
                            <div className="p-3 bg-success rounded-circle text-white me-3">
                                <FaCheckCircle size={24} />
                            </div>
                            <div>
                                <h6 className="text-success mb-0">庫存健全項目</h6>
                                <h3 className="fw-bold mb-0">{products.filter(p => p.stock_status === 'Healthy').length}</h3>
                            </div>
                        </Card.Body>
                    </Card>
                </Col>
            </Row>

            <Card className="border-0 shadow-sm">
                <Card.Body className="p-0">
                    <Table responsive hover className="align-middle mb-0">
                        <thead className="bg-light">
                            <tr>
                                <th className="ps-4">商品資訊</th>
                                <th>目前庫存</th>
                                <th>日均銷量</th>
                                <th>補貨優先級</th>
                                <th>狀態</th>
                                <th className="text-end pe-4">操作</th>
                            </tr>
                        </thead>
                        <tbody>
                            {products.map((p) => (
                                <tr key={p.id}>
                                    <td className="ps-4">
                                        <div className="fw-bold">{p.name}</div>
                                        <small className="text-muted">{p.sku}</small>
                                    </td>
                                    <td>
                                        <div className="fw-bold">{p.stock_quantity}</div>
                                        <small className="text-muted">門檻: {p.min_stock_level}</small>
                                    </td>
                                    <td>{parseFloat(p.avg_daily_sales).toFixed(1)} / 日</td>
                                    <td>
                                        <div className="d-flex align-items-center" style={{ minWidth: '150px' }}>
                                            <ProgressBar
                                                now={Math.min(p.priority_score * 20, 100)}
                                                variant={p.priority_score > 3 ? 'danger' : p.priority_score > 1 ? 'warning' : 'success'}
                                                style={{ height: '8px', flex: 1, marginRight: '10px' }}
                                            />
                                            <small className="fw-bold text-muted">{p.priority_score}</small>
                                        </div>
                                    </td>
                                    <td>{getStatusBadge(p.stock_status)}</td>
                                    <td className="text-end pe-4">
                                        <Button
                                            variant="outline-primary"
                                            size="sm"
                                            className="me-2"
                                            onClick={() => { setSelectedProduct(p); setShowInboundModal(true); }}
                                        >
                                            <FaArrowUp /> 手動進貨
                                        </Button>
                                        <Button
                                            variant="outline-info"
                                            size="sm"
                                            onClick={() => handleTriggerRestock(p.id)}
                                        >
                                            <FaTruckLoading /> 自動捕貨
                                        </Button>
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </Table>
                </Card.Body>
            </Card>

            {/* Inbound Modal */}
            <Modal show={showInboundModal} onHide={() => setShowInboundModal(false)} centered>
                <Modal.Header closeButton className="border-0">
                    <Modal.Title className="fw-bold">手動錄入進貨</Modal.Title>
                </Modal.Header>
                <Form onSubmit={handleInbound}>
                    <Modal.Body className="py-4">
                        <p className="mb-4">
                            您正在為 <strong>{selectedProduct?.name}</strong> 錄入進貨。
                            這將會增加庫存並記錄在異動表中。
                        </p>
                        <Form.Group>
                            <Form.Label>進貨數量</Form.Label>
                            <Form.Control
                                type="number"
                                required
                                value={inboundQty}
                                onChange={(e) => setInboundQty(e.target.value)}
                                min="1"
                                placeholder="請輸入數量"
                            />
                        </Form.Group>
                    </Modal.Body>
                    <Modal.Footer className="border-0 bg-light">
                        <Button variant="secondary" onClick={() => setShowInboundModal(false)}>取消</Button>
                        <Button variant="primary" type="submit" disabled={isSubmitting}>
                            {isSubmitting ? <Spinner size="sm" /> : '確認進貨'}
                        </Button>
                    </Modal.Footer>
                </Form>
            </Modal>
        </Container>
    );
}
