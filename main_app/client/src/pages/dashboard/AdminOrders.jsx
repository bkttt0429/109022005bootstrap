import React, { useState, useEffect, useMemo, useRef } from 'react';
import { Card, Badge, Button, Modal, Table } from 'react-bootstrap';
import { AgGridReact } from 'ag-grid-react';
import { AllCommunityModule, ModuleRegistry } from 'ag-grid-community';
import axios from 'axios';
import { API_BASE_URL } from '../../utils/apiConfig';
import { toast } from 'react-hot-toast';
import { useTheme } from '../../context/ThemeContext';
import { FaEye, FaShippingFast, FaCheck, FaTimes } from 'react-icons/fa';

// Register AG Grid Modules
ModuleRegistry.registerModules([AllCommunityModule]);

export default function AdminOrders() {
    const [rowData, setRowData] = useState([]);
    const [selectedOrder, setSelectedOrder] = useState(null);
    const [showModal, setShowModal] = useState(false);
    const { theme } = useTheme();
    const prevStatusMap = useRef({});

    // Fetch Data with Polling
    useEffect(() => {
        fetchOrders();
        const interval = setInterval(fetchOrders, 5000); // Poll every 5 seconds
        return () => clearInterval(interval);
    }, []);

    const fetchOrders = async () => {
        try {
            const res = await axios.get(`${API_BASE_URL}/orders_api.php`);
            const newOrders = Array.isArray(res.data) ? res.data : [];

            // Check for Status Changes (Notification)
            newOrders.forEach(order => {
                const oldStatus = prevStatusMap.current[order.id];
                if (oldStatus && oldStatus !== order.status && order.status === 'Shipped') {
                    toast.success(`訂單 #${order.order_number} 已自動出貨！ 🚚`, {
                        duration: 5000,
                        position: 'top-right',
                        style: {
                            background: '#10B981',
                            color: '#fff',
                            fontWeight: 'bold',
                        },
                        icon: '🚀',
                    });
                }
                prevStatusMap.current[order.id] = order.status;
            });

            setRowData(newOrders);
        } catch (error) {
            toast.error('無法載入訂單');
        }
    };

    const handleViewDetails = async (id) => {
        try {
            const res = await axios.get(`${API_BASE_URL}/orders_api.php?id=${id}`);
            setSelectedOrder(res.data);
            setShowModal(true);
        } catch (error) {
            toast.error('無法載入訂單詳情');
        }
    };

    const updateOrderStatus = async (id, newStatus) => {
        try {
            await axios.post(`${API_BASE_URL}/orders_api.php`, {
                action: 'update_status',
                id,
                status: newStatus
            });
            toast.success(`訂單 #${id} 狀態已更新為 ${newStatus}`);
            fetchOrders();
            if (selectedOrder && selectedOrder.id === id) {
                handleViewDetails(id); // Refresh modal
            }
        } catch (error) {
            toast.error('更新失敗');
        }
    };

    // Grid State Persistence
    const onGridReady = (params) => {
        const savedState = localStorage.getItem('adminOrdersGridState');
        if (savedState) {
            try {
                const { colState, filterModel } = JSON.parse(savedState);
                if (colState) params.api.applyColumnState({ state: colState, applyOrder: true });
                if (filterModel) params.api.setFilterModel(filterModel);
            } catch (e) {
                console.error('Failed to load grid state', e);
            }
        }
    };

    const onGridStateChanged = (params) => {
        const colState = params.api.getColumnState();
        const filterModel = params.api.getFilterModel();
        localStorage.setItem('adminOrdersGridState', JSON.stringify({ colState, filterModel }));
    };

    // Column Definitions
    const colDefs = useMemo(() => [
        { field: "id", headerName: "ID", width: 80 },
        { field: "order_number", headerName: "訂單編號", width: 220 },
        {
            field: "priority_score",
            headerName: "AI 優先級",
            width: 120,
            sortable: true,
            cellRenderer: p => (
                <div className="d-flex align-items-center">
                    <span className="fw-bold me-1">{parseFloat(p.value || 0).toFixed(1)}</span>
                    <i className="bi bi-magic text-warning" title="基於金額與等待時間的演算法權重"></i>
                </div>
            )
        },
        { field: "user_name", headerName: "客戶", width: 150 },
        {
            field: "total_amount",
            headerName: "總金額",
            width: 120,
            valueFormatter: p => `NT$ ${parseFloat(p.value).toLocaleString()}`
        },
        {
            field: "status",
            headerName: "狀態",
            width: 140,
            cellRenderer: p => {
                const colors = {
                    'Pending': 'warning',
                    'Paid': 'info',
                    'Processing': 'primary',
                    'Completed': 'success',
                    'Cancelled': 'danger'
                };
                return <Badge bg={colors[p.value] || 'secondary'}>{p.value}</Badge>;
            }
        },
        { field: "created_at", headerName: "成立時間", width: 180 },
        {
            headerName: "管理操作",
            width: 250,
            cellRenderer: (params) => (
                <div className="d-flex gap-1" style={{ paddingTop: '5px' }}>
                    <Button size="sm" variant="info" onClick={() => handleViewDetails(params.data.id)}>
                        <FaEye /> 詳情
                    </Button>
                    {params.data.status === 'Pending' && (
                        <Button size="sm" variant="outline-primary" onClick={() => updateOrderStatus(params.data.id, 'Processing')}>
                            <FaShippingFast /> 出貨
                        </Button>
                    )}
                    {params.data.status === 'Paid' && (
                        <Button size="sm" variant="outline-primary" onClick={() => updateOrderStatus(params.data.id, 'Processing')}>
                            <FaShippingFast /> 出貨
                        </Button>
                    )}
                    {params.data.status === 'Shipped' && (
                        /* Allow completing from Shipped if n8n sets it */
                        <Button size="sm" variant="outline-success" onClick={() => updateOrderStatus(params.data.id, 'Completed')}>
                            <FaCheck /> 完成
                        </Button>
                    )}
                    {params.data.status === 'Processing' && (
                        <Button size="sm" variant="outline-success" onClick={() => updateOrderStatus(params.data.id, 'Completed')}>
                            <FaCheck /> 完成
                        </Button>
                    )}
                </div>
            )
        }
    ], [selectedOrder]);

    return (
        <div className="container-fluid h-100 d-flex flex-column py-3">
            <div className={`d-flex justify-content-between align-items-center mb-4 p-3 rounded shadow-sm ${theme === 'dark' ? 'bg-secondary text-white' : 'bg-white text-dark'}`}>
                <h2 className="mb-0 fw-bold">📦 出貨與訂單管理系統</h2>
                <div className="d-flex gap-2">
                    <Button variant="outline-secondary" onClick={fetchOrders}>重新整理</Button>
                    <Button variant="success" onClick={() => window.open(`${API_BASE_URL}/export_api.php?type=orders`, '_blank')}>
                        匯出資料 (CSV)
                    </Button>
                </div>
            </div>

            <Card className={`shadow-sm border-0 flex-grow-1 ${theme === 'dark' ? 'bg-dark text-white' : ''}`} style={{ minHeight: '600px' }}>
                <Card.Body className="p-0 h-100">
                    <div className={`${theme === 'dark' ? 'ag-theme-alpine-dark' : 'ag-theme-alpine'} h-100`} style={{ width: '100%' }}>
                        <AgGridReact
                            rowData={rowData}
                            columnDefs={colDefs}
                            pagination={true}
                            paginationPageSize={15}
                            animateRows={true}
                            onGridReady={onGridReady}
                            onSortChanged={onGridStateChanged}
                            onFilterChanged={onGridStateChanged}
                            onColumnMoved={onGridStateChanged}
                            onColumnPinned={onGridStateChanged}
                            onColumnVisible={onGridStateChanged}
                        />
                    </div>
                </Card.Body>
            </Card>

            {/* Order Details Modal */}
            <Modal show={showModal} onHide={() => setShowModal(false)} size="lg" centered>
                <Modal.Header closeButton className={theme === 'dark' ? 'bg-dark text-white border-secondary' : ''}>
                    <Modal.Title>訂單詳情 #{selectedOrder?.order_number}</Modal.Title>
                </Modal.Header>
                <Modal.Body className={theme === 'dark' ? 'bg-dark text-white' : ''}>
                    {selectedOrder && (
                        <div className="p-2">
                            <div className="row mb-4">
                                <div className="col-md-6">
                                    <h6 className="text-muted text-uppercase small font-weight-bold">客戶資訊</h6>
                                    <p className="mb-1"><strong>名稱:</strong> {selectedOrder.user_name}</p>
                                    <p className="mb-1"><strong>Email:</strong> {selectedOrder.user_email}</p>
                                </div>
                                <div className="col-md-6 text-end">
                                    <h6 className="text-muted text-uppercase small font-weight-bold">收件資訊</h6>
                                    <p className="mb-1"><strong>電話:</strong> {selectedOrder.phone || 'N/A'}</p>
                                    <p className="mb-1"><strong>地址:</strong> {selectedOrder.shipping_address || 'N/A'}</p>
                                </div>
                            </div>

                            <h6 className="text-muted text-uppercase small font-weight-bold mb-3">商品項目</h6>
                            <Table responsive hover className={theme === 'dark' ? 'table-dark' : ''}>
                                <thead className="table-light text-dark">
                                    <tr>
                                        <th>品名</th>
                                        <th className="text-end">單價</th>
                                        <th className="text-center">數量</th>
                                        <th className="text-end">小計</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {selectedOrder.items?.map(item => (
                                        <tr key={item.id}>
                                            <td>{item.product_name}</td>
                                            <td className="text-end">NT$ {parseFloat(item.price).toLocaleString()}</td>
                                            <td className="text-center">{item.quantity}</td>
                                            <td className="text-end">NT$ {parseFloat(item.subtotal).toLocaleString()}</td>
                                        </tr>
                                    ))}
                                </tbody>
                                <tfoot>
                                    <tr className="fw-bold fs-5">
                                        <td colSpan="3" className="text-end">總計金額</td>
                                        <td className="text-end text-primary">NT$ {parseFloat(selectedOrder.total_amount).toLocaleString()}</td>
                                    </tr>
                                </tfoot>
                            </Table>

                            <div className="d-flex justify-content-center gap-3 mt-4">
                                {/* [NEW] Simulate Payment Button for n8n Testing */}
                                <Button variant="warning" size="lg" disabled={selectedOrder.status !== 'Pending'} onClick={() => updateOrderStatus(selectedOrder.id, 'Paid')}>
                                    <i className="bi bi-currency-dollar"></i> 模擬付款 (Set Paid)
                                </Button>

                                <Button variant="primary" size="lg" disabled={!['Pending', 'Paid'].includes(selectedOrder.status)} onClick={() => updateOrderStatus(selectedOrder.id, 'Processing')}>
                                    <FaShippingFast /> 確認出貨
                                </Button>
                                <Button variant="success" size="lg" disabled={!['Processing', 'Shipped'].includes(selectedOrder.status)} onClick={() => updateOrderStatus(selectedOrder.id, 'Completed')}>
                                    <FaCheck /> 完成訂單
                                </Button>
                                <Button variant="danger" size="lg" disabled={['Completed', 'Cancelled'].includes(selectedOrder.status)} onClick={() => updateOrderStatus(selectedOrder.id, 'Cancelled')}>
                                    <FaTimes /> 取消訂單
                                </Button>
                            </div>
                        </div>
                    )}
                </Modal.Body>
            </Modal>
        </div >
    );
}
