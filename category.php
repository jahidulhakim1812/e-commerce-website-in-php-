<?php
// C:\xampp\htdocs\mariyam_fashion\index.php

// ====================================================================
// 1. PHP LOGIC (Includes config.php to establish $pdo and session)
// ====================================================================

// This line includes config.php, which defines the database connection $pdo.
include 'config.php'; 

// CRUCIAL FIX: Declares $pdo as global to ensure it's accessible in the main script scope.
global $pdo;

// Fetch categories
$categoriesList = $pdo->query("SELECT * FROM categories ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);

// Selected category
$selectedCategory = $_GET['category'] ?? '';
$selectedCategoryName = 'All Categories'; // Default title

// Build query
$where = "1=1";
$params = [];
if ($selectedCategory) {
    // Basic validation to ensure category is numeric before using it in the query
    if (is_numeric($selectedCategory)) {
        $where = "p.category_id = :category";
        $params['category'] = $selectedCategory;
        // Find the name of the selected category for the section title
        foreach($categoriesList as $cat) {
            if ($cat['id'] == $selectedCategory) {
                $selectedCategoryName = $cat['name'];
                break;
            }
        }
    } else {
        // Handle invalid category ID gracefully, e.g., default to 'All Categories'
        $selectedCategory = '';
    }
}

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

// Prepare cart items for JS
$cart_items = [];
if(isset($_SESSION['cart']) && !empty($_SESSION['cart'])){
    $product_ids = array_keys($_SESSION['cart']);
    // Use IN clause for efficiency instead of multiple queries in a loop
    $placeholders = implode(',', array_fill(0, count($product_ids), '?'));
    $stmt = $pdo->prepare("
        SELECT p.id, p.name, p.price,
               (SELECT image_path FROM product_images WHERE product_id=p.id LIMIT 1) AS first_image
        FROM products p WHERE p.id IN ($placeholders)
    ");
    $stmt->execute($product_ids);
    $cart_products = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach($cart_products as $prod){
        $id = $prod['id'];
        $qty = $_SESSION['cart'][$id];
        // Note: The product detail page needs to know the correct image path structure
        $image = !empty($prod['first_image']) ? "admin/".$prod['first_image'] : "admin/uploads/default.png";
        $cart_items[$id] = [
            'id' => $prod['id'],
            'name' => $prod['name'],
            'price' => $prod['price'],
            'image' => $image,
            'qty' => $qty
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
<title>Categories - Mariyan Fashion</title>
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
    --accent-orange: #ff8a3d;
    --quick-add-bg: #001f3f;
}

*{margin:0;padding:0;box-sizing:border-box}
body{font-family:'Segoe UI',sans-serif;background:var(--background-color);color:var(--text-color);padding-bottom: 0;}
a{text-decoration:none;color:inherit;}

/* ==================================== */
/* ===== HEADER & NAV STYLES ===== */
/* ==================================== */

/* DESKTOP HEADER & NAV */
.full-header-desktop {
    background: linear-gradient(to right, var(--dark-blue), var(--primary-blue));
    color: #fff;
    padding: 8px 20px;
    display: none;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
}
.full-header-desktop .logo img { max-height: 55px; filter: brightness(0) invert(1);}
.full-header-desktop .brand { 
    position: absolute; 
    left: 50%;
    transform: translateX(-50%); 
    font-size: 24px; 
    font-weight: bold; 
    color: #fff;
    white-space: nowrap;}
.full-header-desktop .icons a { color: #fff; margin-left: 15px; font-size: 16px; position: relative; cursor: pointer; }
.full-header-desktop .icons a span { 
    background: var(--red-alert); color: #fff; border-radius: 50%; padding: 2px 6px; 
    position: absolute; top:-8px; right:-12px; font-size: 12px; 
}

/* *** MODIFIED CSS FOR CENTERED NAVBAR MENU *** */
.desktop-nav {
    background: var(--mid-blue);
    display: none;
    justify-content: center; /* Changed from flex-start to center */
    align-items: center;
    flex-wrap: wrap;
    padding: 0 20px;
}
.desktop-nav a { color: #fff; margin: 10px 18px; font-size: 15px; font-weight: 500; }
.desktop-nav a:hover, .desktop-nav a.active { color: var(--light-blue); }

/* Search Bar Styles (Keeping the original search CSS for reference, but the HTML is removed) */
.search-container {
    margin-left: auto; 
    display: flex;
    align-items: center;
    padding: 5px 0;
}
.search-container input[type="text"] {
    padding: 8px 12px;
    border: 1px solid var(--primary-blue);
    border-right: none;
    border-radius: 4px 0 0 4px;
    width: 250px; 
    font-size: 14px;
    outline: none;
    height: 38px;
}
.search-container button {
    background: var(--primary-blue);
    color: #fff;
    border: none;
    padding: 0 15px;
    cursor: pointer;
    font-size: 16px;
    height: 38px;
    border-radius: 0 4px 4px 0;
    transition: background 0.2s;
    display: flex;
    align-items: center;
    justify-content: center;
}
.search-container button:hover {
    background: var(--dark-blue);
}


/* MOBILE HEADER & NAV */
.main-header {
    background: linear-gradient(to right, var(--dark-blue), var(--primary-blue));
    padding: 10px 15px;
    display: none; 
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
.main-header .icons a { color: #fff; font-size: 20px; position: relative; }
.main-header .icons a .cart-badge {
    background: var(--red-alert); color: #fff; border-radius: 50%; padding: 2px 6px; 
    position: absolute; top: -8px; right: -12px; font-size: 10px; line-height: 1;
}

/* Mobile Menu Overlay */
.mobile-menu-overlay {
    height: 100%; width: 0; position: fixed; z-index: 1010; top: 0; left: 0; 
    background-color: var(--dark-blue); overflow-x: hidden; transition: 0.3s; padding-top: 60px;
}
.mobile-menu-overlay a { padding: 15px; font-size: 20px; color: #fff; display: block; transition: 0.3s; border-bottom: 1px solid rgba(255, 255, 255, 0.1); }
.mobile-menu-overlay .closebtn { position: absolute; top: 20px; right: 35px; font-size: 40px; color: #fff; cursor: pointer; }

/* Bottom Navigation (Mobile Only) */
.bottom-nav {
    position: fixed; bottom: 0; left: 0; width: 100%; background: #fff; 
    box-shadow: 0 -2px 5px rgba(0,0,0,0.1); display: none; 
    justify-content: space-around; 
    padding: 5px 0; z-index: 1100;
}
.bottom-nav a { display: flex; flex-direction: column; align-items: center; font-size: 12px; color: #666; padding: 5px; flex: 1; position: relative; } /* Added position: relative */
.bottom-nav a i { font-size: 22px; margin-bottom: 3px; }
.bottom-nav a.cart-link .cart-badge {
    /* *** Adjusted positioning for bottom nav badge *** */
    background: var(--red-alert); color: #fff; border-radius: 50%; padding: 2px 6px; 
    position: absolute; 
    top: 0px; 
    right: 18px; 
    font-size: 10px; line-height: 1;
}


/* ================================== */
/* ===== CONTENT & SIDEBAR STYLES ===== */
/* ================================== */
.container{display:flex;gap:30px;max-width:1200px;margin:40px auto;padding:0 20px;}
.sidebar{flex:1;background:#fff;padding:20px;border-radius:12px;box-shadow:0 4px 14px rgba(0,0,0,0.1);height:max-content;min-width: 200px;}
.sidebar h3{color:var(--primary-blue);margin-bottom:15px;}
.sidebar ul{list-style:none;padding:0;}
.sidebar ul li{margin-bottom:12px;}
.sidebar ul li a{text-decoration:none;color:#333;font-weight:500; display:block;}
.sidebar ul li a.active, .sidebar ul li a:hover{color:var(--primary-blue);}

.products-content { flex: 3; }
.section-title{
    font-size:28px;margin-bottom:25px;color:var(--primary-blue);border-bottom:2px solid var(--primary-blue);
    display:inline-block;padding-bottom:6px;width: 100%; text-align: center;
}
.products{display:flex;flex-wrap:wrap;gap:20px;justify-content:flex-start; padding: 0;} 


/* ================================== */
/* ===== STREAMLINED PRODUCT CARD STYLING ===== */
/* ================================== */
.product {
    background: #fff; 
    border-radius: 12px; 
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
    display: flex; 
    flex-direction: column; 
    overflow: hidden; 
    transition: transform 0.3s, box-shadow 0.3s; 
    width:calc(33.33% - 14px); 
    padding: 0; 
    cursor: pointer;
}
.product:hover {
    transform: translateY(-3px);
    box-shadow: 0 8px 16px rgba(0,0,0,0.15);
}

.product-card-image-wrapper { 
    display: block;
    position: relative;
    width: 100%;
    padding-top: 100%;
    overflow: hidden;
    background-color: #f8f8f8;
}

.product-card-image-wrapper img { 
    position: absolute; 
    top: 0;
    left: 0;
    width: 100%;
    height: 100%; 
    object-fit: contain;
    padding: 5px; 
    box-sizing: border-box; 
    transition: transform 0.3s ease;
}
.product:hover .product-card-image-wrapper img {
    transform: scale(1.03); 
}

.product-card-content { 
    padding: 10px 10px 15px 10px; 
    flex-grow: 1; 
    text-align: center; 
    min-height: 70px; 
    display: flex; 
    flex-direction: column;
    align-items: center; 
    justify-content: flex-start;
}

.product-card-content h4 {
    margin: 5px 0; 
    font-size: 16px; 
    font-weight: 600;
    color: var(--text-color); 
    line-height: 2;
    height: 3.8em; 
    display: -webkit-box;
    -webkit-line-clamp: 2; 
    -webkit-box-orient: vertical;
    overflow: hidden;
    text-overflow: ellipsis;
    padding-bottom: 5px;
    max-width: 100%;
}
.price { 
    font-weight: bold; 
    color: var(--dark-blue); 
    font-size: 20px; 
    margin: 5px 0 0 0; 
}

.product-footer { 
    display: flex; 
    justify-content: center;
    align-items: center; 
    padding: 0; 
    border-top: none; 
    margin-top: auto; 
    width: 100%;
}

.add-btn { 
    background: var(--quick-add-bg); 
    color: #fff; 
    border: none; 
    padding: 12px 10px; 
    border-radius: 0 0 12px 12px;
    cursor: pointer; 
    font-size: 16px; 
    font-weight: 600;
    width: 100%; 
    text-align: center;
    transition: background 0.2s;
    text-transform: uppercase;
}
.add-btn:hover {
    background: #e67e22; 
}
.product > p, .product > small {
    display: none !important; 
}


/* ================================== */
/* ===== MOBILE MEDIA QUERY (768px and below) ===== */
/* ================================== */
@media(max-width: 768px) {
    /* SHOW MOBILE ELEMENTS */
    .main-header, .bottom-nav { display: flex; }
    body { padding-bottom: 60px; } 
    .container{flex-direction:column;gap:10px;margin:20px auto;padding:0 10px;}
    
    /* Mobile Sidebar styling: horizontal list */
    .sidebar{min-width: unset; width: 100%; border-radius: 0; box-shadow: none; padding: 10px 10px; } 
    .sidebar h3 { display: none; }
    .sidebar ul { display: flex; flex-wrap: wrap; gap: 6px; justify-content: center; }
    .sidebar ul li a { padding: 5px 10px; border: 1px solid #ccc; border-radius: 6px; font-size: 13px; }
    .sidebar ul li a.active { background: var(--primary-blue); color: #fff; border-color: var(--primary-blue); }

    /* Mobile Product Grid */
    .products-content { flex: 1; padding: 0 5px; } 
    .products{
        gap: 10px; 
        justify-content: space-between; 
    }
    .product{
        width: calc(50% - 5px); 
        box-shadow: 0 2px 8px rgba(0,0,0,0.05); 
        transition: none;
        border-radius: 10px;
    } 
    .product:hover { transform: none; }

    /* Mobile adjustments for NEW card style */
    .product-card-content { min-height: 50px; padding: 5px 5px 10px 5px; }
    .product-card-content h4 { font-size: 13px; margin: 3px 0; height: 3.5em; padding-bottom: 3px;} 
    .price { font-size: 16px; margin: 3px 0 0 0;}
    .add-btn { padding: 10px 10px; font-size: 13px; border-radius: 0 0 10px 10px; } 

    .section-title{font-size: 22px; margin-bottom: 15px; text-align: center;}
}


/* --- DESKTOP MEDIA QUERY (769px and up) --- */
@media(min-width: 769px) {
    .full-header-desktop { display: flex; }
    .desktop-nav { display: flex; }
    .main-header, .mobile-menu-overlay, .bottom-nav { display: none !important; }
    body { padding-bottom: 0; }
    .container { margin-top: 40px; } 
    .sidebar ul { display: block; }
}


/* Footer & Cart */
.footer{background:var(--dark-blue);color:#fff;padding:50px 20px;text-align:center;margin-top:50px;}
.footer h3{margin-bottom:20px;font-size:22px;color:var(--light-blue);}
#side-cart {
    position: fixed; top: 0; right: -100%; width: 90%; max-width: 350px; height: 100%;
    background: #fff; box-shadow: -3px 0 10px rgba(0,0,0,0.3); transition: right 0.3s ease;
    z-index: 2000; padding: 20px; overflow-y: auto;
}
#side-cart.active { right: 0; }
@media (max-width: 400px) { #side-cart { width: 85%; } }
#side-cart h3 { margin-top: 0; font-size: 20px; color: var(--mid-blue); }
.cart-item{display:flex;align-items:center;gap:10px;margin:15px 0;border-bottom:1px solid #eee;padding-bottom:10px;}
.cart-item img{width:60px;height:60px;object-fit:cover;border-radius:6px;}
.cart-item .details{flex:1;}
.qty-controls{display:flex;align-items:center;gap:6px;margin-top:5px;}
.qty-controls button{padding:2px 6px;border:none;background:var(--primary-blue);color:#fff;border-radius:4px;cursor:pointer;font-size:12px;}
.delete-btn{background:var(--red-alert) !important;}
#close-cart{cursor:pointer;float:right;font-size:18px;color:var(--red-alert);}
.checkout-btn{display:block;width:100%;padding:12px;background:var(--primary-blue);color:#fff;border:none;border-radius:6px;font-size:16px;margin-top:20px;cursor:pointer;}
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

<div class="desktop-nav">
    <a href="index.php">Home</a>
    <a href="all_products.php">Products</a>
    <a href="category.php" class="active">Category</a>
    <a href="shop.php">Shop</a>
    <a href="about.php">About</a>
    <a href="contact.php">Contact</a>
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


<div class="container">

    <div class="sidebar">
        <h3>Categories</h3>
        <ul>
            <li><a href="category.php" class="<?= !$selectedCategory?'active':'' ?>">All Products</a></li>
            <?php foreach($categoriesList as $cat): ?>
                <li>
                    <a href="category.php?category=<?= $cat['id'] ?>" class="<?= ($selectedCategory==$cat['id'])?'active':'' ?>">
                        <?= htmlspecialchars($cat['name']) ?>
                    </a>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>

    <div class="products-content">
        <div class="section-title">Category: <?= htmlspecialchars($selectedCategoryName) ?></div>
        <div class="products">
            <?php if(count($products) > 0): ?>
                <?php foreach($products as $product): ?>
                    <div class="product" onclick="window.location='product_detail.php?id=<?= $product['id'] ?>'">
                        
                        <a href="product_detail.php?id=<?= $product['id'] ?>" class="product-card-image-wrapper">
                            <img src="<?= !empty($product['first_image']) ? "admin/".$product['first_image'] : "admin/uploads/default.png" ?>" alt="<?= htmlspecialchars($product['name']) ?>">
                        </a>

                        <div class="product-card-content">
                            <h4><?= htmlspecialchars($product['name']) ?></h4>
                            <div class="price">৳<?= htmlspecialchars($product['price']) ?></div>
                        </div>
                        
                        <div class="product-footer">
                            <button class="add-btn" onclick="event.stopPropagation(); addToCart(<?= $product['id'] ?>)">Quick Add</button>
                        </div>
                        
                        <p style="display:none;"></p> 
                        <small style="display:none;"></small>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p>No products found in this category.</p>
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

<div id="side-cart">
    <span id="close-cart" onclick="closeCart()">✖</span>
    <h3>Your Cart</h3>
    <div id="cart-items"></div>
    <a href="checkout.php" class="checkout-btn">Order Now</a>
</div>

<div class="bottom-nav">
    <a href="index.php">
        <i class="fa fa-home"></i> <span>Home</span>
    </a>
    <a href="all_products.php">
        <i class="fa fa-th-large"></i> <span>All Products</span>
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
let cartMemory = <?= json_encode($cart_items) ?>;

function updateAllCartCounts(){
    // Ensure all items have a valid qty, default to 0 if not
    const count = Object.values(cartMemory).reduce((sum,i)=>sum+(i.qty || 0),0);
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

document.addEventListener('DOMContentLoaded', updateAllCartCounts);
</script>
</body>
</html>