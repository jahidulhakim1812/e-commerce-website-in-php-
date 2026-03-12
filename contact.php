<?php
// C:\xampp\htdocs\mariyam_fashion\index.php

// ====================================================================
// 1. PHP LOGIC (Includes config.php to establish $pdo and session)
// ====================================================================

// This line includes config.php, which defines the database connection $pdo.
include 'config.php'; 

// CRUCIAL FIX: Declares $pdo as global to ensure it's accessible in the main script scope.
global $pdo;
// ---------------- DATABASE SETUP (RUN ONCE) ----------------
// This logic ensures the tables exist without running an execution query every time.
function initialize_chat_tables($pdo) {
    // Check if a representative table exists before attempting creation
    try {
        $pdo->query("SELECT 1 FROM chat_sessions LIMIT 1");
    } catch (PDOException $e) {
        // Table doesn't exist, so create it
        $pdo->exec("CREATE TABLE chat_sessions (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_name VARCHAR(255),
            user_email VARCHAR(255),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )");
        $pdo->exec("CREATE TABLE chat_messages (
            id INT AUTO_INCREMENT PRIMARY KEY,
            session_id INT,
            sender ENUM('user','admin'),
            message TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (session_id) REFERENCES chat_sessions(id) ON DELETE CASCADE
        )");
    }
}
// Run table creation logic if needed
// initialize_chat_tables($pdo); 
// NOTE: Commented out the initialization call above. It's safer to run this manually once or only when necessary, but if you keep it, uncomment the line.

// ---------------- PHP AJAX Endpoints ----------------

// Start new chat session
if(isset($_POST['start_session'])){
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    if(empty($name) || empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        http_response_code(400);
        echo "Invalid name or email.";
        exit;
    }

    $stmt = $pdo->prepare("INSERT INTO chat_sessions(user_name,user_email) VALUES(?,?)");
    $stmt->execute([$name,$email]);
    echo $pdo->lastInsertId();
    exit;
}

// Save message
if(isset($_POST['send_message'])){
    $sid = intval($_POST['session_id']);
    $msg = trim($_POST['message']);
    if($msg!==""){
        $stmt = $pdo->prepare("INSERT INTO chat_messages(session_id,sender,message) VALUES (?,?,?)");
        $stmt->execute([$sid,"user",$msg]);
    }
    exit;
}

// Load messages
if(isset($_POST['load_messages'])){
    $sid = intval($_POST['session_id']);
    $stmt = $pdo->prepare("SELECT * FROM chat_messages WHERE session_id=? ORDER BY created_at ASC");
    $stmt->execute([$sid]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC); // Ensure consistent fetch mode
    foreach($rows as $r){
        $cls = $r['sender']=="user"?"user":"admin";
        echo "<div class='message $cls'><div class='text'>".htmlspecialchars($r['message'])."</div></div>";
    }
    exit;
}

// ---------------- Cart Data Initialization ----------------

$cart_items = [];
$cart_count = 0;
if(isset($_SESSION['cart']) && !empty($_SESSION['cart'])){
    $ids = array_keys($_SESSION['cart']);
    $valid_ids = array_filter($ids, 'is_numeric');

    if (!empty($valid_ids)) {
        $placeholders = str_repeat('?,', count($valid_ids) - 1) . '?';
        $stmt = $pdo->prepare("
            SELECT p.id, p.name, p.price,
                   (SELECT image_path FROM product_images WHERE product_id=p.id LIMIT 1) AS first_image
            FROM products p WHERE p.id IN ($placeholders)
        ");
        $stmt->execute($valid_ids);
        $prods = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach($prods as $prod){
            $qty = $_SESSION['cart'][$prod['id']];
            $image = !empty($prod['first_image']) ? "admin/".$prod['first_image'] : "admin/uploads/default.png";
            $cart_items[$prod['id']] = [
                'id' => $prod['id'],
                'name' => htmlspecialchars($prod['name']),
                'price' => floatval($prod['price']),
                'image' => htmlspecialchars($image),
                'qty' => intval($qty)
            ];
        }
    }
    $cart_count = array_sum(array_column($cart_items, 'qty'));
}
$cart_items_json = json_encode($cart_items);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Contact Us - Mariyan Fashion</title>
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
    --chat-red: #ff3b30; /* Specific color for chat elements */
    --chat-green: #00c851; /* Specific color for user messages */
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
/* ===== MOBILE HEADER & NAV ===== */
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
    height: 60px; /* Fixed height for consistent bottom clearance */
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
/* ===== CONTACT & CART STYLES ===== */
/* ================================== */

/* Side Cart */
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
#side-cart.active { right: 0; }
@media (max-width: 400px) { #side-cart { width: 85%; } }
#side-cart h3 { margin-top: 0; font-size: 20px; color: var(--mid-blue); }
.cart-item{display:flex;align-items:center;gap:10px;margin:15px 0;border-bottom:1px solid #eee;padding-bottom:10px;}
.cart-item img{width:60px;height:60px;object-fit:cover;border-radius:6px;}
.cart-item .details{flex:1;}
.qty-controls{display:flex;align-items:center;gap:6px;margin-top:5px;}
.qty-controls button{padding:2px 6px;border:none;background:var(--primary-blue);color:#fff;border-radius:4px;cursor:pointer;font-size:12px;}
.qty-controls button:hover{background:#005fa3;}
.delete-btn{background:var(--red-alert) !important;}
#close-cart{cursor:pointer;float:right;font-size:18px;color:var(--red-alert);}
#cart-total{font-weight:bold;font-size:16px;margin-top:10px;text-align:right;color:var(--mid-blue);}
.checkout-btn{display:block;width:100%;padding:12px;background:var(--primary-blue);color:#fff;border:none;border-radius:6px;font-size:16px;margin-top:20px;cursor:pointer;}
.checkout-btn:hover{background:#005fa3;}

/* Contact Section */
.contact-section{max-width:900px;margin:40px auto;padding:25px;background:#fff;border-radius:20px;box-shadow:0 4px 12px rgba(0,0,0,0.08);}
.contact-section h2{color:var(--mid-blue);font-size:28px;text-align:center;margin-bottom:20px; border-bottom: 2px solid var(--primary-blue); padding-bottom: 5px;}
.contact-section p{font-size:16px;margin-bottom:10px;}
.contact-section p strong{color:var(--primary-blue);}

/* Footer (NOT fixed) */
.footer {
    background: var(--dark-blue);
    color: #fff;
    padding: 30px 20px;
    text-align: center;
    margin-top: 40px; 
    display:fixed;
}
.footer h3{margin-bottom:12px;font-size:20px;color:var(--light-blue);}

/* Chat (Base styles for desktop and mobile consistency) */
#chat-icon{
    position:fixed;
    bottom:20px; /* Desktop position */
    right:20px;
    background:var(--chat-red);
    color:#fff;
    width:60px; 
    height:60px; 
    display:flex;
    justify-content:center;
    align-items:center;
    border-radius:50%;
    cursor:pointer;
    z-index:9999;
    font-size: 2.5em; /* Consistent Icon Size */
}
#chat-widget{
    position:fixed;
    bottom:90px; /* Desktop position (above the icon) */
    right:20px;
    width:320px;
    max-height:400px;
    background:#fff;
    border-radius:12px;
    box-shadow:0 6px 20px rgba(0,0,0,0.2);
    display:none;
    flex-direction:column;
    overflow:hidden;
    z-index:9999;
}
.chat-header{background:var(--chat-red);color:#fff;padding:12px;font-size:16px;font-weight:bold;display:flex;justify-content:space-between;align-items:center;}
.chat-body{flex:1;padding:10px;overflow-y:auto;background:#f4f4f4;}
.message{margin:8px 0;display:flex;align-items:flex-end;}
.message.user{justify-content:flex-end;}
.message.user .text{background:var(--chat-green);color:#fff;border-radius:16px 16px 0 16px;padding:10px 14px;max-width:75%;word-wrap:break-word;}
.message.admin{justify-content:flex-start;}
.message.admin .text{background:#e0e0e0;color:#333;border-radius:16px 16px 16px 0;padding:10px 14px;max-width:75%;word-wrap:break-word;}
.chat-input{display:flex;border-top:1px solid #ddd;}
.chat-input input{flex:1;padding:10px 12px;border:none;font-size:14px;outline:none;}
.chat-input button{padding:10px 12px;border:none;background:var(--chat-red);color:#fff;cursor:pointer;}
#user-info{padding:10px; display:flex; flex-direction: column; gap: 8px;}
#user-info input{width:100%;padding:8px;border:1px solid #ccc; border-radius: 4px; box-sizing: border-box;}
#user-info button{padding:10px;border:none;background:var(--primary-blue);color:#fff;cursor:pointer;border-radius:6px; margin-top: 5px;}


/* ================================== */
/* ===== MEDIA QUERIES (Responsiveness) ===== */
/* ================================== */

/* Desktop & Tablet (769px and up) */
@media(min-width: 769px) {
    .full-header-desktop { display: flex; }
    .desktop-nav { display: flex; }
    .main-header, .mobile-menu-overlay, .bottom-nav { display: none !important; }
    body { padding-bottom: 0; }
    .footer { margin-top: 40px; } 
}

/* Tablet & Mobile (768px and below) */
@media(max-width: 768px) {
    /* Show mobile header/nav, hide desktop */
    .main-header, .bottom-nav { display: flex; }
    .full-header-desktop, .desktop-nav { display: none !important; }
    
    /* Set padding for content to clear the fixed bottom nav (60px height) */
    body { padding-bottom: 70px; } /* Slightly more than 60px for breathing room */
    .footer { margin-bottom: 70px; }

    /* Contact Section Optimization */
    .contact-section { 
        margin: 15px auto; 
        padding: 15px; 
        border-radius: 10px;
    }
    .contact-section h2{font-size: 26px;}

    /* CHAT ICON AND WIDGET: PERFECTED MOBILE POSITIONING */
    #chat-icon { 
        bottom: 75px; /* Sits above the 60px bottom nav + 15px gap */
    }
    #chat-widget { 
        width: 90%; 
        max-width: 320px; 
        /* Positioned above the icon */
        bottom: 150px; /* 75px (icon bottom) + 60px (icon height) + 15px (gap) = 150px */
        right: 5%; 
        left: unset;
        max-height: 70vh;
    }
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
    <a href="about.php">About</a>
    <a href="contact.php" class="active">Contact</a>
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
    <div id="cart-items"></div>
    <div id="cart-total">Total: ৳<span id="cart-total-amount">0.00</span></div>
    <a href="checkout.php" class="checkout-btn">Order Now</a>
</div>

<div class="contact-section">
    <h2>Contact Us</h2>
    <p><strong>Address:</strong> Purbachal 300 feet,Dhaka,Bangladesh</p>
    <p><strong>Email:</strong> mariyamfashionhouse@gmail.com</p>
    <p><strong>Phone:</strong> +880 1342-866580</p>
    <p>We are here to assist you with any inquiries or orders. Use the chat button at the bottom-right corner to start a conversation with our support team.</p>
</div>

<div id="chat-icon"><i class="fas fa-comments"></i></div>

<div id="chat-widget">
    <div class="chat-header">
        Customer Support
        <div>
            <button id="end-chat-btn" style="background:rgba(255,255,255,0.2);border:none;color:#fff;padding:3px 7px;border-radius:5px;cursor:pointer;margin-right:10px;">End</button>
            <span id="close-chat" style="cursor:pointer;"><i class="fas fa-times"></i></span>
        </div>
    </div>
    <div id="user-info">
        <input type="text" id="user-name" placeholder="Enter your name" required>
        <input type="email" id="user-email" placeholder="Enter your email" required>
        <button id="start-chat-btn">Start Chat</button>
    </div>
    <div class="chat-body" id="chat-body" style="display:none;"></div>
    <div class="chat-input" id="chat-input-area" style="display:none;">
        <input type="text" id="chat-input" placeholder="Type a message...">
        <button id="send-btn">Send</button>
    </div>
</div>

<div class="footer">
    <h3>Contact Us</h3>
    <p>📍 Purbachal 300 feet,Dhaka</p>
    <p>📞 +880 1342-866580</p>
    <p>📧 mariyamfashionhouse@gmail.com</p>
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
    <a href="contact.php" class="active">
        <i class="fa fa-phone"></i> <span>Contact</span>
    </a>
</div>

<script>
// Initialize cart from PHP session data
let cartMemory = <?= $cart_items_json ?>;

// --- Chat Logic ---
let chatWidget=document.getElementById("chat-widget");
let chatIcon=document.getElementById("chat-icon");
let closeChatBtn=document.querySelector("#chat-widget #close-chat");
let chatBody=document.getElementById("chat-body");
let chatInput=document.getElementById("chat-input");
let sendBtn=document.getElementById("send-btn");
let userInfo=document.getElementById("user-info");
let chatInputArea=document.getElementById("chat-input-area");
let startChatBtn=document.getElementById("start-chat-btn");
let sessionId=null;

function toggleChatWidget(){
    const isVisible = chatWidget.style.display === 'flex';
    chatWidget.style.display = isVisible ? 'none' : 'flex';
    localStorage.setItem("chatOpen", !isVisible);
    if (!isVisible && sessionId) loadMessages();
}

function endChat(){
    if(confirm("Are you sure you want to end and clear this chat session?")){
        localStorage.removeItem("sessionId");
        localStorage.removeItem("chatOpen");
        sessionId=null;
        chatBody.innerHTML="";
        chatWidget.style.display="none";
        userInfo.style.display="flex";
        chatBody.style.display="none";
        chatInputArea.style.display="none";
    }
}

function startNewSession(){
    let name=document.getElementById("user-name").value.trim();
    let email=document.getElementById("user-email").value.trim();
    if(!name||!email){
        alert("Please enter your name and email to start the chat.");
        return;
    }
    
    // Send data to PHP to create a new session
    fetch("contact.php",{method:"POST",headers:{"Content-Type":"application/x-www-form-urlencoded"},
        body:`start_session=1&name=${encodeURIComponent(name)}&email=${encodeURIComponent(email)}`})
    .then(res=>res.text()).then(id=>{
        if(isNaN(parseInt(id))) {
            alert("Error starting chat session.");
            return;
        }
        
        sessionId=id;
        localStorage.setItem("sessionId",id);
        userInfo.style.display='none';
        chatBody.style.display='block';
        chatInputArea.style.display='flex';
        loadMessages();
    }).catch(error => {
        console.error('Error:', error);
        alert('Failed to start chat session due to a network or server error.');
    });
}

function sendMessage(){
    let msg=chatInput.value.trim();
    if(msg==="" || !sessionId)return;

    // Append message instantly for better UX
    const tempMsg = document.createElement('div');
    tempMsg.className = 'message user';
    tempMsg.innerHTML = `<div class='text'>${msg}</div>`;
    chatBody.appendChild(tempMsg);
    chatInput.value = "";
    chatBody.scrollTop = chatBody.scrollHeight;
    
    fetch("contact.php",{method:"POST",headers:{"Content-Type":"application/x-www-form-urlencoded"},
        body:`send_message=1&session_id=${sessionId}&message=${encodeURIComponent(msg)}`})
    .then(()=>{
        // Message sent, the loadMessages timer will pick up admin replies
    }).catch(error => {
        console.error('Error sending message:', error);
        alert('Failed to send message.');
        // Optionally, remove the temporary message here
    });
}

let messageLoaderTimeout;
function loadMessages(){
    clearTimeout(messageLoaderTimeout);
    if(!sessionId)return;
    
    fetch("contact.php",{method:"POST",headers:{"Content-Type":"application/x-www-form-urlencoded"},
        body:`load_messages=1&session_id=${sessionId}`})
    .then(res=>res.text()).then(html=>{
        const shouldScroll = chatBody.scrollHeight - chatBody.clientHeight <= chatBody.scrollTop + 1;
        
        chatBody.innerHTML=html;
        
        if (shouldScroll) {
            chatBody.scrollTop=chatBody.scrollHeight;
        }
        
        // Poll for new messages every 3 seconds
        messageLoaderTimeout = setTimeout(loadMessages,3000);
    });
}

// Event Listeners for Chat
chatIcon.addEventListener("click", toggleChatWidget);
closeChatBtn.addEventListener("click", toggleChatWidget);
startChatBtn.addEventListener("click", startNewSession);
sendBtn.addEventListener("click", sendMessage);
document.getElementById("end-chat-btn").addEventListener("click", endChat);
chatInput.addEventListener("keypress", e => {if(e.key==="Enter")sendBtn.click();});

// Restore chat state on load
document.addEventListener('DOMContentLoaded', () => {
    if(localStorage.getItem("chatOpen")==="true"){
        chatWidget.style.display='flex';
        sessionId=localStorage.getItem("sessionId");
        if(sessionId){
            userInfo.style.display='none';
            chatBody.style.display='block';
            chatInputArea.style.display='flex';
            loadMessages();
        } else {
             userInfo.style.display='flex';
        }
    }
    updateAllCartCounts(); // Initial cart count load
});


// --- Cart Functions ---

// Mobile Menu
function openMobileMenu() { document.getElementById('mobile-menu').style.width = "70%"; }
function closeMobileMenu() { document.getElementById('mobile-menu').style.width = "0"; }


function updateAllCartCounts(){
    const count = Object.values(cartMemory).reduce((sum,i)=>sum+i.qty,0);
    const countDisplay = count > 99 ? '99+' : count;

    const desktop = document.getElementById('cart-count-desktop');
    const mobile = document.getElementById('cart-count-mobile');
    const bottom = document.getElementById('cart-count-bottom');
    if(desktop) desktop.innerText = countDisplay;
    if(mobile) mobile.innerText = countDisplay;
    if(bottom) bottom.innerText = countDisplay;
}

function loadCartItems(){
    const container=document.getElementById('cart-items'); 
    container.innerHTML='';
    const items=Object.values(cartMemory);
    let total=0;

    if(items.length===0){ 
        container.innerHTML='<p>Your cart is empty.</p>'; 
        document.getElementById('cart-total-amount').innerText = "0.00";
        return; 
    }
    
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
                ৳${item.price.toFixed(2)} x <span class="qty">${item.qty}</span> = ৳<span class="subtotal">${itemTotal.toFixed(2)}</span>
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

function openCart(){document.getElementById('side-cart').classList.add('active'); loadCartItems();}
function closeCart(){document.getElementById('side-cart').classList.remove('active');}

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
                if(cartMemory[id]) cartMemory[id].qty = data.qty;
            }
            updateAllCartCounts();
            loadCartItems(); 
        } else {
             alert('Error updating cart: ' + data.message);
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
        } else {
            alert('Error removing item: ' + data.message);
        }
    });
}
</script>
</body>
</html>