import React, { useState, useEffect } from 'react';
import { Card, Table, Button, Form, Badge, Container, Tab, Tabs, Row, Col, Alert } from 'react-bootstrap';
import axios from 'axios';
import { API_BASE_URL } from '../../utils/apiConfig';
import { toast } from 'react-hot-toast';

export default function AutomationSettings() {
    const [configs, setConfigs] = useState([]);
    const [logs, setLogs] = useState([]);
    const [workflows, setWorkflows] = useState([]);
    const [hasApiKey, setHasApiKey] = useState(false);
    const [apiKey, setApiKey] = useState('');
    const [isLoading, setIsLoading] = useState(false);

    // Config Form State
    const [showForm, setShowForm] = useState(false);
    const [editingConfig, setEditingConfig] = useState(null);

    useEffect(() => {
        fetchConfigs();
        fetchApiKeyStatus();
        fetchLogs();
        const interval = setInterval(fetchLogs, 5000);
        return () => clearInterval(interval);
    }, []);

    useEffect(() => {
        if (hasApiKey) {
            fetchWorkflows();
        }
    }, [hasApiKey]);

    const fetchConfigs = async () => {
        try {
            const res = await axios.get(`${API_BASE_URL}/automation_api.php?action=get_configs`);
            setConfigs(res.data);
        } catch (error) { console.error(error); }
    };

    const fetchLogs = async () => {
        try {
            const res = await axios.get(`${API_BASE_URL}/automation_api.php?action=get_logs`);
            setLogs(res.data);
        } catch (error) { console.error(error); }
    };

    const fetchApiKeyStatus = async () => {
        try {
            const res = await axios.get(`${API_BASE_URL}/automation_api.php?action=get_n8n_key`);
            setHasApiKey(res.data.has_key);
        } catch (error) { console.error(error); }
    };

    const fetchWorkflows = async () => {
        setIsLoading(true);
        try {
            // Proxy request to n8n
            const res = await axios.post(`${API_BASE_URL}/automation_api.php`, {
                action: 'proxy_n8n',
                endpoint: 'workflows',
                method: 'GET'
            });
            // n8n returns { data: [...] }
            setWorkflows(res.data.data || []);
        } catch (error) {
            console.error(error);
            // toast.error('無法連線至 n8n，請檢查 API Key');
        } finally {
            setIsLoading(false);
        }
    };

    const saveApiKey = async (e) => {
        e.preventDefault();
        try {
            await axios.post(`${API_BASE_URL}/automation_api.php`, {
                action: 'save_n8n_key',
                api_key: apiKey
            });
            toast.success('API Key 已儲存');
            setHasApiKey(true);
            setApiKey('');
            fetchWorkflows();
        } catch (error) {
            toast.error('儲存失敗');
        }
    };

    const toggleWorkflow = async (id, isActive) => {
        try {
            await axios.post(`${API_BASE_URL}/automation_api.php`, {
                action: 'proxy_n8n',
                endpoint: `workflows/${id}/${isActive ? 'deactivate' : 'activate'}`,
                method: 'POST'
            });
            toast.success(isActive ? '已停用' : '已啟用');
            fetchWorkflows();
        } catch (error) {
            toast.error('操作失敗');
        }
    };

    const handleSaveConfig = async (e) => {
        e.preventDefault();
        const formData = new FormData(e.target);
        const data = Object.fromEntries(formData.entries());
        try {
            await axios.post(`${API_BASE_URL}/automation_api.php`, {
                action: 'save_config',
                ...data,
                id: editingConfig?.id
            });
            toast.success('設定已儲存');
            setShowForm(false);
            setEditingConfig(null);
            fetchConfigs();
        } catch (error) { toast.error('儲存失敗'); }
    };

    const handleDeleteConfig = async (id) => {
        if (!confirm('確定要刪除嗎？')) return;
        try {
            await axios.post(`${API_BASE_URL}/automation_api.php`, {
                action: 'delete_config',
                id
            });
            toast.success('已刪除');
            fetchConfigs();
        } catch (error) { toast.error('刪除失敗'); }
    };

    return (
        <Container fluid className="py-4">
            <div className="d-flex justify-content-between align-items-center mb-4">
                <h2>⚡ n8n 自動化中控台</h2>
                <div className="d-flex gap-2">
                    <Button variant="outline-primary" href="http://localhost:5678" target="_blank">
                        <i className="bi bi-box-arrow-up-right me-1"></i> n8n 編輯器
                    </Button>
                </div>
            </div>

            <Tabs defaultActiveKey="workflows" className="mb-4">
                <Tab eventKey="workflows" title="🕹️ Workflow 控制台">
                    {!hasApiKey ? (
                        <Alert variant="warning" className="d-flex align-items-center justify-content-between">
                            <div>
                                <i className="bi bi-exclamation-triangle me-2"></i>
                                尚未設定 n8n API Key，無法遙控工作流。
                            </div>
                            <Form onSubmit={saveApiKey} className="d-flex gap-2">
                                <Form.Control
                                    type="password"
                                    placeholder="貼上 API Key"
                                    value={apiKey}
                                    onChange={e => setApiKey(e.target.value)}
                                    size="sm"
                                    style={{ width: '250px' }}
                                />
                                <Button type="submit" variant="primary" size="sm">儲存連線</Button>
                            </Form>
                        </Alert>
                    ) : (
                        <Card className="shadow-sm border-0">
                            <Card.Body>
                                <div className="d-flex justify-content-between mb-3">
                                    <h5 className="mb-0">已部署的自動化機器人</h5>
                                    <div>
                                        <Button size="sm" variant="outline-danger" className="me-2" onClick={() => setHasApiKey(false)}>
                                            重設 API Key
                                        </Button>
                                        <Button size="sm" variant="outline-secondary" onClick={fetchWorkflows} disabled={isLoading}>
                                            <i className="bi bi-arrow-clockwise me-1"></i> 重新整理
                                        </Button>
                                    </div>
                                </div>
                                {isLoading ? (
                                    <div className="text-center py-5">載入中...</div>
                                ) : (
                                    <Table hover>
                                        <thead>
                                            <tr>
                                                <th>狀態</th>
                                                <th>名稱</th>
                                                <th>ID</th>
                                                <th>更新時間</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            {workflows.map(wf => (
                                                <tr key={wf.id}>
                                                    <td>
                                                        <Form.Check
                                                            type="switch"
                                                            checked={wf.active}
                                                            onChange={() => toggleWorkflow(wf.id, wf.active)}
                                                            label={wf.active ? <Badge bg="success">運作中</Badge> : <Badge bg="secondary">停止</Badge>}
                                                        />
                                                    </td>
                                                    <td className="fw-bold">{wf.name}</td>
                                                    <td className="text-muted small">{wf.id}</td>
                                                    <td className="text-muted small">{new Date(wf.updatedAt).toLocaleString()}</td>
                                                </tr>
                                            ))}
                                            {workflows.length === 0 && <tr><td colSpan="4" className="text-center py-4 text-muted">目前沒有可用的工作流 (請檢查 API Key 權限或 n8n 狀態)</td></tr>}
                                        </tbody>
                                    </Table>
                                )}
                            </Card.Body>
                        </Card>
                    )}
                </Tab>

                <Tab eventKey="webhooks" title="🔗 Webhook 連線設定">
                    <div className="d-flex justify-content-end mb-3">
                        <Button variant="success" onClick={() => { setEditingConfig(null); setShowForm(true); }}>
                            <i className="bi bi-plus-lg me-1"></i> 新增 Webhook
                        </Button>
                    </div>

                    {showForm && (
                        <Card className="mb-4 shadow-sm border-0 bg-light">
                            <Card.Body>
                                <Form onSubmit={handleSaveConfig}>
                                    <Row>
                                        <Col md={3}>
                                            <Form.Group className="mb-3">
                                                <Form.Label>觸發事件 (Topic)</Form.Label>
                                                <Form.Select name="event_topic" defaultValue={editingConfig?.event_topic}>
                                                    <option value="ORDER_STATUS_UPDATED">ORDER_STATUS_UPDATED</option>
                                                    <option value="ORDER_CREATED">ORDER_CREATED</option>
                                                    <option value="STOCK_LOW">STOCK_LOW</option>
                                                </Form.Select>
                                            </Form.Group>
                                        </Col>
                                        <Col md={6}>
                                            <Form.Group className="mb-3">
                                                <Form.Label>n8n Webhook URL</Form.Label>
                                                <Form.Control name="n8n_webhook_url" defaultValue={editingConfig?.n8n_webhook_url} placeholder="http://localhost:5678/webhook/..." required />
                                            </Form.Group>
                                        </Col>
                                        <Col md={3}>
                                            <Form.Group className="mb-3">
                                                <Form.Label>描述</Form.Label>
                                                <Form.Control name="description" defaultValue={editingConfig?.description} />
                                            </Form.Group>
                                        </Col>
                                    </Row>
                                    <div className="d-flex gap-2 justify-content-end">
                                        <Button variant="secondary" size="sm" onClick={() => setShowForm(false)}>取消</Button>
                                        <Button variant="primary" size="sm" type="submit">儲存</Button>
                                    </div>
                                </Form>
                            </Card.Body>
                        </Card>
                    )}

                    <Card className="shadow-sm border-0 mb-4">
                        <Table hover responsive className="mb-0">
                            <thead className="table-light">
                                <tr><th>事件</th><th>Webhook URL</th><th>描述</th><th>操作</th></tr>
                            </thead>
                            <tbody>
                                {configs.map(c => (
                                    <tr key={c.id}>
                                        <td><Badge bg="info">{c.event_topic}</Badge></td>
                                        <td className="text-break" style={{ maxWidth: '300px' }}>{c.n8n_webhook_url}</td>
                                        <td>{c.description}</td>
                                        <td>
                                            <Button size="sm" variant="outline-danger" onClick={() => handleDeleteConfig(c.id)}>刪除</Button>
                                        </td>
                                    </tr>
                                ))}
                                {configs.length === 0 && <tr><td colSpan="4" className="text-center py-4 text-muted">尚未設定 Webhook</td></tr>}
                            </tbody>
                        </Table>
                    </Card>
                </Tab>

                <Tab eventKey="logs" title="📜 執行日誌">
                    <Card className="shadow-sm border-0">
                        <div style={{ maxHeight: '500px', overflowY: 'auto' }}>
                            <Table size="sm" hover className="mb-0">
                                <thead className="table-light sticky-top">
                                    <tr>
                                        <th>時間</th>
                                        <th>事件</th>
                                        <th>ID</th>
                                        <th>Payload</th>
                                        <th>狀態</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {logs.map(log => (
                                        <tr key={log.id}>
                                            <td className="small text-muted">{new Date(log.created_at).toLocaleString()}</td>
                                            <td><Badge bg="secondary">{log.event_topic}</Badge></td>
                                            <td>{log.entity_id}</td>
                                            <td className="small font-monospace text-truncate" style={{ maxWidth: '200px' }} title={JSON.stringify(log.payload)}>
                                                {JSON.stringify(log.payload)}
                                            </td>
                                            <td><Badge bg={log.response_status === 200 ? 'success' : 'danger'}>{log.response_status}</Badge></td>
                                        </tr>
                                    ))}
                                    {logs.length === 0 && <tr><td colSpan="5" className="text-center py-4 text-muted">無日誌資料</td></tr>}
                                </tbody>
                            </Table>
                        </div>
                    </Card>
                </Tab>
            </Tabs>
        </Container>
    );
}
