<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<!doctype html>
<html lang="zh-TW">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>109022005 ERP 系統</title>
    <!-- Bootstrap 5 CSS -->
    <link href="assets/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600&display=swap" rel="stylesheet">
    <!-- Custom CSS -->
    <style>
        body { font-family: 'Inter', sans-serif; background-color: #f8f9fa; color: #333; }
        .navbar-glass { background: rgba(255, 255, 255, 0.95); backdrop-filter: blur(10px); box-shadow: 0 4px 6px rgba(0,0,0,0.05); }
        .card { border: none; border-radius: 12px; box-shadow: 0 2px 12px rgba(0,0,0,0.05); transition: all 0.2s; overflow: hidden; }
        .card:hover { transform: translateY(-5px); box-shadow: 0 8px 20px rgba(0,0,0,0.1); }
        .btn { border-radius: 8px; padding: 8px 20px; font-weight: 500; transition: all 0.2s; }
        .btn-primary { background: #0d6efd; border: none; }
        .btn-primary:hover { background: #0b5ed7; transform: translateY(-1px); box-shadow: 0 4px 10px rgba(13, 110, 253, 0.3); }
        .hero-section { background: linear-gradient(135deg, #0d6efd 0%, #0dcaf0 100%); color: white; padding: 80px 0; margin-bottom: 40px; border-radius: 0 0 30px 30px; }
    </style>
</head>
<body>

<!-- Navbar -->
<nav class="navbar navbar-expand-lg navbar-glass fixed-top navbar-light">
  <div class="container">
    <a class="navbar-brand fw-bold text-primary" href="index.php">
        <i class="fa-solid fa-cube me-2"></i>ERP System
    </a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarMain">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="navbarMain">
      <ul class="navbar-nav ms-auto mb-2 mb-lg-0 align-items-center">
        <li class="nav-item"><a class="nav-link" href="index.php">首頁</a></li>
        <li class="nav-item"><a class="nav-link" href="products.php">商品目錄</a></li>
        <li class="nav-item"><a class="nav-link" href="erp_dashboard.html">ERP後台</a></li>
        <li class="nav-item ms-2">
            <a class="nav-link position-relative btn btn-light text-dark" href="cart.php">
                <i class="fa-solid fa-cart-shopping"></i>
                <?php 
                $cartCount = isset($_SESSION['cart']) ? array_sum($_SESSION['cart']) : 0;
                if($cartCount > 0): ?>
                <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                    <?php echo $cartCount; ?>
                </span>
                <?php endif; ?>
            </a>
        </li>
        <?php if(isset($_SESSION['user_id'])): ?>
            <li class="nav-item ms-3 dropdown">
                <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown">
                    <i class="fa-solid fa-user-circle fa-lg"></i>
                </a>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li><a class="dropdown-item" href="#">個人檔案</a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item text-danger" href="logout.php">登出</a></li>
                </ul>
            </li>
        <?php else: ?>
            <li class="nav-item ms-3">
                <a class="btn btn-primary btn-sm text-white" href="signin.php">登入</a>
            </li>
        <?php endif; ?>
      </ul>
    </div>
  </div>
</nav>
<div style="height: 70px;"></div>
