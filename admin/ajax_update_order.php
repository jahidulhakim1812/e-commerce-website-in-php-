<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'admin' || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized or invalid request.']);
    exit;
}

$orderId = filter_input(INPUT_POST, 'order_id', FILTER_VALIDATE_INT);
$updateStatus = filter_input(INPUT_POST, 'update_status', FILTER_SANITIZE_STRING);
$updateShipping = filter_input(INPUT_POST, 'update_shipping', FILTER_SANITIZE_STRING);

if (!$orderId) {
    echo json_encode(['success' => false, 'message' => 'Invalid order ID.']);
    exit;
}

try {
    $pdo = new PDO("mysql:host=localhost;dbname=mariyamf_mariyam_fashion", "mariyamf", "Es)0Abi774An;G");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $sql = "";
    $value = 0;

    if ($updateStatus) {
        $value = ($updateStatus === 'complete') ? 1 : 0;
        $sql = "UPDATE orders SET status = :value WHERE id = :id";
    } elseif ($updateShipping) {
        $value = ($updateShipping === 'shipped') ? 1 : 0;
        $sql = "UPDATE orders SET shipping_status = :value WHERE id = :id";
    }

    if ($sql) {
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':value', $value, PDO::PARAM_INT);
        $stmt->bindParam(':id', $orderId, PDO::PARAM_INT);
        $stmt->execute();

        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'No status to update.']);
    }
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>