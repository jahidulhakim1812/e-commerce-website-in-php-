<?php
// C:\xampp\htdocs\mariyam_fashion\index.php

// ====================================================================
// 1. PHP LOGIC (Includes config.php to establish $pdo and session)
// ====================================================================

// This line includes config.php, which defines the database connection $pdo.
include 'config.php'; 

// CRUCIAL FIX: Declares $pdo as global to ensure it's accessible in the main script scope.
global $pdo;


/**
 * Function to generate Cart HTML and data (used for AJAX responses)
 * @param PDO $pdo Database connection object
 * @return array Contains 'html', 'total', and 'count'
 */
function generateCartHtml($pdo) {
    ob_start();
    $total = 0;
    $cartItemsExist = !empty($_SESSION['cart']);

    if($cartItemsExist) {
        $stmt = $pdo->prepare("SELECT p.id, p.name, p.price, (SELECT image_path FROM product_images WHERE product_id=p.id LIMIT 1) AS first_image FROM products p WHERE id=?");

        foreach($_SESSION['cart'] as $id => $qty) {
            $qty = max(1, intval($qty)); 
            $stmt->execute([$id]);
            $p = $stmt->fetch(PDO::FETCH_ASSOC);
            if(!$p) continue;
            
            $imagePath = !empty($p['first_image']) ? "admin/".$p['first_image'] : "admin/uploads/default.png";
            
            $subtotal = $p['price'] * $qty;
            $total += $subtotal;
            ?>
            <div class="cart-item" data-id="<?= $id ?>">
                <img src="<?= htmlspecialchars($imagePath) ?>" alt="<?= htmlspecialchars($p['name']) ?>">
                <div class="details">
                    <strong><?= htmlspecialchars($p['name']) ?></strong><br>
                    ৳<?= number_format($p['price'], 0) ?> x <span class="qty"><?= $qty ?></span> = ৳<span class="subtotal"><?= number_format($subtotal, 0) ?></span>
                    <div class="qty-controls">
                        <button onclick="updateQty(<?= $id ?>,-1)">-</button>
                        <button onclick="updateQty(<?= $id ?>,1)">+</button>
                        <button class="delete-btn" onclick="removeItem(<?= $id ?>)">✕</button>
                    </div>
                </div>
            </div>
            <?php
        }
    } else {
        echo "<p style='text-align:center; padding:20px;'>Your cart is empty.</p>";
    }
    
    return [
        'html' => ob_get_clean(),
        'total' => $total,
        'count' => $cartItemsExist ? array_sum($_SESSION['cart']) : 0
    ];
}


// ===================================
// ===== AJAX HANDLERS START HERE =====
// ===================================

// Handle AJAX Add to Cart
if(isset($_POST['ajax_add_cart']) && isset($_POST['product_id'])) {
    header('Content-Type: application/json');
    $product_id = intval($_POST['product_id']);
    $qty_to_add = intval($_POST['qty'] ?? 1);
    if(!isset($_SESSION['cart'])) $_SESSION['cart'] = [];

    $success = false;
    if($qty_to_add >= 1) {
        $stmt = $pdo->prepare("SELECT id FROM products WHERE id=?");
        $stmt->execute([$product_id]);
        if ($stmt->rowCount() > 0) {
            if(isset($_SESSION['cart'][$product_id])) {
                $_SESSION['cart'][$product_id] += $qty_to_add;
            } else {
                $_SESSION['cart'][$product_id] = $qty_to_add;
            }
            $success = true;
        }
    }

    $cartData = generateCartHtml($pdo);

    echo json_encode([
        'success'=>$success,
        'cart_count'=>$cartData['count'],
        'cart_html'=>$cartData['html'],
        'cart_total'=>round($cartData['total'], 2)
    ]);
    exit;
}

// Handle AJAX Update Quantity 
if(isset($_POST['ajax_update_qty']) && isset($_POST['product_id']) && isset($_POST['qty_change'])) {
    header('Content-Type: application/json');
    $id = intval($_POST['product_id']);
    $change = intval($_POST['qty_change']);
    $success = false;

    if(isset($_SESSION['cart'][$id])) {
        $new_qty = $_SESSION['cart'][$id] + $change;

        if($new_qty > 0) {
            $_SESSION['cart'][$id] = $new_qty;
            $success = true;
        } else {
            unset($_SESSION['cart'][$id]);
            $success = true;
        }
    }
    
    $cartData = generateCartHtml($pdo);

    echo json_encode([
        'success'=>$success,
        'cart_count'=>$cartData['count'],
        'cart_html'=>$cartData['html'],
        'cart_total'=>round($cartData['total'], 2)
    ]);
    exit;
}

// ===================================
// ===== MAIN PAGE LOGIC START HERE =====
// ===================================

if (!isset($_GET['id']) || empty($_GET['id'])) {
    header('Location: all_products.php'); 
    exit;
}
$product_id = intval($_GET['id']);

// 2. Fetch product details
$stmt = $pdo->prepare("SELECT p.*, c.name AS category_name FROM products p LEFT JOIN categories c ON p.category_id=c.id WHERE p.id=?");
$stmt->execute([$product_id]);
$product = $stmt->fetch(PDO::FETCH_ASSOC);
if(!$product) {
    http_response_code(404);
    die("Product not found.");
}

// 3. Fetch Product Images
$imageStmt = $pdo->prepare("SELECT image_path FROM product_images WHERE product_id=? ORDER BY id ASC");
$imageStmt->execute([$product_id]);
$productImages = $imageStmt->fetchAll(PDO::FETCH_COLUMN);
if(empty($productImages)) $productImages = ['upload/default.png']; 

// 4. Fetch Related Products
$relatedStmt = $pdo->prepare("
    SELECT p.*, (SELECT pi.image_path FROM product_images pi WHERE pi.product_id=p.id ORDER BY pi.id ASC LIMIT 1) AS first_image
    FROM products p WHERE p.category_id=? AND p.id!=? LIMIT 4
");
$relatedStmt->execute([$product['category_id'],$product_id]);
$relatedProducts = $relatedStmt->fetchAll(PDO::FETCH_ASSOC);

// 5. Fetch "You May Also Like" Products
$moreLike = $pdo->query("
    SELECT p.*, (SELECT pi.image_path FROM product_images pi WHERE pi.product_id=p.id ORDER BY pi.id ASC LIMIT 1) AS first_image
    FROM products p ORDER BY RAND() LIMIT 4
")->fetchAll(PDO::FETCH_ASSOC);

// 6. Calculate initial cart state
$cart_count = isset($_SESSION['cart']) ? array_sum($_SESSION['cart']) : 0;
$initialCartData = generateCartHtml($pdo);

// 7. Calculate discounted price for the main product
$price = $product['price'];
$discountedPrice = $price;
if (!empty($product['discount']) && is_numeric($product['discount']) && $product['discount'] > 0) {
    $discountedPrice = $price - ($price * $product['discount'] / 100);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= htmlspecialchars($product['name']) ?> | Mariyan Fashion</title>
<!-- Favicon -->
<link rel="icon" type="image/png" href="upload/favicon.png">

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<style>
/* ===== CSS STYLES (HEADER, FOOTER, CART, & DETAIL PAGE) ===== */
:root {
    --primary-blue: #0074D9; 
    --dark-blue: #001F3F; 
    --mid-blue: #003366; 
    --light-blue: #00BFFF; 
    --background-color: #f4f8fc; 
    --text-color: #333;
    --red-alert: #e63946; 
    --orange-cod: #FF851B; 
}
*{margin:0;padding:0;box-sizing:border-box}
body{
    font-family:'Segoe UI',sans-serif;
    background:var(--background-color);
    color:var(--text-color);
    line-height:1.6;
    padding-bottom: 60px; 
}
a{text-decoration:none;color:inherit}
img{display:block}

/* Utility Bar (Mobile Only) */
.utility-bar {
    background: var(--orange-cod); 
    color: #fff;
    padding: 8px 15px;
    font-size: 14px;
    display: flex;
    justify-content: center;
    align-items: center;
    flex-wrap: wrap;
    text-align: center;
}
.utility-bar p { margin: 0; display: flex; align-items: center; gap: 15px; }
.utility-bar a { color: #fff; margin-left: 5px; font-weight: bold; }
@media(min-width: 769px) { .utility-bar { display: none; } }

/* Main Header (Mobile-first design) */
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
.main-header .logo-brand {
    display: flex;
    flex-direction: column;
    justify-content: center;
    align-items: center;
    flex-grow: 1; 
    /* Center the logo in the middle, accounting for icons */
    margin: 0 auto; 
    position: static;
    transform: none;
}
.main-header .logo img { max-height: 40px; width: auto; filter: brightness(0) invert(1); }
.main-header .brand { display: none; } 
.main-header .icons { display: flex; gap: 15px; flex-shrink: 0; align-items: center; }
.main-header .icons a {
    color: #fff;
    font-size: 20px;
    position: relative;
    cursor: pointer;
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

/* Desktop Header & Navigation */
@media(min-width: 769px){
    .full-header-desktop {
        background: linear-gradient(to right, var(--dark-blue), var(--primary-blue));
        color: #fff;
        padding: 8px 20px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-wrap: wrap;
    }
    .full-header-desktop .logo img { max-height: 55px; }
    .full-header-desktop .brand { position: absolute; 
    left: 50%;
    transform: translateX(-50%); 
    font-size: 24px; 
    font-weight: bold; 
    color: #fff;
    white-space: nowrap;}
    .full-header-desktop .icons { display: flex; align-items: center; gap: 15px;}
    .full-header-desktop .icons a { 
        color: #fff; 
        font-size: 16px; 
        position: relative; 
        cursor: pointer; 
        display: flex; 
        align-items: center; 
    }
    .full-header-desktop .icons a i { margin-right: 5px; font-size: 20px; }
    .full-header-desktop .icons a span { 
        background: var(--red-alert); 
        color: #fff; 
        border-radius: 50%; 
        padding: 2px 6px; 
        position: absolute; 
        top:-8px; 
        right:-12px; 
        font-size: 12px; 
    }
    .desktop-nav {
        background: var(--mid-blue);
        display: flex;
        justify-content: center;
        align-items: center;
        flex-wrap: wrap;
        padding: 5px 0;
    }
    .desktop-nav a {
        color: #fff;
        margin: 10px 18px;
        font-size: 15px;
        font-weight: 500;
    }
    .desktop-nav a:hover { color: var(--light-blue); }
    .main-header { display: none; }
    .utility-bar { display: none; }
}
@media(max-width: 768px) {
    .full-header-desktop { display: none; }
    .desktop-nav { display: none; }
}

/* Mobile Menu Overlay */
.mobile-menu-overlay {
    height: 100%;
    width: 0; 
    position: fixed;
    z-index: 1010;
    top: 0;
    left: 0; 
    background-color: var(--dark-blue); 
    overflow-x: hidden;
    transition: 0.3s; 
    padding-top: 60px;
}
.mobile-menu-overlay-content { position: relative; width: 100%; text-align: center; margin-top: 30px; }
.mobile-menu-overlay a { padding: 15px; font-size: 20px; color: #fff; display: block; transition: 0.3s; border-bottom: 1px solid rgba(255, 255, 255, 0.1); }
.mobile-menu-overlay .closebtn { position: absolute; top: 20px; right: 35px; font-size: 40px; color: #fff; cursor: pointer; }

/* Side Cart */
#side-cart{
    position:fixed;
    top:0;
    right:-100%;
    width:100%;
    max-width:350px;
    height:100%;
    background:#fff;
    box-shadow:-3px 0 10px rgba(0,0,0,0.3);
    transition:right 0.3s ease;
    z-index:2000;
    padding:20px;
    overflow-y:auto
}
#side-cart.active{right:0}
#side-cart h3{margin-top:0;font-size:20px;color:var(--mid-blue)}
.cart-item{display:flex;align-items:center;gap:10px;margin:15px 0;border-bottom:1px solid #eee;padding-bottom:10px}
.cart-item img{width:60px;height:60px;object-fit:cover;border-radius:6px}
.cart-item .details{flex:1}
.qty-controls{display:flex;align-items:center;gap:6px;margin-top:5px}
.qty-controls button{padding:2px 6px;border:none;background:var(--primary-blue);color:#fff;border-radius:4px;cursor:pointer;font-size:12px}
.qty-controls button:hover{background:#005fa3}
.delete-btn{background:var(--red-alert) !important}
#close-cart{cursor:pointer;float:right;font-size:18px;color:var(--red-alert)}
#cart-total{font-weight:bold;font-size:16px;margin-top:10px;text-align:right;color:var(--mid-blue)}
.checkout-btn{display:block;width:100%;padding:12px;background:var(--primary-blue);color:#fff;border:none;border-radius:6px;font-size:16px;margin-top:20px;cursor:pointer}
.checkout-btn:hover{background:#005fa3}


/* Product Detail Content Styles */
.wrapper{max-width:1200px;margin:30px auto;padding:0 15px}
.product-container{display:flex;flex-wrap:wrap;gap:20px}
.image-section{flex:1 1 100%;position:relative;background:#fff;padding:10px;border-radius:8px}
.image-section img{width:100%;height:auto;object-fit:cover;border-radius:6px}
.discount-badge{
    position:absolute;
    top:20px;
    left:20px;
    background:var(--red-alert);
    color:#fff;
    font-size:14px;
    font-weight:bold;
    padding:6px 10px;
    border-radius:5px
}
.thumbnail-container{display:flex;gap:10px;margin-top:10px;flex-wrap:wrap;justify-content:center}
.thumbnail-container img{
    width:60px;
    height:60px;
    object-fit:cover;
    cursor:pointer;
    border:1px solid #ddd;
    border-radius:5px;
    transition:0.2s
}
.thumbnail-container img:hover{border:2px solid var(--primary-blue)}

.details-section{flex:1 1 100%;background:#fff;padding:15px;border-radius:8px}
.details-section h1{font-size:24px;margin-bottom:10px;color:var(--dark-blue)}
.price{font-size:22px;font-weight:bold;margin-bottom:15px}
.price .old-price{text-decoration:line-through;color:#999;margin-right:10px;font-size:16px}
.price .current-price{color:var(--red-alert)}

.btn{
    display:block;
    width:100%;
    padding:12px 0;
    margin:8px 0;
    text-align:center;
    border:none;
    border-radius:6px;
    color:#fff;
    font-weight:bold;
    cursor:pointer;
    font-size:16px;
}
.btn-add-cart{background:#000}
.btn-cod{background:var(--orange-cod)}
.btn-pay-online{background:#FFD700;color:#000}
.btn-chat{background:#111}
.btn-whatsapp{background:#25D366}

.description-title{margin-top:25px;font-size:20px;border-top:1px solid #ddd;padding-top:15px;color:var(--orange-cod)}
.description-text{margin-top:10px}
.contact-info{margin-top:25px;font-size:14px}
.contact-info a{color:var(--primary-blue);font-weight:bold}

.section-title {
    text-align: center;
    font-size: 24px;
    font-weight: bold;
    margin: 30px 0 20px;
    color: var(--mid-blue);
}
.product-box{
    display:grid;
    grid-template-columns:repeat(auto-fit,minmax(180px,1fr)); /* Adjusted minimum width */
    gap:15px;
    margin-top:20px
}
/* Updated Product Card Styling */
.product-card{
    display:flex;
    flex-direction:column;
    text-align:center;
    border:1px solid #eee;
    border-radius:12px; /* Smoother corner radius from image_f7aecb.png */
    overflow:hidden;
    background:#fff;
    transition:0.3s;
    box-shadow: 0 4px 14px rgba(0,0,0,0.1); /* Shadow from image_f7aecb.png */
    position: relative;
}
.product-card:hover{box-shadow:0 6px 16px rgba(0,0,0,0.15)}
.product-img {
    height: 200px; /* Consistent image height for mobile */
    overflow: hidden;
}
.product-img a { display: block; }
.product-img img{width:100%;height:100%;object-fit:cover;transition: transform 0.3s;}
.product-card:hover .product-img img { transform: scale(1.05); }

.product-info{padding:15px 10px 0;}
.product-info h3{
    font-size:16px;
    margin:5px 0 10px;
    color:#333;
    min-height:40px;
    line-height: 1.3;
}
.price-box {
    margin-bottom: 15px;
}
.price-box .current-price{
    font-size:18px;
    font-weight:bold;
    color:var(--dark-blue); /* Changed price color to dark-blue to match Quick Add button */
}
.price-box .old-price{
    text-decoration:line-through;
    color:#999;
    font-size:14px;
    margin-left: 8px;
}
.quick-add-btn{
    display:block;
    width:100%;
    padding:12px 0;
    background:var(--dark-blue);
    color:#fff;
    border:none;
    border-radius:0 0 12px 12px; /* Match card radius for bottom corners */
    cursor:pointer;
    font-size:16px;
    font-weight: bold;
    margin-top: auto;
}
.quick-add-btn:hover{background:#e67e22}
.discount-badge-small{
    position:absolute;
    top:10px;
    left:10px;
    background:var(--red-alert);
    color:#fff;
    font-size:14px;
    font-weight:bold;
    padding:6px 10px;
    border-radius:5px;
    z-index: 10;
}


@media (min-width: 769px) {
    .product-container{gap:40px; flex-wrap:nowrap;}
    .image-section{flex:1 1 400px;padding:0;}
    .details-section{flex:1 1 400px;padding:0;}
    .product-box{grid-template-columns:repeat(4,1fr);gap:20px;}
    .product-img { height: 280px; }
}


/* Footer */
.footer{background:var(--dark-blue);color:#fff;padding:30px 20px;text-align:center;margin-top:40px}
.footer h3{margin-bottom:12px;font-size:20px;color:var(--light-blue)}


/* Floating Social Media Icons */
.social-icons{position:fixed;top:50%;right:12px;transform:translateY(-50%);display:flex;flex-direction:column;gap:10px;z-index:999;}
.social-icons a{width:42px;height:42px;border-radius:50%;display:flex;align-items:center;justify-content:center;color:#fff;font-size:20px;}
.social-icons a.facebook{background:#3b5998;}
.social-icons a.twitter{background:#1da1f2;}
.social-icons a.whatsapp{background:#25d366;}
@media(max-width: 768px){
    .social-icons {
        right: 15px;
        top: auto;
        bottom: 70px; 
        transform: none;
        flex-direction: row; 
        gap: 10px;
    }
}
@media(min-width: 769px){
    .social-icons {
        right: 0;
        top: 50%;
        transform: translateY(-50%);
        flex-direction: column;
    }
}


/* Fixed Bottom Navigation (Mobile Only) */
.bottom-nav {
    position: fixed;
    bottom: 0;
    left: 0;
    width: 100%;
    background: #fff;
    box-shadow: 0 -2px 5px rgba(0,0,0,0.1);
    display: flex;
    justify-content: space-around;
    padding: 5px 0;
    z-index: 1100;
}
.bottom-nav a {
    display: flex;
    flex-direction: column;
    align-items: center;
    font-size: 12px;
    color: #666;
    padding: 5px;
    flex: 1;
}
.bottom-nav a i { font-size: 22px; margin-bottom: 3px; }
.bottom-nav a.active, .bottom-nav a:hover { color: var(--primary-blue); }
.bottom-nav a.cart-link { position: relative; }
.bottom-nav a.cart-link .cart-badge {
    background: var(--red-alert);
    color: #fff;
    border-radius: 50%;
    padding: 2px 6px;
    position: absolute;
    top: -5px;
    right: 10px;
    font-size: 10px;
    line-height: 1;
}
@media(min-width: 769px){ .bottom-nav { display: none; } }

</style>
</head>
<body>

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

<div class="main-header">
    <i class="fa fa-bars menu-icon" onclick="openMobileMenu()"></i>
    
    <div class="logo-brand">
        <div class="logo"><img src="upload/777.png" alt="Logo"></div>
    </div>
    
    <div class="icons">
        <a onclick="openCart()">
            <i class="fa fa-shopping-bag"></i> 
            <span class="cart-badge" id="cart-count-mobile"><?= $cart_count ?></span>
        </a>
    </div>
</div>

<div class="desktop-nav">
    <a href="index.php">Home</a>
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
        <a href="login.php" onclick="closeMobileMenu()"><i class="fa fa-user"></i> Account</a>
    </div>
</div>

<div id="side-cart">
    <span id="close-cart" onclick="closeCart()">✕</span>
    <h3>Your Cart</h3>
    <div id="cart-items">
        <?= $initialCartData['html'] ?>
    </div>
    <div id="cart-total">Total: ৳<span id="cart-total-amount"><?= number_format($initialCartData['total'], 0) ?></span></div>
    <a href="checkout.php" class="checkout-btn">Checkout</a>
</div>

<div class="wrapper">
    <div class="product-container">
        <div class="image-section">
            <?php if (!empty($product['discount'])): ?>
                <div class="discount-badge">-<?= intval($product['discount']) ?>%</div>
            <?php endif; ?>
            <img id="main-product-image" src="admin/<?= htmlspecialchars($productImages[0]) ?>" alt="<?= htmlspecialchars($product['name']) ?>">
            <?php if(count($productImages)>1): ?>
            <div class="thumbnail-container">
                <?php foreach($productImages as $img): ?>
                <img src="admin/<?= htmlspecialchars($img) ?>" alt="<?= htmlspecialchars($product['name']) ?>" onclick="document.getElementById('main-product-image').src='admin/<?= htmlspecialchars($img) ?>'">
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>

        <div class="details-section">
            <h1><?= htmlspecialchars($product['name']) ?></h1>
            <div class="price">
                <?php if(!empty($product['discount'])): ?>
                    <span class="old-price">৳<?= number_format($price,2) ?></span>
                    <span class="current-price">৳<?= number_format($discountedPrice,2) ?></span>
                <?php else: ?>
                    <span class="current-price">৳<?= number_format($price,2) ?></span>
                <?php endif; ?>
            </div>

            <form class="add-to-cart-form">
                <input type="hidden" name="product_id" value="<?= $product['id'] ?>">
                <button type="submit" class="btn btn-add-cart">Add to cart</button>
            </form>
            
            <a href="javascript:void(0)" id="cod-checkout-btn" class="btn btn-cod" data-product-id="<?= $product['id'] ?>">অর্ডার করতে কল/ইনবক্স করুন</a>
            
            <a href="#" class="btn btn-pay-online">Pay Online</a>
            <a href="https://m.me/purbachalmariyamfashionhouse" target="_blank" class="btn btn-chat">
                <i class="fab fa-facebook-messenger"></i> Chat with us
            </a>
            <a href="https://wa.me/+8801710100657" target="_blank" class="btn btn-whatsapp">WhatsApp Us</a>

            <div class="description-title">Description</div>
            <div class="description-text"><?= nl2br(htmlspecialchars($product['description'])) ?></div>
            <div class="contact-info">
                <p>অর্ডার এর জন্য বা কোনো কিছু জানতে WhatsApp করুন:</p>
                <p><a href="tel:+8801321208940">+8801321208940</a></p>
            </div>
        </div>
    </div>

    <h2 class="section-title">Related Products</h2>
    <div class="product-box">
        <?php foreach($relatedProducts as $rp): ?>
        <?php
            $rp_price = $rp['price'];
            $rp_discounted = $rp_price;
            if (!empty($rp['discount']) && is_numeric($rp['discount']) && $rp['discount'] > 0) {
                $rp_discounted = $rp_price - ($rp_price * $rp['discount'] / 100);
            }
        ?>
        <div class="product-card">
            <?php if (!empty($rp['discount'])): ?>
                <div class="discount-badge-small">-<?= intval($rp['discount']) ?>%</div>
            <?php endif; ?>

            <div class="product-img">
                <a href="product_detail.php?id=<?= $rp['id'] ?>">
                    <img src="admin/<?= htmlspecialchars($rp['first_image'] ?: 'upload/default.png') ?>" alt="<?= htmlspecialchars($rp['name']) ?>">
                </a>
            </div>
            <div class="product-info">
                <h3><?= htmlspecialchars($rp['name']) ?></h3>
                <div class="price-box">
                    <?php if (!empty($rp['discount'])): ?>
                        <span class="old-price">৳<?= number_format($rp_price,2) ?></span>
                    <?php endif; ?>
                    <span class="current-price">৳<?= number_format($rp_discounted,2) ?></span>
                </div>
            </div>
            <button class="quick-add-btn" onclick="addToCart(<?= $rp['id'] ?>)">Quick Add</button>
        </div>
        <?php endforeach; ?>
    </div>

    <h2 class="section-title">You May Also Like</h2>
    <div class="product-box">
        <?php foreach($moreLike as $ml): ?>
        <?php
            $ml_price = $ml['price'];
            $ml_discounted = $ml_price;
            if (!empty($ml['discount']) && is_numeric($ml['discount']) && $ml['discount'] > 0) {
                $ml_discounted = $ml_price - ($ml_price * $ml['discount'] / 100);
            }
        ?>
        <div class="product-card">
            <?php if (!empty($ml['discount'])): ?>
                <div class="discount-badge-small">-<?= intval($ml['discount']) ?>%</div>
            <?php endif; ?>

            <div class="product-img">
                <a href="product_detail.php?id=<?= $ml['id'] ?>">
                    <img src="admin/<?= htmlspecialchars($ml['first_image'] ?: 'upload/default.png') ?>" alt="<?= htmlspecialchars($ml['name']) ?>">
                </a>
            </div>
            <div class="product-info">
                <h3><?= htmlspecialchars($ml['name']) ?></h3>
                <div class="price-box">
                    <?php if (!empty($ml['discount'])): ?>
                        <span class="old-price">৳<?= number_format($ml_price,2) ?></span>
                    <?php endif; ?>
                    <span class="current-price">৳<?= number_format($ml_discounted,2) ?></span>
                </div>
            </div>
            <button class="quick-add-btn" onclick="addToCart(<?= $ml['id'] ?>)">Quick Add</button>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<div class="footer">
    <h3>Contact Us</h3>
    <p>🏠 Purbachal 300 feet,Dhaka</p>
    <p>📞 +880 1342-866580</p>
    <p>📧 mariyamfashionhouse@gmail.com</p>
</div>

<div class="social-icons">
    <a href="https://facebook.com/purbachalmariyamfashionhouse" class="facebook" target="_blank"><i class="fab fa-facebook-f"></i></a>
    <a href="https://twitter.com" class="twitter" target="_blank"><i class="fab fa-x-twitter"></i></a>
    <a href="https://wa.me/+8801710100657" class="whatsapp" target="_blank"><i class="fab fa-whatsapp"></i></a>
</div>

<div class="bottom-nav">
    <a href="index.php"><i class="fa fa-home"></i> Home</a>
    <a href="all_products.php"><i class="fa fa-th"></i> All Products</a>
    <a onclick="openCart()" class="cart-link">
        <i class="fa fa-shopping-bag"></i> 
        <span class="cart-badge" id="bottom-cart-count"><?= $cart_count ?></span> Cart
    </a>
    <a href="contact.php"><i class="fa fa-phone"></i> Contact</a>
</div>

<script>
// ===== Mobile Menu Functions =====
function openMobileMenu() { 
    document.getElementById('mobile-menu').style.width = '250px'; 
}
function closeMobileMenu() { 
    document.getElementById('mobile-menu').style.width = '0'; 
}

// ===== Cart UI Functions =====
function openCart(){document.getElementById('side-cart').classList.add('active');}
function closeCart(){document.getElementById('side-cart').classList.remove('active');}

function updateCartCount(count){
    const desktopCount = document.getElementById('cart-count-desktop');
    if(desktopCount) desktopCount.innerText = count;
    
    const mobileCount = document.getElementById('cart-count-mobile');
    if(mobileCount) mobileCount.innerText = count;
    
    const bottomCount = document.getElementById('bottom-cart-count');
    if(bottomCount) bottomCount.innerText = count;
}

// Function to update all cart displays (HTML content and total)
function updateCartDisplay(data) {
    updateCartCount(data.cart_count);
    document.getElementById('cart-items').innerHTML = data.cart_html;
    document.getElementById('cart-total-amount').textContent = data.cart_total % 1 === 0 ? data.cart_total.toFixed(0) : data.cart_total.toFixed(2);
}


// Function to update item quantity in the cart (called by side cart +/- buttons)
function updateQty(productId, qtyChange) {
    const formData = new FormData();
    formData.append('product_id', productId);
    formData.append('qty_change', qtyChange);
    formData.append('ajax_update_qty', 1); // Triggers the PHP AJAX Update Quantity logic

    fetch('', {method: 'POST', body: formData}) // Post to self (product_detail.php)
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            updateCartDisplay(data);
        } else {
            alert('Failed to update cart quantity.');
        }
    })
    .catch(err => console.error('AJAX Error:', err));
}

// Function to remove an item from the cart (called by side cart X button)
function removeItem(productId) {
    const itemElement = document.querySelector(`#side-cart .cart-item[data-id="${productId}"]`);
    if (itemElement) {
        const currentQty = parseInt(itemElement.querySelector('.qty').textContent);
        if (currentQty > 0) {
            // Send the negative value of the current quantity to ensure the item is removed (qty <= 0)
            updateQty(productId, -currentQty); 
        }
    }
}


// Add to Cart AJAX (Using the form submit listener on the main product)
document.querySelectorAll('.add-to-cart-form').forEach(form=>{
    form.addEventListener('submit', function(e){
        e.preventDefault();
        const formData = new FormData();
        formData.append('product_id', this.querySelector('input[name="product_id"]').value);
        formData.append('ajax_add_cart',1);
        
        fetch('', {method:'POST', body:formData})
        .then(res=>res.json())
        .then(data=>{
            if(data.success){
                updateCartDisplay(data);
                openCart();
            } else alert('Failed to add to cart.');
        }).catch(err=>console.error('AJAX Error:', err));
    });
});

// Add to Cart AJAX (For buttons outside the form, like related products - uses Quick Add style)
function addToCart(productId){
    const formData = new FormData();
    formData.append('product_id', productId);
    formData.append('ajax_add_cart',1);

    fetch('', {method:'POST', body:formData})
    .then(res=>res.json())
    .then(data=>{
        if(data.success){
            updateCartDisplay(data);
            openCart();
        } else alert('Failed to add to cart.');
    }).catch(err=>console.error('AJAX Error:', err));
}


// --- FUNCTION: ADD TO CART AND REDIRECT TO CHECKOUT (Used by COD button) ---
function addToCartAndCheckout(productId) {
    const formData = new FormData();
    formData.append('product_id', productId);
    formData.append('ajax_add_cart', 1); 
    
    // Send request to add item to cart
    fetch('', {method: 'POST', body: formData}) 
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            // Success: Update cart display and redirect immediately
            updateCartDisplay(data); 
            window.location.href = 'checkout.php';
        } else {
            alert('Failed to add product to cart before checkout.');
        }
    })
    .catch(err => {
        console.error('AJAX Error:', err);
        alert('An error occurred. Please try again.');
    });
}

// Hook up the COD button to the checkout function
document.addEventListener('DOMContentLoaded', function() {
    const codButton = document.getElementById('cod-checkout-btn');

    // Listener for the COD button
    if (codButton) {
        codButton.addEventListener('click', function(e) {
            e.preventDefault();
            const productId = this.getAttribute('data-product-id');
            if (productId) {
                // Call the function to add to cart and redirect
                addToCartAndCheckout(productId);
            }
        });
    }
});
</script>
</body>
</html>