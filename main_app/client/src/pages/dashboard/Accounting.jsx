import React, { useState, useEffect } from 'react';
import { Container, Row, Col, Card, Table, Spinner } from 'react-bootstrap';
import { AreaChart, Area, XAxis, YAxis, CartesianGrid, Tooltip, ResponsiveContainer, BarChart, Bar, Legend } from 'recharts';
import { FaMoneyBillWave, FaArrowUp, FaArrowDown, FaReceipt, FaWallet } from 'react-icons/fa';
import axios from 'axios';
import { API_BASE_URL } from '../../utils/apiConfig';
import { useTheme } from '../../context/ThemeContext';

export default function Accounting() {
    const [data, setData] = useState(null);
    const [loading, setLoading] = useState(true);
    const { theme } = useTheme();

    useEffect(() => {
        const fetchData = async () => {
            try {
                const res = await axios.get(`${API_BASE_URL}/accounting_api.php`);
                setData(res.data);
            } catch (err) {
                console.error("Failed to fetch accounting data", err);
            } finally {
                setLoading(false);
            }
        };
        fetchData();
    }, []);

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
                        <div style={{ width: '100%', height: 350 }}>
                            <ResponsiveContainer width="100%" height="100%" minWidth={0}>
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
                        <div style={{ width: '100%', height: 300 }}>
                            <ResponsiveContainer width="100%" height="100%" minWidth={0}>
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
                <h5 className="mb-4 fw-bold">最近财务异動</h5>
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
                                    <button className="btn btn-sm btn-outline-secondary">詳情</button>
                                </td>
                            </tr>
                        ))}
                    </tbody>
                </Table>
            </Card>
        </Container>
    );
}
