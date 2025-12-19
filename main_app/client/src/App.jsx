import React from 'react';
import { HashRouter as Router, Routes, Route } from 'react-router-dom';
import { Toaster } from 'react-hot-toast';
import { AuthProvider } from './context/AuthContext';
import { CartProvider } from './context/CartContext';
import { ThemeProvider } from './context/ThemeContext';
import Layout from './components/Layout';
import Home from './pages/Home';
import Products from './pages/Products';
import Cart from './pages/Cart';
import Checkout from './pages/Checkout';
import SignIn from './pages/SignIn';
import SignUp from './pages/SignUp';
import AdminRoute from './components/AdminRoute';

import DashboardLayout from './components/DashboardLayout';
import Overview from './pages/dashboard/Overview';
import AdminProducts from './pages/dashboard/AdminProducts';
import AdminOrders from './pages/dashboard/AdminOrders';
import OrderKanban from './pages/dashboard/OrderKanban';
import AdminChat from './pages/dashboard/AdminChat';
import Accounting from './pages/dashboard/Accounting';
import NotificationManager from './components/NotificationManager';

import { QueryClient, QueryClientProvider } from '@tanstack/react-query';

const queryClient = new QueryClient();

function App() {
  return (
    <ThemeProvider>
      <AuthProvider>
        <CartProvider>
          <QueryClientProvider client={queryClient}>
            <Router>
              <NotificationManager />
              <Toaster position="top-right" reverseOrder={false} />
              <Routes>
                <Route path="/" element={<Layout />}>
                  <Route index element={<Home />} />
                  <Route path="products" element={<Products />} />
                  <Route path="cart" element={<Cart />} />
                  <Route path="checkout" element={<Checkout />} />
                  <Route path="signin" element={<SignIn />} />
                  <Route path="signup" element={<SignUp />} />
                  <Route path="*" element={<Home />} />
                </Route>

                <Route element={<AdminRoute />}>
                  <Route path="/dashboard" element={<DashboardLayout />}>
                    <Route index element={<Overview />} />
                    <Route path="products" element={<AdminProducts />} />
                    <Route path="orders" element={<AdminOrders />} />
                    <Route path="kanban" element={<OrderKanban />} />
                    <Route path="chat" element={<AdminChat />} />
                    <Route path="accounting" element={<Accounting />} />
                  </Route>
                </Route>
              </Routes>
            </Router>
          </QueryClientProvider>
        </CartProvider>
      </AuthProvider>
    </ThemeProvider>
  );
}

export default App;
