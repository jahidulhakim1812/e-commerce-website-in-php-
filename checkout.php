<?php
// Include the configuration file for database connection and session start
// Assuming config.php is in the same directory or adjust path as needed
include 'config.php'; 
global $pdo; // Make sure $pdo is available in the global scope

// Cart calculation
$cartItems = [];
$total = 0.0;

if (!empty($_SESSION['cart'])) {
    // Fetch all products in one query for efficiency
    $product_ids = array_keys($_SESSION['cart']);
    $placeholders = implode(',', array_fill(0, count($product_ids), '?'));

    $stmt = $pdo->prepare("
        SELECT p.id, p.name, p.price,
               (SELECT image_path FROM product_images WHERE product_id = p.id LIMIT 1) AS image_path
        FROM products p
        WHERE p.id IN ({$placeholders})
    ");
    $stmt->execute($product_ids);
    $productsData = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Map fetched data back to cart items with quantity
    foreach ($productsData as $p) {
        $id = $p['id'];
        $qty = $_SESSION['cart'][$id] ?? 0;

        if ($qty > 0) {
            $p['qty'] = (int)$qty;
            $p['price'] = (float)$p['price'];
            $p['subtotal'] = $p['qty'] * $p['price'];
            $total += $p['subtotal'];

            // Image handling (assuming images are stored in 'admin/uploads/')
            $p['image_full'] = !empty($p['image_path']) ? 'admin/' . $p['image_path'] : 'admin/uploads/default.png';

            $cartItems[] = $p;
        }
    }
}

// Default shipping is for Dhaka City (70 Tk)
$defaultShipping = 70.0;
$grandTotal = $total + $defaultShipping;
?>
<!doctype html>
<html lang="bn">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Checkout - Mariyam Fashion</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<style>
/* Color Variables */
:root {
    --primary-orange: #ff8a00;
    --primary-yellow: #ffd500;
    --background-color: #fafafa;
    --text-color: #222;
    --light-border: #ddd;
    --dark-shadow: rgba(18,20,24,0.18);
}

body { margin:0; font-family:"Segoe UI", Roboto, Arial, sans-serif; background:var(--background-color); }

/* Base Mobile/Default Styles */
.checkout-box {
    width:94%; 
    max-width:420px; /* Max width for mobile */
    background:#fff;
    border-radius:8px; 
    padding:18px; 
    margin:20px auto;
    box-shadow:0 8px 34px var(--dark-shadow);
    max-height:95vh; 
    overflow:auto; 
    position:relative;
    border:1px solid rgba(0,0,0,0.06);
    box-sizing: border-box; /* Include padding in width/height */
}
h2 { font-size:18px; margin:6px 0 16px; text-align:center; font-weight:700; color:var(--text-color); }
h3 { font-size:15px; margin:12px 0 6px; font-weight:600; color:var(--text-color); }

/* Form Elements */
.label { font-size:13px; font-weight:600; margin:10px 0 6px; display:block; color:var(--text-color); }
input[type="text"], input[type="email"], textarea {
    width:100%; padding:10px 12px; border:1px solid var(--light-border); border-radius:6px;
    font-size:14px; outline:none; box-sizing:border-box; transition:border-color 0.2s;
}
input[type="text"]:focus, input[type="email"]:focus, textarea:focus {
    border-color: var(--primary-orange);
}
textarea { min-height:80px; resize:vertical; }

/* Shipping and Payment */
.shipping-method, .payment-method {
    display:flex; justify-content:space-between; align-items:center;
    border:1px solid #eee; padding:10px; border-radius:6px; margin-bottom:8px; font-size:14px;
    cursor:pointer;
}
.shipping-method:has(input:checked), .payment-method:has(input:checked) {
    border-color: var(--primary-orange);
    background-color: #fff8f0;
}
.shipping-method div, .payment-method div { font-weight: 600; }
.shipping-method input, .payment-method input { margin-right: 8px; }

/* Cart Items */
.cart-item { display:flex; gap:12px; align-items:center; padding:10px 0; border-bottom:1px solid #f1f1f1; }
.cart-item:last-child { border-bottom: none; }
.cart-item img { width:50px; height:50px; object-fit:cover; border-radius:6px; }
.cart-details { flex:1; }
.cart-details p { margin:0; font-size:14px; font-weight:600; color:var(--text-color); }
.cart-details small { color:#666; display:block; margin-top:4px; font-size: 12px; }

/* Totals */
.totals { margin-top:15px; font-size:15px; }
.totals div { 
    display:flex; justify-content:space-between; 
    padding:8px 0; 
    border-top:1px dashed #e5e5e5; 
    font-weight: 500;
}
.totals div.first { border-top:none; padding-top:0; }
.totals div span:last-child { font-weight: 700; color: var(--text-color); }
.totals div:last-child { font-size:18px; font-weight:800; border-top:2px solid #ddd; padding-top:10px; }

/* Buttons */
.btn-orange, .btn-yellow {
    width:100%; padding:12px; border:none; border-radius:6px; font-weight:700; font-size:16px;
    cursor:pointer; margin-top:12px; transition:opacity 0.2s;
}
.btn-orange { background:var(--primary-orange); color:#fff; }
.btn-orange:hover { opacity: 0.9; }
.btn-yellow { background:var(--primary-yellow); color:#111; }
.btn-yellow:hover { opacity: 0.9; }

/* Close Button */
.close-btn { position:absolute; right:12px; top:10px; font-size:20px; color:#666; cursor:pointer; z-index: 10; }
.close-btn:hover { color:#111; }

/* ================================== */
/* ===== DESKTOP MEDIA QUERY ===== */
/* ================================== */
@media (min-width: 600px) {
    .checkout-box {
        max-width: 600px; /* Wider box on desktop */
        padding: 30px;
        margin: 60px auto;
    }
    h2 { font-size: 24px; margin-bottom: 25px; }
    .label { font-size: 14px; }
    input[type="text"], input[type="email"], textarea {
        padding: 12px 15px;
        font-size: 15px;
    }
    .totals div { font-size: 16px; }
    .totals div:last-child { font-size: 20px; }
    .btn-orange, .btn-yellow { font-size: 18px; padding: 14px; }
    .close-btn { right: 20px; top: 15px; font-size: 24px; }
}

/* Bangladesh localization fixes */
.shipping-method label, .payment-method label { 
    /* Ensures the radio button is aligned with the text */
    display: flex;
    align-items: center;
}
</style>
</head>
<body>

<div class="checkout-box">
    <div class="close-btn" onclick="window.location.href='index.php'"><i class="fa fa-times"></i></div>
    <h2>অর্ডার চেক আউট এবং নিশ্চিত করুন</h2>

    <form method="post" id="checkoutForm" action="place_order.php">
      <label class="label">আপনার নাম *</label>
      <input type="text" name="name" required>

      <label class="label">মোবাইল নম্বর *</label>
      <input type="text" name="phone" required>

      <label class="label">ইমেইল (ঐচ্ছিক)</label>
      <input type="email" name="email">

      <label class="label">ঠিকানা *</label>
      <textarea name="address" required></textarea>

      <h3 style="font-size:14px;margin:12px 0 6px;">ডেলিভারি পদ্ধতি</h3>
      <div class="shipping-method">
        <label><input type="radio" name="shipping" value="70" checked onchange="updateTotal()"> ঢাকা সিটি</label>
        <div>৳ 70</div>
      </div>
      <div class="shipping-method">
        <label><input type="radio" name="shipping" value="120" onchange="updateTotal()"> ঢাকার বাইরে</label>
        <div>৳ 120</div>
      </div>

      <label class="label">অর্ডার নোট (ঐচ্ছিক)</label>
      <textarea name="order_note" placeholder="ডেলিভারি সম্পর্কিত বিশেষ কোনো নির্দেশনা থাকলে লিখুন"></textarea>

      <h3 style="font-size:14px;margin:12px 0 6px;">পেমেন্ট পদ্ধতি</h3>
      <div class="payment-method">
        <label><input type="radio" name="payment_method" value="cod" checked> ক্যাশ অন ডেলিভারি</label>
      </div>
      <div class="payment-method">
        <label><input type="radio" name="payment_method" value="online"> অনলাইন পেমেন্ট</label>
      </div>

      <h3 style="margin-top:20px;">অর্ডার সারাংশ</h3>
      <?php if (empty($cartItems)): ?>
          <div class="cart-item" style="border-bottom:none; display:block; text-align:center;">
              <p style="color:var(--primary-orange); font-weight:500;">কার্ট খালি আছে</p>
          </div>
      <?php else: ?>
          <?php foreach($cartItems as $item): ?>
            <div class="cart-item">
              <img src="<?= htmlspecialchars($item['image_full']) ?>" alt="<?= htmlspecialchars($item['name']) ?>">
              <div class="cart-details">
                <p><?= htmlspecialchars($item['name']) ?></p>
                <small>Qty: <?= $item['qty'] ?> | ৳<?= number_format($item['price'],2) ?>/পিস</small>
              </div>
              <div>৳<?= number_format($item['subtotal'],2) ?></div>
            </div>
          <?php endforeach; ?>
      <?php endif; ?>

      <div class="totals">
        <div class="first"><span>মোট দাম</span><span id="subtotal">৳<?= number_format($total,2) ?></span></div>
        <div><span>ডেলিভারি ফি</span><span id="shipping">৳<?= number_format($defaultShipping,2) ?></span></div>
        <div><span>গ্র্যান্ড টোটাল</span><span id="grandTotal">৳<?= number_format($grandTotal,2) ?></span></div>
      </div>

      <button type="submit" class="btn-orange">✅ অর্ডারটি নিশ্চিত করুন</button>
      <button type="button" class="btn-yellow" onclick="alert('Online Payment is currently unavailable. Please select Cash on Delivery.');">💳 Pay Online</button>
    </form>
</div>

<script>
// Get initial subtotal from PHP
const subtotal = <?= json_encode((float)$total) ?>;

function formatTk(val){ return "৳" + Number(val).toFixed(2); }

function updateTotal(){
    // Get the value of the currently checked shipping radio button
    const checkedShipping = document.querySelector('input[name="shipping"]:checked');
    const ship = parseFloat(checkedShipping ? checkedShipping.value : 70.0); // Fallback to 70 if none found
    
    document.getElementById("shipping").innerText = formatTk(ship);
    
    const grand = subtotal + ship;
    document.getElementById("grandTotal").innerText = formatTk(grand);
}

// Ensure the total is calculated on load (in case JavaScript is disabled, PHP provides the initial value)
document.addEventListener('DOMContentLoaded', updateTotal);
</script>

</body>
</html>