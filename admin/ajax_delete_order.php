<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'admin' || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized or invalid request.']);
    exit;
}

$orderId = filter_input(INPUT_POST, 'order_id', FILTER_VALIDATE_INT);

if (!$orderId) {
    echo json_encode(['success' => false, 'message' => 'Invalid order ID.']);
    exit;
}

// ⚠️ SECURITY WARNING: Storing credentials directly in the code is HIGHLY discouraged.
try {
    $pdo = new PDO("mysql:host=localhost;dbname=mariyamf_mariyam_fashion", "mariyamf", "Es)0Abi774An;G");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    // Start a transaction to ensure both deletes happen or neither does
    $pdo->beginTransaction();

    // 1. DELETE CHILD ROWS FIRST: Delete all items associated with this order ID
    $sql_items = "DELETE FROM order_items WHERE order_id = :id";
    $stmt_items = $pdo->prepare($sql_items);
    $stmt_items->bindParam(':id', $orderId, PDO::PARAM_INT);
    $stmt_items->execute();

    // 2. DELETE PARENT ROW NEXT: Delete the main order record
    $sql_order = "DELETE FROM orders WHERE id = :id";
    $stmt_order = $pdo->prepare($sql_order);
    $stmt_order->bindParam(':id', $orderId, PDO::PARAM_INT);
    $stmt_order->execute();

    // Commit the transaction if both deletions succeeded
    $pdo->commit();

    if ($stmt_order->rowCount() > 0) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Order not found or already deleted.']);
    }

} catch (PDOException $e) {
    // Rollback the transaction on failure
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    // Log the error for debugging and return a generic error to the user
    error_log("Order deletion failed for ID $orderId: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error. Please try again or check logs.']);
}
?>