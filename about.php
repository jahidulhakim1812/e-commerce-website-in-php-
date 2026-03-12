<?php
// C:\xampp\htdocs\mariyam_fashion\index.php

// ====================================================================
// 1. PHP LOGIC (Includes config.php to establish $pdo and session)
// ====================================================================

// This line includes config.php, which defines the database connection $pdo.
include 'config.php'; 

// CRUCIAL FIX: Declares $pdo as global to ensure it's accessible in the main script scope.
global $pdo;

// Prepare cart items for JS (DETAILS FETCHING)
$cart_items = [];
if(isset($_SESSION['cart']) && !empty($_SESSION['cart'])){
    $product_ids = array_keys($_SESSION['cart']);
    
    // Use IN clause for efficiency instead of multiple queries in a loop
    $placeholders = implode(',', array_fill(0, count($product_ids), '?'));
    
    // SQL statement to fetch product details and a single image
    $stmt = $pdo->prepare("
        SELECT p.id, p.name, p.price,
               (SELECT image_path FROM product_images WHERE product_id=p.id LIMIT 1) AS first_image
        FROM products p WHERE p.id IN ($placeholders)
    ");
    $stmt->execute($product_ids);
    $cart_products = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach($cart_products as $prod){
        $id = $prod['id'];
        $qty = $_SESSION['cart'][$id] ?? 0;
        
        // Ensure we only include items that actually exist in the cart session and have a quantity > 0
        if($qty > 0) {
            $image = !empty($prod['first_image']) ? "admin/".$prod['first_image'] : "admin/uploads/default.png";
            $cart_items[$id] = [
                'id' => $prod['id'],
                'name' => $prod['name'],
                'price' => (float)$prod['price'],
                'image' => $image,
                'qty' => (int)$qty
            ];
        }
    }
}
$cart_count = array_sum(array_column($cart_items, 'qty'));
$cart_items_json = json_encode($cart_items);
?>
<!DOCTYPE html>
<html lang="bn">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>About Us - Mariyan Fashion</title>
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
.full-header-desktop .brand { position: absolute; 
    left: 50%;
    transform: translateX(-50%); 
    font-size: 24px; 
    font-weight: bold; 
    color: #fff;
    white-space: nowrap; }
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
/* ===== ABOUT CONTENT STYLES ===== */
/* ================================== */
.about-section{max-width:1200px;margin:40px auto;padding:30px;background:#fff;border-radius:20px;box-shadow:0 4px 20px rgba(0,0,0,0.1);}
.about-section h2{color:var(--mid-blue);font-size:32px;text-align:center;margin-bottom:25px; border-bottom: 2px solid var(--primary-blue); padding-bottom: 5px;}
.about-section p{font-size:16px;color:#555;line-height:1.8;margin-bottom:15px;}
.about-section p strong{color:var(--mid-blue);}

/* Mission-Vision Grid */
.mission-vision{display:grid; grid-template-columns:repeat(auto-fit,minmax(450px,1fr)); gap:25px; margin-top:35px;}
.mission-vision div{background:#f1f7fc;padding:25px;border-radius:15px;box-shadow:0 4px 12px rgba(0,0,0,0.08);transition:0.3s;}
.mission-vision div:hover{transform:translateY(-5px); box-shadow:0 8px 20px rgba(0,0,0,0.15);}
.mission-vision h3{color:var(--primary-blue);margin-bottom:15px;font-size:20px;}

/* ===== Footer ===== */
.footer{background:var(--dark-blue);color:#fff;padding:30px 20px;text-align:center;margin-top:40px;}
.footer h3{margin-bottom:12px;font-size:20px;color:var(--light-blue);}

/* ===== Floating Social ===== */
.social-icons{position:fixed;top:50%;right:12px;transform:translateY(-50%);display:flex;flex-direction:column;gap:10px;z-index:999;}
.social-icons a{width:42px;height:42px;border-radius:50%;display:flex;align-items:center;justify-content:center;color:#fff;font-size:20px;}
.social-icons a.facebook{background:#3b5998;}
.social-icons a.twitter{background:#1da1f2;}
.social-icons a.whatsapp{background:#25d366;}

/* ===== Side Cart Styles (Full Consistency) ===== */
#side-cart {
    position: fixed;
    top: 0;
    right: -100%; 
    width: 90%; 
    max-width: 350px; 
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
@media (max-width: 400px) {
    #side-cart {
        width: 85%;
    }
}
#side-cart h3 { margin-top: 0; font-size: 20px; color: var(--mid-blue); }
#close-cart{cursor:pointer;float:right;font-size:18px;color:red;}
.checkout-btn{display:block;width:100%;padding:12px;background:var(--primary-blue);color:#fff;border:none;border-radius:6px;font-size:16px;margin-top:20px;cursor:pointer;}
.checkout-btn:hover{background:#005fa3;}

/* Cart Item Styles (copied from previous file for full consistency) */
.cart-item{display:flex;align-items:center;gap:10px;margin:15px 0;border-bottom:1px solid #eee;padding-bottom:10px;}
.cart-item img{width:60px;height:60px;object-fit:cover;border-radius:6px;}
.cart-item .details{flex:1;}
.qty-controls{display:flex;align-items:center;gap:6px;margin-top:5px;}
.qty-controls button{padding:2px 6px;border:none;background:var(--primary-blue);color:#fff;border-radius:4px;cursor:pointer;font-size:12px;}
.qty-controls button:hover{background:#005fa3;}
.delete-btn{background:var(--red-alert) !important;}


/* ================================== */
/* ===== MEDIA QUERIES (Responsiveness) ===== */
/* ================================== */

/* Desktop & Tablet (769px and up) */
@media(min-width: 769px) {
    .full-header-desktop { display: flex; }
    .desktop-nav { display: flex; }
    .main-header, .mobile-menu-overlay, .bottom-nav { display: none !important; }
    body { padding-bottom: 0; }
    .about-section { margin-top: 50px; }
}

/* Tablet & Mobile (768px and below) */
@media(max-width: 768px) {
    /* Show mobile header/nav, hide desktop */
    .main-header, .bottom-nav { display: flex; }
    .full-header-desktop, .desktop-nav { display: none !important; }
    body { padding-bottom: 60px; } 

    /* About Section Optimization */
    .about-section { 
        margin: 15px auto; 
        padding: 15px; 
        border-radius: 10px;
    }
    .about-section h2{font-size: 26px;}
    .about-section p{font-size: 15px; line-height: 1.6;}

    /* Mission-Vision Stacking */
    .mission-vision{
        grid-template-columns: 1fr; /* Stack into a single column */
        gap: 15px;
        margin-top: 20px;
    }
    .mission-vision div{padding: 15px;}
    .mission-vision h3{font-size: 18px; margin-bottom: 10px;}

    /* Footer Adjustment */
    .footer { margin-top: 20px; }
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
    <a href="shop.php">Shop</a>
    <a href="about.php" class="active">About</a>
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


<div id="side-cart">
    <span id="close-cart" onclick="closeCart()">✖</span>
    <h3>Your Cart</h3>
    <div id="cart-items">
        <p>Loading cart...</p>
    </div>
    <div id="cart-total"></div>
    <a href="checkout.php" class="checkout-btn">Order Now</a>
</div>

<div class="about-section">
    <h2>আমাদের সম্পর্কে</h2>
    <p><strong>Mariyan Fashion House</strong> বাংলাদেশের ফ্যাশন জগতে মান, স্টাইল এবং শ্রেষ্ঠত্বের প্রতীক। আমরা বিশ্বাস করি যে পোশাক শুধু শরীর ঢেকে রাখে না, বরং এটি মানুষের ব্যক্তিত্ব ও আত্মবিশ্বাস প্রকাশের এক শক্তিশালী মাধ্যম। আমাদের লক্ষ্য হলো এমন পোশাক ও এক্সেসরিজ সরবরাহ করা যা আপনার প্রতিদিনের জীবনে স্টাইল এবং আরাম নিয়ে আসে।</p>
    
    <p>আমাদের প্রতিষ্ঠান শুরু হয়েছিল একটি ছোট্ট দোকান থেকে। আমাদের ক্রেতাদের বিশ্বাস ও ভালোবাসা এবং আধুনিক ফ্যাশন নিয়ে উদ্ভাবনী মনোভাব আমাদের আজকের অবস্থানে পৌঁছে দিয়েছে। আমরা এখন বাংলাদেশের বিভিন্ন প্রান্তে এবং অনলাইনে আমাদের গ্রাহকদের কাছে সর্বোচ্চ মানের ফ্যাশন সরবরাহ করি।</p>

    <p>আমরা বিশ্বাস করি, ফ্যাশন মানেই আত্মপ্রকাশ। তাই আমাদের প্রতিটি পণ্যকে এমনভাবে ডিজাইন করা হয় যাতে এটি ব্যবহারকারীর স্বকীয়তা এবং স্টাইল প্রকাশ করতে সাহায্য করে। আমাদের ডিজাইন এবং ক্রিয়েটিভ টিম প্রতিনিয়ত নতুন ট্রেন্ড, স্টাইল এবং কনসেপ্ট নিয়ে কাজ করে, যাতে গ্রাহকরা সর্বদা নতুনত্ব অনুভব করতে পারে।</p>

    <div class="mission-vision">
        <div>
            <h3>আমাদের মিশন</h3>
            <p>আমাদের মিশন হলো:<br>
            - উচ্চমানের ফ্যাশন পণ্য সরবরাহ করা।<br>
            - স্টাইল এবং আরামের মধ্যে একটি নিখুঁত ভারসাম্য রাখা।<br>
            - গ্রাহকদের জন্য নিরাপদ, সহজ এবং আনন্দদায়ক শপিং অভিজ্ঞতা নিশ্চিত করা।<br>
            - নতুন ফ্যাশন ট্রেন্ডের সঙ্গে সর্বদা আপডেট থাকা।</p>
        </div>
        <div>
            <h3>আমাদের ভিশন</h3>
            <p>আমাদের ভিশন হলো:<br>
            - বাংলাদেশে সবচেয়ে বিশ্বাসযোগ্য, উদ্ভাবনী এবং আধুনিক ফ্যাশন ব্র্যান্ড হয়ে ওঠা।<br>
            - গ্রাহকদের জীবনকে আরও রঙিন, স্টাইলিশ এবং স্বতন্ত্র করা।<br>
            - নতুন ট্রেন্ড ও ডিজাইন উদ্ভাবনের মাধ্যমে ফ্যাশন শিল্পে নেতৃত্ব প্রদর্শন করা।</p>
        </div>
        <div>
            <h3>আমাদের গল্প</h3>
            <p>Mariyan Fashion House-এর যাত্রা শুরু হয়েছিল এক ছোট্ট দোকান থেকে। বছরের পর বছর ধরে আমরা আমাদের প্রোডাক্ট লাইন বিস্তৃত করেছি। এখন আমাদের কাছে রয়েছে আধুনিক পোশাক, এক্সেসরিজ, ফর্মাল ও কজুয়াল পোশাক। আমাদের টিমের প্রতিটি সদস্য ফ্যাশন এবং গ্রাহক সন্তুষ্টির প্রতি প্রতিশ্রুতিবদ্ধ।</p>
        </div>
        <div>
            <h3>কেন Mariyan Fashion House?</h3>
            <p>- সর্বোচ্চ মানের এবং স্টাইলিশ ফ্যাশন।<br>
            - ক্রেতাদের জন্য সহজ ও নিরাপদ শপিং অভিজ্ঞতা।<br>
            - নতুন ট্রেন্ডের সাথে সবসময় আপডেট থাকা।<br>
            - দেশব্যাপী দ্রুত ডেলিভারি এবং বিশ্বস্ত সেবা।</p>
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
    // Calculate total quantity in cart
    const count = Object.values(cartMemory).reduce((sum,i)=>sum+i.qty,0);
    
    // Update all badge elements
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

// --- Full Cart Logic for Consistency ---

function addToCart(id){
    fetch('cart_action.php',{
        method:'POST',
        headers:{'Content-Type':'application/x-www-form-urlencoded'},
        body:'action=add&id='+id
    }).then(res=>res.json()).then(data=>{
        if(data.status==='success'){
            // Ensure product image path is correct for client-side memory
            if(data.product && !data.product.image) {
                const imagePath = data.product.first_image ? "admin/" + data.product.first_image : "admin/uploads/default.png";
                data.product.image = imagePath;
            }
            // Update cartMemory: If item exists, increment qty; otherwise, add new item
            cartMemory[id] = cartMemory[id] ? {...cartMemory[id], qty: cartMemory[id].qty + 1} : {...data.product, qty: 1};
            
            updateAllCartCounts();
            if(document.getElementById('side-cart').classList.contains('active')) loadCartItems();
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
    const totalDiv=document.getElementById('cart-total');
    container.innerHTML='';
    const items=Object.values(cartMemory);
    
    if(items.length===0){ 
        container.innerHTML='<p>Your cart is empty.</p>'; 
        totalDiv.innerText = 'Total: ৳0.00';
        return; 
    }
    let total=0;
    items.forEach(item=>{
        const itemTotal=item.price*item.qty;
        total+=itemTotal;
        const div=document.createElement('div'); 
        div.className='cart-item'; 
        div.dataset.id=item.id;
        div.innerHTML=`
            <img src="${item.image}" alt="${item.name}">
            <div class="details">
                <strong>${item.name}</strong><br>
                ৳${item.price.toFixed(2)} x ${item.qty} = ৳${itemTotal.toFixed(2)}
                <div class="qty-controls">
                    <button onclick="updateQty(${item.id},-1)">-</button>
                    <button onclick="updateQty(${item.id},1)">+</button>
                    <button class="delete-btn" onclick="removeItem(${item.id})">✖</button>
                </div>
            </div>`;
        container.appendChild(div);
    });
    
    totalDiv.style.marginTop='10px';
    totalDiv.style.fontWeight='bold';
    totalDiv.style.fontSize='16px';
    totalDiv.style.textAlign='right';
    totalDiv.innerText=`Total: ৳${total.toFixed(2)}`;
}

document.addEventListener('DOMContentLoaded', () => {
    updateAllCartCounts();
    // Only load items on DOMContentLoaded if the cart is active, but we need to run it once
    // to initialize the display based on the PHP data.
    loadCartItems();
});
</script>

</body>
</html>