# IMPLEMETATION PLAN: Simple ERP & RAG System

## 目標 (Goal)
在 `main_app` 中實作一個簡易的 ERP 系統，並整合 **Google Gemini API** 實作 RAG (檢索增強生成)。
*(v2.0 已完成 React 移植與基礎 RAG 功能)*

## v2.1 介面優化與功能升級計畫 (UI Polish & Enhanced Features)

本階段目標為提升使用者體驗 (UX) 並補足 ERP 核心管理功能。

### 1. 介面美化 (Beautification)
*   **Dark Mode (深色模式)**
    *   實作全站主題切換 (Light/Dark)。
    *   使用 `useTheme` Context 管理狀態，並搭配 React-Bootstrap 的 `data-bs-theme` 屬性。
*   **Dashboard 側邊欄導航 (Sidebar Layout)**
    *   將 Dashboard 從頂部導航改為專業的 "Admin Sidebar" 佈局。
    *   包含：總覽、商品管理、訂單管理、RAG 助理等選項。
*   **商品詳情 Modal**
    *   點擊商品卡片時彈出 Modal，顯示完整描述與大圖，提供更精緻的瀏覽體驗。

### 2. 新增功能 (New Features)
*   **智慧搜尋與過濾 (Smart Search & Filter)**
    *   在商品頁面新增即時搜尋列與分類過濾器 (Client-side filtering)。
*   **後台商品管理 (Admin Product CRUD)**
    *   新增 `/dashboard/products` 頁面。
    *   功能：
        *   **列表**: 表格檢視庫存狀況。
        *   **新增/編輯**: 表單介面 (含圖片 URL 輸入)。
        *   **刪除**: 移除商品。
*   **簡易結帳流程 (Checkout Flow)**
    *   從購物車頁面延伸，新增「送出訂單」按鈕。
    *   Mock 結帳功能：清空購物車並寫入 `orders` 資料表 (或模擬寫入)。

## User Review Required
> [!NOTE]
> 這些變動將大幅改變 Dashboard 的佈局結構。

---
*(以下為舊版 v1.0 計畫內容，保留供參考)*

## 1. 簡易 ERP 架構 (Simple ERP Architecture)
...
