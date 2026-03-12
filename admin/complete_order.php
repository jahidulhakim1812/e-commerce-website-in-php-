<?php
session_start();
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

try {
$pdo = new PDO("mysql:host=localhost;dbname=mariyamf_mariyam_fashion", "mariyamf", "Es)0Abi774An;G");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

// Fetch only completed orders
$orders = $pdo->query("
    SELECT id, customer_name, customer_phone, customer_address, total_amount, status, shipping_status, created_at
    FROM orders
    WHERE status = 1
    ORDER BY created_at DESC
")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Completed Orders - Admin Panel</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<style>
body { margin:0; font-family:'Segoe UI',sans-serif; background:#f4f6f9; color:#333; display:flex; }

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

.sidebar.hidden { left:-220px; }
.sidebar h2 { text-align:center; color:#00BFFF; margin-bottom:20px; position:absolute; top:0; width:100%; 
              padding:15px 0; border-bottom:1px solid rgba(255,255,255,0.2); font-size:18px; font-weight:bold; }
.sidebar ul { list-style:none; padding:0; margin:0; }
.sidebar ul li { margin-bottom:5px; }
.sidebar ul li a { display:flex; align-items:center; color:#fff; padding:12px 20px; text-decoration:none; 
                   font-size:15px; border-radius:8px 0 0 8px; transition:0.3s; cursor:pointer; white-space:nowrap; }
.sidebar ul li a i { margin-right:12px; width:25px; text-align:center; font-size:16px; }
.sidebar ul li a:hover { background:rgba(255,255,255,0.15); }
.sidebar ul li a.active { background:#00BFFF; color:#001F3F; font-weight:bold; }

/* Toggle button */
.toggle-btn { position:fixed; top:15px; left:230px; background:#0056b3; color:#fff; border-radius:50%; padding:8px 10px; cursor:pointer; 
             box-shadow:0 4px 6px rgba(0,0,0,0.2); transition:all 0.3s ease; z-index:1000; }
.sidebar.hidden + .toggle-btn {left:15px;}
.toggle-btn:hover { background:#003d80; }

/* Main content */
.main { margin-left:220px; padding:30px; flex-grow:1; width:calc(100% - 220px); transition:all 0.3s ease; }
.main.expanded {margin-left:0; width:100%;}

/* Logout button */
.logout-btn { position:fixed; top:15px; right:20px; background:#FF4B5C; color:#fff; padding:8px 15px; 
             font-weight:bold; border-radius:6px; text-decoration:none; transition:0.3s; z-index:1000;}
.logout-btn:hover { background:#e63946; }

/* Table styling */
h1 {color:#001F3F; margin-bottom:20px;}
.table-container { overflow-x:auto; }
table { width:100%; border-collapse:collapse; background:#fff; border-radius:10px; overflow:hidden; 
        box-shadow:0 4px 15px rgba(0,0,0,0.1); }
table th, table td { padding:12px 15px; text-align:left; border-bottom:1px solid #eee; font-size:14px; }
table th { background:#0074D9; color:#fff; font-weight:500; }
table tr:hover { background:#f1f1f1; transition:0.2s; }
.status-complete { color:#28a745; font-weight:bold; }
.status-pending { color:#ffc107; font-weight:bold; }
.empty-msg { text-align:center; padding:15px; font-size:16px; color:#555; }

/* Responsive */
@media (max-width: 900px) {
    table, thead, tbody, th, td, tr { display:block; }
    thead { display:none; }
    tr { margin-bottom:20px; background:#fff; padding:15px; border-radius:10px; box-shadow:0 4px 10px rgba(0,0,0,0.05); }
    td { padding:8px 0; border:none; position:relative; padding-left:50%; white-space:normal; }
    td:before { position:absolute; left:15px; width:45%; font-weight:bold; content:attr(data-label); }
}
</style>
<script>
document.addEventListener("DOMContentLoaded", () => {
    // Toggle sidebar
    const toggleBtn = document.getElementById("toggleBtn");
    const sidebar = document.getElementById("sidebar");
    const main = document.getElementById("main");
    toggleBtn.addEventListener("click", () => {
        sidebar.classList.toggle("hidden");
        main.classList.toggle("expanded");
    });

    // Orders submenu
    const ordersMenu = document.getElementById("ordersMenu");
    const ordersSubmenu = document.getElementById("ordersSubmenu");
    ordersMenu.addEventListener("click", () => {
        ordersSubmenu.style.display = ordersSubmenu.style.display === "block" ? "none" : "block";
    });
});
</script>
</head>
<body>

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

<span class="toggle-btn" id="toggleBtn"><i class="fas fa-bars"></i></span>
<a href="logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Logout</a>

<div class="main" id="main">
    <h1>Completed Orders</h1>
    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th>Order ID</th>
                    <th>Customer</th>
                    <th>Phone</th>
                    <th>Address</th>
                    <th>Total Amount</th>
                    <th>Order Status</th>
                    <th>Shipping Status</th>
                    <th>Date</th>
                </tr>
            </thead>
            <tbody>
                <?php if(!empty($orders)): ?>
                    <?php foreach ($orders as $order): ?>
                        <tr>
                            <td data-label="Order ID"><?= htmlspecialchars($order['id']) ?></td>
                            <td data-label="Customer"><?= htmlspecialchars($order['customer_name']) ?></td>
                            <td data-label="Phone"><?= htmlspecialchars($order['customer_phone']) ?></td>
                            <td data-label="Address"><?= htmlspecialchars($order['customer_address']) ?></td>
                            <td data-label="Total Amount">$<?= number_format($order['total_amount'], 2) ?></td>
                            <td data-label="Order Status" class="status-complete">Completed</td>
                            <td data-label="Shipping Status" class="<?= $order['shipping_status'] ? 'status-complete' : 'status-pending' ?>">
                                <?= $order['shipping_status'] ? 'Shipped' : 'Pending' ?>
                            </td>
                            <td data-label="Date"><?= date('M d, Y H:i', strtotime($order['created_at'])) ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="8" class="empty-msg">No completed orders found.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

</body>
</html>
