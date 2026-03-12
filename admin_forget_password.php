<?php
session_start();

try {
    $pdo = new PDO("mysql:host=localhost;dbname=mariyamf_mariyam_fashion", "mariyamf", "Es)0Abi774An;G");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

$message = "";

// Step 1: Enter username
if (isset($_POST['check_username'])) {
    $username = trim($_POST['username']);

    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? AND role='admin'");
    $stmt->execute([$username]);
    $admin = $stmt->fetch();

    if ($admin) {
        $_SESSION['admin_reset'] = $username;
    } else {
        $message = "Admin username not found!";
    }
}

// Step 2: Reset password
if (isset($_POST['reset_password'])) {
    $newpass = trim($_POST['new_password']);

    if (isset($_SESSION['admin_reset'])) {
        $username = $_SESSION['admin_reset'];

        // Hash password before saving
        $hashed = password_hash($newpass, PASSWORD_DEFAULT);

        $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE username = ? AND role='admin'");
        $stmt->execute([$hashed, $username]);

        unset($_SESSION['admin_reset']);
        $message = "Password updated successfully. <a href='admin_login.php'>Login now</a>.";
    } else {
        $message = "Session expired. Please try again.";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Admin Reset Password</title>
    <style>
        body {
            background: #001F3F;
            color: white;
            font-family: Arial, sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
        }
        .box {
            background: rgba(255,255,255,0.1);
            padding: 25px;
            border-radius: 10px;
            width: 320px;
            text-align: center;
        }
        input, button {
            width: 90%;
            padding: 10px;
            margin: 8px 0;
            border: none;
            border-radius: 6px;
        }
        button {
            background: #00BFFF;
            color: white;
            cursor: pointer;
        }
        a { color: #ffeb3b; text-decoration: none; }
    </style>
</head>
<body>
<div class="box">
    <h2>Admin Reset Password</h2>

    <?php if (!isset($_SESSION['admin_reset'])): ?>
        <!-- Step 1: Enter username -->
        <form method="POST">
            <input type="text" name="username" placeholder="Enter Admin Username" required>
            <button type="submit" name="check_username">Next</button>
        </form>
    <?php else: ?>
        <!-- Step 2: Enter new password -->
        <form method="POST">
            <input type="password" name="new_password" placeholder="Enter New Password" required>
            <button type="submit" name="reset_password">Reset Password</button>
        </form>
    <?php endif; ?>

    <p style="color: yellow;"><?= $message ?></p>
</div>
</body>
</html>
