import React, { createContext, useContext, useState, useEffect } from 'react';
import axios from 'axios';
import { API_BASE_URL } from '../utils/apiConfig';
import { toast } from 'react-hot-toast';

const CartContext = createContext();

export function useCart() {
    return useContext(CartContext);
}

export function CartProvider({ children }) {
    const [cart, setCart] = useState({ items: [], total: 0, count: 0 });

    const fetchCart = async () => {
        try {
            const res = await axios.get(`${API_BASE_URL}/cart_api.php`);
            setCart(res.data);
        } catch (err) {
            console.error("Failed to fetch cart", err);
        }
    };

    useEffect(() => {
        fetchCart();
    }, []);

    const addToCart = async (id) => {
        try {
            await axios.post(`${API_BASE_URL}/cart_api.php`, { action: 'add', id });
            await fetchCart();
            toast.success('已加入購物車！');
        } catch (err) {
            console.error("Add failed", err);
            toast.error('加入失敗');
        }
    };

    const removeFromCart = async (id) => {
        try {
            await axios.post(`${API_BASE_URL}/cart_api.php`, { action: 'remove', id });
            await fetchCart();
        } catch (err) {
            console.error("Remove failed", err);
        }
    };

    const clearCart = async () => {
        try {
            await axios.post(`${API_BASE_URL}/cart_api.php`, { action: 'clear' });
            await fetchCart();
        } catch (err) {
            console.error("Clear failed", err);
        }
    };

    return (
        <CartContext.Provider value={{ cart, addToCart, removeFromCart, clearCart }}>
            {children}
        </CartContext.Provider>
    );
}
