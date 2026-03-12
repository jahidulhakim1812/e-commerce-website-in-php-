<?php
session_start();
header('Content-Type: application/json');

if (isset($_POST['product_id'])) {
    $product_id = intval($_POST['product_id']);

    $pdo = new PDO("mysql:host=localhost;dbname=mariyamf_mariyam_fashion", "mariyamf", "Es)0Abi774An;G");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Fetch product info
    $stmt = $pdo->prepare("SELECT id, name, price, discount, image_url FROM products WHERE id=?");
    $stmt->execute([$product_id]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$product) {
        echo json_encode(['success' => false]);
        exit;
    }

    // Initialize cart if not exists
    if (!isset($_SESSION['cart'])) $_SESSION['cart'] = [];

    // Add or increment
    if (isset($_SESSION['cart'][$product_id])) {
        $_SESSION['cart'][$product_id]['quantity']++;
    } else {
        $_SESSION['cart'][$product_id] = [
            'id' => $product['id'],
            'name' => $product['name'],
            'price' => $product['price'],
            'discount' => $product['discount'],
            'image_url' => $product['image_url'],
            'quantity' => 1
        ];
    }

    $cart_count = array_sum(array_column($_SESSION['cart'], 'quantity'));

    echo json_encode([
        'success' => true,
        'cart_count' => $cart_count,
        'cart_item' => $_SESSION['cart'][$product_id]
    ]);

} else {
    echo json_encode(['success' => false]);
}
