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

// Handle image upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['slider_image'])) {
    $uploadDir = 'uploads/sliders/';
    if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);

    $fileName = time() . '_' . basename($_FILES['slider_image']['name']);
    $targetFile = $uploadDir . $fileName;
    $fileType = strtolower(pathinfo($targetFile, PATHINFO_EXTENSION));
    $allowedTypes = ['jpg','jpeg','png','gif'];

    if (in_array($fileType, $allowedTypes)) {
        if (move_uploaded_file($_FILES['slider_image']['tmp_name'], $targetFile)) {
            $stmt = $pdo->prepare("INSERT INTO sliders (image_url, created_at) VALUES (?, NOW())");
            $stmt->execute([$targetFile]);
            echo "<script>alert('✅ Slider image uploaded successfully'); window.location.href='slider.php';</script>";
        } else echo "<script>alert('⚠ Failed to upload the file');</script>";
    } else echo "<script>alert('⚠ Only JPG, PNG, GIF files are allowed');</script>";
}

// Handle delete
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $stmt = $pdo->prepare("SELECT image_url FROM sliders WHERE id=?");
    $stmt->execute([$id]);
    $slider = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($slider && file_exists($slider['image_url'])) unlink($slider['image_url']);
    $pdo->prepare("DELETE FROM sliders WHERE id=?")->execute([$id]);
    echo "<script>alert('✅ Slider deleted successfully'); window.location.href='slider.php';</script>";
}

// Fetch all sliders
$sliders = $pdo->query("SELECT * FROM sliders ORDER BY created_at DESC")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Manage Sliders - Admin Panel</title>
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

.sidebar.hidden{left:-220px;}
.sidebar h2{
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
.sidebar ul{list-style:none;padding:0;margin:0;}
.sidebar ul li{margin-bottom:5px;}
.sidebar ul li a{
    display:flex;
    align-items:center;
    color:#fff;
    padding:12px 20px;
    text-decoration:none;
    font-size:15px;
    border-radius:8px 0 0 8px;
    transition:0.3s;
    cursor:pointer;
    white-space:nowrap;
}
.sidebar ul li a i{
    margin-right:12px;
    width:25px;
    text-align:center;
    font-size:16px;
}
.sidebar ul li a:hover{background:rgba(255,255,255,0.15);}
.sidebar ul li a.active{background:#00BFFF;color:#001F3F;font-weight:bold;}
.submenu{list-style:none;padding-left:20px;margin-top:5px;display:none;}

/* Toggle button */
.toggle-btn{
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
.sidebar.hidden + .toggle-btn{left:15px;}
.toggle-btn:hover{background:#003d80;}

/* Main content */
.main{
    margin-left:220px;
    padding:30px;
    flex-grow:1;
    width:calc(100% - 220px);
    transition:all 0.3s ease;
    margin-top:30px;
}
.main.expanded{margin-left:0;width:100%;}

/* Logout button */
.logout-btn{
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
.logout-btn:hover{background:#e63946;}

/* Form */
.form-container{
    background:#fff;
    padding:25px;
    border-radius:12px;
    box-shadow:0 6px 15px rgba(0,0,0,0.1);
    max-width:600px;
    margin-bottom:30px;
}
.form-container h2{margin-bottom:20px;color:#001F3F;}
.form-container input{width:100%;padding:10px;margin-bottom:15px;border:1px solid #ccc;border-radius:6px;}
.form-container button{background:#0074D9;color:#fff;padding:10px 18px;border:none;border-radius:6px;cursor:pointer;font-weight:bold;transition:0.3s;}
.form-container button:hover{background:#0056b3;}

/* Table */
table{
    width:100%;
    border-collapse:collapse;
    font-size:14px;
    border:1px solid #ccc;
    border-radius:8px;
    overflow:hidden;
    box-shadow:0 2px 5px rgba(0,0,0,0.1);
}
table th, table td{padding:10px 12px;text-align:left;border-bottom:1px solid #ccc;}
table th{font-weight:bold;background:#f9f9f9;}
table tbody tr:last-child td{border-bottom:none;}
table tbody tr:hover{background:#f1f1f1;}
.empty-msg{text-align:center;padding:12px;}
img.slider-thumb{width:120px;border-radius:6px;}
.delete-btn{background:#FF4B5C;color:#fff;padding:5px 10px;border:none;border-radius:5px;cursor:pointer;transition:0.3s;text-decoration:none;}
.delete-btn:hover{background:#e63946;}
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

    <div class="form-container">
        <h2>Upload Slider Image</h2>
        <form method="POST" enctype="multipart/form-data">
            <input type="file" name="slider_image" required>
            <button type="submit">Upload Image</button>
        </form>
    </div>

    <h2>Existing Slider Images</h2>
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Image</th>
                <th>Uploaded On</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php if(!empty($sliders)): ?>
                <?php foreach($sliders as $slider): ?>
                <tr>
                    <td><?= $slider['id'] ?></td>
                    <td><img src="<?= htmlspecialchars($slider['image_url']) ?>" class="slider-thumb" alt="slider"></td>
                    <td><?= date('M d, Y', strtotime($slider['created_at'])) ?></td>
                    <td>
                        <a href="?delete=<?= $slider['id'] ?>" class="delete-btn" onclick="return confirm('Are you sure you want to delete this slider?');">Delete</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr><td colspan="4" class="empty-msg">No slider images found.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<script>
// Sidebar toggle
const toggleBtn = document.getElementById('toggleBtn');
const sidebar = document.getElementById('sidebar');
const main = document.getElementById('main');
toggleBtn.addEventListener('click', ()=>{
    sidebar.classList.toggle('hidden');
    main.classList.toggle('expanded');
});

// Orders submenu
const ordersMenu = document.getElementById('ordersMenu');
const ordersSubmenu = document.getElementById('ordersSubmenu');
ordersMenu.addEventListener('click', ()=> {
    ordersSubmenu.style.display = ordersSubmenu.style.display === 'block' ? 'none' : 'block';
});
</script>
</body>
</html>
