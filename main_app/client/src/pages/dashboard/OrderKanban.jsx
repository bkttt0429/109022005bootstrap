import React, { useState, useEffect } from 'react';
import { Container, Card, Badge, Col, Row } from 'react-bootstrap';
import { DndProvider, useDrag, useDrop } from 'react-dnd';
import { HTML5Backend } from 'react-dnd-html5-backend';
import axios from 'axios';
import { API_BASE_URL } from '../../utils/apiConfig';
import { toast } from 'react-hot-toast';

const ItemTypes = {
    ORDER: 'order',
};

// Draggable Order Card
const OrderCard = ({ order }) => {
    const [{ isDragging }, drag] = useDrag(() => ({
        type: ItemTypes.ORDER,
        item: { id: order.id, status: order.status },
        collect: (monitor) => ({
            isDragging: !!monitor.isDragging(),
        }),
    }));

    return (
        <div ref={drag} style={{ opacity: isDragging ? 0.5 : 1, cursor: 'move' }}>
            <Card className="mb-2 shadow-sm border-0">
                <Card.Body className="p-2">
                    <div className="d-flex justify-content-between">
                        <strong>{order.id}</strong>
                        <Badge bg={
                            order.status === 'Completed' ? 'success' :
                                order.status === 'Pending' ? 'warning' : 'primary'
                        }>{order.status}</Badge>
                    </div>
                    <div className="small text-muted">{order.user_name || 'Guest'}</div>
                    <div className="fw-bold text-primary mt-1">NT$ {parseFloat(order.total_amount).toLocaleString()}</div>
                </Card.Body>
            </Card>
        </div>
    );
};

// Droppable Column
const StatusColumn = ({ status, orders, onDrop }) => {
    const [{ isOver }, drop] = useDrop(() => ({
        accept: ItemTypes.ORDER,
        drop: (item) => onDrop(item.id, status),
        collect: (monitor) => ({
            isOver: !!monitor.isOver(),
        }),
    }));

    const bgMap = {
        'Pending': '#fff3cd',
        'Processing': '#cfe2ff',
        'Completed': '#d1e7dd',
        'Cancelled': '#f8d7da'
    };

    return (
        <Col md={3} className="d-flex flex-column h-100">
            <div className="p-2 fw-bold text-center rounded-top bg-white border-bottom">
                {status} ({orders.length})
            </div>
            <div
                ref={drop}
                className="flex-grow-1 p-2 rounded-bottom"
                style={{
                    backgroundColor: isOver ? '#e9ecef' : bgMap[status] || '#f8f9fa',
                    minHeight: '400px',
                    transition: 'background-color 0.2s'
                }}
            >
                {orders.map(o => <OrderCard key={o.id} order={o} />)}
            </div>
        </Col>
    );
};

export default function OrderKanban() {
    const [orders, setOrders] = useState([]);

    useEffect(() => {
        fetchOrders();
    }, []);

    const fetchOrders = async () => {
        try {
            const res = await axios.get(`${API_BASE_URL}/orders_api.php`);
            if (Array.isArray(res.data)) {
                setOrders(res.data);
            } else {
                console.error("Expected array but got:", res.data);
                setOrders([]);
            }
        } catch (error) {
            console.error(error);
            toast.error('無法載入訂單');
        }
    };

    const handleDrop = async (id, newStatus) => {
        // Optimistic Update
        setOrders(prev => prev.map(o => o.id === id ? { ...o, status: newStatus } : o));

        try {
            await axios.post(`${API_BASE_URL}/orders_api.php`, {
                action: 'update_status',
                id,
                status: newStatus
            });
            toast.success(`訂單 #${id} 已更新為 ${newStatus}`);
        } catch (error) {
            toast.error('更新失敗');
            fetchOrders(); // Revert
        }
    };

    return (
        <DndProvider backend={HTML5Backend}>
            <Container fluid className="h-100 d-flex flex-column">
                <h2 className="mb-4">訂單看板 (Kanban)</h2>
                <Row className="flex-grow-1 g-3">
                    <StatusColumn status="Pending" orders={orders.filter(o => o.status === 'Pending')} onDrop={handleDrop} />
                    <StatusColumn status="Processing" orders={orders.filter(o => o.status === 'Processing')} onDrop={handleDrop} />
                    <StatusColumn status="Completed" orders={orders.filter(o => o.status === 'Completed')} onDrop={handleDrop} />
                    <StatusColumn status="Cancelled" orders={orders.filter(o => o.status === 'Cancelled')} onDrop={handleDrop} />
                </Row>
            </Container>
        </DndProvider>
    );
}
