# 專案分析報告: 109022005bootstrap

## 🚀 主要開發環境 (Active Development)
**資料夾位置**: [`main_app/`](main_app/)

這是目前的**核心開發目錄**，整合了網站的主要功能與常用的 Bootstrap 範本。所有的開發工作應盡量在此資料夾中進行。

### `main_app` 結構說明:
*   **根目錄檔案**:
    *   `index.html`: 首頁 (源自 Album)。
    *   `signin.html`: 登入頁面。
    *   `products.html`: 產品列表。
    *   `cart.html`: 購物車。
    *   `database_schema.sql`: 資料庫結構檔 (請匯入此檔)。
*   **核心資源**:
    *   `api/`: 後端 PHP/SQL 支援。
    *   `assets/`: 共用 Bootstrap 資源 (CSS/JS)。
    *   `css/`, `js/`: 專案專屬樣式與腳本。
*   **📂 `examples/` (範本集)**:
    *   包含所有已整合的 Bootstrap 範本，如：
        *   `dashboard/`, `starter-template/`
        *   `blog/`, `carousel/`, `pricing/`, `features/`
        *   `headers/`, `footers/`, `navbars/`, `sidebars/`
        *   `sign-in/`, `checkout/`, `grid/`, `modals/`

---

## 專案結構概觀

此專案設定在 XAMPP 環境 (`d:\xampp\htdocs\109022005bootstrap`)，包含多個獨立的 Bootstrap 範例。

### 1. 獨立全端模組
以下模組保留在根目錄，可作為獨立參考：

*   **[`album/`](album/)**: 相簿展示功能 (已簡化，移除了與商店相關的 `cart` 等檔案)。
*   **[`shop/`](shop/)**: 完整的電商範例 (包含獨立的 SQL 與完整頁面)。

### 2. 其他 Bootstrap 範例
根目錄下仍保留部分未整合的 Bootstrap 元件範例，例如：
*   `assets/`, `badges/`, `buttons/`
*   `dropdowns/`, `heroes/`, `jumbotron/`
*   `list-groups/`, `masonry/`, `offcanvas/`
*   `navbar-fixed/`, `navbar-static/`, `sticky-footer/`

---

## 開發建議
1.  **資料庫**: 請使用 `main_app/database_schema.sql` 於 phpMyAdmin 建立資料庫。
2.  **伺服器**: 啟動 Apache 與 MySQL 後，存取 `http://localhost/109022005bootstrap/main_app/` 查看整合後的成果。

