<?php
session_start();
// Security check: only authenticated admins can view this page
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

// ⚠️ SECURITY WARNING: Storing credentials directly in the code is HIGHLY discouraged.
// Use environment variables or a separate, secured config file.
try {
   $pdo = new PDO("mysql:host=localhost;dbname=mariyamf_mariyam_fashion", "mariyamf", "Es)0Abi774An;G");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    // Set default fetch mode to associative array
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

// --- Filtering Logic ---
$startDate = $_GET['start_date'] ?? '';
$endDate = $_GET['end_date'] ?? '';

$sql = "
    SELECT id, customer_name, customer_phone, customer_address, total_amount, status, shipping_status, created_at
    FROM orders
";

$whereClauses = [];
$params = [];

if (!empty($startDate)) {
    // Check orders created on or after the start date (at the beginning of the day)
    $whereClauses[] = "created_at >= :start_date";
    $params[':start_date'] = $startDate . ' 00:00:00';
}

if (!empty($endDate)) {
    // Check orders created on or before the end date (at the end of the day)
    $whereClauses[] = "created_at <= :end_date";
    $params[':end_date'] = $endDate . ' 23:59:59';
}

if (!empty($whereClauses)) {
    $sql .= " WHERE " . implode(' AND ', $whereClauses);
}

$sql .= " ORDER BY created_at DESC";

try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $orders = $stmt->fetchAll();
} catch (PDOException $e) {
    // Handle query error
    $orders = [];
    error_log("Order fetching failed: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Orders - Admin Panel</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<style>
body { margin:0; font-family:'Segoe UI',sans-serif; background:#f4f6f9; color:#333; }

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

.main { margin-left:220px; padding:30px; transition:all 0.3s ease; }
.main.expanded {margin-left:0; width:100%;}

.logout-btn { position:fixed; top:15px; right:20px; background:#FF4B5C; color:#fff; padding:8px 15px; font-weight:bold; border-radius:6px; text-decoration:none; transition:0.3s; z-index:1000;}
.logout-btn:hover { background:#e63946; }

/* Table and Filters */
h1 {color:#001F3F; margin-bottom:20px;}
.filter-form { display:flex; gap:15px; align-items:flex-end; margin-bottom:20px; background:#fff; padding:15px; border-radius:8px; box-shadow:0 2px 5px rgba(0,0,0,0.05); }
.filter-group label { display:block; font-weight:bold; margin-bottom:5px; color:#001F3F; font-size:14px; }
.filter-form input[type="date"] { padding:8px; border:1px solid #ccc; border-radius:4px; font-size:14px; }
.filter-form button { padding:8px 15px; background:#0074D9; color:#fff; border:none; border-radius:4px; cursor:pointer; font-weight:bold; transition:0.3s; }
.filter-form button:hover { background:#0056b3; }

.table-container { overflow-x:auto; max-height:75vh; overflow-y:auto; border-radius:10px; box-shadow:0 4px 10px rgba(0,0,0,0.1);}
table { width:100%; border-collapse:collapse; background:#fff; }
table th, table td { padding:10px; text-align:left; border-bottom:1px solid #ddd; font-size:14px; vertical-align:middle; }
table th { background:#0074D9; color:#fff; position:sticky; top:0; z-index:2; }
table tr:hover { background:#f9f9f9; transition:0.2s; }
.status-complete { color:#28a745; font-weight:bold; }
.status-pending { color:#ffc107; font-weight:bold; }

/* Buttons */
.action-btn { padding:6px 12px; border:none; border-radius:20px; cursor:pointer; color:#fff; font-size:13px; text-decoration:none; transition:0.3s; display:block; text-align:center; width:120px; margin:2px auto; }
.complete-btn { background:#28a745; }
.pending-btn { background:#ffc107; color:#001F3F; }
.invoice-btn { background:#007bff; width:100px; }
.delete-btn { background:#dc3545; width:100px; }
.complete-btn:hover { background:#218838; }
.pending-btn:hover { background:#e0a800; }
.invoice-btn:hover { background:#0056b3; }
.delete-btn:hover { background:#c82333; }
.empty-msg { text-align:center; padding:15px; font-size:16px; }

td div { display:flex; flex-direction:column; align-items:center; }

@media (max-width: 900px) {
    table, thead, tbody, th, td, tr { display:block; }
    thead { display:none; }
    tr { margin-bottom:20px; background:#fff; padding:15px; border-radius:10px; box-shadow:0 4px 10px rgba(0,0,0,0.05); }
    td { padding:8px 0; border:none; position:relative; padding-left:50%; }
    td:before { position:absolute; left:15px; width:45%; font-weight:bold; content:attr(data-label); }
    .action-btn { width:100%; margin:5px 0; }
    .filter-form { flex-direction:column; align-items:stretch; }
    .filter-form button { margin-top:10px; }
}
</style>
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

<!-- Toggle button -->
<span class="toggle-btn" id="toggleBtn"><i class="fas fa-bars"></i></span>
<a href="logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Logout</a>

<div class="main" id="main">
    <h1>All Orders</h1>

    <form method="GET" class="filter-form">
        <div class="filter-group">
            <label for="start_date">Start Date:</label>
            <input type="date" id="start_date" name="start_date" value="<?= htmlspecialchars($startDate) ?>">
        </div>
        <div class="filter-group">
            <label for="end_date">End Date:</label>
            <input type="date" id="end_date" name="end_date" value="<?= htmlspecialchars($endDate) ?>">
        </div>
        <button type="submit"><i class="fas fa-filter"></i> Filter</button>
        <?php if (!empty($startDate) || !empty($endDate)): ?>
            <button type="button" onclick="window.location.href='orders.php'" style="background:#6c757d;"><i class="fas fa-undo"></i> Clear Filter</button>
        <?php endif; ?>
    </form>
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
                    <th>Order Action</th>
                    <th>Shipping Action</th>
                    <th>Invoice</th>
                    <th>Delete</th>
                </tr>
            </thead>
            <tbody>
                <?php if(!empty($orders)): ?>
                    <?php foreach ($orders as $order): ?>
                        <tr id="order-row-<?= $order['id'] ?>">
                            <td data-label="Order ID"><?= $order['id'] ?></td>
                            <td data-label="Customer"><?= htmlspecialchars($order['customer_name']) ?></td>
                            <td data-label="Phone"><?= htmlspecialchars($order['customer_phone']) ?></td>
                            <td data-label="Address"><?= htmlspecialchars($order['customer_address']) ?></td>
                            <td data-label="Total Amount">$<?= number_format($order['total_amount'], 2) ?></td>
                            <td data-label="Order Status" id="order-status-<?= $order['id'] ?>" class="<?= $order['status'] ? 'status-complete' : 'status-pending' ?>">
                                <?= $order['status'] ? 'Completed' : 'Pending' ?>
                            </td>
                            <td data-label="Shipping Status" id="shipping-status-<?= $order['id'] ?>" class="<?= $order['shipping_status'] ? 'status-complete' : 'status-pending' ?>">
                                <?= $order['shipping_status'] ? 'Shipped' : 'Pending' ?>
                            </td>
                            <td data-label="Date"><?= date('M d, Y H:i', strtotime($order['created_at'])) ?></td>
                            <td data-label="Order Action">
                                <button class="action-btn <?= $order['status'] ? 'pending-btn' : 'complete-btn' ?>" onclick="updateOrderStatus(<?= $order['id'] ?>,'<?= $order['status'] ? 'pending' : 'complete' ?>')">
                                    <?= $order['status'] ? 'Mark Pending' : 'Mark Complete' ?>
                                </button>
                            </td>
                            <td data-label="Shipping Action">
                                <button class="action-btn <?= $order['shipping_status'] ? 'pending-btn' : 'complete-btn' ?>" onclick="updateShippingStatus(<?= $order['id'] ?>,'<?= $order['shipping_status'] ? 'pending' : 'shipped' ?>')">
                                    <?= $order['shipping_status'] ? 'Mark Pending' : 'Mark Shipped' ?>
                                </button>
                            </td>
                            <td data-label="Invoice">
                                <a href="invoice.php?order_id=<?= $order['id'] ?>" target="_blank" class="action-btn invoice-btn">
                                    <i class="fas fa-print"></i> Print
                                </a>
                            </td>
                            <td data-label="Delete">
                                <button class="action-btn delete-btn" onclick="deleteOrder(<?= $order['id'] ?>)">
                                    <i class="fas fa-trash"></i> Delete
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="12" class="empty-msg">No orders found for the selected criteria.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
const toggleBtn = document.getElementById("toggleBtn");
const sidebar = document.getElementById("sidebar");
const main = document.getElementById("main");
toggleBtn.addEventListener("click", () => {
    sidebar.classList.toggle("hidden");
    main.classList.toggle("expanded");
});
const ordersMenu = document.getElementById("ordersMenu");
const ordersSubmenu = document.getElementById("ordersSubmenu");

// Keep submenu open since we are on an orders page
ordersSubmenu.style.display = 'block'; 

ordersMenu.addEventListener("click", () => {
    // Toggles visibility on click
    ordersSubmenu.style.display = ordersSubmenu.style.display === "block" ? "none" : "block";
});

// AJAX for Status Updates
function updateOrderStatus(orderId, status) {
    fetch('ajax_update_order.php', {
        method:'POST',
        headers: {'Content-Type':'application/x-www-form-urlencoded'},
        body:`order_id=${orderId}&update_status=${status}`
    }).then(res=>res.json()).then(data=>{
        if(data.success){
            const cell = document.getElementById(`order-status-${orderId}`);
            // Assuming the order action button is the 9th child of the parent row
            const btn = cell.parentElement.querySelector('td:nth-child(9) button'); 
            
            cell.textContent = status==='complete'?'Completed':'Pending';
            cell.className = status==='complete'?'status-complete':'status-pending';
            btn.textContent = status==='complete'?'Mark Pending':'Mark Complete';
            btn.className = 'action-btn '+(status==='complete'?'pending-btn':'complete-btn');
            // Update onclick attribute for next toggle
            btn.setAttribute('onclick',`updateOrderStatus(${orderId},'${status==='complete'?'pending':'complete'}')`);
        } else {
            alert('Failed to update order status.');
        }
    }).catch(error => {
        console.error('Error updating order status:', error);
        alert('An error occurred during update.');
    });
}

function updateShippingStatus(orderId, status) {
    fetch('ajax_update_order.php', {
        method:'POST',
        headers: {'Content-Type':'application/x-www-form-urlencoded'},
        body:`order_id=${orderId}&update_shipping=${status}`
    }).then(res=>res.json()).then(data=>{
        if(data.success){
            const cell = document.getElementById(`shipping-status-${orderId}`);
            // Assuming the shipping action button is the 10th child of the parent row
            const btn = cell.parentElement.querySelector('td:nth-child(10) button'); 
            
            cell.textContent = status==='shipped'?'Shipped':'Pending';
            cell.className = status==='shipped'?'status-complete':'status-pending';
            btn.textContent = status==='shipped'?'Mark Pending':'Mark Shipped';
            btn.className = 'action-btn '+(status==='shipped'?'pending-btn':'complete-btn');
            // Update onclick attribute for next toggle
            btn.setAttribute('onclick',`updateShippingStatus(${orderId},'${status==='shipped'?'pending':'shipped'}')`);
        } else {
            alert('Failed to update shipping status.');
        }
    }).catch(error => {
        console.error('Error updating shipping status:', error);
        alert('An error occurred during update.');
    });
}

// AJAX for Deleting an Order
function deleteOrder(orderId) {
    if (!confirm('Are you sure you want to delete Order ID: ' + orderId + '? This action cannot be undone.')) {
        return;
    }

    fetch('ajax_delete_order.php', { // You'll need to create this file
        method:'POST',
        headers: {'Content-Type':'application/x-www-form-urlencoded'},
        body:`order_id=${orderId}`
    }).then(res=>res.json()).then(data=>{
        if(data.success){
            const row = document.getElementById(`order-row-${orderId}`);
            if (row) {
                row.remove(); // Remove the row from the table
                alert('Order ID ' + orderId + ' successfully deleted.');
            }
        } else {
            alert('Failed to delete order: ' + (data.message || 'Unknown error.'));
        }
    }).catch(error => {
        console.error('Error deleting order:', error);
        alert('An error occurred during deletion.');
    });
}
</script>
</body>
</html>