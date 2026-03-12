<?php
session_start();

try {
    $pdo = new PDO("mysql:host=localhost;dbname=mariyamf_mariyam_fashion", "mariyamf", "Es)0Abi774An;G", [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

$error = "";
$success = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $email    = trim($_POST['email']);
    $password = trim($_POST['password']);
    $confirm  = trim($_POST['confirm']);

    // Basic validation
    if (strlen($username) < 3) {
        $error = "Username must be at least 3 characters.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Invalid email address.";
    } elseif ($password !== $confirm) {
        $error = "Passwords do not match.";
    } elseif (strlen($password) < 6) {
        $error = "Password must be at least 6 characters.";
    } else {
        // Check if username or email exists
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username=? OR email=?");
        $stmt->execute([$username, $email]);

        if ($stmt->fetch()) {
            $error = "Username or Email already exists.";
        } else {
            // Secure hash
            $hash = password_hash($password, PASSWORD_DEFAULT);

            $stmt = $pdo->prepare("INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, 'user')");
            if ($stmt->execute([$username, $email, $hash])) {
                $success = "Registration successful! <a href='login.php'>Login here</a>.";
            } else {
                $error = "Something went wrong, please try again.";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Sign Up - Mariyam Fashion</title>
    <style>
        body {
            background: linear-gradient(135deg,#001F3F,#0074D9);
            font-family:'Segoe UI',sans-serif;
            color:#fff;
            display:flex;
            justify-content:center;
            align-items:center;
            height:100vh;
            margin:0;
        }
        .box {
            background: rgba(255,255,255,0.12);
            padding:35px;
            border-radius:12px;
            width:350px;
            box-shadow:0 0 25px rgba(0,0,0,0.4);
        }
        h2 {
            text-align:center;
            color:#00BFFF;
            margin-bottom:25px;
            font-size:22px;
        }
        input {
            width:100%;
            padding:12px;
            margin:10px 0;
            border:none;
            border-radius:6px;
            outline:none;
            font-size:15px;
        }
        button {
            width:100%;
            padding:12px;
            background:#00BFFF;
            border:none;
            color:#fff;
            font-weight:bold;
            border-radius:6px;
            cursor:pointer;
            font-size:16px;
            transition:0.3s;
        }
        button:hover {
            background:#0074D9;
        }
        .message {
            text-align:center;
            margin-top:15px;
            padding:10px;
            border-radius:5px;
            font-size:14px;
        }
        .error {
            background:rgba(255,50,50,0.2);
            color:#ff8080;
        }
        .success {
            background:rgba(50,205,50,0.2);
            color:#90ee90;
        }
    </style>
</head>
<body>
<div class="box">
    <h2>Create Account</h2>
    <form method="POST">
        <input type="text" name="username" placeholder="Username" value="<?= htmlspecialchars($_POST['username'] ?? '') ?>" required />
        <input type="email" name="email" placeholder="Email" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required />
        <input type="password" name="password" placeholder="Password" required />
        <input type="password" name="confirm" placeholder="Confirm Password" required />
        <button type="submit">Sign Up</button>
    </form>

    <?php if($error): ?>
        <div class="message error"><?= htmlspecialchars($error) ?></div>
    <?php elseif($success): ?>
        <div class="message success"><?= $success ?></div>
    <?php endif; ?>
</div>
</body>
</html>
