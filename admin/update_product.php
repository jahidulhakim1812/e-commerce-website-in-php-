<?php
session_start();
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

try {
  $pdo = new PDO("mysql:host=localhost;dbname=mariyamf_mariyam_fashion", "mariyamf", "Es)0Abi774An;G");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

if(!isset($_GET['id'])) {
    header("Location: delete_product.php");
    exit;
}

$productId = intval($_GET['id']);

// Fetch product details
$stmt = $pdo->prepare("SELECT * FROM products WHERE id=?");
$stmt->execute([$productId]);
$product = $stmt->fetch(PDO::FETCH_ASSOC);
if(!$product) {
    echo "<script>alert('Product not found'); window.location.href='delete_product.php';</script>";
    exit;
}

// Fetch product images
$imgStmt = $pdo->prepare("SELECT * FROM product_images WHERE product_id=?");
$imgStmt->execute([$productId]);
$images = $imgStmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch categories
$categories = $pdo->query("SELECT * FROM categories ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);

// Handle image deletion via GET
if(isset($_GET['delete_img'])) {
    $imgId = intval($_GET['delete_img']);
    $stmt = $pdo->prepare("SELECT image_path FROM product_images WHERE id=?");
    $stmt->execute([$imgId]);
    $img = $stmt->fetch(PDO::FETCH_ASSOC);
    if($img && file_exists($img['image_path'])) unlink($img['image_path']);
    $pdo->prepare("DELETE FROM product_images WHERE id=?")->execute([$imgId]);
    header("Location: update_product.php?id=$productId");
    exit;
}

// Handle form submission
if(isset($_POST['update_product'])) {
    $name = trim($_POST['name']);
    $price = floatval($_POST['price']);
    $description = trim($_POST['description']);
    $category_id = intval($_POST['category']);

    // Update main product info
    $updateStmt = $pdo->prepare("UPDATE products SET name=?, price=?, description=?, category_id=? WHERE id=?");
    $updateStmt->execute([$name, $price, $description, $category_id, $productId]);

    // Handle multiple image uploads
    if(isset($_FILES['images'])) {
        $files = $_FILES['images'];
        $total = count($files['name']);
        $targetDir = "uploads/products/";
        if(!is_dir($targetDir)) mkdir($targetDir, 0777, true);

        for($i=0;$i<$total;$i++) {
            if($files['error'][$i] === 0) {
                $fileName = time().'_'.$files['name'][$i];
                $targetFile = $targetDir.$fileName;
                if(move_uploaded_file($files['tmp_name'][$i], $targetFile)) {
                    $pdo->prepare("INSERT INTO product_images (product_id, image_path) VALUES (?,?)")
                        ->execute([$productId, $targetFile]);
                }
            }
        }
    }

    echo "<script>alert('✅ Product updated successfully!'); window.location.href='delete_product.php';</script>";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Update Product - Admin Panel</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<style>
body {margin:0; font-family:'Segoe UI',sans-serif; background:#f4f6f9;}
.container {max-width:700px; margin:50px auto; background:#fff; padding:20px; border-radius:8px; box-shadow:0 2px 8px rgba(0,0,0,0.1);}
h2 {text-align:center; color:#0056b3;}
form {display:flex; flex-direction:column;}
label {margin-top:10px; font-weight:bold;}
input, select, textarea {padding:10px; margin-top:5px; border:1px solid #ccc; border-radius:6px; font-size:14px; width:100%;}
textarea {resize:vertical; min-height:80px;}
button {margin-top:20px; padding:10px; background:#0074D9; color:#fff; border:none; border-radius:6px; cursor:pointer; font-size:14px;}
button:hover {background:#005fa3;}
.back-btn {margin-top:10px; text-align:center; display:block; text-decoration:none; color:#0074D9;}
.back-btn:hover {text-decoration:underline;}
img.preview {margin-top:10px; max-width:120px; border-radius:6px; margin-right:10px;}
.image-container {display:flex; flex-wrap:wrap; margin-top:10px;}
.image-container div {position:relative; margin-bottom:10px;}
.image-container a.delete-img {position:absolute; top:2px; right:2px; background:#FF4B5C; color:#fff; padding:2px 6px; font-size:12px; border-radius:50%; text-decoration:none;}
.image-container a.delete-img:hover {background:#e63946;}
</style>
</head>
<body>
<div class="container">
    <h2>Update Product</h2>
    <form method="post" enctype="multipart/form-data">
        <label>Product Name</label>
        <input type="text" name="name" value="<?= htmlspecialchars($product['name']) ?>" required>

        <label>Price ($)</label>
        <input type="number" step="0.01" name="price" value="<?= $product['price'] ?>" required>

        <label>Description</label>
        <textarea name="description" required><?= htmlspecialchars($product['description']) ?></textarea>

        <label>Category</label>
        <select name="category" required>
            <?php foreach($categories as $cat): ?>
                <option value="<?= $cat['id'] ?>" <?= $product['category_id']==$cat['id']?'selected':'' ?>><?= htmlspecialchars($cat['name']) ?></option>
            <?php endforeach; ?>
        </select>

        <label>Product Images</label>
        <input type="file" name="images[]" accept="image/*" multiple>

        <?php if(!empty($images)): ?>
            <div class="image-container">
                <?php foreach($images as $img): ?>
                    <div>
                        <img src="<?= $img['image_path'] ?>" class="preview">
                        <a href="?id=<?= $productId ?>&delete_img=<?= $img['id'] ?>" class="delete-img" title="Delete Image">&times;</a>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <button type="submit" name="update_product">Update Product</button>
    </form>
    <a href="delete_product.php" class="back-btn"><i class="fas fa-arrow-left"></i> Back to Products</a>
</div>
</body>
</html>
