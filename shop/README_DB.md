# 在 XAMPP 上設定資料庫以綁定購物車

步驟（簡短）：

1. 開啟 phpMyAdmin（通常是 http://localhost/phpmyadmin）或使用 mysql CLI
2. 執行或匯入檔案：`shop/sql/create_shop_db.sql` （這會建立 `shop_db`、`users` 與 `carts` 表格）；若你已先前建立過 `carts` 表，請執行 `shop/sql/migration_add_users_and_userid.sql` 來為現有資料庫新增 `user_id` 與 `users` 表。
3. 檢查 / 修改資料庫連線設定：
   - 檔案：`shop/api/db.php` （以及 `album/api/db.php` 若你也使用 album）
   - 變更常數：DB_HOST, DB_NAME, DB_USER, DB_PASS
   - XAMPP 預設通常是 host=127.0.0.1, user=root, password 空字串
4. 開啟網頁 (例如 http://localhost/109022005bootstrap/shop/products.html)
   - 加入商品到購物車會同時嘗試儲存在 localStorage 與資料庫（以 PHP session 綁定）

開發與偵錯：
- 若在前端呼叫 `api/cart.php` 出錯，請檢查瀏覽器的 Network 面板與 PHP 錯誤日誌（xampp/apache/logs）
- 你可以在 phpMyAdmin 檢視 `shop_db.carts` 表的內容，確認 session_id、user_id、product_id、quantity 被儲存

登入與購物車整合：
- 現在新增了使用者系統（`users` 表）與 `api/auth.php`。
- 登入或註冊會把 session（未登入）狀態下的購物車合併到 `user_id` 下，之後的變更會儲存在 `carts.user_id`（session 相關資料會被移除）

CSRF 注意：
- 所有變更購物車或登入/註冊/登出的 API（POST）現在需要傳送 X-CSRF-Token header（可從 `api/auth.php` 的 GET 回傳 csrf 欄位取得）。

注意：目前購物車是以 PHP session 為綁定鍵（匿名），若要做到跨裝置或跨瀏覽器的永久綁定，請在未來整合使用者帳號系統並把 cart 連結到 user_id。
