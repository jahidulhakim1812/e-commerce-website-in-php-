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
    $price = $_POST['price'];
    $description = $_POST['description'];
    $category_id = $_POST['category_id'];

    $stmt = $pdo->prepare("INSERT INTO products (name, price, description, category_id) VALUES (?, ?, ?, ?)");
    $stmt->execute([$name, $price, $description, $category_id]);
    $productId = $pdo->lastInsertId();

    if (!empty($_FILES['images']['name'][0])) {
        $targetDir = "uploads/";
        if (!is_dir($targetDir)) mkdir($targetDir, 0777, true);

        foreach ($_FILES['images']['tmp_name'] as $key => $tmpName) {
            $fileName = basename($_FILES['images']['name'][$key]);
            $targetFile = $targetDir . time() . "_" . $fileName;

            if (move_uploaded_file($tmpName, $targetFile)) {
                $imgStmt = $pdo->prepare("INSERT INTO product_images (product_id, image_path) VALUES (?, ?)");
                $imgStmt->execute([$productId, $targetFile]);
            }
        }
    }

    $successMsg = "✅ Product added successfully!";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Add Product - Admin Panel</title>
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

.sidebar.hidden {left:-220px;} /* Fully hidden when collapsed */

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
    letter-spacing:1px;
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
    left:230px; /* Just outside sidebar */
    background:#0056b3;
    color:#fff;
    border-radius:50%;
    padding:8px 10px;
    cursor:pointer;
    box-shadow:0 4px 6px rgba(0,0,0,0.2);
    transition:all 0.3s ease;
    z-index:1000;
}
.sidebar.hidden + .toggle-btn {left:15px;} /* Move button when hidden */
.toggle-btn:hover {background:#003d80;}

/* Main content adjustment when sidebar collapsed */
.main {
    margin-left:220px;
    padding:30px;
    flex-grow:1;
    width:calc(100% - 220px);
    transition:all 0.3s ease;
}
.main.expanded {
    margin-left:0;
    width:100%;
}

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
            max-width:700px;
            margin:auto;
        }
        .form-container h2 {margin-bottom:20px; color:#001F3F; text-align:center;}
        .form-container label {font-weight:bold; display:block; margin-top:10px;}
        .form-container input, .form-container textarea, .form-container select {
            width:100%; padding:10px; margin:8px 0 15px;
            border:1px solid #ccc; border-radius:6px;
            font-size:14px;
        }
        .form-container button {
            background:#0074D9; color:#fff;
            padding:12px 20px; border:none;
            border-radius:6px; cursor:pointer; font-weight:bold;
            width:100%; font-size:16px;
        }
        .form-container button:hover {background:#0056b3;}

        /* Success message */
        .success-msg {
            background:#28a745; color:#fff;
            padding:12px 20px;
            border-radius:6px;
            margin-bottom:20px;
            text-align:center;
            font-weight:bold;
            box-shadow:0 4px 10px rgba(0,0,0,0.1);
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


<!-- Toggle button placed outside -->
<span class="toggle-btn" id="toggleBtn"><i class="fas fa-bars"></i></span>

<div class="main" id="main">
    <a href="logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Logout</a>

    <div class="form-container" style="margin-top:60px;">
        <?php if(isset($successMsg)) echo "<div class='success-msg'>{$successMsg}</div>"; ?>
        <h2>Add New Product</h2>
        <form method="POST" enctype="multipart/form-data">
            <label>Product Name</label>
            <input type="text" name="product_name" required>

            <label>Price ($)</label>
            <input type="number" step="0.01" name="price" required>

            <label>Description</label>
            <textarea name="description" rows="3"></textarea>

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

            <label>Product Images (you can select multiple)</label>
            <input type="file" name="images[]" multiple>

            <button type="submit">Add Product</button>
        </form>
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

   const ordersMenu = document.getElementById("ordersMenu");
const ordersSubmenu = document.getElementById("ordersSubmenu");
ordersMenu.addEventListener("click", () => {
    ordersSubmenu.style.display = ordersSubmenu.style.display === "block" ? "none" : "block";
});

</script>

</body>
</html>
