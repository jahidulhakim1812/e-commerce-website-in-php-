<?php
session_start();
require "includes/db.php";

if (isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $stmt = $pdo->prepare("UPDATE products SET status = NOT status WHERE id=?");
    $stmt->execute([$id]);
}
header("Location: manage_products.php");
exit;
