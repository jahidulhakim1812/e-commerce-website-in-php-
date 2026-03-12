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

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $name = $_POST['product_name'];
    $description = $_POST['description'];
    $expected_date = $_POST['expected_date'];
    $expected_price = $_POST['expected_price'];
    $category_id = $_POST['category_id'];

    $photoPath = null;
    if (!empty($_FILES['photo']['name'])) {
        $targetDir = "uploads/upcoming/";
        if (!is_dir($targetDir)) mkdir($targetDir, 0777, true);

        $fileName = basename($_FILES['photo']['name']);
        $photoPath = $targetDir . time() . "_" . $fileName;

        if (!move_uploaded_file($_FILES['photo']['tmp_name'], $photoPath)) {
            $photoPath = null;
        }
    }

    $stmt = $pdo->prepare("INSERT INTO upcoming_products (name, description, expected_date, expected_price, category_id, photo) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute([$name, $description, $expected_date, $expected_price, $category_id, $photoPath]);

    $_SESSION['success_message'] = "✅ Upcoming product added successfully!";
    header("Location: upcoming_product.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Add Upcoming Product - Admin Panel</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<style>
body {
    margin:0;
    font-family:'Segoe UI',sans-serif;
    display:flex;
    background:#f4f6f9;
    color:#333;
}

/* Sidebar */
.sidebar {
    width:220px;
    background: linear-gradient(180deg,#001F3F,#0056b3);
    color:#fff;
    height:100vh;
    position:fixed;
    top:0;
    left:0;
    padding-top:60px;
    box-shadow:2px 0 10px rgba(0,0,0,0.2);
    transition:all 0.3s ease;
    overflow-y:auto; /* scrollable */
    overflow-x:hidden;
    z-index:1000;
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
.sidebar ul li a i {
    margin-right:12px;
    width:25px;
    text-align:center;
    font-size:16px;
}
.sidebar ul li a:hover {background:rgba(255,255,255,0.15);}
.sidebar ul li a.active {background:#00BFFF; color:#001F3F; font-weight:bold;}

/* Toggle button */
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
    z-index:1100;
}
.sidebar.hidden + .toggle-btn {left:15px;}
.toggle-btn:hover {background:#003d80;}

/* Main content */
.main {
    margin-left:220px;
    padding:30px;
    flex-grow:1;
    width:calc(100% - 220px);
    transition:all 0.3s ease;
}
.main.expanded {margin-left:0; width:100%;}

/* Logout button */
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

/* Form container */
.form-container {
    background:#fff;
    padding:30px;
    border-radius:12px;
    box-shadow:0 6px 15px rgba(0,0,0,0.1);
    max-width:600px;
    margin:60px auto 0 auto;
}
.form-container h2 {margin-bottom:20px; color:#001F3F; text-align:center;}
.form-container label {font-weight:bold;}
.form-container input,
.form-container textarea,
.form-container select {
    width:100%; padding:10px; margin:8px 0 15px; border:1px solid #ccc; border-radius:6px;
}
.form-container button {
    background:#0074D9; color:#fff; padding:12px 20px; border:none; border-radius:6px;
    cursor:pointer; font-weight:bold; width:100%;
}
.form-container button:hover {background:#0056b3;}
.success-msg {
    background:#28a745; color:#fff; padding:12px 20px; border-radius:6px;
    margin-bottom:20px; text-align:center; font-weight:bold; box-shadow:0 4px 10px rgba(0,0,0,0.1);
}
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

<div class="main" id="main">
    <a href="logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Logout</a>

    <div class="form-container">
        <h2>Add Upcoming Product</h2>
        <?php
        if(isset($_SESSION['success_message'])) {
            echo "<div class='success-msg'>".$_SESSION['success_message']."</div>";
            unset($_SESSION['success_message']);
        }
        ?>
        <form method="POST" enctype="multipart/form-data">
            <label>Product Name</label>
            <input type="text" name="product_name" required>

            <label>Description</label>
            <textarea name="description" rows="3"></textarea>

            <label>Expected Date</label>
            <input type="date" name="expected_date" required>

            <label>Expected Price ($)</label>
            <input type="number" step="0.01" name="expected_price" required>

            <label>Category</label>
            <select name="category_id" required>
                <option value="">-- Select Category --</option>
                <?php
                $categories = $pdo->query("SELECT * FROM categories ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);
                foreach ($categories as $cat) {
                    echo "<option value='{$cat['id']}'>{$cat['name']}</option>";
                }
                ?>
            </select>

            <label>Product Photo</label>
            <input type="file" name="photo" accept="image/*">

            <button type="submit">Add Upcoming Product</button>
        </form>
    </div>
</div>

<script>
// Sidebar toggle
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
</script>
</body>
</html>
