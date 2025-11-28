# Album / Shop Demo

This folder contains the Album front-end pages plus PHP APIs for cart and auth. The cart endpoints write directly into MySQL so you can run the site inside XAMPP (Apache + PHP + MySQL).

## XAMPP setup
1. Copy the `album/` folder into your XAMPP `htdocs` or configure a virtual host that points here.
2. Import the schema into MySQL:
   ```sh
   mysql -u root < api/schema.sql
   ```
   The script creates the `shop_db` database, `users`, `carts`, and `inventory_logs` tables, and seeds a demo user (`demo@example.com` / `password`). If you forget this step, the PHP endpoints will lazily create the same tables (and seed user) the first time they connect so you won't see "table doesn't exist" 500 errors.
3. Adjust database credentials in `api/db.php` if your XAMPP MySQL username/password differ from the defaults.
4. Visit `http://localhost/album/index.html` (or your vhost) and add items to the cart. Requests to `api/cart.php` persist the cart to MySQL using either the logged-in user ID or the current PHP session ID.

## Notes
- All cart operations go through `api/cart.php`, which expects a CSRF token returned from `api/auth.php`. The front-end (`js/shop.js`) fetches the token on page load.
- If the database connection fails, the UI falls back to localStorage so the page still works offline, but XAMPP needs a live database for real persistence.
- Inventory/audit events for cart add/update/remove/clear actions are logged to `inventory_logs`. You can download a CSV of the current cart or the log from buttons on `cart.html`, which call `api/report.php`.
