<?php
// ====================================================================
// order_details.php
// Fetches order and item details for a specific order ID.
// ====================================================================

session_start();

// --- DATABASE CREDENTIALS (Using variables from your dashboard file) ---
$db_host = 'localhost';   
$db_username = 'mariyamf';    
$db_password = 'Es)0Abi774An;G';        
$dbname = 'mariyamf_mariyam_fashion';

$conn = null;
$order = null;
$items = [];
$user_id = $_SESSION['user_id'] ?? null;
$order_id = (int)($_GET['id'] ?? 0);

// --- Security Check: Must be logged in and provide a valid ID ---
if (!$user_id || $order_id === 0) {
    header('Location: /mariyam_fashion/login.php');
    exit;
}

try {
    $conn = new mysqli($servername, $db_username, $db_password, $dbname);
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error); 
    }

    // 1. Fetch the order details and verify ownership
    $orderStmt = $conn->prepare("
        SELECT id, total_amount, status, created_at, shipping_fee, payment_method, 
               customer_name, customer_phone, customer_email, customer_address, note
        FROM orders 
        WHERE id = ? AND user_id = ?
    ");
    $orderStmt->bind_param("ii", $order_id, $user_id);
    $orderStmt->execute();
    $result = $orderStmt->get_result();
    
    if ($result->num_rows === 0) {
        // Order not found or user does not own it
        header('Location: /mariyam_fashion/dashboard.php');
        exit;
    }
    $order = $result->fetch_assoc();
    $orderStmt->close();

    // 2. Fetch the items for this order
    $itemsStmt = $conn->prepare("
        SELECT oi.quantity, oi.price AS price_at_purchase, p.name 
        FROM order_items oi
        JOIN products p ON oi.product_id = p.id
        WHERE oi.order_id = ?
    ");
    $itemsStmt->bind_param("i", $order_id);
    $itemsStmt->execute();
    $items_result = $itemsStmt->get_result();

    while ($item = $items_result->fetch_assoc()) {
        $items[] = $item;
    }
    $itemsStmt->close();

} catch (Exception $e) {
    error_log("Order details error: " . $e->getMessage());
    die("An unexpected error occurred while loading order details.");
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
    <title>Order #<?= $order_id ?> Details</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    
    <style>
        /* ------------------------------------------- */
        /* COLOR PALETTE & RESET (Deep Blue Theme) */
        /* ------------------------------------------- */
        :root {
            --deep-blue: #001f4f; /* Darkest Blue */
            --accent-blue: #0056b3; /* Primary Blue */
            --light-blue: #e0f0ff; /* Light Background */
            --text-dark: #222;
            --text-light: #666;
            --primary-orange: #ff6a00; /* Accent */
            --shadow-dark: rgba(0, 0, 0, 0.1);
        }
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Arial', sans-serif; }
        body { background-color: var(--light-blue); color: var(--text-dark); line-height: 1.6; }
        a { text-decoration: none; color: inherit; }

        .detail-container {
            max-width: 900px; margin: 40px auto; padding: 30px;
            background: #fff; border-radius: 12px;
            box-shadow: 0 4px 15px var(--shadow-dark);
        }
        h1 { color: var(--deep-blue); font-size: 30px; border-bottom: 2px solid var(--accent-blue); padding-bottom: 10px; margin-bottom: 20px; }
        h2 { color: var(--accent-blue); font-size: 20px; margin-top: 25px; margin-bottom: 15px; }

        /* Order Info Box */
        .info-box {
            background-color: #f9f9f9; border: 1px solid #eee; border-radius: 8px;
            padding: 15px; margin-bottom: 20px;
        }
        .info-box p { margin: 5px 0; font-size: 15px; }
        .info-box strong { color: var(--deep-blue); display: inline-block; min-width: 120px; }
        
        /* Status Badge */
        .status-badge {
            padding: 5px 12px; border-radius: 20px; font-size: 14px; font-weight: 700;
            color: #fff; text-transform: uppercase; margin-left: 10px;
        }
        .status-Pending { background-color: #ffc107; color: #333; }
        .status-Processing { background-color: var(--accent-blue); }
        .status-Shipped { background-color: #28a745; }
        .status-Delivered { background-color: #17a2b8; }
        .status-Cancelled { background-color: #dc3545; }

        /* Items Table */
        table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        table th, table td { padding: 12px; border: 1px solid #ddd; text-align: left; font-size: 14px; }
        table th { background-color: var(--deep-blue); color: #fff; font-weight: 600; }
        table tr:nth-child(even) { background-color: #f7f7f7; }
        table tfoot td { font-weight: 700; background-color: var(--light-blue); }
        .total-row td { color: var(--deep-blue); font-size: 16px; }

        .back-link { margin-top: 20px; display: block; color: var(--primary-orange); font-weight: 600; }
        .back-link:hover { text-decoration: underline; }

        @media (max-width: 768px) {
            .detail-container { margin: 15px; padding: 15px; }
            table, thead, tbody, th, td, tr { display: block; }
            thead tr { position: absolute; top: -9999px; left: -9999px; }
            tr { border: 1px solid #ccc; margin-bottom: 10px; border-radius: 5px; }
            td { border: none; position: relative; padding-left: 50% !important; text-align: right !important; }
            td:before { position: absolute; top: 6px; left: 6px; width: 45%; padding-right: 10px; white-space: nowrap; text-align: left; font-weight: bold; color: var(--accent-blue); }
            
            /* Label the data */
            td:nth-of-type(1):before { content: "Product"; }
            td:nth-of-type(2):before { content: "Price"; }
            td:nth-of-type(3):before { content: "Quantity"; }
            td:nth-of-type(4):before { content: "Subtotal"; }
            
            /* Fix footer alignment on mobile */
            table tfoot td { text-align: left !important; }
        }

    </style>
</head>
<body>

<div class="detail-container">
    <h1>Order Details #<?= htmlspecialchars($order['id']) ?> 
        <span class="status-badge status-<?= htmlspecialchars($order['status']) ?>">
            <?= htmlspecialchars($order['status']) ?>
        </span>
    </h1>
    
    <p style="font-size: 16px; color: var(--text-light);">
        Placed on: <strong><?= date('F j, Y, g:i a', strtotime($order['created_at'])) ?></strong>
    </p>

    <h2>Customer & Shipping Information</h2>
    <div class="info-box">
        <p><strong>Customer:</strong> <?= htmlspecialchars($order['customer_name']) ?></p>
        <p><strong>Phone:</strong> <?= htmlspecialchars($order['customer_phone']) ?></p>
        <p><strong>Email:</strong> <?= htmlspecialchars($order['customer_email']) ?: 'N/A' ?></p>
        <p><strong>Address:</strong> <?= nl2br(htmlspecialchars($order['customer_address'])) ?></p>
        <p><strong>Payment:</strong> <?= htmlspecialchars($order['payment_method']) ?></p>
        <?php if (!empty($order['note'])): ?>
            <p><strong>Note:</strong> <?= htmlspecialchars($order['note']) ?></p>
        <?php endif; ?>
    </div>
    
    <h2>Items Summary</h2>
    <table>
        <thead>
            <tr>
                <th>Product</th>
                <th>Price (at purchase)</th>
                <th>Quantity</th>
                <th>Subtotal</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            $subtotal_items = 0;
            foreach ($items as $item): 
                $item_total = $item['price_at_purchase'] * $item['quantity'];
                $subtotal_items += $item_total;
            ?>
            <tr>
                <td><?= htmlspecialchars($item['name']) ?></td>
                <td>৳<?= number_format($item['price_at_purchase'], 2) ?></td>
                <td><?= htmlspecialchars($item['quantity']) ?></td>
                <td>৳<?= number_format($item_total, 2) ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
        <tfoot>
            <tr>
                <td colspan="3" style="text-align: right;">Items Subtotal:</td>
                <td>৳<?= number_format($subtotal_items, 2) ?></td>
            </tr>
            <tr>
                <td colspan="3" style="text-align: right;">Shipping Fee:</td>
                <td>৳<?= number_format($order['shipping_fee'], 2) ?></td>
            </tr>
            <tr class="total-row">
                <td colspan="3" style="text-align: right;">GRAND TOTAL:</td>
                <td>৳<?= number_format($order['total_amount'], 2) ?></td>
            </tr>
        </tfoot>
    </table>

    <a href="/mariyam_fashion/dashboard.php" class="back-link">← Back to My Orders</a>
</div>

</body>
</html>