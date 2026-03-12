<?php
// C:\xampp\htdocs\mariyam_fashion\index.php

// ====================================================================
// 1. PHP LOGIC (Includes config.php to establish $pdo and session)
// ====================================================================

// This line includes config.php, which defines the database connection $pdo.
include 'config.php'; 

// CRUCIAL FIX: Declares $pdo as global to ensure it's accessible in the main script scope.
global $pdo;

// ====================================
// LIVE SEARCH LOGIC (AJAX ENDPOINT)
// ====================================

// Check if this is a live search request (query parameter is present)
if (isset($_GET['query'])) {
    header('Content-Type: application/json');

    $query = isset($_GET['query']) ? trim($_GET['query']) : '';

    if (empty($query) || strlen($query) < 3) {
        echo json_encode([]);
        exit;
    }

    $searchTerm = "%$query%";

    try {
        // Fetch products matching the search term
        $stmt = $pdo->prepare("
            SELECT 
                p.id, 
                p.name, 
                p.price,
                (SELECT image_path FROM product_images WHERE product_id=p.id LIMIT 1) AS first_image
            FROM 
                products p
            WHERE 
                p.name LIKE :search OR p.description LIKE :search
            LIMIT 7
        ");

        $stmt->bindParam(':search', $searchTerm, PDO::PARAM_STR);
        $stmt->execute();
        $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode($products);
        
    } catch (PDOException $e) {
        error_log("Live search query failed: " . $e->getMessage());
        echo json_encode([]);
    }
    exit; // Terminate script after sending JSON response
}


// ====================================
// MAIN PAGE LOGIC (Normal Page Load)
// ====================================

// Fetch categories
$categoriesList = $pdo->query("SELECT * FROM categories ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);

// Handle search + filter
$searchQuery = isset($_GET['search']) ? trim($_GET['search']) : '';
$filter = isset($_GET['filter']) ? $_GET['filter'] : '';

$where = "1=1";
$params = [];
if ($searchQuery) {
    $where .= " AND (p.name LIKE :search OR p.description LIKE :search)";
    $params['search'] = "%$searchQuery%";
}
if ($filter) {
    $where .= " AND p.category_id = :filter";
    $params['filter'] = $filter;
}

// Pagination setup
$limit = 12;
$page = isset($_GET['page']) ? max(1,intval($_GET['page'])) : 1;
$offset = ($page - 1) * $limit;

// Count total products
$countStmt = $pdo->prepare("SELECT COUNT(*) FROM products p WHERE $where");
$countStmt->execute($params);
$totalProducts = $countStmt->fetchColumn();
$totalPages = ceil($totalProducts / $limit);

// Fetch products
$productsStmt = $pdo->prepare("
    SELECT p.*, c.name AS category_name,
            (SELECT image_path FROM product_images WHERE product_id=p.id LIMIT 1) AS first_image
    FROM products p
    LEFT JOIN categories c ON p.category_id = c.id
    WHERE $where
    ORDER BY p.created_at DESC
    LIMIT $limit OFFSET $offset
");

$productsStmt->execute($params);
$products = $productsStmt->fetchAll(PDO::FETCH_ASSOC);

// Prepare cart items for JS
$cart_items = [];
if(isset($_SESSION['cart']) && !empty($_SESSION['cart']))
    foreach($_SESSION['cart'] as $id => $qty){
        $stmt = $pdo->prepare("
    SELECT p.id, p.name, p.price, 
            (SELECT image_path FROM product_images WHERE product_id=p.id LIMIT 1) AS first_image
    FROM products p
    WHERE p.id=?
");
$stmt->execute([$id]);
$prod = $stmt->fetch(PDO::FETCH_ASSOC);
if($prod){
    $cart_items[$id] = [
        'id'=>$prod['id'],
        'name'=>$prod['name'],
        'price'=>$prod['price'],
        'image'=> !empty($prod['first_image']) ? "admin/".$prod['first_image'] : "admin/uploads/default.png",
        'qty'=>$qty
    ];
}

}
$cart_count = array_sum(array_column($cart_items, 'qty'));
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>All Products - Mariyan Fashion</title>
<!-- Favicon -->
<link rel="icon" type="image/png" href="upload/favicon.png">

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<style>
/* ===== Color Palette and Reset (From new style) ===== */
:root {
    --primary-blue: #0074D9; /* Bright Blue */
    --dark-blue: #001F3F; /* Dark Blue */
    --mid-blue: #003366; /* Medium Blue */
    --light-blue: #00BFFF; /* Light Blue/Cyan */
    --background-color: #f4f8fc; /* Light Gray/Blue background */
    --text-color: #333;
    --red-alert: #e74c3c; /* A professional red */
    --accent-orange: #ff8a3d; /* The Quick Add button orange */
}

body {
    margin: 0;
    font-family: 'Segoe UI', sans-serif;
    background: var(--background-color);
    color: var(--text-color);
    padding-bottom: 60px; /* Space for fixed bottom nav on mobile */
}
a {
    text-decoration: none;
    color: inherit;
}

/* ==================================== */
/* ===== DESKTOP STYLES (Updated) ===== */
/* ==================================== */
.full-header-desktop {
    background: linear-gradient(to right, var(--dark-blue), var(--primary-blue));
    color: #fff;
    padding: 8px 20px;
    display: none; /* Hidden by default, shown on desktop */
    align-items: center;
    justify-content: space-between; 
    position: relative; 
    min-height: 60px; 
}

/* Header Left Group (Logo only) */
.header-left-group {
    display: flex;
    align-items: center;
}

.full-header-desktop .logo img { max-height: 55px; }

/* Website Name/Brand Center Positioning (Keep same) */
.header-brand-center {
    position: absolute; 
    left: 50%;
    transform: translateX(-50%); 
    font-size: 24px; 
    font-weight: bold; 
    color: #fff;
    white-space: nowrap;
}

.full-header-desktop .icons a { color: #fff; margin-left: 15px; font-size: 16px; position: relative; cursor: pointer; }
.full-header-desktop .icons a span { 
    background: var(--red-alert); color: #fff; border-radius: 50%; padding: 2px 6px; 
    position: absolute; top:-8px; right:-12px; font-size: 12px; 
}

/* Desktop Navigation Bar - Links centered, Search on right */
.desktop-nav {
    background: var(--mid-blue);
    display: none; 
    /* Use space-between to push links to center and search to right */
    justify-content: space-between; 
    align-items: center;
    padding: 0 20px; 
    position: relative; 
}

/* Navigation Links - Centered */
.desktop-nav-links {
    display: flex;
    align-items: center;
    flex-grow: 1;
    justify-content: center; /* Main Change: Center the navigation links */
    /* Remove padding buffers added previously */
    padding-left: 0; 
    padding-right: 0;
}

.desktop-nav a { 
    color: #fff; 
    padding: 10px 18px; 
    font-size: 15px; 
    font-weight: 500; 
    white-space: nowrap; 
}
.desktop-nav a:hover, .desktop-nav a.active { color: var(--light-blue); }

/* Desktop Search Bar style - Pushed back to the far right */
.desktop-search-form {
    display: flex;
    max-width: 100px; /* Increased size slightly from the last version */
    position: absolute; /* Changed from absolute back to relative/default flow */
    /* Remove positioning for central fix */
    left: none; 
    transform: none; 
    top: auto;
    /* Ensure it aligns to the right */
    flex-shrink: 0; 
}
.desktop-search-form input[type="text"] {
    padding: 8px 10px; 
    border: none;
    border-radius: 5px 0 0 5px; 
    font-size: 15px;
    /* --- CHANGE THIS LINE --- */
    width: 250px; /* or 300px, or whatever value fits your design */
    /* ------------------------ */
}
.desktop-search-form button {
    padding: 10px 15px; /* Reverted padding */
    border: none;
    background: var(--primary-blue); 
    color: #fff;
    border-radius: 0 5px 5px 0;
    cursor: pointer;
    font-size: 16px; /* Reverted font size */
    transition: background 0.2s;
}
.desktop-search-form button:hover {
    background: var(--mid-blue); 
}

@media(min-width: 769px) {
    .full-header-desktop { display: flex; }
    .desktop-nav { display: flex; }
    /* --- Hiding mobile components on desktop --- */
    .main-header, .bottom-nav { display: none !important; } 
    body { padding-bottom: 0; }
}


/* ================================== */
/* ===== MOBILE STYLES (Keep Same) ===== */
/* ================================== */

/* Main Header (Mobile Top Bar) */
.main-header {
    background: linear-gradient(to right, var(--dark-blue), var(--primary-blue));
    padding: 10px 15px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    position: sticky;
    top: 0;
    z-index: 1000;
    height: 60px;
}
.main-header .menu-icon { font-size: 24px; cursor: pointer; flex-shrink: 0; color: #fff; }
.main-header .logo-brand { display: flex; flex-direction: column; justify-content: center; align-items: center; flex-grow: 1; }
.main-header .logo img { max-height: 50px; width: auto; filter: brightness(0) invert(1); }
.main-header .brand { font-size: 14px; font-weight: bold; color: #fff; margin-top: 5px; }
.main-header .icons { display: flex; gap: 15px; flex-shrink: 0; }
.main-header .icons a { color: #fff; font-size: 20px; position: relative; }
.main-header .icons a .cart-badge {
    background: var(--red-alert); color: #fff; border-radius: 50%; padding: 2px 6px; 
    position: absolute; top: -8px; right: -12px; font-size: 10px; line-height: 1;
}

/* Search Modal Overlay (for mobile search icon) */
#search-modal {
    position: fixed; top: 0; left: 0; width: 100%; height: 100%;
    background-color: rgba(0,0,0,0.9); z-index: 3000;
    display: none; /* Hidden by default */
    align-items: flex-start;
    justify-content: center;
    padding-top: 20px;
}
#search-modal-content {
    width: 90%;
    max-width: 600px;
    background: #fff;
    padding: 15px;
    border-radius: 8px;
    position: relative;
    box-shadow: 0 5px 15px rgba(0,0,0,0.5);
}
#search-modal-content form {
    display: flex;
    gap: 8px;
    position: relative; /* Crucial for positioning the live results */
}
#search-modal-content input[type="text"] {
    flex-grow: 1;
    padding: 10px;
    border-radius: 6px;
    border: 1px solid var(--primary-blue);
    font-size: 16px;
}
#search-modal-content button {
    background: var(--primary-blue);
    color: #fff;
    border: none;
    padding: 10px 15px;
    border-radius: 6px;
    cursor: pointer;
    flex-shrink: 0;
    font-size: 16px;
    transition: background 0.2s;
}
#search-modal-content button:hover {
    background: var(--mid-blue); /* Darker blue on hover */
}
#search-modal .close-search-btn {
    position: absolute;
    top: 5px;
    right: 15px;
    font-size: 28px;
    color: #333;
    cursor: pointer;
    line-height: 1;
}


/* Mobile Menu Overlay */
.mobile-menu-overlay {
    height: 100%; width: 0; position: fixed; z-index: 1010; top: 0; left: 0; 
    background-color: var(--dark-blue); overflow-x: hidden; transition: 0.3s; padding-top: 60px;
}
.mobile-menu-overlay-content { position: relative; width: 100%; text-align: center; margin-top: 30px; }
.mobile-menu-overlay a { padding: 15px; font-size: 20px; color: #fff; display: block; transition: 0.3s; border-bottom: 1px solid rgba(255, 255, 255, 0.1); }
.mobile-menu-overlay a:hover, .mobile-menu-overlay a:focus { color: var(--light-blue); background-color: var(--mid-blue); }
.mobile-menu-overlay .closebtn { position: absolute; top: 20px; right: 35px; font-size: 40px; color: #fff; cursor: pointer; }


/* Bottom Navigation (Mobile Only) */
.bottom-nav {
    position: fixed; bottom: 0; left: 0; width: 100%; background: #fff; 
    box-shadow: 0 -2px 5px rgba(0,0,0,0.1); display: flex; justify-content: space-around; 
    padding: 5px 0; z-index: 1100;
}
.bottom-nav a { 
    display: flex; flex-direction: column; align-items: center; font-size: 12px; color: #666; 
    padding: 5px; flex: 1; 
}
.bottom-nav a i { font-size: 22px; margin-bottom: 3px; }

.bottom-nav a.active i, .bottom-nav a:hover i { color: var(--primary-blue); } 
.bottom-nav a.active { color: var(--primary-blue); }

.bottom-nav a.cart-link { position: relative; }
.bottom-nav a.cart-link .cart-badge {
    background: var(--red-alert); color: #fff; border-radius: 50%; padding: 2px 6px; 
    position: absolute; top: -5px; right: 10px; font-size: 10px; line-height: 1;
}

/* ==================================== */
/* ===== LIVE SEARCH RESULTS STYLES (Keep Same) ===== */
/* ==================================== */

.live-results-list {
    position: absolute;
    top: 100%; 
    left: 0;
    /* Increase Width: Makes the box wider than the search bar */
    width: 350px; /* Increased from 100% (desktop search is 250px input + button) */
    /* Increase Max Height: Shows more results before scrolling */
    max-height: 450px; /* Increased from 300px */
    overflow-y: auto;
    background: #fff;
    border: 1px solid #ddd;
    border-top: none;
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
    z-index: 2000; 
    border-radius: 0 0 5px 5px;
    padding: 0;
    margin: 0;
    list-style: none;
    text-align: left;
    display: none; 
}
/* Styling for live results in the mobile modal */
#search-modal-content .live-results-list {
    /* Set to 100% of the modal content width minus padding */
    width: calc(100% - 30px); 
    top: 70px; /* Position below the search form */
    left: 15px; /* Align with padding of modal content */
    border-radius: 6px;
    margin-top: 10px;
    border: 1px solid #ddd;
    /* Apply the larger max-height here as well for mobile */
    max-height: 450px; 
}

.live-result-item a {
    display: flex;
    align-items: center;
    padding: 10px;
    transition: background 0.1s;
    color: var(--text-color);
}

.live-result-item a:hover {
    background: var(--background-color);
    color: var(--primary-blue);
}

.live-result-item img {
    width: 40px;
    height: 40px;
    object-fit: contain;
    margin-right: 10px;
    border: 1px solid #eee;
    border-radius: 3px;
    flex-shrink: 0;
}

.result-details {
    flex-grow: 1;
}

.result-details strong {
    display: block;
    font-size: 14px;
    line-height: 1.2;
    overflow: hidden;
    white-space: nowrap;
    text-overflow: ellipsis;
}

.result-details span {
    font-size: 13px;
    color: var(--red-alert);
    font-weight: 600;
}
/* ==================================== */
/* ===== NEW PROFESSIONAL PRODUCT CARD STYLES (Keep Same) ===== */
/* ==================================== */
.container{padding:40px 20px;text-align:center;}
@media(max-width: 768px){ .container{padding:20px 10px;} }

.section-title{
    font-size:28px;margin-bottom:25px;color:var(--primary-blue);border-bottom:2px solid var(--primary-blue);
    display:inline-block;padding-bottom:6px;
}
@media(max-width: 768px){ .section-title{font-size:24px;margin-bottom:15px;} }


/* Product Grid (Responsive) */
.product-grid { 
    display: grid; 
    grid-template-columns: repeat(auto-fit, minmax(130px, 1fr)); 
    gap: 12px; 
    justify-content: center; /* Center the grid items if there aren't enough to fill the row */
}
.product-card { 
    background: #fff; 
    border-radius: 10px;
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
    display: flex; 
    flex-direction: column; 
    overflow: hidden; 
    transition: transform 0.3s, box-shadow 0.3s; 
    cursor: pointer;
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
    padding-top: 100%; /* Creates a perfect 1:1 square aspect ratio */
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
    display: flex; 
    align-items: center;
    justify-content: center;
}

.product-card-content a {
    display: block;
}

.product-card-content h4 {
    margin: 0; 
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

/* Description/Category (REMOVED/Hidden for the streamlined card look) */
.product-card-content p, .product-card-content small {
    display: none; 
}


/* 4. Product Footer/Button Styling */
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
    color: var(--mid-blue); 
    font-size: 16px; 
    margin: 5px 0 8px 0; 
}

/* Note: The Quick Add button now uses --accent-orange */
.add-btn { 
    background: #001F3F; 
    color: #fff; 
    border: none; 
    padding: 10px 10px; 
    border-radius: 0 0 10px 10px; /* Aligns with card's border radius */
    cursor: pointer; 
    font-size: 15px; 
    font-weight: 600;
    width: 100%; /* Makes the button full width */
    text-align: center;
    transition: background 0.2s;
}
.add-btn:hover {
    background: #e67e22; /* Slightly darker orange on hover */
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

/* Pagination & Footer (Keep as is) */
.pagination{display:flex;justify-content:center;margin-top:30px;gap:8px;flex-wrap:wrap;}
.pagination a{padding:8px 14px;background:var(--primary-blue);color:#fff;border-radius:6px;text-decoration:none;}
.pagination a.active{background:#005fa3;font-weight:bold;}
.pagination a:hover{background:#005fa3;}

.footer{background:var(--dark-blue);color:#fff;padding:50px 20px;text-align:center;margin-top:50px;}
.footer h3{margin-bottom:20px;font-size:22px;color:var(--light-blue);}

/* Side Cart (Keep as is) */
#side-cart {
    position: fixed; top: 0; right: -100%; width: 90%; max-width: 350px; 
    height: 100%; background: #fff; box-shadow: -3px 0 10px rgba(0,0,0,0.3); 
    transition: right 0.3s ease; z-index: 2000; padding: 20px; overflow-y: auto;
}
#side-cart.active { right: 0; }
@media (max-width: 400px) { #side-cart { width: 85%; } }
/* ... rest of the cart item styles ... */
#side-cart h3 { margin-top: 0; font-size: 20px; color: var(--mid-blue); }
.cart-item{display:flex;align-items:center;gap:10px;margin:15px 0;border-bottom:1px solid #eee;padding-bottom:10px;}
.cart-item img{width:60px;height:60px;object-fit:cover;border-radius:6px;}
.cart-item .details{flex:1;}
.qty-controls button{padding:2px 6px;border:none;background:var(--primary-blue);color:#fff;border-radius:4px;cursor:pointer;font-size:12px;}
.qty-controls button:hover{background:#005fa3;}
.delete-btn{background:var(--red-alert) !important;}
#close-cart{cursor:pointer;float:right;font-size:18px;color:var(--red-alert);}
.checkout-btn{display:block;width:100%;padding:12px;background:var(--primary-blue);color:#fff;border:none;border-radius:6px;font-size:16px;margin-top:20px;cursor:pointer;}
.checkout-btn:hover{background:#005fa3;}
</style>
</head>
<body>

<div class="full-header-desktop">
    <div class="header-left-group">
        <div class="logo"><img src="upload/777.png" alt="Logo"></div>
    </div>
    
    <div class="header-brand-center">
        MARIYAM FASHION HOUSE
    </div>
    
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

<div class="desktop-nav">
    <div class="desktop-nav-links">
        <a href="index.php">Home</a>
        <a href="all_products.php" class="active">Products</a>
        <a href="category.php">Category</a>
        <a href="shop.php">Shop</a>
        <a href="about.php">About</a>
        <a href="contact.php">Contact</a>
    </div>
    
    <form method="GET" class="desktop-search-form" id="desktop-search-form">
        <input type="text" name="search" id="desktop-search-input" placeholder="Search products..." value="<?= htmlspecialchars($searchQuery) ?>" oninput="fetchLiveResults(this.value, 'desktop-live-results')">
        <input type="hidden" name="filter" value="<?= htmlspecialchars($filter) ?>">
        <button type="submit"><i class="fa fa-search"></i></button>
        <ul id="desktop-live-results" class="live-results-list"></ul>
    </form>
</div>

<div class="main-header">
    <i class="fa fa-bars menu-icon" onclick="openMobileMenu()"></i>
    
    <div class="logo-brand">
        <div class="logo"><img src="upload/777.png" alt="Logo"></div>
    </div>
    
    <div class="icons">
        <a onclick="openSearchModal()">
            <i class="fa fa-search"></i>
        </a>
        <a onclick="openCart()">
            <i class="fa fa-shopping-bag"></i> 
            <span class="cart-badge" id="cart-count-mobile"><?= $cart_count ?></span>
        </a>
    </div>
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


<div id="search-modal">
    <div id="search-modal-content">
        <span class="close-search-btn" onclick="closeSearchModal()">&times;</span>
        <h3>Search</h3>
        <form method="GET">
            <input type="text" name="search" id="mobile-search-input" placeholder="Search products..." value="<?= htmlspecialchars($searchQuery) ?>" oninput="fetchLiveResults(this.value, 'mobile-live-results')">
            <select name="filter">
                <option value="">All Categories</option>
                <?php foreach($categoriesList as $cat): ?>
                    <option value="<?= $cat['id'] ?>" <?= ($filter==$cat['id'])?'selected':'' ?>><?= htmlspecialchars($cat['name']) ?></option>
                <?php endforeach; ?>
            </select>
            <button type="submit"><i class="fa fa-search"></i></button>
            <ul id="mobile-live-results" class="live-results-list"></ul>
        </form>
    </div>
</div>


<div class="container">
    <div class="section-title">🛍️ All Products</div>

    <div class="product-grid">
        <?php if($products): ?>
            <?php foreach($products as $product): ?>
                <div class="product-card" onclick="window.location='product_detail.php?id=<?= $product['id'] ?>'">
                    <a href="product_detail.php?id=<?= $product['id'] ?>" class="product-card-image-wrapper">
                        <img src="<?= !empty($product['first_image']) ? 'admin/'.$product['first_image'] : 'admin/uploads/default.png' ?>" alt="<?= htmlspecialchars($product['name']) ?>">
                    </a>

                    <div class="product-card-content">
                        <a href="product_detail.php?id=<?= $product['id'] ?>">
                            <h4><?= htmlspecialchars($product['name']) ?></h4>
                        </a>
                        </div>
                    
                    <div class="product-footer">
                        <div class="price">৳<?= htmlspecialchars($product['price']) ?></div>
                        <button class="add-btn" onclick="event.stopPropagation(); addToCart(<?= $product['id'] ?>)">Quick Add</button>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p>No products found.</p>
        <?php endif; ?>
    </div>

    <div class="pagination">
        <?php for($p=1;$p<=$totalPages;$p++): ?>
            <a href="?page=<?= $p ?>&search=<?= urlencode($searchQuery) ?>&filter=<?= urlencode($filter) ?>" class="<?= ($p==$page)?'active':'' ?>"><?= $p ?></a>
        <?php endfor; ?>
    </div>
</div>

<div class="footer">
    <h3>Contact Us</h3>
    <p>📍 Purbachal 300 feet,Dhaka</p>
    <p>📞 +880 1342-866580</p>
    <p>📧 mariyamfashionhouse@gmail.com</p>
</div>

<div id="side-cart">
    <span id="close-cart" onclick="closeCart()">✖</span>
    <h3>Your Cart</h3>
    <div id="cart-items"></div>
    <a href="checkout.php" class="checkout-btn">Order Now</a>
</div>

<div class="bottom-nav">
    <a href="index.php" class="active">
        <i class="fa fa-home"></i> <span>Home</span>
    </a>
    <a href="all_products.php">
        <i class="fa fa-th-large"></i> <span>All Products</span>
    </a>
    <a onclick="openCart()" class="cart-link">
        <i class="fa fa-lock"></i> <span>Cart</span>
        <span class="cart-badge" id="cart-count-bottom"><?= $cart_count ?></span>
    </a>
    <a href="contact.php">
        <i class="fa fa-phone"></i> <span>Contact</span>
    </a>
</div>

<script>
let cartMemory = <?= json_encode($cart_items) ?>;
let searchTimeout;
// Get the current filename for AJAX calls
const currentScript = '<?= basename($_SERVER['PHP_SELF']) ?>';


function updateAllCartCounts(){
    const count = Object.values(cartMemory).reduce((sum,i)=>sum+i.qty,0);
    document.getElementById('cart-count-desktop').innerText = count;
    document.getElementById('cart-count-mobile').innerText = count;
    document.getElementById('cart-count-bottom').innerText = count;
}

document.addEventListener('DOMContentLoaded', updateAllCartCounts);

function openCart(){document.getElementById('side-cart').classList.add('active'); loadCartItems();}
function closeCart(){document.getElementById('side-cart').classList.remove('active');}

function openMobileMenu() { document.getElementById('mobile-menu').style.width = '250px'; }
function closeMobileMenu() { document.getElementById('mobile-menu').style.width = '0'; }

// SEARCH MODAL FUNCTIONS
function openSearchModal() { 
    document.getElementById('search-modal').style.display = 'flex'; 
    // Clear results when modal opens
    document.getElementById('mobile-live-results').style.display = 'none';
    document.getElementById('mobile-live-results').innerHTML = '';
}
function closeSearchModal() { 
    document.getElementById('search-modal').style.display = 'none'; 
}

// ====================================
// NEW LIVE SEARCH LOGIC
// ====================================

function fetchLiveResults(query, resultsId) {
    clearTimeout(searchTimeout);
    const resultsContainer = document.getElementById(resultsId);

    if (query.length < 3) {
        resultsContainer.style.display = 'none';
        resultsContainer.innerHTML = '';
        return;
    }

    // Delay search request to prevent excessive server load while typing
    searchTimeout = setTimeout(() => {
        // Updated to call the current file itself
        fetch(currentScript + '?query=' + encodeURIComponent(query))
            .then(response => response.json())
            .then(data => {
                displayResults(data, resultsId);
            })
            .catch(error => {
                console.error('Live search error:', error);
                resultsContainer.style.display = 'none';
            });
    }, 300); // 300ms delay
}

function displayResults(products, resultsId) {
    const resultsContainer = document.getElementById(resultsId);
    resultsContainer.innerHTML = '';

    if (products.length > 0) {
        products.forEach(product => {
            const imagePath = product.first_image ? "admin/" + product.first_image : "admin/uploads/default.png";
            const listItem = document.createElement('li');
            listItem.className = 'live-result-item';
            listItem.innerHTML = `
                <a href="product_detail.php?id=${product.id}" onclick="closeSearchModal()">
                    <img src="${imagePath}" alt="${product.name}">
                    <div class="result-details">
                        <strong>${product.name}</strong>
                        <span>৳${product.price}</span>
                    </div>
                </a>
            `;
            resultsContainer.appendChild(listItem);
        });
        resultsContainer.style.display = 'block';
    } else {
        resultsContainer.innerHTML = '<li class="live-result-item" style="padding: 10px;">No products found.</li>';
        resultsContainer.style.display = 'block';
    }
}

// Hide live results when clicking outside (Desktop only)
document.addEventListener('click', function(event) {
    const desktopForm = document.getElementById('desktop-search-form');
    const desktopResults = document.getElementById('desktop-live-results');
    
    // Check if the click is outside the desktop search form area
    if (desktopForm && !desktopForm.contains(event.target)) {
        if(desktopResults) desktopResults.style.display = 'none';
    }
});
document.getElementById('desktop-search-input').addEventListener('focus', function() {
    // Show results if there are characters entered
    if (this.value.length >= 3) {
        document.getElementById('desktop-live-results').style.display = 'block';
    }
});


// CART FUNCTIONS
function addToCart(id){
    fetch('cart_action.php',{
        method:'POST',
        headers:{'Content-Type':'application/x-www-form-urlencoded'},
        body:'action=add&id='+id
    }).then(res=>res.json()).then(data=>{
        if(data.status==='success'){
            // 1. Update the client-side cart memory
            if(cartMemory[id]){
                cartMemory[id].qty += 1;
            } else if(data.product) {
                // Ensure the 'image' path is correctly formatted for the front-end display
                const imagePath = data.product.first_image ? "admin/" + data.product.first_image : "admin/uploads/default.png";
                cartMemory[id] = {...data.product, image: imagePath, qty: 1};
            }
            
            // 2. Update all cart badges (Desktop/Mobile/Bottom)
            updateAllCartCounts();
            
            // 3. CRUCIAL: Slide the cart open (you need the openCart function available)
            // Assuming openCart() function is defined globally:
            openCart(); 
            
            // 4. Load/refresh the items inside the now-open side cart
            loadCartItems();
        }
    });
}

function updateQty(id,change){
    fetch('cart_action.php',{
        method:'POST',
        headers:{'Content-Type':'application/x-www-form-urlencoded'},
        body:'action=update&id='+id+'&change='+change
    }).then(res=>res.json()).then(data=>{
        if(data.status==='success'){
            if(data.qty<=0) delete cartMemory[id]; else cartMemory[id].qty=data.qty;
            updateAllCartCounts();
            if(document.getElementById('side-cart').classList.contains('active')) loadCartItems();
        }
    });
}

function removeItem(id){
    fetch('cart_action.php',{
        method:'POST',
        headers:{'Content-Type':'application/x-www-form-urlencoded'},
        body:'action=remove&id='+id
    }).then(res=>res.json()).then(data=>{
        if(data.status==='success'){ 
            delete cartMemory[id];
            updateAllCartCounts();
            if(document.getElementById('side-cart').classList.contains('active')) loadCartItems();
        }
    });
}

function loadCartItems(){
    const container=document.getElementById('cart-items'); 
    container.innerHTML='';
    const items=Object.values(cartMemory);
    if(items.length===0){ 
        container.innerHTML='<p>Your cart is empty.</p>'; 
        return; 
    }
    let total=0;
    items.forEach(item=>{
        // Ensure price is treated as a float for accurate math
        const price = parseFloat(item.price); 
        const qty = parseInt(item.qty);
        
        const itemTotal = price * qty;
        total += itemTotal;
        
        const div=document.createElement('div'); 
        div.className='cart-item'; 
        div.dataset.id=item.id;
        div.innerHTML=`
            <img src="${item.image}" alt="${item.name}">
            <div class="details">
                <strong>${item.name}</strong><br>
                
                ৳${price.toFixed(2)} x ${qty} = ৳<span class="subtotal">${itemTotal.toFixed(2)}</span>
                
                <div class="qty-controls">
                    <button onclick="updateQty(${item.id},-1)">-</button>
                    <button onclick="updateQty(${item.id},1)">+</button>
                    <button class="delete-btn" onclick="removeItem(${item.id})">✖</button>
                </div>
            </div>`;
        container.appendChild(div);
    });
    const totalDiv=document.createElement('div');
    totalDiv.style.marginTop='10px';
    totalDiv.style.fontWeight='bold';
    totalDiv.style.fontSize='16px';
    totalDiv.style.textAlign='right';
    
    // Display Grand Total with two decimal places for accuracy
    totalDiv.innerText=`Total: ৳${total.toFixed(2)}`; 
    
    container.appendChild(totalDiv);
}
  
</script>
</body>
</html>