import React, { useState, useEffect, useRef } from 'react';
import { Card, Form, Button } from 'react-bootstrap';
import { FaPaperPlane, FaRobot, FaUser } from 'react-icons/fa';
import { BarChart, Bar, XAxis, YAxis, CartesianGrid, Tooltip, Legend, ResponsiveContainer } from 'recharts';
import ReactMarkdown from 'react-markdown';
import { toast } from 'react-hot-toast';
import { motion, AnimatePresence } from 'framer-motion';
import axios from 'axios';
import { API_BASE_URL, API_V1_URL } from '../../utils/apiConfig';

export default function AdminChat() {
    const [messages, setMessages] = useState(() => {
        const saved = localStorage.getItem('admin_chat_history');
        return saved ? JSON.parse(saved) : [
            { sender: 'ai', text: '你好！我是 ERP 智慧助理。請問想查詢什麼資料？', timestamp: new Date().toLocaleTimeString() }
        ];
    });
    const [input, setInput] = useState('');
    const [loading, setLoading] = useState(false);
    const messagesEndRef = useRef(null);

    // Save history
    useEffect(() => {
        localStorage.setItem('admin_chat_history', JSON.stringify(messages));
    }, [messages]);

    const scrollToBottom = () => {
        messagesEndRef.current?.scrollIntoView({ behavior: "smooth" });
    };

    useEffect(() => {
        scrollToBottom();
    }, [messages]);

    const handleSend = async (e) => {
        e.preventDefault();
        if (!input.trim()) return;

        const userMsg = {
            sender: 'user',
            text: input,
            timestamp: new Date().toLocaleTimeString()
        };
        setMessages(prev => [...prev, userMsg]);
        setInput('');
        setLoading(true);

        try {
            const res = await axios.post(`${API_V1_URL}/chat`, { message: input });
            let replyText = res.data.reply;

            // Parse for <action> tags (NEW: Trigger API)
            const actionMatch = replyText.match(/<action>(.*?)<\/action>/s);
            if (actionMatch) {
                try {
                    const actionData = JSON.parse(actionMatch[1]);
                    replyText = replyText.replace(/<action>.*?<\/action>/s, '').trim();

                    if (actionData.type === 'update_status') {
                        await axios.put(`${API_V1_URL}/orders/${actionData.id}/status`, {
                            status: actionData.status
                        });
                        toast.success(`🤖 AI 執行指令：將訂單 #${actionData.id} 更新為 ${actionData.status}`, { icon: '⚡' });
                        replyText += `\n\n✅ **操作完成**：已將訂單 #${actionData.id} 設定為 ${actionData.status}。`;
                    }
                } catch (e) {
                    console.error("Failed to parse action data", e);
                }
            }

            // Parse for <data> tags
            let chartData = null;
            const dataMatch = replyText.match(/<data>(.*?)<\/data>/s);
            if (dataMatch) {
                try {
                    chartData = JSON.parse(dataMatch[1]);
                    replyText = replyText.replace(/<data>.*?<\/data>/s, '').trim();
                } catch (e) {
                    console.error("Failed to parse chart data", e);
                }
            }

            const aiMsg = {
                sender: 'ai',
                text: replyText,
                chartData: chartData,
                timestamp: new Date().toLocaleTimeString(),
                debug: res.data.debug_context
            };
            setMessages(prev => [...prev, aiMsg]);
        } catch (err) {
            setMessages(prev => [...prev, {
                sender: 'ai',
                text: '抱歉，發生連線錯誤，請稍後再試。',
                timestamp: new Date().toLocaleTimeString()
            }]);
        } finally {
            setLoading(false);
        }
    };

    const clearHistory = () => {
        if (window.confirm('確定要清除對話紀錄嗎？')) {
            setMessages([{ sender: 'ai', text: '對話紀錄已清除。我是 ERP 智慧助理，請問想查詢什麼資料？', timestamp: new Date().toLocaleTimeString() }]);
        }
    };

    return (
        <div className="container-fluid h-100 d-flex flex-column py-3 bg-light">
            <div className="d-flex justify-content-between align-items-center mb-3 px-2">
                <h2 className="m-0 fw-bold text-primary">
                    <FaRobot className="me-2" />
                    RAG 智慧助理
                </h2>
                <Button variant="outline-secondary" size="sm" onClick={clearHistory}>
                    清除紀錄
                </Button>
            </div>

            <Card className="border-0 shadow-lg flex-grow-1 d-flex flex-column" style={{ overflow: 'hidden', borderRadius: '15px' }}>
                <Card.Body className="p-0 d-flex flex-column bg-white">
                    <div className="flex-grow-1 overflow-auto p-4 custom-scrollbar" style={{ background: '#f8f9fa' }}>
                        <AnimatePresence initial={false}>
                            {messages.map((msg, idx) => (
                                <motion.div
                                    key={idx}
                                    initial={{ opacity: 0, y: 10, scale: 0.95 }}
                                    animate={{ opacity: 1, y: 0, scale: 1 }}
                                    transition={{ duration: 0.3 }}
                                    className={`d-flex mb-4 ${msg.sender === 'user' ? 'justify-content-end' : 'justify-content-start'}`}
                                >
                                    <div className={`p-3 position-relative ${msg.sender === 'user' ? 'bg-primary text-white ms-5' : 'bg-white border text-dark me-5'}`}
                                        style={{
                                            borderRadius: msg.sender === 'user' ? '20px 20px 4px 20px' : '20px 20px 20px 4px',
                                            boxShadow: '0 4px 15px rgba(0,0,0,0.05)',
                                            maxWidth: '80%'
                                        }}>
                                        <div className={`d-flex align-items-center mb-2 small font-weight-bold ${msg.sender === 'user' ? 'text-white-50' : 'text-primary'}`}>
                                            {msg.sender === 'user' ? <FaUser className="me-2" /> : <FaRobot className="me-2" />}
                                            {msg.sender === 'user' ? '管理員' : 'ERP Bot'}
                                            <span className="ms-2 opacity-50" style={{ fontSize: '0.75rem' }}>{msg.timestamp}</span>
                                        </div>

                                        <div className="markdown-content" style={{ fontSize: '0.95rem', lineHeight: '1.5' }}>
                                            <ReactMarkdown>{msg.text}</ReactMarkdown>
                                        </div>

                                        {/* Render Chart if data exists */}
                                        {msg.chartData && (
                                            <motion.div
                                                initial={{ opacity: 0, height: 0 }}
                                                animate={{ opacity: 1, height: 'auto' }}
                                                className="mt-4 p-2 bg-light rounded"
                                            >
                                                <div className="small fw-bold text-secondary mb-2">📊 數據視覺化</div>
                                                <div style={{ height: '250px', width: '100%' }}>
                                                    <ResponsiveContainer width="100%" height="100%">
                                                        <BarChart data={msg.chartData} layout="vertical">
                                                            <CartesianGrid strokeDasharray="3 3" horizontal={false} />
                                                            <XAxis type="number" />
                                                            <YAxis dataKey="name" type="category" width={80} tick={{ fontSize: 10 }} />
                                                            <Tooltip cursor={{ fill: 'rgba(0,0,0,0.05)' }} />
                                                            <Bar dataKey="value" fill="#0d6efd" radius={[0, 4, 4, 0]} />
                                                        </BarChart>
                                                    </ResponsiveContainer>
                                                </div>
                                            </motion.div>
                                        )}
                                    </div>
                                </motion.div>
                            ))}
                        </AnimatePresence>

                        {loading && (
                            <motion.div
                                initial={{ opacity: 0 }}
                                animate={{ opacity: 1 }}
                                className="d-flex justify-content-start mb-4"
                            >
                                <div className="bg-white border p-3 rounded-pill d-flex align-items-center shadow-sm">
                                    <div className="typing-dot"></div>
                                    <div className="typing-dot ms-1"></div>
                                    <div className="typing-dot ms-1"></div>
                                    <span className="ms-2 small text-muted">AI 正在思考並處理中...</span>
                                </div>
                            </motion.div>
                        )}
                        <div ref={messagesEndRef} />
                    </div>
                </Card.Body>

                <Card.Footer className="bg-light border-top p-3" style={{ borderRadius: '0 0 15px 15px' }}>
                    <Form onSubmit={handleSend} className="d-flex align-items-center">
                        <Form.Control
                            type="text"
                            placeholder="輸入指令，例如：把訂單 123 改成 Paid..."
                            value={input}
                            onChange={(e) => setInput(e.target.value)}
                            disabled={loading}
                            className="py-2 border chat-input-field rounded-pill px-4 me-2"
                            style={{ fontSize: '1rem', background: '#ffffff', color: '#212529' }}
                        />
                        <Button
                            variant="primary"
                            type="submit"
                            disabled={loading || !input.trim()}
                            className="rounded-circle d-flex align-items-center justify-content-center shadow-sm"
                            style={{ width: '45px', height: '45px', minWidth: '45px', backgroundColor: '#4f46e5', borderColor: '#4f46e5' }}
                        >
                            <FaPaperPlane />
                        </Button>
                    </Form>
                </Card.Footer>
            </Card>

            <style>{`
                .custom-scrollbar::-webkit-scrollbar { width: 6px; }
                .custom-scrollbar::-webkit-scrollbar-thumb { background: #dee2e6; border-radius: 10px; }
                .typing-dot { width: 6px; height: 6px; background: #adb5bd; border-radius: 50%; animation: blink 1.4s infinite both; }
                .typing-dot:nth-child(2) { animation-delay: 0.2s; }
                .typing-dot:nth-child(3) { animation-delay: 0.4s; }
                @keyframes blink { 0%, 80%, 100% { opacity: 0; } 40% { opacity: 1; } }
                .markdown-content p:last-child { margin-bottom: 0; }
                .bg-primary { background-color: #4f46e5 !important; }
                .text-primary { color: #4f46e5 !important; }
                .chat-input-field::placeholder { color: #adb5bd; opacity: 1; }
                .chat-input-field:focus { border-color: #4f46e5 !important; box-shadow: 0 0 0 0.25rem rgba(79, 70, 229, 0.1) !important; }
            `}</style>
        </div>
    );
}
