<?php
// C:\xampp\htdocs\mariyam_fashion\index.php

// ====================================================================
// 1. PHP LOGIC (Includes config.php to establish $pdo and session)
// ====================================================================

// This line includes config.php, which defines the database connection $pdo.
include 'config.php'; 

// CRUCIAL FIX: Declares $pdo as global to ensure it's accessible in the main script scope.
global $pdo;

// --- Data Fetching for Home Page ---

// Fetch categories (for the navigation/menus)
$categoriesList = $pdo->query("SELECT * FROM categories ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);

// Fetch recent products (limit to 10 for home page display)
$recentProductsStmt = $pdo->prepare("
    SELECT p.*, c.name AS category_name, 
            (SELECT image_path FROM product_images WHERE product_id=p.id LIMIT 1) AS first_image
    FROM products p
    LEFT JOIN categories c ON p.category_id = c.id
    ORDER BY p.created_at DESC
    LIMIT 10
");
$recentProductsStmt->execute();
$recentProducts = $recentProductsStmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch upcoming products
$upcomingProducts = $pdo->query("SELECT * FROM upcoming_products ORDER BY expected_date ASC")->fetchAll(PDO::FETCH_ASSOC);

// Fetch slider images
$sliderImages = $pdo->query("SELECT * FROM sliders ORDER BY created_at DESC")->fetchAll(PDO::FETCH_ASSOC);


// --- Cart Logic (Shared with shop.php) ---
$cart_items = [];
$cart_count = 0;
if(isset($_SESSION['cart']) && !empty($_SESSION['cart'])){
    $ids = array_keys($_SESSION['cart']);
    if (!empty($ids)) {
        $placeholders = str_repeat('?,', count($ids) - 1) . '?';
        $stmt = $pdo->prepare("
            SELECT p.id, p.name, p.price,
                    (SELECT image_path FROM product_images WHERE product_id=p.id LIMIT 1) AS first_image
            FROM products p WHERE p.id IN ($placeholders)
        ");
        $stmt->execute($ids);
        $prods = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach($prods as $prod){
            $qty = $_SESSION['cart'][$prod['id']];
            $image = !empty($prod['first_image']) ? "admin/".$prod['first_image'] : "admin/uploads/default.png";
            $cart_items[$prod['id']] = [
                'id' => $prod['id'],
                'name' => $prod['name'],
                'price' => $prod['price'],
                'image' => $image, 
                'qty' => $qty
            ];
        }
    }
    $cart_count = array_sum(array_column($cart_items, 'qty'));
}
$cart_items_json = json_encode($cart_items);
// Fetch all products with first image
$allProductsStmt = $pdo->prepare("
    SELECT p.*, c.name AS category_name, 
            (SELECT image_path FROM product_images WHERE product_id=p.id LIMIT 1) AS first_image
    FROM products p
    LEFT JOIN categories c ON p.category_id = c.id
    WHERE $where
    ORDER BY p.created_at DESC
");
$allProductsStmt->execute($params);
$allProducts = $allProductsStmt->fetchAll();
?>




<div class="icons">
        <?php if ($logged_in_user): ?>
            <a href="dashboard.php" class="user-link">
                <i class="fa fa-user"></i> Hello, <?= $display_username ?>
            </a>
            <a href="logout.php" class="user-link">
                <i class="fa fa-sign-out-alt"></i> Sign Out
            </a>
        <?php else: ?>
            <a href="login.php" class="user-link">
                <i class="fa fa-user"></i> Account
            </a>
        <?php endif; ?>
        <a onclick="openCart()"><i class="fa fa-shopping-cart"></i> <span id="cart-count-desktop"><?= $cart_count ?></span></a>
    </div>
</div>