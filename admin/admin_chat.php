<?php
session_start();
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

try {
$pdo = new PDO("mysql:host=localhost;dbname=mariyamf_mariyam_fashion", "mariyamf", "Es)0Abi774An;G");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

// Send admin reply via AJAX
if(isset($_POST['send_reply'])){
    $session_id = $_POST['session_id'];
    $message = $_POST['message'];
    $stmt = $pdo->prepare("INSERT INTO chat_messages(session_id, sender, message) VALUES (?, 'admin', ?)");
    $stmt->execute([$session_id, $message]);
    echo "success";
    exit;
}

// Delete chat session via AJAX
if(isset($_POST['delete_chat'])){
    $session_id = $_POST['session_id'];
    $stmt = $pdo->prepare("DELETE FROM chat_messages WHERE session_id=?");
    $stmt->execute([$session_id]);
    $stmt2 = $pdo->prepare("DELETE FROM chat_sessions WHERE id=?");
    $stmt2->execute([$session_id]);
    echo "deleted";
    exit;
}

// Fetch messages for a session via AJAX
if(isset($_GET['session_id'])){
    $session_id = $_GET['session_id'];
    $stmt = $pdo->prepare("SELECT * FROM chat_messages WHERE session_id=? ORDER BY created_at ASC");
    $stmt->execute([$session_id]);
    $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach($messages as $msg){
        $type = $msg['sender'] === 'user' ? 'user' : 'admin';
        echo "<div class='message $type'><div class='text'>".htmlspecialchars($msg['message'])."</div></div>";
    }
    exit;
}

// Fetch all chat sessions
$sessions = $pdo->query("SELECT * FROM chat_sessions ORDER BY started_at DESC")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Admin Chat - Admin Panel</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<style>
body {margin:0;font-family:'Segoe UI',sans-serif;display:flex;background:#f0f4ff;color:#333;}
/* Sidebar */
.sidebar {
    width:220px;
    background: linear-gradient(180deg,#001F3F,#0056b3);
    color:#fff;
    height:100vh;
    position:fixed;
    top:0;
    left:0;
    padding-top:60px;
    box-shadow:2px 0 10px rgba(0,0,0,0.2);
    transition:all 0.3s ease;
    overflow-y:auto;
    overflow-x:hidden;
    z-index:1000;
}
.sidebar.hidden {left:-220px;}
.sidebar h2 {text-align:center;color:#00BFFF;margin-bottom:20px;position:absolute;top:0;width:100%;padding:15px 0;border-bottom:1px solid rgba(255,255,255,0.2);font-size:18px;font-weight:bold;}
.sidebar ul {list-style:none;padding:0;margin:0;}
.sidebar ul li {margin-bottom:5px;}
.sidebar ul li a {display:flex; align-items:center;color:#fff;padding:12px 20px;text-decoration:none;font-size:15px;border-radius:8px 0 0 8px;transition:0.3s;cursor:pointer;white-space:nowrap;}
.sidebar ul li a i {margin-right:12px;width:25px;text-align:center;font-size:16px;}
.sidebar ul li a:hover {background:rgba(255,255,255,0.15);}
.sidebar ul li a.active {background:#00BFFF;color:#001F3F;font-weight:bold;}
/* Toggle button */
.toggle-btn {position:fixed; top:15px; left:230px; background:#0056b3; color:#fff; border-radius:50%; padding:8px 10px; cursor:pointer; box-shadow:0 4px 6px rgba(0,0,0,0.2); transition:all 0.3s ease; z-index:1100;}
.sidebar.hidden + .toggle-btn {left:15px;}
.toggle-btn:hover {background:#003d80;}
/* Main content */
.main {margin-left:220px; padding:30px; flex-grow:1; width:calc(100% - 220px); transition:all 0.3s ease; margin-top:30px;}
.main.expanded {margin-left:0;width:100%;}
/* Logout button */
.logout-btn {position:absolute; top:15px; right:20px; background:#FF4B5C; color:#fff; padding:8px 15px; font-weight:bold; border-radius:6px; text-decoration:none; transition:0.3s;}
.logout-btn:hover {background:#e63946;}
/* Sessions list */
.sessions {margin-bottom:20px; max-height:300px; overflow-y:auto;}
.sessions button {display:block;width:100%;padding:10px;margin-bottom:5px;border:none;background:#0074D9;color:#fff;text-align:left;cursor:pointer;border-radius:5px;font-size:14px;}
.sessions button:hover {background:#005fa3;}
.sessions button.active {background:#00BFFF;color:#001F3F;}
/* Chat window */
.chat-window {border:1px solid #ccc;border-radius:8px;background:#fff;display:flex;flex-direction:column;height:400px;}
.chat-body {flex:1;padding:10px;overflow-y:auto;background:#f4f4f4;}
.message {margin:8px 0;display:flex;align-items:flex-end;}
.message.user {justify-content:flex-start;}
.message.user .text {background:#e0e0e0;color:#333;border-radius:16px 16px 16px 0;padding:10px 14px;max-width:75%;word-wrap:break-word;}
.message.admin {justify-content:flex-end;}
.message.admin .text {background:#00c851;color:#fff;border-radius:16px 16px 0 16px;padding:10px 14px;max-width:75%;word-wrap:break-word;}
.chat-input {display:flex;border-top:1px solid #ddd;}
.chat-input input {flex:1;padding:10px;border:none;outline:none;}
.chat-input button {padding:10px 12px;border:none;cursor:pointer;margin-left:5px;}
.chat-input button#sendReply {background:#0074D9;color:#fff;}
.chat-input button#deleteChat {background:#FF4B5C;color:#fff;}
.chat-input button:hover {opacity:0.9;}
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

<div class="main" id="main">
    <a href="logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Logout</a>

    <h2>Chat Sessions</h2>
    <div class="sessions">
        <?php if(!empty($sessions)): ?>
            <?php foreach($sessions as $s): ?>
                <button data-id="<?= $s['id'] ?>"><?= htmlspecialchars($s['user_name'])." (".htmlspecialchars($s['user_email']).")" ?></button>
            <?php endforeach; ?>
        <?php else: ?>
            <p>No chat sessions found.</p>
        <?php endif; ?>
    </div>

    <div class="chat-window" id="chatWindow" style="display:none;">
        <div class="chat-body" id="chatBody"></div>
        <div class="chat-input">
            <input type="text" id="adminInput" placeholder="Type a reply...">
            <button id="sendReply"><i class="fas fa-paper-plane"></i></button>
            <button id="deleteChat"><i class="fas fa-trash"></i> Delete Chat</button>
        </div>
    </div>
</div>

<script>
// Sidebar toggle
const toggleBtn = document.getElementById("toggleBtn");
const sidebar = document.getElementById("sidebar");
const main = document.getElementById("main");
toggleBtn.addEventListener("click", () => {
    sidebar.classList.toggle("hidden");
    main.classList.toggle("expanded");
});

// Orders submenu toggle
const ordersMenu = document.getElementById("ordersMenu");
const ordersSubmenu = document.getElementById("ordersSubmenu");
ordersMenu.addEventListener("click", () => {
    ordersSubmenu.style.display = ordersSubmenu.style.display === "block" ? "none" : "block";
});

// Chat session toggle
const sessionButtons = document.querySelectorAll(".sessions button");
const chatWindow = document.getElementById("chatWindow");
const chatBody = document.getElementById("chatBody");
let currentSession = null;
let refreshInterval = null;

sessionButtons.forEach(btn => {
    btn.addEventListener("click", () => {
        const sessionId = btn.getAttribute("data-id");

        // Toggle off if clicking same session
        if(currentSession === sessionId){
            chatWindow.style.display = "none";
            chatBody.innerHTML = '';
            btn.classList.remove("active");
            currentSession = null;
            if(refreshInterval) clearInterval(refreshInterval);
            return;
        }

        // Open new session
        sessionButtons.forEach(b => b.classList.remove("active"));
        btn.classList.add("active");
        currentSession = sessionId;
        chatWindow.style.display = "flex";
        loadMessages();
        if(refreshInterval) clearInterval(refreshInterval);
        refreshInterval = setInterval(loadMessages, 3000);
    });
});

function appendMessage(type,text){
    const msgDiv = document.createElement('div');
    msgDiv.classList.add('message',type);
    const textDiv = document.createElement('div');
    textDiv.classList.add('text');
    textDiv.textContent=text;
    msgDiv.appendChild(textDiv);
    chatBody.appendChild(msgDiv);
    chatBody.scrollTop = chatBody.scrollHeight;
}

function loadMessages(){
    if(!currentSession) return;
    fetch('admin_chat.php?session_id='+currentSession)
        .then(res => res.text())
        .then(html => {
            chatBody.innerHTML = html;
            chatBody.scrollTop = chatBody.scrollHeight;
        });
}

// Send reply
document.getElementById("sendReply").addEventListener("click", ()=>{
    const msg = document.getElementById("adminInput").value.trim();
    if(!msg || !currentSession) return;
    appendMessage('admin', msg);
    document.getElementById("adminInput").value='';
    fetch('admin_chat.php', {
        method:'POST',
        headers:{'Content-Type':'application/x-www-form-urlencoded'},
        body:`send_reply=1&session_id=${currentSession}&message=${encodeURIComponent(msg)}`
    });
});

document.getElementById("adminInput").addEventListener("keypress",(e)=>{
    if(e.key==='Enter') document.getElementById("sendReply").click();
});

// Delete chat
document.getElementById("deleteChat").addEventListener("click", ()=>{
    if(!currentSession) return;
    if(confirm("Are you sure you want to delete this chat?")) {
        fetch('admin_chat.php', {
            method:'POST',
            headers:{'Content-Type':'application/x-www-form-urlencoded'},
            body:`delete_chat=1&session_id=${currentSession}`
        }).then(res=>res.text())
        .then(data=>{
            if(data==='deleted'){
                alert("Chat deleted successfully!");
                chatBody.innerHTML = '';
                chatWindow.style.display='none';
                const btn = document.querySelector(`.sessions button[data-id='${currentSession}']`);
                if(btn) btn.remove();
                currentSession = null;
                clearInterval(refreshInterval);
            }
        });
    }
});
</script>
</body>
</html>
