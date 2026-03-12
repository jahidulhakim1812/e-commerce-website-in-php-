<?php
// C:\xampp\htdocs\mariyam_fashion\shop.php

// ====================================================================
// 1. PHP LOGIC (Includes config.php to establish $pdo and session)
// ====================================================================

// This line includes config.php, which defines $pdo
include 'config.php'; 

// FIX: Declare $pdo as global to ensure it is accessible in the main script scope.
global $pdo;

// Fetch categories (Uses the $pdo object now guaranteed to be available)
$categoriesList = $pdo->query("SELECT * FROM categories ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);

// Handle search + category + price filters
$searchQuery = isset($_GET['search']) ? trim($_GET['search']) : '';
$filter = isset($_GET['filter']) ? $_GET['filter'] : '';
$minPrice = isset($_GET['min_price']) ? (int)$_GET['min_price'] : 0;
$maxPrice = isset($_GET['max_price']) ? (int)$_GET['max_price'] : 50000;
// Ensure filters are sane
$maxAllowedPrice = 50000;
$minPrice = max(0, min($minPrice, $maxAllowedPrice));
$maxPrice = max(0, min($maxPrice, $maxAllowedPrice));
if ($minPrice > $maxPrice) {
    $temp = $minPrice; $minPrice = $maxPrice; $maxPrice = $temp;
}


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

$where .= " AND p.price BETWEEN :minPrice AND :maxPrice";
$params['minPrice'] = $minPrice;
$params['maxPrice'] = $maxPrice;

// Fetch products
$productsStmt = $pdo->prepare("
    SELECT p.*, c.name AS category_name, 
            (SELECT image_path FROM product_images WHERE product_id=p.id LIMIT 1) AS first_image
    FROM products p
    LEFT JOIN categories c ON p.category_id = c.id
    WHERE $where
    ORDER BY p.created_at DESC
");
$productsStmt->execute($params);
$products = $productsStmt->fetchAll(PDO::FETCH_ASSOC);

// Cart count & items (re-fetch items in PHP for initial cart population)
$cart_items = [];
$cart_count = 0;
if(isset($_SESSION['cart']) && !empty($_SESSION['cart'])){
    $ids = array_keys($_SESSION['cart']);
    // Handle empty array case for IN clause
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
            // Uses a default image if none is found
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
$max_range_limit = $maxAllowedPrice; // Use maxAllowedPrice for consistency
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Shop - Mariyam Fashion</title>
<!-- Favicon -->
<link rel="icon" type="image/png" href="upload/favicon.png">

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<style>
/* ===== Color Palette and Reset (Variables for cleaner code) ===== */
:root {
    --primary-blue: #0074D9;
    --dark-blue: #001F3F;
    --mid-blue: #003366;
    --light-blue: #00BFFF;
    --background-color: #f4f8fc;
    --text-color: #333;
    --red-alert: red;
}
body{margin:0;font-family:'Segoe UI',sans-serif;background:var(--background-color);color:var(--text-color);padding-bottom: 0;}
a{text-decoration:none;color:inherit;}


/* ==================================== */
/* ===== DESKTOP HEADER & NAV (Shared Styles) ===== */
/* ==================================== */
.full-header-desktop {
    background: linear-gradient(to right, var(--dark-blue), var(--primary-blue));
    color: #fff;
    padding: 8px 20px;
    display: none; /* Hidden by default, shown on desktop */
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
}
.full-header-desktop .logo img { max-height: 55px; }
.full-header-desktop .brand { 
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

.desktop-nav {
    background: var(--mid-blue);
    display: none; /* Hidden by default, shown on desktop */
    justify-content: center;
    align-items: center;
    flex-wrap: wrap;
}
.desktop-nav a { color: #fff; margin: 10px 18px; font-size: 15px; font-weight: 500; }
.desktop-nav a:hover, .desktop-nav a.active { color: var(--light-blue); }

/* ================================== */
/* ===== MOBILE HEADER & NAV (New/Refactored) ===== */
/* ================================== */

/* Mobile Top Header */
.main-header {
    background: linear-gradient(to right, var(--dark-blue), var(--primary-blue));
    padding: 10px 15px;
    display: none; /* Hidden by default, shown on mobile */
    align-items: center;
    justify-content: space-between;
    position: sticky;
    top: 0;
    z-index: 1000;
    height: 60px;
    height:60px;
}
.main-header .menu-icon { font-size: 24px; cursor: pointer; flex-shrink: 0; color: #fff; }
.main-header .logo-brand { 
    display: flex; 
    flex-direction: column; 
    justify-content: center; 
    align-items: center; 
    flex-grow: 1; /* Allow it to take up available space */
    /* Add a small negative margin to pull it perfectly center between the icons */
    margin-right: 10px; 
}
.main-header .logo img { 
    max-height: 55px; /* Increased from 50px */
    width: auto; 
    filter: brightness(0) invert(1); 
}
.main-header .brand { 
    font-size: 12px; /* Slightly smaller text */
    font-weight: bold; 
    color: #fff; 
    margin-top: 2px; /* Reduced top margin */
}
.main-header .icons { display: flex; gap: 15px; flex-shrink: 0; }
.main-header .icons a { color: #fff; font-size: 20px; position: relative; }
.main-header .icons a .cart-badge {
    background: var(--red-alert); color: #fff; border-radius: 50%; padding: 2px 6px; 
    position: absolute; top: -8px; right: -12px; font-size: 10px; line-height: 1;
}
/* NEW: Filter icon style */
.main-header .icons .filter-icon { color: #fff; font-size: 20px; cursor: pointer; }


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
    box-shadow: 0 -2px 5px rgba(0,0,0,0.1); display: none; /* Hidden by default, shown on mobile */
    justify-content: space-around; 
    padding: 5px 0; z-index: 1100;
}
.bottom-nav a { display: flex; flex-direction: column; align-items: center; font-size: 12px; color: #666; padding: 5px; flex: 1; }
.bottom-nav a i { font-size: 22px; margin-bottom: 3px; }
.bottom-nav a.active, .bottom-nav a:hover { color: var(--primary-blue); }
.bottom-nav a.cart-link { position: relative; }
.bottom-nav a.cart-link .cart-badge {
    background: var(--red-alert); color: #fff; border-radius: 50%; padding: 2px 6px; 
    position: absolute; top: -5px; right: 10px; font-size: 10px; line-height: 1;
}


/* ================================== */
/* ===== FILTERS & PRODUCTS LAYOUT (General) ===== */
/* ================================== */
.oval-box{background:#fff;border-radius:20px;padding:25px;margin:30px auto;max-width:1200px;box-shadow:0 4px 12px rgba(0,0,0,0.08); display:flex; gap:20px; align-items:flex-start;}
.section-title{text-align:center;font-size:28px;font-weight:bold;margin-bottom:20px;color:var(--mid-blue); border-bottom: 2px solid var(--primary-blue); padding-bottom: 5px;}
.product-box{display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:20px;}

/* --- MODIFIED PRODUCT CARD STYLES --- */
.product{
    background:#fff;
    padding:10px; /* Reduced padding */
    border-radius:12px; /* Rounded corners */
    /* Updated Box Shadow: A softer shadow for a cleaner look */
    box-shadow:0 4px 14px rgba(0,0,0,0.1); 
    display:flex;
    flex-direction:column;
    transition:0.3s; 
    cursor:pointer; 
    position: relative;
    /* Removed the explicit product-footer */
    padding-bottom: 0; 
}
/* Make the internal link cover the main product content (image, text, price) */
.product > a { 
    display: block; 
    flex-grow: 1;
    color: inherit;
    padding-bottom: 10px; /* Add space above the Quick Add button */
}
.product:hover{transform:translateY(-5px);}
.product img{width:100%;height:200px;object-fit:cover;border-radius:8px;}
.product h4{margin:12px 0 6px;font-size:17px;color:#222; text-align: center;}
.product p{font-size:14px;color:#666;margin-bottom:10px;height:38px;overflow:hidden; line-height: 1.3;}

/* NEW: The price and Quick Add are no longer in a product-footer div. They are styled directly */
/* Remove the old product-footer styling */
.product-footer{display:none;} 

.price{
    font-weight:bold;
    color:var(--primary-blue);
    font-size:16px;
    text-align: center; /* Center the price */
    margin-bottom: 10px; /* Space above the button */
}

/* Updated Add Button to match the full-width 'Quick Add' style */
.add-btn{
    background:var(--dark-blue); /* Dark Blue background */
    color:#fff;
    border:none;
    padding:10px 0; /* Full-width vertical padding */
    border-radius:0 0 12px 12px; /* Only bottom corners rounded to match product card */
    cursor:pointer;
    font-size:16px;
    font-weight: bold; /* Bold text for 'Quick Add' */
    width: 100%;
    /* Positioned at the very bottom */
    margin-top: auto; 
    align-self: flex-end;
}
.add-btn:hover{background:#e67e22;} /* Changed hover to primary-blue */
/* --- END MODIFIED PRODUCT CARD STYLES --- */


/* Filters (Desktop only) */
#filters{
    max-width:280px; flex-shrink:0; background:#fff; padding:20px; 
    border-radius:12px; box-shadow:0 4px 14px rgba(0,0,0,0.1); 
    height:max-content; min-width: 250px;
}
#filters h3{color:var(--primary-blue);margin-bottom:15px;}
#filters label{font-weight: 500;}
#filters select, #filters input[type=range], #filters input[type=text]{width:100%;margin-top:8px;padding:8px;border-radius:6px;border:1px solid #ccc; box-sizing: border-box;}
#filters input[type=text] { background: var(--background-color); border-color: var(--primary-blue); font-weight: bold; cursor: default; }
#filters .price-range{display:flex;justify-content:space-between;margin-top:5px;}
#filters .price-range input{width:48%;text-align:center;}


/* NEW: Mobile Slide-out Filter */
#mobile-filters-slide {
    position: fixed;
    top: 0;
    left: -100%; /* Start off-screen */
    width: 85%;
    max-width: 300px;
    height: 100%;
    background: #fff;
    box-shadow: 3px 0 10px rgba(0,0,0,0.3);
    transition: left 0.3s ease;
    z-index: 2000;
    padding: 20px;
    overflow-y: auto;
    display: none; /* Hide by default, shown by media query/JS */
}
#mobile-filters-slide.active {
    left: 0;
}
#mobile-filters-slide .close-filter-btn {
    float: right;
    font-size: 24px;
    color: var(--red-alert);
    cursor: pointer;
}


/* Footer */
.footer{background:var(--dark-blue);color:#fff;padding:30px 20px;text-align:center;margin-top:40px;}
.footer h3{margin-bottom:12px;font-size:20px;color:var(--light-blue);}

/* Floating Social */
.social-icons{position:fixed;top:50%;right:12px;transform:translateY(-50%);display:flex;flex-direction:column;gap:10px;z-index:999;}
.social-icons a{width:42px;height:42px;border-radius:50%;display:flex;align-items:center;justify-content:center;color:#fff;font-size:20px;}
.social-icons a.facebook{background:#3b5998;}
.social-icons a.twitter{background:#1da1f2;}
.social-icons a.whatsapp{background:#25d366;}

/* Side Cart */
#side-cart {
    position: fixed;
    top: 0;
    /* Corrected to -100% and set narrower width for partial display */
    right: -100%; 
    
    /* Set to 90% or 85% for the half-slide effect */
    width: 90%; 
    max-width: 350px; /* Limits the cart size on larger screens */
    
    height: 100%;
    background: #fff;
    box-shadow: -3px 0 10px rgba(0,0,0,0.3);
    transition: right 0.3s ease;
    z-index: 2000;
    padding: 20px;
    overflow-y: auto;
}
#side-cart.active { 
    right: 0; 
}

/* Fine-tune for the smallest screens */
@media (max-width: 400px) {
    #side-cart {
        width: 85%;
    }
}
#side-cart h3 { margin-top: 0; font-size: 20px; color: var(--mid-blue); }
.cart-item{display:flex;align-items:center;gap:10px;margin:15px 0;border-bottom:1px solid #eee;padding-bottom:10px;}
.cart-item img{width:60px;height:60px;object-fit:cover;border-radius:6px;}
.cart-item .details{flex:1;}
.qty-controls{display:flex;align-items:center;gap:6px;margin-top:5px;}
.qty-controls button{padding:2px 6px;border:none;background:var(--primary-blue);color:#fff;border-radius:4px;cursor:pointer;font-size:12px;}
.qty-controls button:hover{background:#005fa3;}
.delete-btn{background:red !important;}
#close-cart{cursor:pointer;float:right;font-size:18px;color:red;}
#cart-total{font-weight:bold;font-size:16px;margin-top:10px;text-align:right;color:var(--mid-blue);}
.checkout-btn{display:block;width:100%;padding:12px;background:var(--primary-blue);color:#fff;border:none;border-radius:6px;font-size:16px;margin-top:20px;cursor:pointer;}
.checkout-btn:hover{background:#005fa3;}


/* ================================== */
/* ===== MEDIA QUERIES (Responsiveness) ===== */
/* ================================== */

/* Desktop & Tablet (769px and up) */
@media(min-width: 769px) {
    .full-header-desktop { display: flex; }
    .desktop-nav { display: flex; }
    .main-header, .mobile-menu-overlay, .bottom-nav { display: none !important; }
    .oval-box { margin-top: 40px; }
    body { padding-bottom: 0; }
}

/* Tablet & Mobile (768px and below) */
@media(max-width: 768px) {
    /* Show mobile header/nav, hide desktop */
    .main-header, .bottom-nav { display: flex; }
    .full-header-desktop, .desktop-nav { display: none !important; }
    body { padding-bottom: 60px; } 

    /* Main Container layout change */
    .oval-box { 
        flex-direction: column; 
        padding: 15px 10px;
        margin: 15px auto;
    }

    /* Filters: HIDE STATIC FILTERS FOR MOBILE */
    #filters { display: none; }
    #mobile-filters-slide { display: block; } /* Show slide filter container */

    /* Product Grid: 2 columns (User Request) */
    .product-box { 
        grid-template-columns: repeat(2, 1fr); /* Enforce 2 columns */
        gap: 15px;
    }

    /* Product Card Optimization for Mobile */
    .product{padding: 8px 8px 0 8px;} /* Adjust padding for mobile to allow button to be flush */
    .product > a { padding-bottom: 8px; }
    .product img{height:140px;} 
    .product h4{font-size: 15px; margin: 8px 0 4px;} 
    .product p{font-size: 12px; height: 32px; margin-bottom: 4px; line-height: 1.3;} 
    /* The product-footer is now display:none so these can be removed */
    /* .product-footer{margin-top: 5px; padding-top: 5px;}
    .price{font-size: 14px;} */
    .add-btn{padding: 8px 0; font-size: 14px; border-radius: 0 0 8px 8px;} /* Adjust mobile button style */
}

/* Keep 2 columns even on small mobiles (as per explicit request for mobile view) */
@media(max-width: 480px) {
    .product-box { 
        grid-template-columns: repeat(2, 1fr);
    }
    .product img{height: 120px;}
    .social-icons { right: 5px; }
}
</style>
</head>
<body>

<div class="full-header-desktop">
    <div class="logo"><a href="index.php"><img src="upload/777.png" alt="Logo"></a></div>
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

<div class="desktop-nav">
    <a href="index.php">Home</a>
    <a href="all_products.php">Products</a>
    <a href="category.php">Category</a>
    <a href="shop.php" class="active">Shop</a>
    <a href="about.php">About</a>
    <a href="contact.php">Contact</a>
</div>

<div class="main-header">
    <i class="fa fa-bars menu-icon" onclick="openMobileMenu()"></i>
    
    <div class="logo-brand">
        <div class="logo"><img src="upload/777.png" alt="Logo"></div>
    </div>
    
    <div class="icons">
                <i class="fa fa-filter filter-icon" onclick="openMobileFilters()"></i> 
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


<div id="side-cart">
    <span id="close-cart" onclick="closeCart()">✖</span>
    <h3>Your Cart</h3>
    <div id="cart-items"></div>
    <div id="cart-total">Total: ৳<span id="cart-total-amount">0</span></div>
    <a href="checkout.php" class="checkout-btn">Order Now</a>
</div>


<div id="mobile-filters-slide">
    <span class="close-filter-btn" onclick="closeMobileFilters()">✖</span>
    <h3>Filter Products</h3>
        <div id="mobile-filter-content">
        <label><b>Category</b></label>
        <select id="categoryFilterMobile">
            <option value="">All Categories</option>
            <?php foreach($categoriesList as $cat): ?>
                <option value="<?= $cat['id'] ?>" <?= ($filter == $cat['id']) ? "selected" : "" ?>>
                    <?= htmlspecialchars($cat['name']) ?>
                </option>
            <?php endforeach; ?>
        </select>

        <label style="margin-top:15px; display:block;"><b>Price Range</b></label>
        <input type="range" id="minPriceMobile" min="0" max="<?= $max_range_limit ?>" step="100" value="<?= $minPrice ?>" oninput="updatePriceRangeVisuals('mobile');" onchange="syncMobileToDesktopPrices();">
        <input type="range" id="maxPriceMobile" min="0" max="<?= $max_range_limit ?>" step="100" value="<?= $maxPrice ?>" oninput="updatePriceRangeVisuals('mobile');" onchange="syncMobileToDesktopPrices();">
        <div class="price-range">
            <input type="text" id="minPriceTextMobile" value="৳<?= $minPrice ?>" readonly>
            <input type="text" id="maxPriceTextMobile" value="৳<?= $maxPrice ?>" readonly>
        </div>
        <button class="add-btn" style="width: 100%; margin-top: 15px;" onclick="applyFilters(true)">Apply Filters</button>
    </div>
</div>


<div class="oval-box">
        <div id="filters">
        <h3>Filters</h3>

        <label><b>Category</b></label>
        <select id="categoryFilter" onchange="applyFilters()">
            <option value="">All Categories</option>
            <?php foreach($categoriesList as $cat): ?>
                <option value="<?= $cat['id'] ?>" <?= ($filter == $cat['id']) ? "selected" : "" ?>>
                    <?= htmlspecialchars($cat['name']) ?>
                </option>
            <?php endforeach; ?>
        </select>

        <label style="margin-top:15px; display:block;"><b>Price Range</b></label>
        <input type="range" id="minPrice" min="0" max="<?= $max_range_limit ?>" step="100" value="<?= $minPrice ?>" oninput="updatePriceRangeVisuals();" onchange="syncDesktopToMobilePrices();">
        <input type="range" id="maxPrice" min="0" max="<?= $max_range_limit ?>" step="100" value="<?= $maxPrice ?>" oninput="updatePriceRangeVisuals();" onchange="syncDesktopToMobilePrices();">
        <div class="price-range">
            <input type="text" id="minPriceText" value="৳<?= $minPrice ?>" readonly>
            <input type="text" id="maxPriceText" value="৳<?= $maxPrice ?>" readonly>
        </div>
        <button class="add-btn" style="width: 100%; margin-top: 15px;" onclick="applyFilters()">Apply Filters</button>
    </div>
        <div style="flex:1;">
        <h2 class="section-title">🛍 Shop All Products</h2>
        <div class="product-box">
            <?php if (count($products) > 0): ?>
                <?php foreach ($products as $product): ?>
                    <div class="product">
                        <a href="product_detail.php?id=<?= $product['id'] ?>">
                            <?php $imagePath = !empty($product['first_image']) ? "admin/" . $product['first_image'] : "admin/uploads/default.png"; ?>
                            <img src="<?= htmlspecialchars($imagePath) ?>" alt="<?= htmlspecialchars($product['name']) ?>">
                            <h4><?= htmlspecialchars($product['name']) ?></h4>
                            <div class="price">৳<?= htmlspecialchars($product['price']) ?></div>
                        </a>
                        <button class="add-btn" onclick="addToCart(<?= $product['id'] ?>)">Quick Add</button>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p style="text-align: center; width: 100%; margin-top: 30px;">No products found matching your filters.</p>
            <?php endif; ?>
        </div>
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
    <a href="index.php">
        <i class="fa fa-home"></i> <span>Home</span>
    </a>
    <a href="all_products.php">
        <i class="fa fa-th-large"></i> <span>Products</span>
    </a>
    <a onclick="openCart()" class="cart-link">
        <i class="fa fa-shopping-bag"></i> <span>Cart</span>
        <span class="cart-badge" id="cart-count-bottom"><?= $cart_count ?></span>
    </a>
    <a href="contact.php">
        <i class="fa fa-phone"></i> <span>Contact</span>
    </a>
</div>

<script>
// Initialize cart from PHP session data
let cartMemory = <?= $cart_items_json ?>;

function updateAllCartCounts(){
    const count = Object.values(cartMemory).reduce((sum,i)=>sum+i.qty,0);
    const desktop = document.getElementById('cart-count-desktop');
    const mobile = document.getElementById('cart-count-mobile');
    const bottom = document.getElementById('cart-count-bottom');
    if(desktop) desktop.innerText = count;
    if(mobile) mobile.innerText = count;
    if(bottom) bottom.innerText = count;
}

function openCart(){document.getElementById('side-cart').classList.add('active'); loadCartItems();}
function closeCart(){document.getElementById('side-cart').classList.remove('active');}

function openMobileMenu() { 
    document.getElementById('mobile-menu').style.width = '250px'; 
}
function closeMobileMenu() { 
    document.getElementById('mobile-menu').style.width = '0'; 
}

// NEW: Mobile Filter functions
function openMobileFilters() {
    // Before opening, ensure mobile filter reflects current applied desktop/URL filters
    document.getElementById('categoryFilterMobile').value = document.getElementById('categoryFilter').value;
    document.getElementById('minPriceMobile').value = document.getElementById('minPrice').value;
    document.getElementById('maxPriceMobile').value = document.getElementById('maxPrice').value;
    updatePriceRangeVisuals('mobile'); // Ensure texts are updated

    document.getElementById('mobile-filters-slide').classList.add('active');
}
function closeMobileFilters() {
    document.getElementById('mobile-filters-slide').classList.remove('active');
}


function addToCart(id){
    fetch('cart_action.php',{
        method:'POST',
        headers:{'Content-Type':'application/x-www-form-urlencoded'},
        body:'action=add&id='+id
    }).then(res=>res.json()).then(data=>{
        if(data.status==='success'){
            // Corrected to use 'image' property if available, but falls back to 'first_image' for initial product fetch
            if(data.product && !data.product.image) {
                // Ensure the path matches the PHP logic for images fetched from DB
                const imagePath = data.product.first_image ? "admin/" + data.product.first_image : "admin/uploads/default.png";
                data.product.image = imagePath;
            }

            if(cartMemory[id]){
                cartMemory[id].qty += 1;
            } else {
                cartMemory[id] = {...data.product, qty: 1};
            }
            
            updateAllCartCounts();
            openCart(); 
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
            if(data.qty <= 0) {
                delete cartMemory[id];
            } else { 
                cartMemory[id].qty = data.qty; 
            }
            updateAllCartCounts();
            loadCartItems(); 
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
            loadCartItems(); 
        }
    });
}

function loadCartItems(){
    const container=document.getElementById('cart-items'); 
    container.innerHTML='';
    const items=Object.values(cartMemory);
    let total=0;

    if(items.length===0){ 
        container.innerHTML='<p>Your cart is empty.</p>'; 
        document.getElementById('cart-total-amount').innerText = 0;
        return; 
    }
    
    items.forEach(item=>{
        const itemPrice = parseFloat(item.price); // Ensure price is a number
        const itemTotal=itemPrice * item.qty;
        total+=itemTotal;
        const div=document.createElement('div'); 
        div.className='cart-item'; 
        div.dataset.id=item.id;
        div.innerHTML=`
            <img src="${item.image}" alt="${item.name}">
            <div class="details">
                <strong>${item.name}</strong><br>
                ৳${itemPrice.toFixed(2)} x <span class="qty">${item.qty}</span> = ৳<span class="subtotal">${itemTotal.toFixed(2)}</span>
                <div class="qty-controls">
                    <button onclick="updateQty(${item.id},-1)">-</button>
                    <button onclick="updateQty(${item.id},1)">+</button>
                    <button class="delete-btn" onclick="removeItem(${item.id})">✖</button>
                </div>
            </div>`;
        container.appendChild(div);
    });
    
    document.getElementById('cart-total-amount').innerText = total.toFixed(2);
}


// ===== Filters =====

/**
 * Ensures the price slider visuals (text inputs) match the range slider values, 
 * and handles the min/max value swap to prevent crossover.
 * @param {string} type - 'mobile' or default (desktop)
 */
function updatePriceRangeVisuals(type=null){
    let minId, maxId, minTextId, maxTextId;

    if (type === 'mobile') {
        minId = 'minPriceMobile'; maxId = 'maxPriceMobile';
        minTextId = 'minPriceTextMobile'; maxTextId = 'maxPriceTextMobile';
    } else {
        minId = 'minPrice'; maxId = 'maxPrice';
        minTextId = 'minPriceText'; maxTextId = 'maxPriceText';
    }

    let minRange = document.getElementById(minId);
    let maxRange = document.getElementById(maxId);
    
    if (!minRange || !maxRange) return;

    let minVal = parseInt(minRange.value);
    let maxVal = parseInt(maxRange.value);

    // Prevent min from passing max and vice versa (BUG FIX: This logic was not enforced on input type='range' itself)
    if (minVal > maxVal) {
        // Swap values in the range inputs to prevent crossover
        minRange.value = maxVal;
        maxRange.value = minVal;
        
        // Re-read swapped values for display
        minVal = parseInt(minRange.value);
        maxVal = parseInt(maxRange.value);
    }

    document.getElementById(minTextId).value = `৳${minVal}`;
    document.getElementById(maxTextId).value = `৳${maxVal}`;
}

/**
 * Syncs the desktop filter values to the mobile filter values.
 */
function syncDesktopToMobilePrices() {
    const minD = document.getElementById('minPrice').value;
    const maxD = document.getElementById('maxPrice').value;
    
    document.getElementById('minPriceMobile').value = minD;
    document.getElementById('maxPriceMobile').value = maxD;
    updatePriceRangeVisuals('mobile');
}

/**
 * Syncs the mobile filter values to the desktop filter values.
 */
function syncMobileToDesktopPrices() {
    const minM = document.getElementById('minPriceMobile').value;
    const maxM = document.getElementById('maxPriceMobile').value;
    
    document.getElementById('minPrice').value = minM;
    document.getElementById('maxPrice').value = maxM;
    updatePriceRangeVisuals(false);
}


function applyFilters(isMobile=false){
    // Retrieve search query from PHP variable (assuming there's a search input on the page not shown, or it uses a separate component)
    let search = "<?= htmlspecialchars($searchQuery) ?>"; 
    
    let category, minPrice, maxPrice;

    // Use the *current* state of the filters, prioritizing mobile if active
    if (isMobile) {
        category = document.getElementById("categoryFilterMobile").value;
        minPrice = document.getElementById("minPriceMobile").value;
        maxPrice = document.getElementById("maxPriceMobile").value;
        closeMobileFilters(); // Close filter after applying on mobile
    } else {
        category = document.getElementById("categoryFilter").value;
        minPrice = document.getElementById("minPrice").value;
        maxPrice = document.getElementById("maxPrice").value;
    }

    // IMPORTANT: Ensure the URL uses the corrected (possibly swapped) min/max values 
    // from the range inputs after updatePriceRangeVisuals has run.
    const minVal = Math.min(parseInt(minPrice), parseInt(maxPrice));
    const maxVal = Math.max(parseInt(minPrice), parseInt(maxPrice));

    
    let url = `shop.php?search=${encodeURIComponent(search)}&filter=${category}&min_price=${minVal}&max_price=${maxVal}`;
    window.location.href = url;
}


document.addEventListener('DOMContentLoaded', () => {
    updateAllCartCounts();
    loadCartItems(); 
    
    // Initialize price text inputs with current range from PHP (for both desktop and mobile sliders)
    // Run this twice to ensure both sets of visuals are initialized correctly with the PHP values
    updatePriceRangeVisuals(false); 
    updatePriceRangeVisuals(true);
});
</script>

</body>
</html>