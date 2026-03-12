<?php
session_start();

// Database connection
$pdo = new PDO("mysql:host=localhost;dbname=mariyamf_mariyam_fashion", "mariyamf", "Es)0Abi774An;G");
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$error = "";
$success = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $newpass = trim($_POST['newpass']);
    $confirm = trim($_POST['confirm']);

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Invalid email format.";
    } elseif ($newpass !== $confirm) {
        $error = "Passwords do not match.";
    } elseif (strlen($newpass) < 6) {
        $error = "Password must be at least 6 characters.";
    } else {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email=? AND role='user'");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user) {
            $hashedPassword = password_hash($newpass, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE users SET password=? WHERE email=? AND role='user'");
            if ($stmt->execute([$hashedPassword, $email])) {
                $success = "✅ Password reset successful! <a href='login.php'>Login here</a>.";
            } else {
                $error = "Something went wrong. Please try again.";
            }
        } else {
            $error = "Email not found.";
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Forgot Password - User</title>
    <style>
        body {
            background: linear-gradient(to right, #001F3F, #0074D9);
            font-family: 'Segoe UI', sans-serif;
            color: #fff;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
        }
        .box {
            background: rgba(255,255,255,0.1);
            padding: 30px;
            border-radius: 10px;
            width: 320px;
            box-shadow: 0 0 15px rgba(0,0,0,0.3);
        }
        h2 {
            text-align: center;
            color: #00BFFF;
            margin-bottom: 20px;
        }
        input {
            width: 100%;
            padding: 10px;
            margin: 10px 0;
            border: none;
            border-radius: 5px;
        }
        button {
            width: 100%;
            padding: 10px;
            background: #00BFFF;
            border: none;
            color: #fff;
            font-weight: bold;
            border-radius: 5px;
            cursor: pointer;
        }
        button:hover {
            background: #0074D9;
        }
        .message {
            text-align: center;
            margin-top: 10px;
        }
        .error {
            color: #ff8080;
        }
        .success {
            color: #90ee90;
        }
    </style>
</head>
<body>
<div class="box">
    <h2>User Forgot Password</h2>
    <form method="POST">
        <input type="email" name="email" placeholder="Registered Email" required />
        <input type="password" name="newpass" placeholder="New Password" required />
        <input type="password" name="confirm" placeholder="Confirm Password" required />
        <button type="submit">Reset Password</button>
    </form>
    <?php if ($error): ?>
        <div class="message error"><?= htmlspecialchars($error) ?></div>
    <?php elseif ($success): ?>
        <div class="message success"><?= $success ?></div>
    <?php endif; ?>
</div>
</body>
</html>
