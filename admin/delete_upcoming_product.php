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

// Handle deletion
if (isset($_GET['delete'])) {
    $productId = intval($_GET['delete']);
    $stmt = $pdo->prepare("SELECT * FROM upcoming_products WHERE id = ?");
    $stmt->execute([$productId]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($product) {
        if (!empty($product['photo']) && file_exists($product['photo'])) {
            unlink($product['photo']); // delete photo file
        }
        $deleteStmt = $pdo->prepare("DELETE FROM upcoming_products WHERE id = ?");
        if ($deleteStmt->execute([$productId])) {
            $_SESSION['success_message'] = "✅ Upcoming product deleted successfully!";
        } else {
            $_SESSION['error_message'] = "❌ Error: Could not delete product from database.";
        }
    } else {
        $_SESSION['error_message'] = "❌ Error: Product not found.";
    }
    header("Location: delete_upcoming_product.php");
    exit;
}

// Fetch all upcoming products
$upcomingProducts = $pdo->query("SELECT * FROM upcoming_products ORDER BY expected_date ASC")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Manage Upcoming Products - Admin Panel</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<style>
body { margin:0; font-family:'Segoe UI',sans-serif; display:flex; background:#f4f6f9; color:#333; }

/* Sidebar */
.sidebar { width:220px; background: linear-gradient(180deg,#001F3F,#0056b3); color:#fff; height:100vh; position:fixed; top:0; left:0; padding-top:60px; box-shadow:2px 0 10px rgba(0,0,0,0.2); transition: all 0.3s ease; overflow-y:auto; }
.sidebar.hidden {left:-220px;}
.sidebar h2 { text-align:center; color:#00BFFF; margin-bottom:20px; position:absolute; top:0; width:100%; padding:15px 0; border-bottom:1px solid rgba(255,255,255,0.2); font-size:18px; font-weight:bold; }
.sidebar ul { list-style:none; padding:0; margin:0; }
.sidebar ul li { margin-bottom:5px; }
.sidebar ul li a { display:flex; align-items:center; color:#fff; padding:12px 20px; text-decoration:none; font-size:15px; border-radius:8px 0 0 8px; transition:0.3s; cursor:pointer; white-space:nowrap; }
.sidebar ul li a i { margin-right:12px; width:25px; text-align:center; font-size:16px; }
.sidebar ul li a:hover { background:rgba(255,255,255,0.15); }
.sidebar ul li a.active { background:#00BFFF; color:#001F3F; font-weight:bold; }

/* Toggle button */
.toggle-btn { position:fixed; top:15px; left:230px; background:#0056b3; color:#fff; border-radius:50%; padding:8px 10px; cursor:pointer; box-shadow:0 4px 6px rgba(0,0,0,0.2); transition:all 0.3s ease; z-index:1000; }
.sidebar.hidden + .toggle-btn {left:15px;}
.toggle-btn:hover {background:#003d80;}

/* Main content */
.main { margin-left:220px; padding:30px; flex-grow:1; width:calc(100% - 220px); transition:all 0.3s ease; }
.main.expanded {margin-left:0; width:100%;}

/* Logout button */
.logout-btn { position:absolute; top:15px; right:20px; background:#FF4B5C; color:#fff; padding:8px 15px; font-weight:bold; border-radius:6px; text-decoration:none; transition:0.3s; }
.logout-btn:hover { background:#e63946; }

/* Messages */
.success-msg { background:#28a745; color:#fff; padding:12px 20px; border-radius:6px; margin-bottom:20px; text-align:center; font-weight:bold; }
.error-msg { background:#dc3545; color:#fff; padding:12px 20px; border-radius:6px; margin-bottom:20px; text-align:center; font-weight:bold; }

/* Table */
table { width:100%; border-collapse:collapse; font-size:14px; border:1px solid #ccc; border-radius:8px; overflow:hidden; box-shadow:0 2px 5px rgba(0,0,0,0.1); margin-top:20px; }
table th, table td { padding:10px 12px; text-align:left; border-bottom:1px solid #ccc; vertical-align:middle; }
table th { font-weight:bold; background:#eee; }
table tbody tr:last-child td { border-bottom:none; }
table tbody tr:hover { background:#f1f1f1; }
.action-btn { padding:6px 12px; border:none; border-radius:6px; cursor:pointer; font-size:13px; }
.delete-btn { background:#FF4B5C; color:#fff; }
.delete-btn:hover { background:#e63946; }
.update-btn { background:#0074D9; color:#fff; margin-left:5px; }
.update-btn:hover { background:#005fa3; }
.empty-msg { text-align:center; padding:12px; }

/* Photo and description */
.product-img { width:60px; height:60px; object-fit:cover; border-radius:6px; }
.description { max-width:200px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
</style>
<script>
function confirmDelete(id) {
    if(confirm("Are you sure you want to delete this upcoming product?")) {
        window.location.href = 'delete_upcoming_product.php?delete=' + id;
    }
}
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
    <h2>Manage Upcoming Products</h2>

    <?php
    if(isset($_SESSION['success_message'])) {
        echo "<div class='success-msg'>".$_SESSION['success_message']."</div>";
        unset($_SESSION['success_message']);
    }
    if(isset($_SESSION['error_message'])) {
        echo "<div class='error-msg'>".$_SESSION['error_message']."</div>";
        unset($_SESSION['error_message']);
    }
    ?>

    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Photo</th>
                <th>Product Name</th>
                <th>Description</th>
                <th>Expected Date</th>
                <th>Expected Price ($)</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if(!empty($upcomingProducts)): ?>
                <?php foreach($upcomingProducts as $prod): ?>
                    <tr>
                        <td><?= $prod['id'] ?></td>
                        <td>
                            <?php if(!empty($prod['photo']) && file_exists($prod['photo'])): ?>
                                <img src="<?= $prod['photo'] ?>" class="product-img" alt="Product Photo">
                            <?php else: ?>
                                <span style="color:#999;">No image</span>
                            <?php endif; ?>
                        </td>
                        <td><?= htmlspecialchars($prod['name']) ?></td>
                        <td class="description" title="<?= htmlspecialchars($prod['description']) ?>"><?= htmlspecialchars($prod['description']) ?></td>
                        <td><?= date('M d, Y', strtotime($prod['expected_date'])) ?></td>
                        <td><?= number_format($prod['expected_price'], 2) ?></td>
                        <td>
                            <a href="update_upcoming_product.php?id=<?= $prod['id'] ?>" class="action-btn update-btn">Update</a>
                            <button class="action-btn delete-btn" onclick="confirmDelete(<?= $prod['id'] ?>)">Delete</button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr><td colspan="7" class="empty-msg">No upcoming products found.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<script>
const toggleBtn = document.getElementById("toggleBtn");
const sidebar = document.getElementById("sidebar");
const main = document.getElementById("main");
toggleBtn.addEventListener("click", () => {
    sidebar.classList.toggle("hidden");
    main.classList.toggle("expanded");
});
</script>
</body>
</html>
