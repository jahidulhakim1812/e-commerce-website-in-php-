<?php
session_start();
$pdo = new PDO("mysql:host=localhost;dbname=mariyamf_mariyam_fashion", "mariyamf", "Es)0Abi774An;G");
$pdo->setAttribute(PDO::ATTR_ERRMODE,PDO::ERRMODE_EXCEPTION);

$cart = $_SESSION['cart'] ?? [];

$products = [];
$total = 0;

if ($cart) {
    $ids = implode(',', array_keys($cart));
    $stmt = $pdo->query("SELECT * FROM products WHERE id IN ($ids)");
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($products as $p) {
        $total += $p['price'] * $cart[$p['id']];
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Your Cart</title>
    <style>
        body{font-family:'Segoe UI',sans-serif;padding:20px;background:#f4f8fc;}
        table{width:100%;border-collapse:collapse;margin-bottom:20px;}
        th,td{border:1px solid #ccc;padding:10px;text-align:center;}
        th{background:#0074D9;color:#fff;}
        input.qty{width:50px;text-align:center;}
        button{padding:6px 12px;background:#0074D9;color:#fff;border:none;border-radius:4px;cursor:pointer;}
        button:hover{background:#005fa3;}
    </style>
</head>
<body>
<h2>Your Cart</h2>
<?php if(!$cart): ?>
<p>Your cart is empty. <a href="index.php">Go Shopping</a></p>
<?php else: ?>
<table>
    <tr>
        <th>Product</th>
        <th>Price</th>
        <th>Quantity</th>
        <th>Subtotal</th>
        <th>Action</th>
    </tr>
    <?php foreach($products as $p): ?>
    <tr>
        <td><?= htmlspecialchars($p['name']) ?></td>
        <td>$<?= $p['price'] ?></td>
        <td>
            <input type="number" class="qty" value="<?= $cart[$p['id']] ?>" min="1" data-id="<?= $p['id'] ?>">
        </td>
        <td>$<?= $p['price'] * $cart[$p['id']] ?></td>
        <td><button class="remove-btn" data-id="<?= $p['id'] ?>">Remove</button></td>
    </tr>
    <?php endforeach; ?>
</table>

<h3>Total: $<span id="total"><?= $total ?></span></h3>

<button id="checkout-btn">Proceed to Checkout</button>
<button id="clear-cart">Clear Cart</button>

<script>
function updateCart(action,id,qty=1){
    const data = new URLSearchParams();
    data.append('action',action);
    data.append('id',id);
    data.append('qty',qty);

    fetch('cart_action.php',{
        method:'POST',
        body:data
    }).then(res=>res.json())
    .then(data=>{
        if(data.status==='success'){
            location.reload();
        }
    });
}

// Quantity change
document.querySelectorAll('.qty').forEach(input=>{
    input.addEventListener('change',e=>{
        const id = e.target.dataset.id;
        const qty = parseInt(e.target.value);
        if(qty>0) updateCart('update',id,qty);
    });
});

// Remove button
document.querySelectorAll('.remove-btn').forEach(btn=>{
    btn.addEventListener('click',e=>{
        const id = e.target.dataset.id;
        updateCart('remove',id);
    });
});

// Clear cart
document.getElementById('clear-cart').addEventListener('click',()=>{
    updateCart('clear',0);
});

// Checkout
document.getElementById('checkout-btn').addEventListener('click',()=>{
    window.location.href = 'login.php';
});
</script>
<?php endif; ?>
</body>
</html>
