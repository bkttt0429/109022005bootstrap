import React, { lazy, Suspense } from 'react';
import { HashRouter as Router, Routes, Route } from 'react-router-dom';
import { Toaster } from 'react-hot-toast';
import { AuthProvider } from './context/AuthContext';
import { CartProvider } from './context/CartContext';
import { ThemeProvider } from './context/ThemeContext';
import Layout from './components/Layout';
import AdminRoute from './components/AdminRoute';
import NotificationManager from './components/NotificationManager';
import { QueryClient, QueryClientProvider } from '@tanstack/react-query';

// Lazy load pages
const Home = lazy(() => import('./pages/Home'));
const Products = lazy(() => import('./pages/Products'));
const Cart = lazy(() => import('./pages/Cart'));
const Checkout = lazy(() => import('./pages/Checkout'));
const SignIn = lazy(() => import('./pages/SignIn'));
const SignUp = lazy(() => import('./pages/SignUp'));

const DashboardLayout = lazy(() => import('./components/DashboardLayout'));
const Overview = lazy(() => import('./pages/dashboard/Overview'));
const AdminProducts = lazy(() => import('./pages/dashboard/AdminProducts'));
const AdminOrders = lazy(() => import('./pages/dashboard/AdminOrders'));
const OrderKanban = lazy(() => import('./pages/dashboard/OrderKanban'));
const AdminChat = lazy(() => import('./pages/dashboard/AdminChat'));
const Accounting = lazy(() => import('./pages/dashboard/Accounting'));

const queryClient = new QueryClient();

// Loading component
const PageLoader = () => (
  <div className="d-flex justify-content-center align-items-center" style={{ height: '80vh' }}>
    <div className="spinner-border text-primary" role="status">
      <span className="visually-hidden">Loading...</span>
    </div>
  </div>
);

function App() {
  return (
    <ThemeProvider>
      <AuthProvider>
        <CartProvider>
          <QueryClientProvider client={queryClient}>
            <Router>
              <NotificationManager />
              <Toaster position="top-right" reverseOrder={false} />
              <Suspense fallback={<PageLoader />}>
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
              </Suspense>
            </Router>
          </QueryClientProvider>
        </CartProvider>
      </AuthProvider>
    </ThemeProvider>
  );
}

export default App;
