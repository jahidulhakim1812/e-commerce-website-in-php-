<?php
session_start();
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

try {
    // Note: It's best practice to use environment variables or a configuration file
    // for database credentials instead of hardcoding them.
    $pdo = new PDO("mysql:host=localhost;dbname=mariyamf_mariyam_fashion", "mariyamf", "Es)0Abi774An;G");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    // Set default fetch mode to associative array for consistency
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // In a production environment, avoid echoing the raw error message.
    die("Database connection failed: " . $e->getMessage());
}

// --- Handle DELETE request ---
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['delete_category_id'])) {
    $categoryId = (int)$_POST['delete_category_id'];

    if ($categoryId > 0) {
        try {
            // BEST PRACTICE: Use a transaction if there are related tables (e.g., products)
            // to ensure data integrity (e.g., delete all products in this category first, or set their category_id to NULL/default).
            // For simplicity, we are just deleting the category here.

            $stmt = $pdo->prepare("DELETE FROM categories WHERE id = ?");
            $stmt->execute([$categoryId]);

            if ($stmt->rowCount() > 0) {
                // Successful deletion
                echo "<script>alert('🗑️ Category deleted successfully!'); window.location.href='add_category.php';</script>";
            } else {
                // Category ID not found
                echo "<script>alert('⚠ Category not found or already deleted!'); window.location.href='add_category.php';</script>";
            }
            exit;
        } catch (PDOException $e) {
            // Handle database error during deletion
            echo "<script>alert('❌ Error deleting category: " . addslashes($e->getMessage()) . "'); window.location.href='add_category.php';</script>";
            exit;
        }
    }
}

// --- Handle ADD request ---
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['category_name'])) {
    $categoryName = trim($_POST['category_name']);

    if (!empty($categoryName)) {
        // Check if category already exists (case-insensitive check is often better)
        $checkStmt = $pdo->prepare("SELECT id FROM categories WHERE name = ?");
        $checkStmt->execute([$categoryName]);

        if ($checkStmt->rowCount() == 0) {
            $stmt = $pdo->prepare("INSERT INTO categories (name) VALUES (?)");
            $stmt->execute([$categoryName]);
            echo "<script>alert('✅ Category added successfully!'); window.location.href='add_category.php';</script>";
            exit;
        } else {
            echo "<script>alert('⚠ Category already exists!');</script>";
        }
    } else {
        echo "<script>alert('⚠ Please enter a category name!');</script>";
    }
}

// Fetch all categories for display
$categories = $pdo->query("SELECT * FROM categories ORDER BY name ASC")->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Add Category - Admin Panel</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<style>
/* ... (Your existing CSS styles remain the same) ... */
body {
    margin:0;
    font-family:'Segoe UI',sans-serif;
    display:flex;
    background:#f0f4ff;
    color:#333;
}
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
    overflow-y: auto;
    overflow-x: hidden;
    z-index: 1000;
}
.sidebar.hidden {left:-220px;}
.sidebar h2 {
    text-align:center;
    color:#00BFFF;
    margin-bottom:20px;
    position:absolute;
    top:0;
    width:100%;
    padding:15px 0;
    border-bottom:1px solid rgba(255,255,255,0.2);
    font-size:18px;
    font-weight:bold;
}
.sidebar ul {list-style:none; padding:0; margin:0;}
.sidebar ul li {margin-bottom:5px;}
.sidebar ul li a {
    display:flex; align-items:center;
    color:#fff;
    padding:12px 20px;
    text-decoration:none;
    font-size:15px;
    border-radius:8px 0 0 8px;
    transition:0.3s;
    cursor:pointer;
    white-space:nowrap;
}
.sidebar ul li a i {margin-right:12px; width:25px; text-align:center; font-size:16px;}
.sidebar ul li a:hover {background:rgba(255,255,255,0.15);}
.sidebar ul li a.active {background:#00BFFF; color:#001F3F; font-weight:bold;}
.toggle-btn {
    position:fixed;
    top:15px;
    left:230px;
    background:#0056b3;
    color:#fff;
    border-radius:50%;
    padding:8px 10px;
    cursor:pointer;
    box-shadow:0 4px 6px rgba(0,0,0,0.2);
    transition:all 0.3s ease;
    z-index:1000;
}
.sidebar.hidden + .toggle-btn {left:15px;}
.toggle-btn:hover {background:#003d80;}
.main {
    margin-left:220px;
    padding:30px;
    flex-grow:1;
    width:calc(100% - 220px);
    transition:all 0.3s ease;
    display:flex;
    flex-direction:column;
    align-items:center;
    margin:30px;
}
.main.expanded {margin-left:0; width:100%;}
.logout-btn {
    position:absolute;
    top:15px;
    right:20px;
    background:#FF4B5C;
    color:#fff;
    padding:8px 15px;
    font-weight:bold;
    border-radius:6px;
    text-decoration:none;
    transition:0.3s;
}
.logout-btn:hover {background:#e63946;}
.form-container {
    background:#fff;
    padding:25px;
    border-radius:12px;
    box-shadow:0 6px 15px rgba(0,0,0,0.1);
    width:600px;
    margin-bottom:30px;
}
.form-container h2 {margin-bottom:20px; color:#001F3F;}
.form-container label {font-weight:bold;}
.form-container input {
    width:100%; padding:10px; margin:8px 0 15px;
    border:1px solid #ccc; border-radius:6px;
}
.form-container button {
    background:#0074D9; color:#fff;
    padding:10px 18px; border:none; border-radius:6px;
    cursor:pointer; font-weight:bold; transition:0.3s;
}
.form-container button:hover {background:#0056b3;}
.table-container {
    width:100%;
    max-width:900px;
    overflow-x:auto;
}
table {
    width:100%;
    border-collapse:collapse;
    font-size:14px;
    border:1px solid #ccc;
    border-radius:8px;
    overflow:hidden;
    box-shadow:0 2px 5px rgba(0,0,0,0.1);
}
table th, table td {
    padding:12px 15px;
    text-align:left;
    border-bottom:1px solid #ccc;
}
table th {
    font-weight:bold;
    background:#f9f9f9;
}
table tbody tr:last-child td {border-bottom:none;}
.empty-msg {text-align:center; padding:12px;}

/* New style for delete button */
.delete-btn {
    background: #FF4B5C;
    color: #fff;
    border: none;
    padding: 6px 10px;
    border-radius: 4px;
    cursor: pointer;
    font-size: 13px;
    transition: 0.3s;
}
.delete-btn:hover {background: #e63946;}
</style>
</head>
<body>

<div class="sidebar" id="sidebar">
    <h2>Admin Panel</h2>
    <ul>
        <li><a href="admin_dashboard.php"><i class="fas fa-chart-line"></i> Dashboard</a></li>
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
        <li><a href="add_category.php" class="active"><i class="fas fa-tags"></i> Categories</a></li>
        <li><a href="customer.php"><i class="fas fa-users"></i> Customers</a></li>
        <li><a href="slider.php"><i class="fas fa-image"></i> Home Sliders</a></li>
        <li><a href="admin_chat.php"><i class="fas fa-comment"></i> Messages</a></li>
    </ul>
</div>


<span class="toggle-btn" id="toggleBtn"><i class="fas fa-bars"></i></span>

<div class="main" id="main">
    <a href="logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Logout</a>

    <div class="form-container">
        <h2>Add New Category</h2>
        <form method="POST">
            <label>Category Name</label>
            <input type="text" name="category_name" placeholder="Enter category name" required>
            <button type="submit" name="add_category">Add Category</button>
        </form>
    </div>

    <div class="table-container">
        <h2>Existing Categories</h2>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Category Name</th>
                    <th>Action</th> </tr>
            </thead>
            <tbody>
                <?php if(!empty($categories)): ?>
                    <?php foreach($categories as $cat): ?>
                        <tr>
                            <td><?= $cat['id'] ?></td>
                            <td><?= htmlspecialchars($cat['name']) ?></td>
                            <td>
                                <form method="POST" style="display:inline;" onsubmit="return confirm('Are you sure you want to delete the category: <?= htmlspecialchars($cat['name']) ?>? This action is irreversible.');">
                                    <input type="hidden" name="delete_category_id" value="<?= $cat['id'] ?>">
                                    <button type="submit" class="delete-btn"><i class="fas fa-trash"></i> Delete</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="3" class="empty-msg">No categories found.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
// Sidebar Toggle
const toggleBtn = document.getElementById("toggleBtn");
const sidebar = document.getElementById("sidebar");
const main = document.getElementById("main");
toggleBtn.addEventListener("click", () => {
    sidebar.classList.toggle("hidden");
    main.classList.toggle("expanded");
});

// Collapsible Orders submenu
const ordersMenu = document.getElementById("ordersMenu");
const ordersSubmenu = document.getElementById("ordersSubmenu");
ordersMenu.addEventListener("click", () => {
    ordersSubmenu.style.display = ordersSubmenu.style.display === "block" ? "none" : "block";
});
</script>
</body>
</html>