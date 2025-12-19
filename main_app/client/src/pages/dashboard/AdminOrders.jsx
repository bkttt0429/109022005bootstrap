import React, { useState, useEffect, useMemo } from 'react';
import { Card, Badge, Button } from 'react-bootstrap';
import { AgGridReact } from 'ag-grid-react';
import { AllCommunityModule, ModuleRegistry } from 'ag-grid-community';
import axios from 'axios';
import { API_BASE_URL } from '../../utils/apiConfig';
import { toast } from 'react-hot-toast';
import { useTheme } from '../../context/ThemeContext';

// Register AG Grid Modules
ModuleRegistry.registerModules([AllCommunityModule]);

export default function AdminOrders() {
    const [rowData, setRowData] = useState([]);
    const { theme } = useTheme();

    // Fetch Data
    useEffect(() => {
        fetchOrders();
    }, []);

    const fetchOrders = async () => {
        try {
            const res = await axios.get(`${API_BASE_URL}/orders_api.php`);
            if (Array.isArray(res.data)) {
                setRowData(res.data);
            } else {
                setRowData([]);
            }
        } catch (error) {
            toast.error('無法載入訂單');
        }
    };

    // Column Definitions
    const colDefs = useMemo(() => [
        { field: "id", headerName: "ID", width: 80, sortable: true, filter: true },
        { field: "order_number", headerName: "訂單編號", width: 220, filter: true },
        { field: "user_name", headerName: "客戶", width: 150, filter: true }, // From Join
        {
            field: "total_amount",
            headerName: "總金額",
            width: 120,
            sortable: true,
            valueFormatter: p => `NT$ ${parseFloat(p.value).toLocaleString()}`
        },
        {
            field: "status",
            headerName: "狀態",
            width: 120,
            cellRenderer: p => (
                <Badge bg={
                    p.value === 'Completed' ? 'success' :
                        p.value === 'Pending' ? 'warning' : 'danger'
                }>
                    {p.value}
                </Badge>
            )
        },
        { field: "created_at", headerName: "建立時間", width: 180, sortable: true },
        {
            headerName: "操作",
            width: 150,
            cellRenderer: (params) => (
                <Button size="sm" variant="outline-primary" onClick={() => alert(`查看訂單 #${params.data.id}`)}>
                    查看詳情
                </Button>
            )
        }
    ], []);

    return (
        <div className="container-fluid h-100 d-flex flex-column">
            <div className="d-flex justify-content-between align-items-center mb-4 p-2 bg-info bg-opacity-10 rounded">
                <h2 className={`mb-0 ${theme === 'dark' ? 'text-white' : 'text-dark'}`}>🟢 訂單管理核心 (已更新)</h2>
                <Button
                    variant="success"
                    className="btn-lg shadow"
                    onClick={() => window.open(`${API_BASE_URL}/export_api.php?type=orders`, '_blank')}
                    style={{ zIndex: 9999 }}
                >
                    💾 點我匯出 CSV 測試
                </Button>
            </div>
            <Card className={`shadow-sm border-0 flex-grow-1 ${theme === 'dark' ? 'bg-dark text-white border border-secondary' : ''}`} style={{ minHeight: '500px' }}>
                <Card.Body className="p-0 h-100">
                    <div className={`${theme === 'dark' ? 'ag-theme-alpine-dark' : 'ag-theme-alpine'} h-100`} style={{ width: '100%', height: '100%' }}>
                        <AgGridReact
                            rowData={rowData}
                            columnDefs={colDefs}
                            pagination={true}
                            paginationPageSize={10}
                            paginationPageSizeSelector={[10, 20, 50]}
                            rowSelection={{ mode: 'multiRow' }} // Fixed deprecated string value
                            animateRows={true}
                        />
                    </div>
                </Card.Body>
            </Card>
        </div>
    );
}
