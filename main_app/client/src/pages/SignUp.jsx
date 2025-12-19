import React from 'react';
import { Container, Card, Form, Button } from 'react-bootstrap';
import { useForm } from 'react-hook-form';
import { Link, useNavigate } from 'react-router-dom';
import axios from 'axios';
import { API_BASE_URL } from '../utils/apiConfig';
import { toast } from 'react-hot-toast';

export default function SignUp() {
    const { register, handleSubmit, formState: { errors } } = useForm();
    const navigate = useNavigate();

    const onSubmit = async (data) => {
        try {
            await axios.post(`${API_BASE_URL}/register_api.php`, data);
            toast.success('註冊成功！請登入');
            navigate('/signin');
        } catch (error) {
            toast.error(error.response?.data?.error || '註冊失敗');
        }
    };

    return (
        <Container className="d-flex justify-content-center align-items-center" style={{ minHeight: '80vh' }}>
            <Card className="shadow-lg border-0" style={{ width: '400px' }}>
                <Card.Body className="p-5">
                    <h3 className="text-center mb-4 fw-bold text-primary">建立新帳號</h3>
                    <Form onSubmit={handleSubmit(onSubmit)}>
                        <Form.Group className="mb-3">
                            <Form.Label>姓名</Form.Label>
                            <Form.Control
                                type="text"
                                placeholder="Your Name"
                                {...register("name", { required: "請輸入姓名" })}
                                isInvalid={!!errors.name}
                            />
                            <Form.Control.Feedback type="invalid">{errors.name?.message}</Form.Control.Feedback>
                        </Form.Group>

                        <Form.Group className="mb-3">
                            <Form.Label>Email</Form.Label>
                            <Form.Control
                                type="email"
                                placeholder="user@example.com"
                                {...register("email", { required: "請輸入 Email" })}
                                isInvalid={!!errors.email}
                            />
                            <Form.Control.Feedback type="invalid">{errors.email?.message}</Form.Control.Feedback>
                        </Form.Group>

                        <Form.Group className="mb-4">
                            <Form.Label>密碼</Form.Label>
                            <Form.Control
                                type="password"
                                placeholder="Password"
                                {...register("password", { required: "請輸入密碼", minLength: { value: 6, message: "密碼至少需 6 碼" } })}
                                isInvalid={!!errors.password}
                            />
                            <Form.Control.Feedback type="invalid">{errors.password?.message}</Form.Control.Feedback>
                        </Form.Group>

                        <Button variant="primary" type="submit" className="w-100 mb-3 py-2">
                            立即註冊
                        </Button>

                        <div className="text-center text-muted">
                            已經有帳號？ <Link to="/signin" className="text-decoration-none">登入</Link>
                        </div>
                    </Form>
                </Card.Body>
            </Card>
        </Container>
    );
}
