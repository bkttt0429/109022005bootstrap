import React, { useState, useEffect } from 'react';
import { Container, Row, Col, Card, Table, Spinner, Modal, Button, ListGroup, Badge } from 'react-bootstrap';
import { AreaChart, Area, XAxis, YAxis, CartesianGrid, Tooltip, ResponsiveContainer, BarChart, Bar, Legend } from 'recharts';
import { FaMoneyBillWave, FaArrowUp, FaArrowDown, FaReceipt, FaWallet, FaBox } from 'react-icons/fa';
import axios from 'axios';
import { API_BASE_URL } from '../../utils/apiConfig';
import { useTheme } from '../../context/ThemeContext';

export default function Accounting() {
    const [data, setData] = useState(null);
    const [loading, setLoading] = useState(true);
    const [selectedOrder, setSelectedOrder] = useState(null);
    const [showModal, setShowModal] = useState(false);
    const [fetchingOrder, setFetchingOrder] = useState(false);
    const { theme } = useTheme();

    useEffect(() => {
        const fetchData = async () => {
            try {
                const res = await axios.get(`${API_BASE_URL}/accounting_api.php`);
                const data = res.data;
                if (data && typeof data === 'object') {
                    data.chartData = Array.isArray(data.chartData) ? data.chartData : [];
                    data.recentTransactions = Array.isArray(data.recentTransactions) ? data.recentTransactions : [];
                    setData(data);
                } else {
                    setData(null);
                }
            } catch (err) {
                console.error("Failed to fetch accounting data", err);
            } finally {
                setLoading(false);
            }
        };
        fetchData();
    }, []);

    const handleShowDetails = async (orderId) => {
        setFetchingOrder(true);
        try {
            const res = await axios.get(`${API_BASE_URL}/orders_api.php?id=${orderId}`);
            setSelectedOrder(res.data);
            setShowModal(true);
        } catch (err) {
            console.error("Failed to fetch order details", err);
        } finally {
            setFetchingOrder(false);
        }
    };

    if (loading) {
        return (
            <div className="d-flex justify-content-center align-items-center" style={{ height: '80vh' }}>
                <Spinner animation="border" variant="primary" />
            </div>
        );
    }

    if (!data || !data.summary) {
        return (
            <div className="text-center mt-5">
                <h4>無法載入財務數據</h4>
                <p>請確認伺服器連線或稍後再試。</p>
            </div>
        );
    }

    const summaryItems = [
        { title: '總營收', value: `$${data.summary.totalRevenue.toLocaleString()}`, icon: <FaMoneyBillWave />, color: 'primary', trend: '+12%' },
        { title: '預估支出', value: `$${data.summary.totalExpenses.toLocaleString()}`, icon: <FaWallet />, color: 'danger', trend: '+5%' },
        { title: '淨利潤', value: `$${data.summary.grossProfit.toLocaleString()}`, icon: <FaReceipt />, color: 'success', trend: '+18%' },
        { title: '淨利率', value: data.summary.netMargin, icon: <FaArrowUp />, color: 'warning', trend: 'Stabilizing' },
    ];

    return (
        <Container fluid>
            <h2 className="mb-4 fw-bold">會計系統概覽</h2>

            <Row className="mb-4">
                {summaryItems.map((item, index) => (
                    <Col key={index} md={3}>
                        <Card className="border-0 shadow-sm h-100">
                            <Card.Body>
                                <div className="d-flex justify-content-between align-items-center mb-2">
                                    <div className={`p-2 rounded bg-${item.color} bg-opacity-10 text-${item.color}`}>
                                        {item.icon}
                                    </div>
                                    <small className="text-success fw-bold">{item.trend}</small>
                                </div>
                                <h6 className="text-muted mb-1">{item.title}</h6>
                                <h3 className="fw-bold mb-0">{item.value}</h3>
                            </Card.Body>
                        </Card>
                    </Col>
                ))}
            </Row>

            <Row className="mb-4">
                <Col lg={8}>
                    <Card className="border-0 shadow-sm p-4">
                        <h5 className="mb-4 fw-bold">營收與支出趨勢</h5>
                        <div style={{ width: '100%', height: 350, position: 'relative' }}>
                            <ResponsiveContainer width="100%" height="100%" minWidth={0} debounce={100}>
                                <AreaChart data={data.chartData}>
                                    <defs>
                                        <linearGradient id="colorRev" x1="0" y1="0" x2="0" y2="1">
                                            <stop offset="5%" stopColor="#0d6efd" stopOpacity={0.1} />
                                            <stop offset="95%" stopColor="#0d6efd" stopOpacity={0} />
                                        </linearGradient>
                                    </defs>
                                    <CartesianGrid strokeDasharray="3 3" vertical={false} stroke={theme === 'dark' ? '#444' : '#eee'} />
                                    <XAxis dataKey="name" stroke={theme === 'dark' ? '#888' : '#666'} />
                                    <YAxis stroke={theme === 'dark' ? '#888' : '#666'} />
                                    <Tooltip
                                        contentStyle={{ backgroundColor: theme === 'dark' ? '#333' : '#fff', borderRadius: '10px', border: 'none', boxShadow: '0 4px 6px rgba(0,0,0,0.1)' }}
                                    />
                                    <Legend />
                                    <Area type="monotone" dataKey="revenue" name="營收" stroke="#0d6efd" fillOpacity={1} fill="url(#colorRev)" strokeWidth={3} />
                                    <Area type="monotone" dataKey="expenses" name="支出" stroke="#dc3545" fillOpacity={0} strokeWidth={2} strokeDasharray="5 5" />
                                </AreaChart>
                            </ResponsiveContainer>
                        </div>
                    </Card>
                </Col>
                <Col lg={4}>
                    <Card className="border-0 shadow-sm p-4 h-100">
                        <h5 className="mb-4 fw-bold">獲利分析</h5>
                        <div style={{ width: '100%', height: 300, position: 'relative' }}>
                            <ResponsiveContainer width="100%" height="100%" minWidth={0} debounce={100}>
                                <BarChart data={data.chartData}>
                                    <XAxis dataKey="name" hide />
                                    <Tooltip />
                                    <Bar dataKey="profit" name="純利" fill="#198754" radius={[4, 4, 0, 0]} />
                                </BarChart>
                            </ResponsiveContainer>
                        </div>
                        <div className="mt-auto">
                            <div className="d-flex justify-content-between mb-2">
                                <span className="text-muted">本月預估回報率</span>
                                <span className="fw-bold text-success">24.5%</span>
                            </div>
                            <div className="progress" style={{ height: '8px' }}>
                                <div className="progress-bar bg-success" style={{ width: '70%', borderRadius: '4px' }}></div>
                            </div>
                        </div>
                    </Card>
                </Col>
            </Row>

            <Card className="border-0 shadow-sm p-4">
                <h5 className="mb-4 fw-bold">最近財務異動</h5>
                <Table responsive hover borderless className="align-middle">
                    <thead className="bg-light">
                        <tr>
                            <th>單號</th>
                            <th>日期</th>
                            <th>金額</th>
                            <th>狀態</th>
                            <th>操作</th>
                        </tr>
                    </thead>
                    <tbody>
                        {data.recentTransactions.map((tx) => (
                            <tr key={tx.id}>
                                <td className="fw-bold text-primary">{tx.order_number}</td>
                                <td className="text-muted">{new Date(tx.created_at).toLocaleDateString()}</td>
                                <td>${(parseFloat(tx.total_amount) || 0).toLocaleString()}</td>
                                <td>
                                    <span className={`badge rounded-pill bg-${tx.status === 'Completed' ? 'success' : 'warning'} bg-opacity-10 text-${tx.status === 'Completed' ? 'success' : 'warning'}`}>
                                        {tx.status}
                                    </span>
                                </td>
                                <td>
                                    <button
                                        className="btn btn-sm btn-outline-primary"
                                        onClick={() => handleShowDetails(tx.id)}
                                        disabled={fetchingOrder}
                                    >
                                        {fetchingOrder ? <Spinner size="sm" /> : '詳情'}
                                    </button>
                                </td>
                            </tr>
                        ))}
                    </tbody>
                </Table>
            </Card>

            {/* Transaction Details Modal */}
            <Modal show={showModal} onHide={() => setShowModal(false)} size="lg" centered>
                <Modal.Header closeButton className="border-0 bg-light">
                    <Modal.Title className="fw-bold fs-5">
                        <FaReceipt className="me-2 text-primary" />
                        交易詳細資訊 - {selectedOrder?.order_number}
                    </Modal.Title>
                </Modal.Header>
                <Modal.Body className="p-4">
                    {selectedOrder && (
                        <>
                            <Row className="mb-4">
                                <Col md={6}>
                                    <div className="mb-3">
                                        <small className="text-muted d-block uppercase mb-1">客戶資訊</small>
                                        <div className="fw-bold">{selectedOrder.user_name || '系統訪客'}</div>
                                        <div className="text-muted small">{selectedOrder.user_email}</div>
                                    </div>
                                    <div>
                                        <small className="text-muted d-block uppercase mb-1">下單日期</small>
                                        <div>{new Date(selectedOrder.created_at).toLocaleString()}</div>
                                    </div>
                                </Col>
                                <Col md={6}>
                                    <div className="mb-3 text-md-end">
                                        <small className="text-muted d-block uppercase mb-1">目前狀態</small>
                                        <Badge bg={selectedOrder.status === 'Completed' ? 'success' : 'warning'}>
                                            {selectedOrder.status}
                                        </Badge>
                                    </div>
                                    <div className="text-md-end">
                                        <small className="text-muted d-block uppercase mb-1">總金額</small>
                                        <h4 className="text-primary fw-bold">${parseFloat(selectedOrder.total_amount).toLocaleString()}</h4>
                                    </div>
                                </Col>
                            </Row>

                            <h6 className="fw-bold border-bottom pb-2 mb-3">商品清單</h6>
                            <ListGroup variant="flush">
                                {selectedOrder.items?.map((item, idx) => (
                                    <ListGroup.Item key={idx} className="px-0 d-flex justify-content-between align-items-center border-0 py-2">
                                        <div className="d-flex align-items-center">
                                            <div className="p-2 bg-light rounded me-3">
                                                <FaBox className="text-secondary" />
                                            </div>
                                            <div>
                                                <div className="fw-bold small">{item.product_name || `商品 ID: ${item.product_id}`}</div>
                                                <div className="text-muted extra-small">數量: {item.quantity} × ${parseFloat(item.price).toLocaleString()}</div>
                                            </div>
                                        </div>
                                        <div className="fw-bold text-dark">
                                            ${(item.quantity * item.price).toLocaleString()}
                                        </div>
                                    </ListGroup.Item>
                                ))}
                            </ListGroup>
                        </>
                    )}
                </Modal.Body>
                <Modal.Footer className="border-0 bg-light rounded-bottom">
                    <Button variant="secondary" onClick={() => setShowModal(false)} className="px-4">
                        關閉
                    </Button>
                </Modal.Footer>
            </Modal>

            <style>{`
                .extra-small { font-size: 0.75rem; }
                .uppercase { text-transform: uppercase; letter-spacing: 0.5px; }
            `}</style>
        </Container>
    );
}
