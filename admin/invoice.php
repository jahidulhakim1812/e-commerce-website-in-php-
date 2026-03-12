<?php
session_start();
if(!isset($_SESSION['username']) || $_SESSION['role']!=='admin'){
    header("Location: login.php");
    exit;
}

if(!isset($_GET['order_id'])){
    die("Invalid order ID.");
}

$orderId = intval($_GET['order_id']);

try {
   $pdo = new PDO("mysql:host=localhost;dbname=mariyamf_mariyam_fashion", "mariyamf", "Es)0Abi774An;G");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Fetch order details
    $stmt = $pdo->prepare("SELECT * FROM orders WHERE id=?");
    $stmt->execute([$orderId]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);
    if(!$order){
        die("Order not found.");
    }

    // Fetch order items with product names from products table
    $stmt2 = $pdo->prepare("
        SELECT oi.quantity, oi.price, p.name AS product_name
        FROM order_items oi
        JOIN products p ON oi.product_id = p.id
        WHERE oi.order_id = ?
    ");
    $stmt2->execute([$orderId]);
    $items = $stmt2->fetchAll(PDO::FETCH_ASSOC);

} catch(PDOException $e){
    die("Database error: ".$e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Invoice #<?= $order['id'] ?></title>
<style>
body { font-family: Arial, sans-serif; margin:20px; background:#f4f6f9; }
.invoice-box { max-width:900px; margin:auto; padding:30px; border:1px solid #eee; box-shadow:0 0 10px rgba(0,0,0,0.15); background:#fff; }
h1 { text-align:center; margin-bottom:20px; }
.invoice-header { margin-bottom:20px; }
.invoice-header p { margin:4px 0; }
table { width:100%; border-collapse: collapse; margin-top:20px; }
table th, table td { border:1px solid #ddd; padding:10px; text-align:left; }
table th { background:#0074D9; color:#fff; }
.total { text-align:right; font-weight:bold; }
.print-btn { margin:20px 0; padding:10px 20px; background:#28a745; color:#fff; border:none; border-radius:5px; cursor:pointer; }
.print-btn:hover { background:#218838; }
.status { font-weight:bold; }
.status-complete { color:#28a745; }
.status-pending { color:#ffc107; }
</style>
</head>
<body>

<div class="invoice-box">
    <h1>Invoice #<?= $order['id'] ?></h1>

    <div class="invoice-header">
        <p><strong>Customer:</strong> <?= htmlspecialchars($order['customer_name']) ?></p>
        <p><strong>Phone:</strong> <?= htmlspecialchars($order['customer_phone']) ?></p>
        <p><strong>Address:</strong> <?= htmlspecialchars($order['customer_address']) ?></p>
        <p><strong>Date:</strong> <?= date('M d, Y H:i', strtotime($order['created_at'])) ?></p>
        <p><strong>Order Status:</strong> 
            <span class="status <?= $order['status'] ? 'status-complete' : 'status-pending' ?>">
                <?= $order['status'] ? 'Completed' : 'Pending' ?>
            </span>
        </p>
        <p><strong>Shipping Status:</strong> 
            <span class="status <?= $order['shipping_status'] ? 'status-complete' : 'status-pending' ?>">
                <?= $order['shipping_status'] ? 'Shipped' : 'Pending' ?>
            </span>
        </p>
    </div>

    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Product</th>
                <th>Quantity</th>
                <th>Price</th>
                <th>Subtotal</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            $count = 1;
            foreach($items as $item): 
                $subtotal = $item['quantity'] * $item['price'];
            ?>
            <tr>
                <td><?= $count++ ?></td>
                <td><?= htmlspecialchars($item['product_name']) ?></td>
                <td><?= $item['quantity'] ?></td>
                <td>$<?= number_format($item['price'],2) ?></td>
                <td>$<?= number_format($subtotal,2) ?></td>
            </tr>
            <?php endforeach; ?>
            <tr>
                <td colspan="4" class="total">Total Amount</td>
                <td>$<?= number_format($order['total_amount'],2) ?></td>
            </tr>
        </tbody>
    </table>

    <button class="print-btn" onclick="window.print()">Print Invoice</button>
</div>

</body>
</html>
