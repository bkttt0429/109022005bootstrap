import React from 'react';
import { Container, Row, Col, Card, ListGroup, Badge, Button } from 'react-bootstrap';
import { useNavigate } from 'react-router-dom';
import { useAuth } from '../../context/AuthContext';
import axios from 'axios';
import { FaRobot, FaChartBar, FaEye } from 'react-icons/fa';
import { BarChart, Bar, XAxis, YAxis, CartesianGrid, Tooltip, Legend, ResponsiveContainer } from 'recharts';
import Skeleton from 'react-loading-skeleton';
import { useQuery } from '@tanstack/react-query';
import { useTheme } from '../../context/ThemeContext';

import { API_BASE_URL } from '../../utils/apiConfig';

export default function Overview() {
    const { user, loading: authLoading } = useAuth();
    const { theme } = useTheme();
    const navigate = useNavigate();

    // React Query Fetcher
    const { data: dashboardData, isLoading, isError } = useQuery({
        queryKey: ['dashboardStats'],
        queryFn: async () => {
            const res = await axios.get(`${API_BASE_URL}/dashboard_stats.php`);
            return res.data;
        },
        refetchInterval: 30000, // Background Refresh every 30s
    });

    if (authLoading || !user) return null;

    // Destructure Data or Use Defaults
    const chartData = dashboardData?.chartData || [];
    const stats = dashboardData?.stats || {};

    return (
        <Container className="py-4">
            <h2 className="mb-4">
                {isLoading ? <Skeleton width={200} /> : '管理控制台'}
            </h2>
            <Row>
                <Col md={8} className="mb-4">
                    <Card className="shadow-sm border-0 mb-4">
                        <Card.Header className="bg-light border-0 fw-bold d-flex align-items-center text-dark" style={{ borderTopLeftRadius: '10px', borderTopRightRadius: '10px' }}>
                            <FaChartBar className="me-2 text-primary" /> 月銷售/庫存概覽
                        </Card.Header>
                        <Card.Body style={{ height: '350px', position: 'relative' }}>
                            {isLoading ? (
                                <Skeleton height={260} />
                            ) : (
                                <ResponsiveContainer width="100%" height="100%" minWidth={0} debounce={100}>
                                    <BarChart data={chartData}>
                                        <CartesianGrid strokeDasharray="3 3" vertical={false} stroke={theme === 'dark' ? '#eee' : '#ccc'} />
                                        <XAxis dataKey="name" tick={{ fill: '#666' }} />
                                        <YAxis tick={{ fill: '#666' }} />
                                        <Tooltip contentStyle={{ borderRadius: '10px', border: 'none', boxShadow: '0 4px 6px rgba(0,0,0,0.1)' }} />
                                        <Legend />
                                        <Bar dataKey="sales" fill="#0d6efd" name="銷售額" radius={[4, 4, 0, 0]} />
                                        <Bar dataKey="inventory" fill="#0dcaf0" name="庫存量" radius={[4, 4, 0, 0]} />
                                    </BarChart>
                                </ResponsiveContainer>
                            )}
                        </Card.Body>
                    </Card>

                    <Card className="shadow-sm border-0">
                        <Card.Body className="d-flex align-items-center justify-content-center flex-column text-muted py-5">
                            <FaRobot size={40} className="mb-3" />
                            <h5>需要協助嗎？</h5>
                            <p>前往 <a href="#/dashboard/chat">RAG 智慧助理</a> 查詢庫存與訂單。</p>
                            <Button variant="outline-primary" onClick={() => navigate('/dashboard/chat')}>開啟助理</Button>
                        </Card.Body>
                    </Card>
                </Col>

                <Col md={4}>
                    <Card className="shadow-sm border-0">
                        <Card.Header className="bg-light border-0 fw-bold text-dark" style={{ borderTopLeftRadius: '10px', borderTopRightRadius: '10px' }}>系統狀態</Card.Header>
                        <ListGroup variant="flush">
                            <ListGroup.Item className="d-flex justify-content-between align-items-center">
                                伺服器狀態
                                {isLoading ? <Skeleton width={50} /> : <Badge bg="success">{stats.system?.server}</Badge>}
                            </ListGroup.Item>
                            <ListGroup.Item className="d-flex justify-content-between align-items-center">
                                資料庫連線
                                {isLoading ? <Skeleton width={50} /> : <Badge bg="success">{stats.system?.db}</Badge>}
                            </ListGroup.Item>
                            <ListGroup.Item className="d-flex justify-content-between align-items-center">
                                目前使用者
                                {isLoading ? <Skeleton width={100} /> : <span className="text-muted">{user.email}</span>}
                            </ListGroup.Item>
                            <ListGroup.Item className="d-flex justify-content-between align-items-center">
                                總商品數
                                {isLoading ? <Skeleton width={50} /> : <strong>{stats.products}</strong>}
                            </ListGroup.Item>
                            <ListGroup.Item className="d-flex justify-content-between align-items-center">
                                總訂單數
                                {isLoading ? <Skeleton width={50} /> : <strong>{stats.orders}</strong>}
                            </ListGroup.Item>
                            <ListGroup.Item className="d-flex justify-content-between align-items-center">
                                低庫存警示
                                {isLoading ? <Skeleton width={50} /> : <Badge bg={stats.lowStock > 0 ? 'danger' : 'secondary'}>{stats.lowStock}</Badge>}
                            </ListGroup.Item>
                        </ListGroup>
                    </Card>
                </Col>
            </Row>
        </Container>
    );
}
