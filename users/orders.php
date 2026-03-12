<?php
// ====================================================================
// dashboard.php - Customer Dashboard (DELIVERED Orders ONLY)
// Shows orders where status IS strictly 'Delivered'.
// ====================================================================

session_start();

// --- CONFIGURATION ---
const DB_HOST = 'localhost';    
const DB_USERNAME = 'mariyamf';     
const DB_PASSWORD = 'Es)0Abi774An;G';        
const DB_NAME = 'mariyamf_mariyam_fashion';

// --- INITIALIZE VARIABLES ---
$conn = null;
$logged_in_user = ['username' => 'Customer']; 
$delivered_orders = []; 
$user_id = null;
$display_username = 'Customer';

try {
    // --- 1. Database Connection ---
    $conn = new mysqli(DB_HOST, DB_USERNAME, DB_PASSWORD, DB_NAME);
    if ($conn->connect_error) {
        error_log("DB Connection failed: " . $conn->connect_error); 
        die("Database connection failed. Please try again later."); 
    }
    
    // --- 2. Authentication Check ---
    if (!isset($_SESSION['user_id'])) {
        header("Location: /mariyam_fashion/login.php"); 
        exit;
    }
    $user_id = $_SESSION['user_id'];
    
    // --- 3. Fetch User Details ---
    $stmt_user = $conn->prepare("SELECT username FROM users WHERE id = ?");
    $stmt_user->bind_param("i", $user_id);
    $stmt_user->execute();
    $result_user = $stmt_user->get_result();
    if ($row = $result_user->fetch_assoc()) {
        $logged_in_user = $row;
        $display_username = htmlspecialchars($row['username']);
    }
    $stmt_user->close();
    
    // --- 4. Fetch DELIVERED ORDERS ONLY ---
    $stmt_orders = $conn->prepare("
        SELECT id, total_amount, status, created_at, shipping_fee 
        FROM orders 
        WHERE user_id = ? 
        AND status = '1' 
        ORDER BY created_at DESC
    ");
    $stmt_orders->bind_param("i", $user_id);
    $stmt_orders->execute();
    $result_orders = $stmt_orders->get_result();
    
    while ($order = $result_orders->fetch_assoc()) {
        $order['final_total'] = $order['total_amount'] + $order['shipping_fee'];
        $delivered_orders[] = $order;
    }
    $stmt_orders->close();
    
} catch (Exception $e) {
    error_log("Delivered Orders Dashboard error: " . $e->getMessage());
    die("An error occurred while loading your orders.");
} finally {
    if ($conn && $conn->ping()) {
        $conn->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Delivered Orders - Dashboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    
    <style>
        /* dashboard.css - shared styles for customer dashboards */

/* Variables */
:root {
    --corporate-blue: #092C45; 
    --accent-sky: #1D976C;    
    --light-bg: #F5F7FA;      
    --card-bg: #FFFFFF;       
    --text-dark: #2C3E50;     
    --primary-red: #D64545;   
    --status-delivered: #1D976C; 
    --status-pending: #F39C12; 
    --border-subtle: #DDE1E5; 
}

/* Reset + global */
* { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Inter', 'Arial', sans-serif; }
body { background-color: var(--light-bg); color: var(--text-dark); line-height: 1.6; }
a { text-decoration: none; color: inherit; transition: color 0.3s; }

/* HEADER */
.page-container { min-height: 100vh; display: flex; flex-direction: column; }
.header {
    background: var(--corporate-blue); 
    color: #fff;
    padding: 15px 5%;
    box-shadow: 0 4px 10px rgba(0, 0, 0, 0.15); 
    display: flex; justify-content: space-between; align-items: center;
    flex-wrap: wrap; 
}
.logo-text { font-size: 28px; font-weight: 800; color: #fff; letter-spacing: 1px; }

.main-nav { display: flex; gap: 25px; margin-left: 40px; margin-right: auto; align-items: center; }
.nav-link { font-size: 15px; font-weight: 500; color: rgba(255, 255, 255, 0.7); padding: 5px 0; }
.nav-link:hover { color: #fff; }
.nav-link.active { color: #fff; font-weight: 700; border-bottom: 3px solid var(--accent-sky); }

/* User info and logout */
.user-info { display: flex; align-items: center; gap: 20px; }
.username-display {
    font-size: 15px; font-weight: 600; color: rgba(255, 255, 255, 0.9);
    padding: 4px 8px; border-radius: 4px; background: rgba(255, 255, 255, 0.1);
}
.logout-btn {
    background-color: var(--primary-red); color: #fff;
    padding: 10px 18px; font-weight: 600; display: flex; align-items: center; gap: 8px;
    border-radius: 25px; box-shadow: 0 3px 8px rgba(214, 69, 69, 0.3);
}
.logout-btn:hover { background-color: #C0392B; transform: translateY(-2px); box-shadow: 0 5px 10px rgba(214, 69, 69, 0.5); }

/* MAIN CONTENT */
.main-content { flex-grow: 1; padding: 40px 5%; max-width: 1200px; width: 100%; margin: 0 auto; }
.main-content h1 { font-size: 36px; font-weight: 800; margin-bottom: 5px; color: var(--corporate-blue); }
.main-content h2 { font-size: 22px; margin-bottom: 30px; color: var(--text-dark); font-weight: 500; border-bottom: 2px solid var(--border-subtle); padding-bottom: 8px; }

.order-card-wrapper { display: flex; flex-direction: column; gap: 15px; }
.order-card {
    background: var(--card-bg); border-left: 6px solid var(--status-pending);
    border-radius: 8px; padding: 20px 25px; box-shadow: 0 2px 5px rgba(0,0,0,0.05); 
    display: flex; align-items: center;
}
.order-summary { flex: 1 1 50%; display: flex; flex-direction: column; gap: 4px; }
.order-summary h3 { font-size: 18px; color: #fff; }
.order-summary p { font-size: 14px; color: #fff; }
.order-summary .details-link { margin-top: 8px; color: var(--corporate-blue); font-weight: 600; display: flex; gap: 5px; }

/* Right side */
.order-details-right { display: flex; align-items: center; gap: 30px; margin-left: auto; }
.order-card .total { font-size: 24px; font-weight: 800; color: #fff; min-width: 120px; text-align: right; }

/* Status badge */
.status-badge { padding: 8px 16px; border-radius: 20px; font-size: 12px; font-weight: 700; color: #fff; text-transform: uppercase; letter-spacing: 0.5px; min-width: 120px; text-align: center; }
.status-Pending { background-color: var(--status-pending); }
.status-Delivered { background-color: var(--status-delivered); }

/* Empty state */
.empty-state { background: #fff; border: 2px dashed var(--border-subtle); border-radius: 8px; padding: 60px; text-align: center; margin-top: 30px; box-shadow: 0 4px 8px rgba(0,0,0,0.05); }
.message-heading { font-size: 26px; color: var(--corporate-blue); margin-bottom: 10px; font-weight: 700; }
.message-subtext a { color: var(--accent-sky); font-weight: 600; text-decoration: underline; }

/* Footer */
.footer { background-color: var(--corporate-blue); color: rgba(255, 255, 255, 0.6); padding: 20px 5%; text-align: center; margin-top: 40px; font-size: 14px; }

/* Responsive */
@media (max-width: 768px) {
    .header { flex-direction: column; align-items: flex-start; }
    .main-nav { margin: 15px 0 0 0; width: 100%; justify-content: space-between; order: 2; gap: 10px; }
    .user-info { width: 100%; justify-content: space-between; margin-top: 10px; order: 1; }
    .order-card { flex-direction: column; align-items: flex-start; padding: 15px; border-left-width: 4px; }
    .order-details-right { width: 100%; justify-content: space-between; gap: 15px; }
    .order-card .total { font-size: 20px; text-align: left; min-width: auto; }
}

    </style>
</head>
<body>
   <div class="page-container">
        <header class="header">
            <a href="/index.php" class="logo"><span class="logo-text">MARIYAM FASHION</span></a>
            
            <!-- Navigation Menu -->
            <nav class="main-nav">
                <a href="/index.php" class="nav-link">Home</a>
                <!-- This link is ACTIVE for the Pending Orders page -->
                <a href="/users/dashboard.php" class="nav-link active">Pending Orders</a>
                <a href="/users/orders.php" class="nav-link">Delivered Orders</a>
            </nav>
            
            <!-- User Profile and Logout Button -->
            <div class="user-info">
                <span class="username-display"><i class="fa-solid fa-user-circle"></i> <?php echo $display_username; ?></span>
                
                <!-- LOGOUT LINK: Redirects to index.php after logout -->
                <!-- This link ensures the user is logged out and sent to the index page -->
               <a href="logout.php" class="user-link" onclick="return confirm('Are you sure you want to log out?');">
    <i class="fa fa-sign-out-alt"></i> Sign Out
</a>

            </div>
        </header>

        <main class="main-content">
            <h1>Customer Dashboard</h1> 
            <h2>Your Delivered Orders (<?php echo count($delivered_orders); ?>)</h2> 
            
            <?php if (empty($delivered_orders)): ?>
                <div class="empty-state">
                    <p class="message-heading">No Delivered Orders Yet. <i class="fa-solid fa-box"></i></p>
                    <p class="message-subtext">Delivered orders will appear here. Check <a href="/mariyam_fashion/users/orders.php">Complete Orders</a> for history.</p>
                </div>
            <?php else: ?>
                <?php foreach ($delivered_orders as $order): ?>
                    <div class="order-card status-Delivered">
                        <div class="order-summary">
                            <h3><i class="fa-solid fa-truck"></i> Order ID #<?php echo htmlspecialchars($order['id']); ?></h3>
                            <p>Placed on: <?php echo date('F j, Y', strtotime($order['created_at'])); ?></p>
                            <a href="/mariyam_fashion/order_details.php?id=<?php echo $order['id']; ?>">View Details <i class="fa-solid fa-arrow-right"></i></a>
                        </div>
                        <div class="total">৳<?php echo number_format($order['final_total'], 2); ?></div>
                        <span class="status-badge status-Delivered">Delivered</span>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </main>

        <footer class="footer">
            <p>&copy; 2025 Mariyam Fashion</p>
        </footer>
    </div>
</body>
</html>
