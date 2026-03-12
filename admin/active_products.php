<?php
session_start();
require "includes/db.php";

$products = $pdo->query("SELECT * FROM products WHERE status=1 ORDER BY created_at DESC")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html>
<head><title>Active Products</title></head>
<body>
    <h1>Active Products</h1>
    <a href="manage_products.php">⬅ Back</a>
    <table border="1" cellpadding="10">
        <tr><th>ID</th><th>Name</th><th>Price</th><th>Actions</th></tr>
        <?php foreach($products as $p): ?>
            <tr>
                <td><?= $p['id'] ?></td>
                <td><?= htmlspecialchars($p['name']) ?></td>
                <td>$<?= number_format($p['price'],2) ?></td>
                <td>
                    <a href="toggle_status.php?id=<?= $p['id'] ?>">Set Upcoming</a> | 
                    <a href="delete_product.php?id=<?= $p['id'] ?>" onclick="return confirm('Delete this product?')">Delete</a>
                </td>
            </tr>
        <?php endforeach; ?>
    </table>
</body>
</html>
