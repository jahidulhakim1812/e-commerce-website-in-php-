<?php
// ====================================================================
// orders.php - Customer Dashboard (PENDING Orders ONLY)
// Shows orders where status IS strictly '0' (representing Pending/Processing).
// ====================================================================

session_start();

// --- DATABASE CREDENTIALS ---
// NOTE: For a production environment, place these credentials outside 
// the web root or use configuration files for better security.
$db_host = 'localhost';
$db_username = 'mariyamf';
$db_password = 'Es)0Abi774An;G';
$dbname = 'mariyamf_mariyam_fashion';

$conn = null;
$logged_in_user = ['username' => 'Customer']; 
$pending_orders = []; // Variable to hold orders with status = 0

try {
    // Attempt database connection
    $conn = new mysqli($db_host, $db_username, $db_password, $dbname);
    if ($conn->connect_error) {
        die("Database connection failed: " . $conn->connect_error); 
    }
    
    // Security check: Ensure user is logged in
    if (!isset($_SESSION['user_id'])) {
        header("Location: /mariyam_fashion/login.php"); 
        exit;
    }
    $user_id = $_SESSION['user_id'];
    
    // --- 1. Fetch User Details ---
    $stmt_user = $conn->prepare("SELECT username FROM users WHERE id = ?");
    $stmt_user->bind_param("i", $user_id);
    $stmt_user->execute();
    $result_user = $stmt_user->get_result();
    if ($row = $result_user->fetch_assoc()) {
        $logged_in_user = $row;
    }
    $stmt_user->close();
    
    // --- 2. Fetch PENDING ORDERS ONLY (Status = 0) ---
    // CRITICAL: This query filters the results to ONLY show orders 
    // where the 'status' column is 0 (representing Pending)
    $stmt_orders = $conn->prepare("
        SELECT id, total_amount, status, created_at, shipping_fee 
        FROM orders 
        WHERE user_id = ? 
        AND status = 0 
        ORDER BY created_at DESC
    ");
    $stmt_orders->bind_param("i", $user_id);
    $stmt_orders->execute();
    $result_orders = $stmt_orders->get_result();
    
    while ($order = $result_orders->fetch_assoc()) {
        $pending_orders[] = $order; 
    }
    $stmt_orders->close();
    
} catch (Exception $e) {
    error_log("FATAL PENDING ORDERS ERROR for user " . $user_id . ": " . $e->getMessage());
    die("An internal error occurred. Please try again later."); 
} finally {
    // Close connection safely
    if ($conn instanceof mysqli && $conn->ping()) {
        $conn->close();
    }
}

$display_username = htmlspecialchars($logged_in_user['username']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer Pending Orders</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    
    <style>
        /* ------------------------------------------- */
        /* CSS EMBEDDED DIRECTLY IN FILE - Professional Styling */
        /* ------------------------------------------- */
        :root {
            --corporate-blue: #092C45; 
            --accent-sky: #1D976C;    
            --light-bg: #F5F7FA;      
            --card-bg: #FFFFFF;       
            --text-dark: #2C3E50;     
            --primary-red: #D64545;   /* Danger color (Logout) */
            --status-delivered: #1D976C; 
            --status-pending: #F39C12; /* NEW: Orange for Pending */
            --border-subtle: #DDE1E5; 
        }
        * { 
            box-sizing: border-box; 
            margin: 0; 
            padding: 0; 
            font-family: 'Inter', 'Arial', sans-serif; 
        }
        body { 
            background-color: var(--light-bg); 
            color: var(--text-dark); 
            line-height: 1.6; 
        }
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
        .logo-text { 
            font-size: 28px; 
            font-weight: 800; 
            color: #fff; 
            letter-spacing: 1px;
        }
        
        .main-nav { 
            display: flex; 
            gap: 25px; 
            margin-left: 40px; 
            margin-right: auto;
            align-items: center;
        }
        .nav-link { 
            font-size: 15px; 
            font-weight: 500; 
            color: rgba(255, 255, 255, 0.7); 
            padding: 5px 0;
            transition: color 0.3s, border-bottom 0.3s;
        }
        .nav-link:hover { color: #fff; }
        .nav-link.active { 
            color: #fff; 
            font-weight: 700; 
            border-bottom: 3px solid var(--accent-sky); 
        }

        /* User Info and Logout Button */
        .user-info {
            display: flex;
            align-items: center;
            gap: 20px;
        }
        .username-display {
            font-size: 15px;
            font-weight: 600;
            color: rgba(255, 255, 255, 0.9);
            padding: 4px 8px; 
            border-radius: 4px;
            background: rgba(255, 255, 255, 0.1);
        }
        .logout-btn {
            background-color: var(--primary-red);
            color: #fff;
            padding: 10px 18px; 
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 8px;
            border-radius: 25px; 
            box-shadow: 0 3px 8px rgba(214, 69, 69, 0.3);
            transition: background-color 0.3s, transform 0.1s, box-shadow 0.3s;
        }
        .logout-btn:hover {
            background-color: #C0392B;
            transform: translateY(-2px);
            box-shadow: 0 5px 10px rgba(214, 69, 69, 0.5);
        }

        /* MAIN CONTENT & ORDERS */
        .main-content { 
            flex-grow: 1; 
            padding: 40px 5%; 
            max-width: 1200px; 
            width: 100%; 
            margin: 0 auto; 
        }
        .main-content h1 { 
            font-size: 36px; 
            font-weight: 800; 
            margin-bottom: 5px; 
            color: var(--corporate-blue); 
        }
        .main-content h2 { 
            font-size: 22px; 
            margin-bottom: 30px; 
            color: var(--text-dark); 
            font-weight: 500; 
            border-bottom: 2px solid var(--border-subtle);
            padding-bottom: 8px;
        }
        
        .order-card-wrapper {
             display: flex;
             flex-direction: column;
             gap: 15px; 
        }

        .order-card {
            background: var(--card-bg); 
            /* Set border color based on status */
            border-left: 6px solid var(--status-pending); 
            border-radius: 8px; 
            padding: 20px 25px; 
            box-shadow: 0 2px 5px rgba(0,0,0,0.05); 
            display: flex; 
            align-items: center;
            transition: box-shadow 0.3s, transform 0.1s;
        }
        .order-card:hover {
            box-shadow: 0 8px 15px rgba(0,0,0,0.1);
            transform: translateY(-2px);
        }

        /* Order Card Layout Details */
        .order-summary { 
            flex: 1 1 50%; 
            display: flex;
            flex-direction: column;
            gap: 4px;
        }
        .order-summary h3 { 
            font-size: 18px; 
            color: var(--corporate-blue); 
        }
        .order-summary p { 
            font-size: 14px; 
            color: #777; 
            font-weight: 400;
        }
        .order-summary .details-link { 
            margin-top: 8px;
            color: var(--corporate-blue); 
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 5px;
            font-size: 14px;
        }
        .order-summary .details-link i {
            transition: transform 0.3s;
        }
        .order-summary .details-link:hover i {
            transform: translateX(3px);
            color: var(--accent-sky);
        }

        /* Total and Status Group */
        .order-details-right {
            display: flex;
            align-items: center;
            gap: 30px;
            margin-left: auto; 
        }

        .order-card .total { 
            font-size: 24px; 
            font-weight: 800; 
            color: var(--corporate-blue); 
            min-width: 120px; 
            text-align: right;
        }
        
        /* STATUS BADGE STYLES */
        .status-badge {
            padding: 8px 16px; 
            border-radius: 20px; 
            font-size: 12px; 
            font-weight: 700;
            color: #fff; 
            text-transform: uppercase; 
            letter-spacing: 0.5px;
            min-width: 120px; 
            text-align: center;
        }
        /* Specific status color for Pending (status=0) */
        .status-Pending { 
            background-color: var(--status-pending); 
        }

        /* Empty State */
        .empty-state { 
            background: #fff; 
            border: 2px dashed var(--border-subtle); 
            border-radius: 8px; 
            padding: 60px; 
            text-align: center; 
            margin-top: 30px; 
            box-shadow: 0 4px 8px rgba(0,0,0,0.05);
        }
        .message-heading { 
            font-size: 26px; 
            color: var(--corporate-blue); 
            margin-bottom: 10px; 
            font-weight: 700;
        }
        .message-subtext a { 
            color: var(--accent-sky); 
            font-weight: 600; 
            text-decoration: underline;
        }

        /* Footer */
        .footer { 
            background-color: var(--corporate-blue); 
            color: rgba(255, 255, 255, 0.6); 
            padding: 20px 5%; 
            text-align: center; 
            margin-top: 40px; 
            font-size: 14px; 
        }


        /* ------------------------------------------- */
        /* MOBILE RESPONSIVENESS (Screens < 768px) */
        /* ------------------------------------------- */
        @media (max-width: 768px) {
            .header {
                flex-direction: column; 
                align-items: flex-start;
            }

            .main-nav {
                margin: 15px 0 0 0; 
                width: 100%;
                justify-content: space-between; 
                order: 2;
                gap: 10px;
            }
            
            .nav-link {
                flex-basis: 30%; 
                text-align: center;
                border-bottom: none !important; 
            }

            .user-info {
                width: 100%;
                justify-content: space-between;
                margin-top: 10px;
                order: 1; 
            }
            
            .username-display {
                font-size: 13px;
                padding: 6px 10px;
            }

            .logout-btn {
                padding: 8px 12px;
                font-size: 13px;
                gap: 5px;
            }

            .main-content h1 {
                font-size: 28px;
            }
            .main-content h2 {
                font-size: 18px;
            }
            
            .order-card {
                flex-direction: column; 
                align-items: flex-start;
                padding: 15px;
                border-left-width: 4px; 
            }

            .order-summary {
                width: 100%;
                margin-bottom: 15px;
            }
            
            .order-details-right {
                width: 100%;
                justify-content: space-between;
                gap: 15px;
            }

            .order-card .total {
                font-size: 20px;
                text-align: left;
                min-width: auto;
            }

            .status-badge {
                margin-left: 0; 
                min-width: 100px;
            }
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
            
            <!-- H2 confirms this is for pending orders only -->
            <h2>Your Pending Orders (<?php echo count($pending_orders); ?>)</h2>
            
            <div class="order-card-wrapper">
            <?php if (empty($pending_orders)): ?>
                <div class="empty-state">
                    <i class="fa-solid fa-spinner" style="font-size: 40px; color: #BBB; margin-bottom: 20px;"></i>
                    <p class="message-heading">No Pending Orders.</p>
                    <p class="message-subtext">All your orders have either been processed or are already delivered. Check your completed orders on the <a href="/mariyam_fashion/users/dashboard.php">Delivered Orders</a> page.</p>
                </div>
            <?php else: ?>
                <?php foreach ($pending_orders as $order): ?>
                    <div class="order-card">
                        <div class="order-summary">
                            <h3><i class="fa-solid fa-tag"></i> Order ID #<?php echo htmlspecialchars($order['id']); ?></h3>
                            <p>
                                <i class="fa-solid fa-calendar-alt"></i> 
                                Placed On: <?php echo date('F j, Y \a\t g:i A', strtotime($order['created_at'])); ?>
                            </p>
                            <a href="/mariyam_fashion/order_details.php?id=<?php echo $order['id']; ?>" class="details-link">
                                View Details
                                <i class="fa-solid fa-arrow-right"></i>
                            </a>
                        </div>
                        
                        <div class="order-details-right">
                            <div class="total">₹<?php echo number_format($order['total_amount'], 2); ?></div>
                            
                            <!-- Status is explicitly set to 'Pending' for display and CSS class -->
                            <span class="status-badge status-Pending">
                                Pending
                            </span>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
            </div>
        </main>

        <footer class="footer">
            <p>&copy; <?php echo date('Y'); ?> Mariyam Fashion. All Rights Reserved. | Customer Portal</p>
        </footer>
    </div>
</body>
</html>
