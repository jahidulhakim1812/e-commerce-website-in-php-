<?php
// C:\xampp\htdocs\mariyam_fashion\index.php

// ====================================================================
// 1. PHP LOGIC (Includes config.php to establish $pdo and session)
// ========================================================a============

// This line includes config.php, which defines the database connection $pdo.
include 'config.php'; 
$logged_in_user = isset($_SESSION['user_id']); 
$display_username = isset($_SESSION['username']) ? $_SESSION['username'] : '';

// CRUCIAL FIX: Declares $pdo as global to ensure it's accessible in the main script scope.
global $pdo;

// Fetch categories
$categoriesList = $pdo->query("SELECT * FROM categories ORDER BY name ASC")->fetchAll();

// Handle search + filter
$searchQuery = isset($_GET['search']) ? trim($_GET['search']) : '';
$filter = isset($_GET['filter']) ? $_GET['filter'] : ''; 

$where = "1=1";
$params = [];
if ($searchQuery) {
    // Escape the LIKE operator wildcards in the search query
    $safeSearch = str_replace(['%', '_'], ['\\%', '\\_'], $searchQuery);
    $where .= " AND (p.name LIKE :search OR p.description LIKE :search)";
    $params['search'] = "%$safeSearch%";
}
if ($filter && is_numeric($filter)) {
    $where .= " AND p.category_id = :filter";
    $params['filter'] = $filter;
}

// --- Dynamic Query Execution ---

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

// Fetch recent products with first image (applies the same filter if active)
$recentProductsStmt = $pdo->prepare("
    SELECT p.*, c.name AS category_name, 
            (SELECT image_path FROM product_images WHERE product_id=p.id LIMIT 1) AS first_image
    FROM products p
    LEFT JOIN categories c ON p.category_id = c.id
    WHERE $where
    ORDER BY p.created_at DESC
    LIMIT 10
");
$recentProductsStmt->execute($params);
$recentProducts = $recentProductsStmt->fetchAll();

// Upcoming products (not affected by search/filter)
$upcomingProducts = $pdo->query("SELECT * FROM upcoming_products ORDER BY expected_date ASC")->fetchAll();

// Slider images
$sliderImages = $pdo->query("SELECT * FROM sliders ORDER BY created_at DESC")->fetchAll();

// Cart count
$cart_count = isset($_SESSION['cart']) ? array_sum($_SESSION['cart']) : 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Mariyam Fashion - Home</title>
<!-- Favicon -->
<link rel="icon" type="image/png" href="upload/favicon.png">


<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<style>
/* ===== Color Palette ===== */
:root {
    --primary-blue: #0074D9;
    --dark-blue: #001F3F;
    --mid-blue: #003366;
    --light-blue: #00BFFF;
    --background-color: #f4f8fc;
    --text-color: #333;
    --red-alert: red;
    --accent-orange: #ff8c3a; /* New professional accent color */
}

/* ===== Basic Reset & Typography ===== */
body {
    margin: 0;
    font-family: 'Segoe UI', sans-serif;
    background: var(--background-color);
    color: var(--text-color);
    padding-bottom: 60px; 
}
a {
    text-decoration: none;
    color: inherit;
}

/* ===== Top Utility Bar & Headers (Mobile & Desktop) ===== */
.utility-bar {
    background: var(--dark-blue);
    color: #fff;
    padding: 8px 20px;
    font-size: 14px;
    display: flex;
    justify-content: space-between;
    align-items: center;
}
.utility-bar a {
    color: var(--light-blue);
    margin-left: 15px;
}
@media(min-width: 769px) {
    .utility-bar { display: none; }
}

.main-header {
    background: linear-gradient(to right, var(--dark-blue), var(--primary-blue));
    border-bottom: 1px solid #eee;
    padding: 10px 15px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    position: sticky;
    top: 0;
    z-index: 1000;
    height: 60px;
}
.main-header .menu-icon {
    font-size: 24px;
    cursor: pointer;
    flex-shrink: 0;
    color: #fff;
}
.main-header .logo-brand {
    display: flex;
    flex-direction: column;
    justify-content: center;
    align-items: center;
    flex-grow: 1; 
}
.main-header .logo img {
    max-height: 50px;
    width: auto;
    filter: brightness(0) invert(1);
}

/* --- ICON STYLES --- */
.main-header .icons, .full-header-desktop .icons {
    display: flex;
    flex-shrink: 0;
    align-items: center; 
}
.main-header .icons a, .full-header-desktop .icons a {
    color: #fff;
    position: relative;
    margin-left: 10px; 
}
.main-header .icons a {
    font-size: 20px;
}
.main-header .icons .search-icon {
    cursor: pointer; 
    margin-right: 15px;
}
.main-header .icons a .cart-badge {
    background: var(--red-alert);
    color: #fff;
    border-radius: 50%;
    padding: 2px 6px;
    position: absolute;
    top: -8px;
    right: -12px;
    font-size: 10px;
    line-height: 1;
}

/* --- Floating Search Bar Styles --- */
.header-search-form {
    position: absolute;
    top: 60px; 
    left: 0;
    width: 100%;
    padding: 10px 15px;
    background: var(--background-color);
    box-shadow: 0 2px 5px rgba(0,0,0,0.1);
    z-index: 999;
    display: none;
    box-sizing: border-box;
    opacity: 0;
    transform: translateY(-10px);
    transition: opacity 0.2s ease, transform 0.2s ease;
}
.header-search-form.active {
    display: block;
    opacity: 1;
    transform: translateY(0);
}
.header-search-form input[type="text"], 
.header-search-form select {
    padding: 8px;
    border-radius: 4px;
    border: 1px solid var(--primary-blue);
    font-size: 14px;
    margin-bottom: 5px;
    width: 100%;
    box-sizing: border-box;
}
.header-search-form select {
    margin-top: 5px;
}
.header-search-form button {
    display: none; 
}

/* Desktop Header & Navigation */
.full-header-desktop {
    background: linear-gradient(to right, var(--dark-blue), var(--primary-blue));
    color: #fff;
    padding: 8px 20px;
    display: none; 
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
}
.full-header-desktop .logo img { max-height: 55px; }
/* Centered Brand Name */
.full-header-desktop .brand { 
    font-size: 22px; 
    font-weight: bold; 
    text-align: center; 
    position: absolute;       /* 1. Allows absolute positioning relative to header */
    left: 50%;                /* 2. Moves the element's left edge to the center */
    transform: translateX(-50%); /* 3. **Crucial:** Shifts the element back by half its own width, achieving perfect center. */
    white-space: nowrap;      /* Prevents text wrap */
    z-index: 10;
}
.full-header-desktop .icons a { color: #fff; margin-left: 15px; font-size: 16px; position: relative; cursor: pointer; }
.full-header-desktop .icons a span { 
    background: var(--red-alert); color: #fff; border-radius: 50%; padding: 2px 6px; 
    position: absolute; top:-8px; right:-12px; font-size: 12px; 
}

.desktop-nav {
    background: var(--mid-blue);
    display: none; 
    justify-content: center;
    align-items: center;
    flex-wrap: wrap;
}
.desktop-nav a { color: #fff; margin: 10px 18px; font-size: 15px; font-weight: 500; }
.desktop-nav a:hover, .desktop-nav a.active { color: var(--light-blue); }

@media(min-width: 769px) {
    .full-header-desktop { display: flex; }
    .desktop-nav { display: flex; }
    /* --- Hiding mobile components on desktop --- */
    .main-header, .bottom-nav { display: none !important; } 
    body { padding-bottom: 0; }
}

/* ===== Other Sections (Unchanged but included for completeness) ===== */
.mobile-menu-overlay { height: 100%; width: 0; position: fixed; z-index: 1010; top: 0; left: 0; background-color: var(--dark-blue); overflow-x: hidden; transition: 0.3s; padding-top: 60px; }
.mobile-menu-overlay-content { position: relative; width: 100%; text-align: center; margin-top: 30px; }
.mobile-menu-overlay a { padding: 15px; font-size: 20px; color: #fff; display: block; transition: 0.3s; border-bottom: 1px solid rgba(255, 255, 255, 0.1); }
.mobile-menu-overlay .closebtn { position: absolute; top: 20px; right: 35px; font-size: 40px; color: #fff; cursor: pointer; }
#side-cart { position: fixed; top: 0; right: -100%; width: 90%; max-width: 350px; height: 100%; background: #fff; box-shadow: -3px 0 10px rgba(0,0,0,0.3); transition: right 0.3s ease; z-index: 2000; padding: 20px; overflow-y: auto; }
#side-cart.active { right: 0; }
@media (max-width: 400px) { #side-cart { width: 85%; } }
#side-cart h3 { margin-top: 0; font-size: 20px; color: var(--mid-blue); }
.cart-item { display: flex; align-items: center; gap: 10px; margin: 15px 0; border-bottom: 1px solid #eee; padding-bottom: 10px; }
.cart-item img { width: 60px; height: 60px; object-fit: cover; border-radius: 6px; }
.cart-item .details { flex: 1; }
.qty-controls { display: flex; align-items: center; gap: 6px; margin-top: 5px; }
.qty-controls button { padding: 2px 6px; border: none; background: var(--primary-blue); color: #fff; border-radius: 4px; cursor: pointer; font-size: 12px; }
.delete-btn { background: var(--red-alert) !important; }
#close-cart { cursor: pointer; float: right; font-size: 18px; color: var(--red-alert); }
#cart-total { font-weight: bold; font-size: 16px; margin-top: 10px; text-align: right; color: var(--mid-blue); }
.checkout-btn { display: block; width: 100%; padding: 12px; background: var(--primary-blue); color: #fff; border: none; border-radius: 6px; font-size: 16px; margin-top: 20px; cursor: pointer; }
.slider { width: 100%; position: relative; overflow: hidden; margin-bottom: 20px; }
.slider::before { content: ""; display: block; padding-top: 40%; }
.slides { display: flex; position: absolute; top: 0; left: 0; width: 100%; height: 100%; transition: transform 0.5s ease-in-out; }
.slides img { width: 100%; height: 100%; object-fit: cover; flex-shrink: 0; }
.slider-btn { position: absolute; top: 50%; transform: translateY(-50%); background: rgba(0,0,0,0.5); color: #fff; border: none; padding: 10px; cursor: pointer; font-size: 18px; border-radius: 50%; z-index: 10; }
.prev { left: 15px; }
.next { right: 15px; }
@media (max-width: 768px) {
   .header .logo img {
    width: 160px;   /* or whatever size fits best */
    height: auto;
    display: block;
    margin: 0 auto;
  }
}
.content-area { max-width: 1200px; margin: 0 auto; padding: 0 15px; }
.section-title { text-align: center; font-size: 24px; font-weight: bold; margin: 30px 0 20px; color: var(--mid-blue); }

/* --- PERFECTED PRODUCT GRID AND CARD STYLES --- */
.product-grid { 
    display: grid; 
    grid-template-columns: repeat(auto-fit, minmax(130px, 1fr)); 
    gap: 12px; 
}
.product-card { 
    background: #fff; 
    border-radius: 10px;
    /* Updated shadow for a cleaner, more professional look */
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
    display: flex; 
    flex-direction: column; 
    overflow: hidden; 
    transition: transform 0.3s, box-shadow 0.3s; 
}
.product-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 8px 16px rgba(0,0,0,0.15);
}

/* 1. Image Wrapper: Ensures a fixed 1:1 square aspect ratio */
.product-card-image-wrapper { 
    display: block;
    position: relative;
    width: 100%;
    padding-top: 100%; /* Creates a perfect square (1:1 aspect ratio) */
    overflow: hidden;
    background-color: #f8f8f8;
}

/* 2. Image Itself: Fills the fixed container, ensuring the whole image is visible */
.product-card img { 
    position: absolute; 
    top: 0;
    left: 0;
    width: 100%;
    height: 100%; 
    object-fit: contain; /* Ensures the whole image is visible (letter-boxing if needed) */
    padding: 5px; /* Small padding inside the box */
    box-sizing: border-box; 
    transition: transform 0.3s ease;
}
.product-card:hover img {
    transform: scale(1.03); /* Subtle hover effect */
}

/* 3. Product Content Styling (Name only) */
.product-card-content { 
    padding: 8px 5px 0 5px; 
    flex-grow: 1; 
    text-align: center; 
    min-height: 40px; 
    display: flex; /* Centers the title vertically if space allows */
    align-items: center;
    justify-content: center;
}

.product-card-content a {
    display: block;
}

.product-card-content h4 {
    margin: 5px; 
    font-size: 14px; 
    font-weight: 600;
    color: var(--text-color); 
    line-height: 2;
    height: 3.4em; /* Enforce 2 lines max */
    display: -webkit-box;
    -webkit-line-clamp: 2; 
    -webkit-box-orient: vertical;
    overflow: hidden;
    text-overflow: ellipsis;
    padding-bottom: 5px;
}

/* 4. Product Description REMOVED/Hidden */
.product-card-content p {
    display: none; 
}

/* 5. Product Footer/Button Styling */
.product-footer { 
    display: flex; 
    flex-direction: column; 
    justify-content: center;
    align-items: center; 
    padding: 0; 
    border-top: none; 
}

.price { 
    font-weight: bold; 
    color: var(--mid-blue); /* More professional price color */
    font-size: 16px; 
    margin: 5px 0 8px 0; 
}

.add-btn { 
    background: #001F3F; /* Using the accent color */
    color: #fff; 
    border: none; 
    padding: 10px 10px; 
    border-radius: 0 0 10px 10px; /* Aligns with card's border radius */
    cursor: pointer; 
    font-size: 15px; 
    font-weight: 600;
    width: 100%; 
    text-align: center;
    transition: background 0.2s;
}
.add-btn:hover {
    background: #e67e22; /* Slightly darker orange on hover */
}
.coming-soon-btn { 
    background: #001F3F; 
    cursor: default; 
    color: #666;
}

@media(max-width: 600px){ 
    .product-grid { grid-template-columns: repeat(auto-fit, minmax(120px, 1fr)); gap: 8px; } 
    .product-card-content h4 { font-size: 13px; }
    .price { font-size: 15px; margin: 3px 0 8px 0; }
    .add-btn { padding: 8px 8px; font-size: 14px; }
}
@media(max-width: 350px){ 
    .product-grid { grid-template-columns: 1fr 1fr; } 
}

/* Other media queries */
.footer { background: var(--dark-blue); color: #fff; padding: 30px 20px; text-align: center; margin-top: 40px; }
.social-icons{position:fixed;top:50%;right:12px;transform:translateY(-50%);display:flex;flex-direction:column;gap:10px;z-index:999;}
.social-icons a{width:42px;height:42px;border-radius:50%;display:flex;align-items:center;justify-content:center;color:#fff;font-size:20px;}
.social-icons a.facebook{background:#3b5998;}
.social-icons a.twitter{background:#1da1f2;}
.social-icons a.whatsapp{background:#25d366;}
@media(max-width: 768px){ .social-icons { right: 15px; top: auto; bottom: 70px; transform: none; flex-direction: row; gap: 10px; } }
.bottom-nav { position: fixed; bottom: 0; left: 0; width: 100%; background: #fff; box-shadow: 0 -2px 5px rgba(0,0,0,0.1); display: flex; justify-content: space-around; padding: 5px 0; z-index: 1100; }
.bottom-nav a { display: flex; flex-direction: column; align-items: center; font-size: 12px; color: #666; padding: 5px; flex: 1; }
.bottom-nav a i { font-size: 22px; margin-bottom: 3px; }
.bottom-nav a.cart-link { position: relative; }
.bottom-nav a.cart-link .cart-badge { background: var(--red-alert); color: #fff; border-radius: 50%; padding: 2px 6px; position: absolute; top: -5px; right: 10px; font-size: 10px; line-height: 1; }
@media(min-width: 769px){ .bottom-nav { display: none; } }
</style>
</head>
<?php if (isset($_GET['success']) && $_GET['success'] == 1): ?>
<script>
    alert("🎉 Order placed successfully!");
</script>
<?php endif; ?>

<body>

<div class="utility-bar">
    <p>আমাদের যে কোন পণ্য অর্ডার করতে কল বা WhatsApp করুন:</p>
    <p><a href="tel:+8801342866580">+8801342866580
    </a></p>
</div>

<div class="full-header-desktop">
    <div class="logo"><img src="upload/777.png" alt="Logo"></div>
    <div class="brand">MARIYAM FASHION HOUSE</div>
    <div class="icons">
        <?php if ($logged_in_user): ?>
            <a href="users/dashboard.php" class="user-link">
                <i class="fa fa-user"></i><?= $display_username ?>
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
    <form method="GET" class="header-search-form" id="desktop-search-form">
        <input type="text" name="search" id="desktop-search-input" placeholder="Search products..." value="<?= htmlspecialchars($searchQuery) ?>" onkeyup="if(event.key === 'Enter') this.form.submit();">
        <select name="filter" onchange="this.form.submit()">
            <option value="">All Categories</option>
            <?php foreach($categoriesList as $cat): ?>
                <option value="<?= $cat['id'] ?>" <?= (string)$filter === (string)$cat['id'] ? "selected" : "" ?>><?= htmlspecialchars($cat['name']) ?></option>
            <?php endforeach; ?>
        </select>
        <button type="submit">Search</button>
    </form>
</div>

<div class="main-header">
    <i class="fa fa-bars menu-icon" onclick="openMobileMenu()"></i>
    
    <div class="logo-brand">
        <div class="logo"><img src="upload/777.png" alt="Logo"></div>
    </div>
    
    <div class="icons">
        <a class="search-icon" onclick="toggleHeaderSearch(this, 'mobile')"><i class="fa fa-search"></i></a>
        <a onclick="openCart()">
            <i class="fa fa-shopping-bag"></i> 
            <span class="cart-badge" id="cart-count-mobile"><?= $cart_count ?></span>
        </a>
    </div>
    <form method="GET" class="header-search-form" id="mobile-search-form">
        <input type="text" name="search" id="mobile-search-input" placeholder="Search products..." value="<?= htmlspecialchars($searchQuery) ?>" onkeyup="if(event.key === 'Enter') this.form.submit();">
        <select name="filter" onchange="this.form.submit()">
            <option value="">All Categories</option>
            <?php foreach($categoriesList as $cat): ?>
                <option value="<?= $cat['id'] ?>" <?= (string)$filter === (string)$cat['id'] ? "selected" : "" ?>><?= htmlspecialchars($cat['name']) ?></option>
            <?php endforeach; ?>
        </select>
        <button type="submit">Search</button>
    </form>
</div>

<div class="desktop-nav">
    <a href="index.php" class="active">Home</a>
    <a href="all_products.php">Products</a>
    <a href="category.php">Category</a>
    <a href="shop.php">Shop</a>
    <a href="about.php">About</a>
    <a href="contact.php">Contact</a>
</div>

<div id="mobile-menu" class="mobile-menu-overlay">
    <span class="closebtn" onclick="closeMobileMenu()">&times;</span>
    <div class="mobile-menu-overlay-content">
        <a href="index.php" onclick="closeMobileMenu()">Home</a>
        <a href="all_products.php" onclick="closeMobileMenu()">Products</a>
        <a href="category.php" onclick="closeMobileMenu()">Category</a>
        <a href="shop.php" onclick="closeMobileMenu()">Shop</a>
        <a href="about.php" onclick="closeMobileMenu()">About</a>
        <a href="contact.php" onclick="closeMobileMenu()">Contact</a>

        <?php if ($logged_in_user): ?>
            <a href="users/dashboard.php" onclick="closeMobileMenu()">
                <i class="fa fa-user"></i> <?= htmlspecialchars($display_username) ?>
            </a>
            <a href="logout.php" onclick="closeMobileMenu()">
                <i class="fa fa-sign-out-alt"></i> Sign Out
            </a>
        <?php else: ?>
            <a href="login.php" onclick="closeMobileMenu()">
                <i class="fa fa-user"></i> Account
            </a>
        <?php endif; ?>
    </div>
</div>

<div id="side-cart">
    <span id="close-cart" onclick="closeCart()">✖</span>
    <h3>Your Cart</h3>
    <div id="cart-items">
        <?php 
        $total = 0; 
        if(!empty($_SESSION['cart'])): 
            foreach($_SESSION['cart'] as $id => $qty):
                $stmt = $pdo->prepare("
                    SELECT p.*, 
                            (SELECT image_path FROM product_images WHERE product_id=p.id LIMIT 1) AS first_image 
                    FROM products p WHERE id=?
                ");
                $stmt->execute([$id]);
                $p = $stmt->fetch();
                if(!$p) continue;

                $imagePath = !empty($p['first_image']) ? "admin/" . $p['first_image'] : "admin/uploads/default.png";
                $subtotal = $p['price'] * $qty;
                $total += $subtotal;
        ?>
        <div class="cart-item" data-id="<?= $id ?>" data-price="<?= $p['price'] ?>">
            <img src="<?= htmlspecialchars($imagePath) ?>" alt="<?= htmlspecialchars($p['name']) ?>">
            <div class="details">
                <strong><?= htmlspecialchars($p['name']) ?></strong><br>
                ৳<?= $p['price'] ?> x <span class="qty"><?= $qty ?></span> = ৳<span class="subtotal"><?= $subtotal ?></span>
                <div class="qty-controls">
                    <button onclick="updateQty(<?= $id ?>,-1)">-</button>
                    <button onclick="updateQty(<?= $id ?>,1)">+</button>
                    <button class="delete-btn" onclick="removeItem(<?= $id ?>)">✖</button>
                </div>
            </div>
        </div>
        <?php endforeach; 
        else: ?>
        <p>Your cart is empty.</p>
        <?php endif; ?>
    </div>
    <div id="cart-total">Total: ৳<span id="cart-total-amount"><?= $total ?></span></div>
    <a href="checkout.php" class="checkout-btn">Order Now</a>
</div>


<div class="slider">
    <div class="slides" id="slides">
        <?php foreach ($sliderImages as $slide): ?>
            <img src="<?= 'admin/' . htmlspecialchars($slide['image_url']) ?>" alt="Slide">
        <?php endforeach; ?>
    </div>
    <button class="slider-btn prev" onclick="moveSlide(-1)">❮</button>
    <button class="slider-btn next" onclick="moveSlide(1)">❯</button>
</div>

<div class="content-area">
    <?php if ($searchQuery || $filter): ?>
        <h2 class="section-title">Search Results (<?= htmlspecialchars($searchQuery ?: 'Filtered') ?>)</h2>
    <?php else: ?>
        <h2 class="section-title">All Products</h2>
    <?php endif; ?>
    <div class="product-grid">
        <?php if (empty($allProducts)): ?>
            <p style="grid-column: 1 / -1; text-align: center; color: var(--red-alert); font-size: 1.2em;">No products found matching your search or filter criteria.</p>
        <?php endif; ?>

        <?php foreach ($allProducts as $product): ?>
            <div class="product-card">
                <a href="product_detail.php?id=<?= $product['id'] ?>" class="product-card-image-wrapper">
                    <?php 
                    $imagePath = !empty($product['first_image']) 
                        ? "admin/" . $product['first_image'] 
                        : "admin/uploads/default.png"; 
                    ?>
                    <img src="<?= htmlspecialchars($imagePath) ?>" alt="Product Image">
                </a>
                <div class="product-card-content">
                    <a href="product_detail.php?id=<?= $product['id'] ?>">
                        <h4><?= htmlspecialchars($product['name']) ?></h4>
                        </a>
                </div>
                <div class="product-footer">
                    <div class="price">৳<?= htmlspecialchars($product['price']) ?></div>
                    <button class="add-btn" onclick="addToCart(<?= $product['id'] ?>)">Quick Add</button>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
    
    <br>

    <?php if (!$searchQuery && !$filter): // Only show Recent Arrivals if no search is active ?>
        <h2 class="section-title">Recent Arrivals</h2>
        <div class="product-grid">
            <?php foreach ($recentProducts as $product): ?>
                <div class="product-card">
                    <a href="product_detail.php?id=<?= $product['id'] ?>" class="product-card-image-wrapper">
                        <?php 
                        $imagePath = !empty($product['first_image']) 
                            ? "admin/" . $product['first_image'] 
                            : "admin/uploads/default.png"; 
                        ?>
                        <img src="<?= htmlspecialchars($imagePath) ?>" alt="Product Image">
                    </a>
                    <div class="product-card-content">
                        <a href="product_detail.php?id=<?= $product['id'] ?>">
                            <h4><?= htmlspecialchars($product['name']) ?></h4>
                             </a>
                    </div>
                    <div class="product-footer">
                        <div class="price">৳<?= htmlspecialchars($product['price']) ?></div>
                        <button class="add-btn" onclick="addToCart(<?= $product['id'] ?>)">Quick Add</button>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        <br>
    <?php endif; ?>

    <h2 class="section-title">Upcoming Products</h2>
    <div class="product-grid">
        <?php foreach ($upcomingProducts as $product): ?>
            <div class="product-card">
                <a href="#" class="product-card-image-wrapper">
                    <?php
                    $upcomingImage = !empty($product['photo']) 
                        ? "admin/" . $product['photo'] 
                        : "admin/uploads/upcoming/default.png";
                    ?>
                    <img src="<?= htmlspecialchars($upcomingImage) ?>" alt="<?= htmlspecialchars($product['name']) ?>">
                </a>
                <div class="product-card-content">
                    <h4><?= htmlspecialchars($product['name']) ?></h4>
                     </div>
                <div class="product-footer">
                    <div class="price">৳<?= htmlspecialchars($product['expected_price']) ?></div>
                    <button class="add-btn coming-soon-btn">Coming Soon</button>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

</div> 
<div class="footer">
    <h3>Contact Us</h3>
    <p>📍 Purbachal 300 feet,Dhaka</p>
    <p>📞 +880 1342-866580</p>
    <p>📧 mariyamfashionhouse@gmail.com</p>
</div>


<div class="social-icons">
    <a href="https://facebook.com/purbachalmariyamfashionhouse" class="facebook" target="_blank"><i class="fab fa-facebook-f"></i></a>
    <a href="https://twitter.com" class="twitter" target="_blank"><i class="fab fa-x-twitter"></i></a>
    <a href="https://wa.me/+8801710100657" class="whatsapp" target="_blank"><i class="fab fa-whatsapp"></i></a>
</div>


<div class="bottom-nav">
    <a href="index.php" class="active"><i class="fa fa-home"></i> Home</a>
    <a href="all_products.php"><i class="fa fa-th"></i> All Products</a>
    <a onclick="openCart()" class="cart-link">
        <i class="fa fa-shopping-bag"></i> 
        <span class="cart-badge" id="bottom-cart-count"><?= $cart_count ?></span> Cart
    </a>
    <a href="contact.php"><i class="fa fa-phone"></i> Contact</a>
</div>

<script>
// --- Search Toggling Functionality ---
function toggleHeaderSearch(iconElement, view) {
    let formId = view === 'mobile' ? 'mobile-search-form' : 'desktop-search-form';
    let inputId = view === 'mobile' ? 'mobile-search-input' : 'desktop-search-input';
    const form = document.getElementById(formId);
    const input = document.getElementById(inputId);

    if (form.classList.contains('active')) {
        form.classList.remove('active');
        
        if (input.value.trim() === '' && (document.location.search.includes('search=') || document.location.search.includes('filter='))) {
            window.location.href = 'index.php';
        }
        
    } else {
        form.classList.add('active');
        const urlParams = new URLSearchParams(window.location.search);
        const currentSearch = urlParams.get('search') || '';
        input.value = currentSearch;

        setTimeout(() => input.focus(), 50);
    }
}

// --- Menu and Slider Functions ---
function openMobileMenu() { document.getElementById('mobile-menu').style.width = '250px'; }
function closeMobileMenu() { document.getElementById('mobile-menu').style.width = '0'; }

let currentIndex = 0;
const slides = document.querySelectorAll('#slides img');

function showSlide(index) {
    if (slides.length === 0) return;
    if (index >= slides.length) currentIndex = 0;
    else if (index < 0) currentIndex = slides.length - 1;
    else currentIndex = index;

    document.getElementById('slides').style.transform = 'translateX(' + (-currentIndex * 100) + '%)';
}

function moveSlide(step) {
    showSlide(currentIndex + step);
}

if (slides.length > 1) {
    showSlide(0); 
    setInterval(() => moveSlide(1), 4000);
}


// --- Cart Functions ---
function openCart(){document.getElementById('side-cart').classList.add('active');}
function closeCart(){document.getElementById('side-cart').classList.remove('active');}

function updateCartCount(count){
    const countElements = document.querySelectorAll('[id^="cart-count-"], #bottom-cart-count');
    countElements.forEach(el => el.innerText = count);
}

function updateCartTotal(){
    let total = 0;
    document.querySelectorAll('#cart-items .cart-item').forEach(item=>{
        let price = parseFloat(item.dataset.price); 
        
        if (isNaN(price)) {
            const detailsHtml = item.querySelector('.details').innerHTML;
            const priceTextMatch = detailsHtml.split('<br>')[0].match(/৳(\d+)/); 
            price = priceTextMatch ? parseFloat(priceTextMatch[1]) : 0;
        }
        
        let qty = parseInt(item.querySelector('.qty').innerText);
        let subtotal = price * qty;
        
        item.querySelector('.subtotal').innerText = subtotal.toFixed(0); 
        total += subtotal;
    });
    document.getElementById('cart-total-amount').innerText = total.toFixed(0);
}

function addToCart(productId){
    fetch('cart_action.php',{
        method:'POST',
        headers:{'Content-Type':'application/x-www-form-urlencoded'},
        body:'action=add&id='+productId
    })
    .then(res=>res.json())
    .then(data=>{
        if(data.status==='success'){
            updateCartCount(data.cart_count);

            let existing = document.querySelector(`#cart-items .cart-item[data-id='${productId}']`);
            if(existing){
                let qtySpan = existing.querySelector('.qty');
                qtySpan.innerText = parseInt(qtySpan.innerText) + 1;
            } else {
                const cartItems = document.getElementById('cart-items');
                const emptyMessage = cartItems.querySelector('p');
                if (emptyMessage && emptyMessage.innerText === 'Your cart is empty.') {
                    emptyMessage.remove();
                }

                const unitPrice = data.product.price || 0;

                const itemDiv = document.createElement('div');
                itemDiv.classList.add('cart-item');
                itemDiv.dataset.id = productId;
                itemDiv.dataset.price = unitPrice; 
                
                itemDiv.innerHTML = `
                    <img src="${data.product.image || 'admin/uploads/default.png'}" alt="">
                    <div class="details">
                        <strong>${data.product.name || 'Product'}</strong><br>
                        ৳${unitPrice} x <span class="qty">1</span> = ৳<span class="subtotal">${unitPrice}</span>
                        <div class="qty-controls">
                            <button onclick="updateQty(${productId},-1)">-</button>
                            <button onclick="updateQty(${productId},1)">+</button>
                            <button class="delete-btn" onclick="removeItem(${productId})">✖</button>
                        </div>
                    </div>
                `;
                cartItems.append(itemDiv); 
            }

            openCart();
            updateCartTotal(); 
        } else if (data.message) {
             alert(data.message);
        }
    });
}

function updateQty(productId,change){
    fetch('cart_action.php',{
        method:'POST',
        headers:{'Content-Type':'application/x-www-form-urlencoded'},
        body:'action=update&id='+productId+'&change='+change
    }).then(res=>res.json()).then(data=>{
        if(data.status==='success'){
            updateCartCount(data.cart_count);
            let item = document.querySelector(`#cart-items .cart-item[data-id='${productId}']`);
            
            if(data.qty <= 0){ 
                item.remove(); 
                if (document.querySelectorAll('#cart-items .cart-item').length === 0) {
                    const cartItems = document.getElementById('cart-items');
                    cartItems.innerHTML = '<p>Your cart is empty.</p>';
                }
            }
            else { item.querySelector('.qty').innerText = data.qty; }
            
            updateCartTotal(); 
        }
    });
}

function removeItem(productId){
    fetch('cart_action.php',{
        method:'POST',
        headers:{'Content-Type':'application/x-www-form-urlencoded'},
        body:'action=remove&id='+productId
    }).then(res=>res.json()).then(data=>{
        if(data.status==='success'){
            updateCartCount(data.cart_count);
            let item = document.querySelector(`#cart-items .cart-item[data-id='${productId}']`);
            if(item) item.remove();
            
            if (document.querySelectorAll('#cart-items .cart-item').length === 0) {
                const cartItems = document.getElementById('cart-items');
                cartItems.innerHTML = '<p>Your cart is empty.</p>';
            }
            
            updateCartTotal(); 
        }
    });
}

</script>
</body>
</html>