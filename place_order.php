<?php

// ====================================================================

// place_order.php - Final Version (Mariyam Fashion)

// Handles guest & logged-in checkout securely using PDO transactions

// Redirects with success popup flag after order placement

// ====================================================================



if (session_status() == PHP_SESSION_NONE) {

    session_start();

}



// --- 1. Include config and initialize DB connection ---

if (!file_exists('config.php')) {

    die("Error: config.php not found. Cannot connect to the database.");

}

include 'config.php';

global $pdo;



// --- 2. Ensure POST request ---

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {

    header('Location: checkout.php');

    exit;

}



// --- 3. Check if cart exists ---

$cart = $_SESSION['cart'] ?? [];

if (empty($cart)) {

    header('Location: shop.php');

    exit;

}



// --- 4. Identify user (guest if not logged in) ---

$user_id = $_SESSION['user_id'] ?? null;



// --- 5. Validate and sanitize user input ---

$customer_name    = filter_input(INPUT_POST, 'name', FILTER_SANITIZE_SPECIAL_CHARS);

$customer_phone   = filter_input(INPUT_POST, 'phone', FILTER_SANITIZE_SPECIAL_CHARS);

$customer_email   = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);

$customer_address = filter_input(INPUT_POST, 'address', FILTER_SANITIZE_SPECIAL_CHARS);

$payment_method   = filter_input(INPUT_POST, 'payment_method', FILTER_SANITIZE_SPECIAL_CHARS);

$note             = filter_input(INPUT_POST, 'order_note', FILTER_SANITIZE_SPECIAL_CHARS);

$shipping_fee     = filter_input(INPUT_POST, 'shipping', FILTER_VALIDATE_FLOAT);



if (empty($customer_name) || empty($customer_phone) || empty($customer_address) || $shipping_fee === false) {

    header('Location: checkout.php?error=missing_details');

    exit;

}



try {

    // --- 6. Begin transaction ---

    $pdo->beginTransaction();



    // --- 7. Fetch product prices from database ---

    $ids = array_keys($cart);

    $placeholders = implode(',', array_fill(0, count($ids), '?'));

    $stmt = $pdo->prepare("SELECT id, price FROM products WHERE id IN ($placeholders)");

    $stmt->execute($ids);

    $prods = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);



    $subtotal_amount = 0;

    $cart_items_for_insert = [];



    foreach ($cart as $product_id => $qty) {

        $qty = (int)$qty;

        $price = isset($prods[$product_id]) ? (float)$prods[$product_id] : 0.00;



        if ($qty > 0) {

            $subtotal_amount += ($price * $qty);

            $cart_items_for_insert[] = [

                'product_id' => $product_id,

                'qty'        => $qty,

                'price'      => $price

            ];

        }

    }



    if (empty($cart_items_for_insert)) {

        throw new Exception("No valid items found in cart.");

    }



    $total_amount = $subtotal_amount + $shipping_fee;

    $status = 'Pending';



    // --- 8. Insert into orders table ---

    $order_sql = "

        INSERT INTO orders (

            user_id, customer_name, customer_phone, customer_email, customer_address, 

            total_amount, shipping_fee, payment_method, note, created_at, status

        ) VALUES (

            :user_id, :name, :phone, :email, :address,

            :total, :shipping_fee, :payment_method, :note, NOW(), :status

        )

    ";

    $order_stmt = $pdo->prepare($order_sql);

    $order_stmt->execute([

        'user_id'        => $user_id,

        'name'           => $customer_name,

        'phone'          => $customer_phone,

        'email'          => $customer_email,

        'address'        => $customer_address,

        'total'          => $total_amount,

        'shipping_fee'   => $shipping_fee,

        'payment_method' => $payment_method,

        'note'           => $note,

        'status'         => $status

    ]);



    $order_id = $pdo->lastInsertId();



    // --- 9. Insert order items ---

    $order_item_sql = "

        INSERT INTO order_items (order_id, product_id, quantity, unit_price)

        VALUES (:order_id, :product_id, :qty, :unit_price)

    ";

    $order_item_stmt = $pdo->prepare($order_item_sql);



    foreach ($cart_items_for_insert as $item) {

        $order_item_stmt->execute([

            'order_id'   => (int)$order_id,

            'product_id' => (int)$item['product_id'],

            'qty'        => (int)$item['qty'],

            'unit_price' => (float)$item['price']

        ]);

    }



    // --- 10. Commit transaction ---

    $pdo->commit();



    // --- 11. Clear cart and redirect with success flag ---

    $_SESSION['cart'] = [];

    $_SESSION['last_order_id'] = $order_id;

    header("Location: index.php?success=1&id=$order_id");

    exit;



} catch (Exception $e) {

    if ($pdo->inTransaction()) {

        $pdo->rollBack();

    }

    error_log("Order failed for User ID " . ($user_id ?? 'Guest') . " [Ref: " . date('YmdHis') . "]: " . $e->getMessage());

    die("Database Error: Could not place order. Details: " . $e->getMessage());

}