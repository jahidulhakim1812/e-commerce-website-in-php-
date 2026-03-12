<?php
session_start();

// Check if admin is logged in
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

// Database connection
try {
   $pdo = new PDO("mysql:host=localhost;dbname=mariyamf_mariyam_fashion", "mariyamf", "Es)0Abi774An;G");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

// === DASHBOARD STATS ===
$totalOrders = $pdo->query("SELECT COUNT(*) FROM orders")->fetchColumn();
$totalIncome = $pdo->query("SELECT SUM(total_amount) FROM orders WHERE status=1")->fetchColumn() ?: 0;
$totalCompleted = $pdo->query("SELECT COUNT(*) FROM orders WHERE status=1")->fetchColumn();
$totalPending = $pdo->query("SELECT COUNT(*) FROM orders WHERE status=0")->fetchColumn();
$totalPendingIncome = $pdo->query("SELECT SUM(total_amount) FROM orders WHERE status=0")->fetchColumn() ?: 0;
$totalCustomers = $pdo->query("SELECT COUNT(DISTINCT id) FROM orders")->fetchColumn();
$totalProducts = $pdo->query("SELECT COUNT(*) FROM products")->fetchColumn();
$totalCompletedShipping = $pdo->query("SELECT COUNT(*) FROM orders WHERE shipping_status=1")->fetchColumn();
$totalPendingShipping = $pdo->query("SELECT COUNT(*) FROM orders WHERE shipping_status=0")->fetchColumn();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Admin Dashboard - Mariyam Fashion</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<style>
    body {
        margin: 0;
        font-family: 'Segoe UI', sans-serif;
        display: flex;
        background: #f4f6f9;
        color: #333;
    }

    /* Sidebar */
    /* Sidebar */
.sidebar {
    width: 220px;
    background: linear-gradient(180deg,#001F3F,#0056b3);
    color: #fff;
    height: 100vh;
    position: fixed;
    top: 0;
    left: 0;
    padding-top: 60px;
    box-shadow: 2px 0 10px rgba(0,0,0,0.2);
    transition: all 0.3s ease;
    overflow-y: auto; /* make sidebar scrollable */
    overflow-x: hidden; /* prevent horizontal scroll */
    z-index: 1000;
}

    .sidebar.hidden {left: -220px;}

    .sidebar h2 {
        text-align: center;
        color: #00BFFF;
        margin-bottom: 20px;
        position: absolute;
        top: 0;
        width: 100%;
        padding: 15px 0;
        border-bottom: 1px solid rgba(255,255,255,0.2);
        font-size: 18px;
        letter-spacing: 1px;
        font-weight: bold;
    }
    .sidebar ul {list-style: none; padding: 0; margin: 0;}
    .sidebar ul li {margin-bottom: 5px;}
    .sidebar ul li a {
        display: flex;
        align-items: center;
        color: #fff;
        padding: 12px 20px;
        text-decoration: none;
        font-size: 15px;
        border-radius: 8px 0 0 8px;
        transition: 0.3s;
        cursor: pointer;
        white-space: nowrap;
    }
    .sidebar ul li a i {
        margin-right: 12px;
        width: 25px;
        text-align: center;
        font-size: 16px;
    }
    .sidebar ul li a:hover {background: rgba(255,255,255,0.15);}
    .sidebar ul li a.active {background: #00BFFF; color: #001F3F; font-weight: bold;}

    /* Toggle button */
    .toggle-btn {
        position: fixed;
        top: 15px;
        left: 230px;
        background: #0056b3;
        color: #fff;
        border-radius: 50%;
        padding: 8px 10px;
        cursor: pointer;
        box-shadow: 0 4px 6px rgba(0,0,0,0.2);
        transition: all 0.3s ease;
        z-index: 1000;
    }
    .sidebar.hidden + .toggle-btn {left: 15px;}
    .toggle-btn:hover {background: #003d80;}

    /* Main content */
    .main {
        margin-left: 220px;
        padding: 30px;
        flex-grow: 1;
        width: calc(100% - 220px);
        transition: all 0.3s ease;
    }
    .main.expanded {
        margin-left: 0;
        width: 100%;
    }

    /* Logout button */
    .logout-btn {
        position: absolute;
        top: 15px;
        right: 20px;
        background: #FF4B5C;
        color: #fff;
        padding: 8px 15px;
        font-weight: bold;
        border-radius: 6px;
        text-decoration: none;
        transition: 0.3s;
    }
    .logout-btn:hover {background: #e63946;}

    /* Dashboard header */
    .dashboard-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 30px;
    }
    .dashboard-header h1 {color: #001F3F;}
    .dashboard-header p {color: #555;}

    /* Cards */
    .container {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
        gap: 20px;
    }
    .card {
        background: #fff;
        padding: 25px;
        border-radius: 12px;
        box-shadow: 0 6px 15px rgba(0,0,0,0.1);
        text-align: center;
        transition: 0.3s;
    }
    .card:hover {transform: translateY(-5px);}
    .card h2 {margin: 0; font-size: 32px; color: #0074D9;}
    .card p {margin: 10px 0 0; font-size: 16px; color: #555;}

    /* Card border colors */
    .card.total-orders       { border-left: 5px solid #00BFFF; }
    .card.total-income       { border-left: 5px solid #28a745; }
    .card.completed-orders   { border-left: 5px solid #0074D9; }
    .card.pending-orders     { border-left: 5px solid #ffc107; }
    .card.pending-income     { border-left: 5px solid #ff6f61; }
    .card.total-customers    { border-left: 5px solid #6f42c1; }
    .card.total-products     { border-left: 5px solid #20c997; }
    .card.completed-shipping { border-left: 5px solid #17a2b8; }
    .card.pending-shipping   { border-left: 5px solid #fd7e14; }
</style>
</head>
<body>

<!-- Sidebar -->
<div class="sidebar" id="sidebar">
    <h2>Admin Panel</h2>
    <ul>
        <li><a href="admin_dashboard.php" class="active"><i class="fas fa-chart-line"></i> Dashboard</a></li>
        <li>
            <a href="javascript:void(0);" id="ordersMenu"><i class="fas fa-shopping-cart"></i> Orders <i class="fas fa-caret-down" style="margin-left:auto;"></i></a>
            <ul id="ordersSubmenu" style="list-style:none; padding-left:20px; margin-top:5px; display:none;">
                <li><a href="orders.php"><i class="fas fa-shopping-cart"></i> Total Orders</a></li>
                <li><a href="complete_order.php"><i class="fas fa-check-circle"></i> Complete Orders</a></li>
                <li><a href="pending_order.php"><i class="fas fa-hourglass-half"></i> Pending Orders</a></li>
                <li><a href="pending_shipping.php"><i class="fas fa-truck"></i> Pending Shipping</a></li>
            </ul>
        </li>
        <li><a href="add_products.php"><i class="fas fa-plus-circle"></i> Add Product</a></li>
        <li><a href="delete_product.php"><i class="fas fa-trash"></i> Manage Product</a></li>
        <li><a href="upcoming_product.php"><i class="fas fa-clock"></i> Add Upcoming Product</a></li>
        <li><a href="delete_upcoming_product.php"><i class="fas fa-times-circle"></i> Manage Upcoming</a></li>
        <li><a href="add_category.php"><i class="fas fa-tags"></i> Categories</a></li>
        <li><a href="customer.php"><i class="fas fa-users"></i> Customers</a></li>
        <li><a href="slider.php"><i class="fas fa-image"></i> Home Sliders</a></li>
        <li><a href="admin_chat.php" class="active"><i class="fas fa-comment"></i> Messages</a></li>
    </ul>
</div>

<!-- Toggle button -->
<span class="toggle-btn" id="toggleBtn"><i class="fas fa-bars"></i></span>

<!-- Main content -->
<div class="main" id="main">
    <a href="logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Logout</a>

    <div class="dashboard-header">
        <h1>Welcome, Admin 👑</h1>
        <p><?= date('l, F j, Y') ?></p>
    </div>

    <div class="container">
        <div class="card total-orders"><h2><?= $totalOrders ?></h2><p>Total Orders</p></div>
        <div class="card total-income"><h2>$<?= number_format($totalIncome,2) ?></h2><p>Total Income</p></div>
        <div class="card completed-orders"><h2><?= $totalCompleted ?></h2><p>Completed Orders</p></div>
        <div class="card pending-orders"><h2><?= $totalPending ?></h2><p>Pending Orders</p></div>
        <div class="card pending-income"><h2>$<?= number_format($totalPendingIncome,2) ?></h2><p>Pending Income</p></div>
        <div class="card total-customers"><h2><?= $totalCustomers ?></h2><p>Total Customers</p></div>
        <div class="card total-products"><h2><?= $totalProducts ?></h2><p>Total Products</p></div>
        <div class="card completed-shipping"><h2><?= $totalCompletedShipping ?></h2><p>Completed Shipping</p></div>
        <div class="card pending-shipping"><h2><?= $totalPendingShipping ?></h2><p>Pending Shipping</p></div>
    </div>
</div>

<script>
    // Toggle sidebar
    const toggleBtn = document.getElementById("toggleBtn");
    const sidebar = document.getElementById("sidebar");
    const main = document.getElementById("main");
    toggleBtn.addEventListener("click", () => {
        sidebar.classList.toggle("hidden");
        main.classList.toggle("expanded");
    });

    // Toggle Orders submenu
    const ordersMenu = document.getElementById("ordersMenu");
    const ordersSubmenu = document.getElementById("ordersSubmenu");
    ordersMenu.addEventListener("click", () => {
        ordersSubmenu.style.display = ordersSubmenu.style.display === "block" ? "none" : "block";
    });
</script>
</body>
</html>
