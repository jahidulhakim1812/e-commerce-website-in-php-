<?php
session_start();
$pdo = new PDO("mysql:host=localhost;dbname=mariyamf_mariyam_fashion", "mariyamf", "Es)0Abi774An;G");
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

header('Content-Type: application/json');

$action = $_POST['action'] ?? '';
$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;

if(!isset($_SESSION['cart'])) $_SESSION['cart'] = [];

$response = ['status'=>'error'];

// Function to fetch product info including first image
function getProductInfo($pdo, $id){
    $stmt = $pdo->prepare("SELECT p.*, 
           (SELECT image_path FROM product_images WHERE product_id=p.id LIMIT 1) AS first_image
           FROM products p WHERE id=?");
    $stmt->execute([$id]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);
    if($product){
        $product['image'] = !empty($product['first_image']) ? "admin/".$product['first_image'] : "admin/uploads/default.png";
    }
    return $product;
}

// ===== Add to cart =====
if($action === 'add' && $id > 0){
    if(isset($_SESSION['cart'][$id])) {
        $_SESSION['cart'][$id]++;
    } else {
        $_SESSION['cart'][$id] = 1;
    }
    $product = getProductInfo($pdo, $id);
    if($product){
        $response = [
            'status' => 'success',
            'cart_count' => array_sum($_SESSION['cart']),
            'product' => [
                'id' => $product['id'],
                'name' => $product['name'],
                'price' => $product['price'],
                'image' => $product['image']
            ]
        ];
    }
}

// ===== Update quantity =====
if($action === 'update' && $id > 0){
    $change = isset($_POST['change']) ? (int)$_POST['change'] : 0;
    if(isset($_SESSION['cart'][$id])){
        $_SESSION['cart'][$id] += $change;
        if($_SESSION['cart'][$id] <= 0){
            unset($_SESSION['cart'][$id]);
            $qty = 0;
        } else {
            $qty = $_SESSION['cart'][$id];
        }
        $product = getProductInfo($pdo, $id);
        $response = [
            'status'=>'success',
            'cart_count'=>array_sum($_SESSION['cart']),
            'qty'=>$qty,
            'product'=> $product ? ['id'=>$product['id'], 'name'=>$product['name'], 'price'=>$product['price'], 'image'=>$product['image']] : null
        ];
    }
}

// ===== Remove item =====
if($action === 'remove' && $id > 0){
    if(isset($_SESSION['cart'][$id])){
        unset($_SESSION['cart'][$id]);
    }
    $product = getProductInfo($pdo, $id);
    $response = [
        'status'=>'success',
        'cart_count'=>array_sum($_SESSION['cart']),
        'product'=> $product ? ['id'=>$product['id'], 'name'=>$product['name'], 'price'=>$product['price'], 'image'=>$product['image']] : null
    ];
}

echo json_encode($response);
