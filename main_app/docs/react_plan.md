# React Migration Implementation Plan

## 1. Backend API Layer (PHP -> JSON)
We need to decouple the View from Logic.

### A. Products API (`api/products_api.php`)
- **Method**: GET
- **Response**: `[{id, name, price, description, image_url, stock_quantity}]`

### B. Cart API (`api/cart_api.php`)
- **Method**: GET (Fetch Cart), POST (Add/Remove)
- **POST Body**: `{action: 'add'|'remove', id: int, qty: int}`
- **Response**: `{items: [], total: float, count: int}` (Syncs session state)

### C. Auth API (`api/auth_api.php`)
- **Method**: GET (Check Status), POST (Login)
- **POST Body**: `{email, password}`
- **Response**: `{success: bool, user: {name, ...}, error: string}`

### D. RAG Chat API (`api/rag_chat.php`)
- Existing API returns JSON. Compatible.

## 2. Frontend Application (React + Vite)
Located in `main_app/client`.

### Structure
- `src/App.jsx`: Main Router (React Router v6).
- `src/components/Layout.jsx`: Navbar + Footer.
- `src/context/AuthContext.jsx`: Global User State.
- `src/context/CartContext.jsx`: Global Cart State.
- `src/pages/`:
  - `Home.jsx`
  - `Products.jsx`
  - `Cart.jsx`
  - `SignIn.jsx`
  - `Dashboard.jsx` (Includes Chat Widget logic)

## 3. Deployment
- **Dev**: Run `npm run dev` in `client/` folder. Proxy `/api` to `http://localhost/109022005bootstrap/main_app/api`.
- **Prod**: Run `npm run build`. Copy `dist/*` to `main_app/`.
