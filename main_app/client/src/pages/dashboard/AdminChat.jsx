import React, { useState, useEffect, useRef } from 'react';
import { Card, Form, Button } from 'react-bootstrap';
import { FaPaperPlane, FaRobot, FaUser } from 'react-icons/fa';
import { BarChart, Bar, XAxis, YAxis, CartesianGrid, Tooltip, Legend, ResponsiveContainer } from 'recharts';
import axios from 'axios';
import { API_BASE_URL } from '../../utils/apiConfig';

export default function AdminChat() {
    const [messages, setMessages] = useState([
        { sender: 'ai', text: '你好！我是 ERP 智慧助理。請問想查詢什麼資料？' }
    ]);
    const [input, setInput] = useState('');
    const [loading, setLoading] = useState(false);
    const messagesEndRef = useRef(null);

    const scrollToBottom = () => {
        messagesEndRef.current?.scrollIntoView({ behavior: "smooth" });
    };

    useEffect(() => {
        scrollToBottom();
    }, [messages]);

    const handleSend = async (e) => {
        e.preventDefault();
        if (!input.trim()) return;

        const userMsg = { sender: 'user', text: input };
        setMessages(prev => [...prev, userMsg]);
        setInput('');
        setLoading(true);

        try {
            const res = await axios.post(`${API_BASE_URL}/rag_chat.php`, { message: input });

            // Parse for <data> tags
            let replyText = res.data.reply;
            let chartData = null;

            const dataMatch = replyText.match(/<data>(.*?)<\/data>/s);
            if (dataMatch) {
                try {
                    chartData = JSON.parse(dataMatch[1]);
                    // Clean the tag from display text
                    replyText = replyText.replace(/<data>.*?<\/data>/s, '').trim();
                } catch (e) {
                    console.error("Failed to parse chart data", e);
                }
            }

            const aiMsg = {
                sender: 'ai',
                text: replyText,
                chartData: chartData,
                debug: res.data.debug_context
            };
            setMessages(prev => [...prev, aiMsg]);
        } catch (err) {
            setMessages(prev => [...prev, { sender: 'ai', text: '抱歉，發生連線錯誤。' }]);
        } finally {
            setLoading(false);
        }
    };

    return (
        <div className="container-fluid h-100 d-flex flex-column">
            <h2 className="mb-4">RAG 智慧助理</h2>
            <Card className="border-0 shadow-sm flex-grow-1" style={{ minHeight: '500px' }}>
                <Card.Body className="d-flex flex-column bg-light" style={{ overflowY: 'auto' }}>
                    <div className="flex-grow-1 overflow-auto p-3">
                        {messages.map((msg, idx) => (
                            <div key={idx} className={`d-flex mb-3 ${msg.sender === 'user' ? 'justify-content-end' : 'justify-content-start'}`}>
                                <div className={`p-3 rounded-3 shadow-sm ${msg.sender === 'user' ? 'bg-primary text-white' : 'bg-white text-dark'}`} style={{ maxWidth: '85%' }}>
                                    <div className="small mb-1 opacity-75">
                                        {msg.sender === 'user' ? <FaUser className="me-1" /> : <FaRobot className="me-1" />}
                                        {msg.sender === 'user' ? 'You' : 'AI Assistant'}
                                    </div>
                                    <div style={{ whiteSpace: 'pre-wrap' }}>{msg.text}</div>

                                    {/* Render Chart if data exists */}
                                    {msg.chartData && (
                                        <div className="mt-3" style={{ height: '300px', width: '100%', minWidth: '300px' }}>
                                            <div className="small text-muted mb-2 border-top pt-2">📊 視覺化分析</div>
                                            <ResponsiveContainer width="100%" height="100%" minWidth={0}>
                                                <BarChart data={msg.chartData} layout="vertical" margin={{ top: 5, right: 30, left: 40, bottom: 5 }}>
                                                    <CartesianGrid strokeDasharray="3 3" />
                                                    <XAxis type="number" />
                                                    <YAxis dataKey="name" type="category" width={80} tick={{ fontSize: 12 }} />
                                                    <Tooltip contentStyle={{ color: '#333' }} />
                                                    <Bar dataKey="value" fill="#0d6efd" name="數值" radius={[0, 4, 4, 0]} />
                                                </BarChart>
                                            </ResponsiveContainer>
                                        </div>
                                    )}
                                </div>
                            </div>
                        ))}
                        {loading && <div className="text-center text-muted small">AI 正在思考中...</div>}
                        <div ref={messagesEndRef} />
                    </div>
                </Card.Body>
                <Card.Footer className="bg-white p-3">
                    <Form onSubmit={handleSend} className="d-flex">
                        <Form.Control
                            type="text"
                            placeholder="輸入問題，例如：庫存最少的商品？"
                            value={input}
                            onChange={(e) => setInput(e.target.value)}
                            className="me-2"
                            disabled={loading}
                        />
                        <Button variant="primary" type="submit" disabled={loading}>
                            <FaPaperPlane className="me-1" /> 發送
                        </Button>
                    </Form>
                </Card.Footer>
            </Card>
        </div>
    );
}
