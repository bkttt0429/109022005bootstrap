import React, { useState } from 'react';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import axios from 'axios';
import { Button, Form, Spinner, Alert, Badge, ListGroup } from 'react-bootstrap';
import { useAuth } from '../context/AuthContext';
import ReactMarkdown from 'react-markdown';

export default function ProductReviews({ productId }) {
    const { user } = useAuth();
    const queryClient = useQueryClient();
    const [summary, setSummary] = useState('');
    const [loadingSummary, setLoadingSummary] = useState(false);

    // Form State
    const [rating, setRating] = useState(5);
    const [comment, setComment] = useState('');

    // Fetch Reviews
    const { data, isLoading } = useQuery({
        queryKey: ['reviews', productId],
        queryFn: async () => {
            const res = await axios.get(`api/reviews_api.php?product_id=${productId}`);
            return res.data;
        }
    });

    // Submit Review Mutation
    const mutation = useMutation({
        mutationFn: async (newReview) => {
            return await axios.post('api/reviews_api.php', newReview);
        },
        onSuccess: () => {
            queryClient.invalidateQueries(['reviews', productId]);
            setComment('');
        }
    });

    const handleSubmit = (e) => {
        e.preventDefault();
        if (!user) return;
        mutation.mutate({
            user_id: user.id,
            product_id: productId,
            rating,
            comment
        });
    };

    const fetchSummary = async () => {
        setLoadingSummary(true);
        try {
            const res = await axios.get(`api/ai_summary.php?product_id=${productId}`);
            setSummary(res.data.summary);
        } catch (error) {
            setSummary('無法生成摘要，請稍後再試。');
        } finally {
            setLoadingSummary(false);
        }
    };

    if (isLoading) return <Spinner animation="border" size="sm" />;

    return (
        <div className="mt-4">
            <h5 className="border-bottom pb-2 mb-3">商品評論 (Avg: {data?.average_rating} ⭐ / {data?.total} 則)</h5>

            {/* AI Summary Section */}
            <div className="mb-4 p-3 bg-light rounded text-center">
                {!summary && !loadingSummary && (
                    <Button variant="outline-primary" size="sm" onClick={fetchSummary}>
                        ✨ 生成 AI 評論摘要
                    </Button>
                )}
                {loadingSummary && (
                    <div className="d-flex align-items-center justify-content-center gap-2">
                        <Spinner animation="grow" size="sm" />
                        <small className="text-muted">Gemini 正在分析所有評論...</small>
                    </div>
                )}
                {summary && (
                    <div className="text-start">
                        <strong className="d-block mb-2 text-primary">🤖 AI 購物助手分析：</strong>
                        <ReactMarkdown>{summary}</ReactMarkdown>
                    </div>
                )}
            </div>

            {/* Review List */}
            <ListGroup variant="flush" className="mb-4" style={{ maxHeight: '300px', overflowY: 'auto' }}>
                {data?.reviews.map((review) => (
                    <ListGroup.Item key={review.id} className="px-0">
                        <div className="d-flex justify-content-between align-items-start">
                            <div>
                                <strong>{review.user_name || 'Anonymous'}</strong>
                                <span className="ms-2 text-warning">{'★'.repeat(review.rating)}</span>
                            </div>
                            <small className="text-muted">{new Date(review.created_at).toLocaleDateString()}</small>
                        </div>
                        <p className="mb-0 mt-1 small text-secondary">{review.comment}</p>
                    </ListGroup.Item>
                ))}
            </ListGroup>

            {/* Review Form */}
            {user ? (
                <Form onSubmit={handleSubmit} className="bg-light p-3 rounded">
                    <h6>撰寫評論</h6>
                    <Form.Group className="mb-2">
                        <Form.Label>評分</Form.Label>
                        <Form.Select
                            value={rating}
                            onChange={(e) => setRating(Number(e.target.value))}
                            size="sm"
                        >
                            <option value="5">5 ★★★★★ (非常滿意)</option>
                            <option value="4">4 ★★★★ (滿意)</option>
                            <option value="3">3 ★★★ (普通)</option>
                            <option value="2">2 ★★ (不滿意)</option>
                            <option value="1">1 ★ (非常差)</option>
                        </Form.Select>
                    </Form.Group>
                    <Form.Group className="mb-2">
                        <Form.Control
                            as="textarea"
                            rows={2}
                            placeholder="分享您的使用心得..."
                            value={comment}
                            onChange={(e) => setComment(e.target.value)}
                            required
                        />
                    </Form.Group>
                    <div className="d-flex justify-content-end">
                        <Button type="submit" size="sm" disabled={mutation.isPending}>
                            {mutation.isPending ? '提交中...' : '送出評論'}
                        </Button>
                    </div>
                </Form>
            ) : (
                <Alert variant="info" className="py-2 text-center small">
                    <a href="#/signin">登入</a> 後即可發表評論
                </Alert>
            )}
        </div>
    );
}
