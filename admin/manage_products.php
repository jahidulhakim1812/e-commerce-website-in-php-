<?php
session_start();
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Manage Products - Admin Panel</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        body {margin:0; font-family:'Segoe UI',sans-serif; display:flex; background:#f0f4ff;}
        .sidebar {width:220px; background:linear-gradient(180deg,#001F3F,#0056b3); color:#fff; height:100vh; position:fixed; top:0; left:0; padding-top:60px; box-shadow:2px 0 10px rgba(0,0,0,0.2);}
        .sidebar h2 {text-align:center;color:#00BFFF;margin-bottom:20px;position:absolute;top:0;width:100%;padding:15px 0;border-bottom:1px solid rgba(255,255,255,0.2);font-size:18px;font-weight:bold;}
        .sidebar ul {list-style:none;padding:0;margin:0;}
        .sidebar ul li a {display:flex; align-items:center; color:#fff; padding:12px 20px; text-decoration:none; font-size:15px; border-radius:8px 0 0 8px; transition:0.3s;}
        .sidebar ul li a:hover {background:#0074D9;}
        .sidebar ul li a.active {background:#00BFFF;color:#001F3F;font-weight:bold;}
        .main {margin-left:220px; padding:30px; flex-grow:1;}
        h1 {color:#001F3F;margin-bottom:20px;}
        .submenu {margin-bottom:20px;}
        .submenu a {margin-right:15px; text-decoration:none; padding:8px 14px; border-radius:6px; background:#0074D9; color:#fff; font-weight:bold;}
        .submenu a:hover {background:#0056b3;}
    </style>
</head>
<body>
    <div class="sidebar">
        <h2>Admin Panel</h2>
        <ul>
            <li><a href="admin_dashboard.php"><i class="fas fa-chart-line"></i> Dashboard</a></li>
            <li><a href="manage_products.php" class="active"><i class="fas fa-box"></i> Manage Products</a></li>
            <li><a href="orders.php"><i class="fas fa-shopping-cart"></i> Orders</a></li>
            <li><a href="categories.php"><i class="fas fa-tags"></i> Categories</a></li>
        </ul>
    </div>

    <div class="main">
        <h1>Manage Products</h1>
        <div class="submenu">
            <a href="add_product.php">➕ Add Product</a>
            <a href="active_products.php">✅ Active Products</a>
            <a href="upcoming_products.php">⏳ Upcoming Products</a>
        </div>
        <p>Choose a section above to manage products.</p>
    </div>
</body>
</html>
