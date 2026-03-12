<?php
$order_id = $_GET['order_id'] ?? null;
if(!$order_id){
    echo "No order found.";
    exit;
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Order Success - Mariyan Fashion</title>
    <style>
        body{font-family:'Segoe UI', sans-serif; text-align:center; padding:50px; background:#f4f8fc;}
        h2{color:#0074D9;}
        a{color:#fff; background:#0074D9; padding:10px 20px; border-radius:6px; text-decoration:none;}
        a:hover{background:#005fa3;}
    </style>
</head>
<body>
    <h2>Thank you for your order!</h2>
    <p>Your order ID is: <strong><?= htmlspecialchars($order_id) ?></strong></p>
    <p><a href="index.php">Continue Shopping</a></p>
</body>
</html>
