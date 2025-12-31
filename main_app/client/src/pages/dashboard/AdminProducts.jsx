import React, { useState, useEffect } from 'react';
import { Card, Table, Button, Modal, Form, Badge } from 'react-bootstrap';
import { FaPlus, FaEdit, FaTrash } from 'react-icons/fa';
import axios from 'axios';
import { API_V1_URL } from '../../utils/apiConfig';
import { toast } from 'react-hot-toast';
import Swal from 'sweetalert2';
import withReactContent from 'sweetalert2-react-content';
import { useForm } from 'react-hook-form';
import { useTheme } from '../../context/ThemeContext';

const MySwal = withReactContent(Swal);

export default function AdminProducts() {
    const { theme } = useTheme();
    const [products, setProducts] = useState([]);
    const [show, setShow] = useState(false);
    const [editing, setEditing] = useState(null);

    // React Hook Form
    const { register, handleSubmit, reset, setValue, formState: { errors } } = useForm();

    useEffect(() => {
        fetchProducts();
    }, []);

    const fetchProducts = async () => {
        try {
            const res = await axios.get(`${API_V1_URL}/products`);
            const data = Array.isArray(res.data) ? res.data : [];
            setProducts(data);
        } catch (error) {
            toast.error('無法載入商品資料');
        }
    };

    const handleClose = () => {
        setShow(false);
        setEditing(null);
        reset(); // Clear form
    };

    const handleShow = (product = null) => {
        if (product) {
            setEditing(product);
            // Set form values dynamically
            setValue('name', product.name);
            setValue('sku', product.sku);
            setValue('category', product.category);
            setValue('price', product.price);
            setValue('stock_quantity', product.stock_quantity);
            setValue('image_url', product.image_url);
            setValue('description', product.description);
        } else {
            reset();
        }
        setShow(true);
    };

    const onFormSubmit = async (data) => {
        try {
            if (editing) {
                await axios.put(`${API_V1_URL}/products/${editing.id}`, data);
                toast.success('商品已更新');
            } else {
                await axios.post(`${API_V1_URL}/products`, data);
                toast.success('商品已新增');
            }
            fetchProducts();
            handleClose();
        } catch (error) {
            toast.error(error.response?.data?.error || '操作失敗');
        }
    };

    const handleDelete = async (id) => {
        const result = await MySwal.fire({
            title: '確定要刪除嗎？',
            text: "此動作無法復原！",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: '是的，刪除它！',
            cancelButtonText: '取消'
        });

        if (result.isConfirmed) {
            try {
                await axios.delete(`${API_V1_URL}/products/${id}`);

                MySwal.fire(
                    '已刪除！',
                    '商品資料已移除。',
                    'success'
                );
                fetchProducts();
            } catch (error) {
                toast.error(error.response?.data?.error || '刪除失敗');
            }
        }
    };

    return (
        <div className="container-fluid">
            <div className="d-flex justify-content-between align-items-center mb-4">
                <h2 className={theme === 'dark' ? 'text-white' : 'text-dark'}>商品管理</h2>
                <div>
                    <Button variant="success" className="me-2" onClick={() => window.open(`${API_V1_URL.replace('/v1', '')}/export_api.php?type=products`, '_blank')}>
                        匯出 CSV
                    </Button>
                    <Button variant="primary" onClick={() => handleShow()}>
                        <FaPlus className="me-2" />新增商品
                    </Button>
                </div>
            </div>

            <Card className="border-0 shadow-sm">
                <Card.Body className="p-0">
                    <Table responsive hover className="mb-0 aligned-middle">
                        <thead className="bg-light">
                            <tr>
                                <th className="ps-4">商品名稱</th>
                                <th style={{ width: '150px' }}>SKU</th>
                                <th style={{ width: '150px' }}>分類</th>
                                <th style={{ width: '120px' }}>價格</th>
                                <th style={{ width: '100px' }}>庫存</th>
                                <th className="text-end pe-4" style={{ width: '120px' }}>操作</th>
                            </tr>
                        </thead>
                        <tbody>
                            {products.map(product => (
                                <tr key={product.id}>
                                    <td className="ps-4">
                                        <div className="d-flex align-items-center">
                                            {product.image_url && (
                                                <img src={product.image_url} alt={product.name} className="rounded me-3" style={{ width: '40px', height: '40px', objectFit: 'cover' }} />
                                            )}
                                            <span className="fw-bold">{product.name}</span>
                                        </div>
                                    </td>
                                    <td>{product.sku || '-'}</td>
                                    <td><Badge bg="secondary">{product.category}</Badge></td>
                                    <td>NT$ {product.price}</td>
                                    <td>
                                        <Badge bg={product.stock_quantity < 10 ? 'danger' : 'success'}>
                                            {product.stock_quantity}
                                        </Badge>
                                    </td>
                                    <td className="text-end pe-4">
                                        <Button variant="outline-primary" size="sm" className="me-2" onClick={() => handleShow(product)}>
                                            <FaEdit />
                                        </Button>
                                        <Button variant="outline-danger" size="sm" onClick={() => handleDelete(product.id)}>
                                            <FaTrash />
                                        </Button>
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </Table>
                </Card.Body>
            </Card>

            <Modal show={show} onHide={handleClose} centered size="lg">
                <Modal.Header closeButton>
                    <Modal.Title>{editing ? '編輯商品' : '新增商品'}</Modal.Title>
                </Modal.Header>
                <Modal.Body>
                    <Form onSubmit={handleSubmit(onFormSubmit)}>
                        <Form.Group className="mb-3">
                            <Form.Label>商品名稱</Form.Label>
                            <Form.Control
                                type="text"
                                {...register("name", { required: "商品名稱為必填" })}
                                isInvalid={!!errors.name}
                            />
                            <Form.Control.Feedback type="invalid">{errors.name?.message}</Form.Control.Feedback>
                        </Form.Group>

                        <div className="row">
                            <div className="col-md-6">
                                <Form.Group className="mb-3">
                                    <Form.Label>分類 (Category)</Form.Label>
                                    <Form.Select {...register("category")}>
                                        <option value="Electronics">Electronics</option>
                                        <option value="Home">Home</option>
                                        <option value="Clothing">Clothing</option>
                                        <option value="Other">Other</option>
                                    </Form.Select>
                                </Form.Group>
                            </div>
                            <div className="col-md-6">
                                <Form.Group className="mb-3">
                                    <Form.Label>SKU (庫存單位)</Form.Label>
                                    <Form.Control type="text" {...register("sku")} />
                                </Form.Group>
                            </div>
                        </div>

                        <div className="row">
                            <div className="col-md-6">
                                <Form.Group className="mb-3">
                                    <Form.Label>價格</Form.Label>
                                    <Form.Control
                                        type="number"
                                        {...register("price", { required: "價格為必填", min: 0 })}
                                        isInvalid={!!errors.price}
                                    />
                                    <Form.Control.Feedback type="invalid">{errors.price?.message}</Form.Control.Feedback>
                                </Form.Group>
                            </div>
                            <div className="col-md-6">
                                <Form.Group className="mb-3">
                                    <Form.Label>庫存數量</Form.Label>
                                    <Form.Control
                                        type="number"
                                        {...register("stock_quantity", { required: "庫存為必填", min: 0 })}
                                        isInvalid={!!errors.stock_quantity}
                                    />
                                    <Form.Control.Feedback type="invalid">{errors.stock_quantity?.message}</Form.Control.Feedback>
                                </Form.Group>
                            </div>
                        </div>

                        <Form.Group className="mb-3">
                            <Form.Label>圖片網址 (URL)</Form.Label>
                            <Form.Control type="text" {...register("image_url")} placeholder="https://..." />
                        </Form.Group>

                        <Form.Group className="mb-3">
                            <Form.Label>描述</Form.Label>
                            <Form.Control as="textarea" rows={3} {...register("description")} />
                        </Form.Group>

                        <div className="d-flex justify-content-end">
                            <Button variant="secondary" className="me-2" onClick={handleClose}>取消</Button>
                            <Button variant="primary" type="submit">{editing ? '更新' : '新增'}</Button>
                        </div>
                    </Form>
                </Modal.Body>
            </Modal>
        </div>
    );
}
