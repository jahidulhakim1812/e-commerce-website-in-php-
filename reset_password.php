<?php
$pdo = new PDO("mysql:host=localhost;dbname=mariyamf_mariyam_fashion", "mariyamf", "Es)0Abi774An;G");
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$message = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'];
    $code = $_POST['reset_code'];
    $new_password = md5($_POST['new_password']); // ⚠️ Use password_hash() in real projects

    $stmt = $pdo->prepare("SELECT * FROM users WHERE username=? AND reset_code=? AND role='admin' AND reset_expiry >= NOW()");
    $stmt->execute([$email, $code]);
    $admin = $stmt->fetch();

    if ($admin) {
        $stmt = $pdo->prepare("UPDATE users SET password=?, reset_code=NULL, reset_expiry=NULL WHERE username=?");
        $stmt->execute([$new_password, $email]);
        $message = "Password successfully updated!";
    } else {
        $message = "Invalid or expired reset code.";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Reset Admin Password</title>
</head>
<body>
    <h2>Reset Admin Password</h2>
    <form method="POST">
        <input type="text" name="email" placeholder="Admin Email" required />
        <input type="text" name="reset_code" placeholder="Enter Reset Code" required />
        <input type="password" name="new_password" placeholder="New Password" required />
        <button type="submit">Reset Password</button>
    </form>
    <p style="color:yellow;"><?= $message ?></p>
</body>
</html>
