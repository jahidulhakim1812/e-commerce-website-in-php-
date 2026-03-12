<?php
session_start();

try {
    // Database connection
  $pdo = new PDO("mysql:host=localhost;dbname=mariyamf_mariyam_fashion", "mariyamf", "Es)0Abi774An;G");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

$error = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);
    $role = strtolower(trim($_POST['role'])); // Normalize role (admin/user)

    // Fetch user by username + role
    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? AND role = ?");
    $stmt->execute([$username, $role]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        $login_ok = false;

        // Check with password_hash()
        if (password_verify($password, $user['password'])) {
            $login_ok = true;
        }
        // Check legacy MD5 (optional, for old accounts)
        elseif (md5($password) === $user['password']) {
            // Upgrade to password_hash for future logins
            $stmtUpdate = $pdo->prepare("UPDATE users SET password = ? WHERE username = ?");
            $stmtUpdate->execute([password_hash($password, PASSWORD_DEFAULT), $username]);
            $login_ok = true;
        }

        if ($login_ok) {
            // Store all important session data
            $_SESSION['user_id']   = $user['id'];
            $_SESSION['username']  = $user['username'];
            $_SESSION['role']      = $user['role'];
            $_SESSION['email']     = $user['email'] ?? null;

            // Redirect by role
            if ($user['role'] === 'admin') {
                header("Location: admin/admin_dashboard.php");
// OR, if the admin_dashboard is indeed in a folder called 'admin' at the root:
// header("Location: /admin/admin_dashboard.php");
            } else {
                header("Location: users/dashboard.php"); // send normal users to homepage
            }

            exit;
        } else {
            $error = "Invalid password.";
        }
    } else {
        $error = "Invalid username or role.";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Login - Mariyam Fashion</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        /* Base Styles (Desktop-First) */
        body {
            background: linear-gradient(to right, #001F3F, #0074D9);
            font-family: 'Segoe UI', sans-serif;
            color: #fff;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
            position: relative; /* Needed for admin icon/panel positioning */
        }
        .login-box {
            background: rgba(255,255,255,0.12);
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 0 18px rgba(0,0,0,0.35);
            width: 320px;
            max-width: 90%; /* Added max-width for mobile constraint */
        }
        .login-box h2 {
            text-align: center;
            margin-bottom: 20px;
            color: #00BFFF;
        }
        .login-box input {
            width: 100%;
            padding: 12px;
            margin: 10px 0;
            border: none;
            border-radius: 6px;
            font-size: 14px;
            box-sizing: border-box; /* Crucial for padding/width calculation */
        }
        .login-box button {
            width: 100%;
            padding: 12px;
            background: #00BFFF;
            border: none;
            color: #fff;
            font-weight: bold;
            border-radius: 6px;
            cursor: pointer;
            transition: background 0.3s ease;
        }
        .login-box button:hover {
            background: #0074D9;
        }
        .links {
            display: flex;
            justify-content: space-between;
            margin-top: 15px;
        }
        .links a {
            color: #ffeb3b;
            text-decoration: none;
            font-size: 14px;
        }
        .links a:hover {
            text-decoration: underline;
        }
        .error {
            color: #ff8080;
            text-align: center;
            margin-top: 10px;
            font-size: 14px;
        }

        /* Admin Icon (Desktop) */
        .admin-icon {
            position: absolute;
            top: 20px;
            right: 20px;
            font-size: 22px;
            background: #00BFFF;
            color: #fff;
            padding: 10px 14px;
            border-radius: 50%;
            cursor: pointer;
            transition: background 0.3s ease;
            z-index: 10; /* Ensures it's above other elements */
        }
        .admin-icon:hover {
            background: #0074D9;
        }

        /* Admin Login Panel (Desktop) */
        .admin-login {
            display: none;
            position: absolute;
            top: 60px;
            right: 20px;
            background: rgba(255,255,255,0.15);
            padding: 20px;
            border-radius: 10px;
            width: 260px;
            box-shadow: 0 0 12px rgba(0,0,0,0.4);
            opacity: 0;
            transform: translateY(-10px);
            transition: all 0.3s ease-in-out;
            z-index: 9;
        }
        .admin-login.show {
            display: block;
            opacity: 1;
            transform: translateY(0);
        }
        .admin-login h3 {
            text-align: center;
            margin-bottom: 15px;
            color: #ffeb3b;
        }
        .admin-login input, .admin-login button {
            width: 100%;
            padding: 10px;
            margin: 8px 0;
            border-radius: 6px;
            border: none;
            font-size: 14px;
            box-sizing: border-box;
        }
        .admin-login button {
            background: #ff9800;
            color: #fff;
            font-weight: bold;
            cursor: pointer;
            transition: background 0.3s ease;
        }
        .admin-login button:hover {
            background: #e68900;
        }
        
        /* ================================== */
        /* ===== MOBILE STYLES (Max 768px) ===== */
        /* ================================== */
        @media (max-width: 768px) {
            body {
                /* Allows content to flow vertically instead of being strictly centered */
                align-items: flex-start; 
                padding-top: 50px;
                height: auto; /* Use auto height on mobile to allow scrolling */
                min-height: 100vh;
            }

            .login-box {
                margin: 0 auto; /* Center the box */
                width: 90%; 
                padding: 20px;
            }
            
            /* Admin Icon (Mobile) */
            .admin-icon {
                /* Fix the admin icon to the top right of the viewport */
                position: fixed; 
                top: 10px;
                right: 10px;
                font-size: 18px;
                padding: 8px 12px;
                z-index: 100; /* High z-index to ensure visibility */
            }

            /* Admin Login Panel (Mobile - Full Width Dropdown) */
            .admin-login {
                /* Makes the panel stretch across the top */
                position: fixed;
                top: 60px; /* Below the icon */
                right: 10px;
                left: 10px; /* Full width with padding */
                width: auto; 
                padding: 15px;
                box-sizing: border-box;
                z-index: 99;
                background: rgba(0, 31, 63, 0.9); /* Darker background for focus */
            }

             /* Hide the separate "Forgot Password?" link inside the admin panel on mobile
                to simplify the small UI, since a similar one exists in the main box,
                or combine them if feasible in your actual flow. Keeping it simple here: */
            .admin-login .links {
                display: none;
            }
        }
    </style>
</head>
<body>

<div class="login-box">
    <h2><i class="fa fa-user"></i> Customer Login</h2>
    <form method="POST">
        <input type="hidden" name="role" value="user">
        <input type="text" name="username" placeholder="Username" required />
        <input type="password" name="password" placeholder="Password" required />
        <button type="submit">Login</button>
        <?php if ($error && ($_POST['role'] ?? '') === "user"): ?>
            <div class="error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
    </form>
    <div class="links">
        <a href="signup.php">Sign Up</a>
        <a href="forget_password.php">Forgot Password?</a>
    </div>
</div>

<div class="admin-icon" onclick="toggleAdmin()"><i class="fa fa-lock"></i></div>

<div class="admin-login" id="admin-panel">
    <h3><i class="fa fa-wrench"></i> Admin Login</h3>
    <form method="POST">
        <input type="hidden" name="role" value="admin">
        <input type="text" name="username" placeholder="Admin Username" required />
        <input type="password" name="password" placeholder="Password" required />
        <button type="submit">Login</button>
        <?php if ($error && ($_POST['role'] ?? '') === "admin"): ?>
            <div class="error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
    </form>
    <div class="links">
        <a href="admin_forget_password.php">Forgot Admin Password?</a>
    </div>
</div>

<script>
function toggleAdmin(){
    document.getElementById('admin-panel').classList.toggle('show');
}

// Optional: Close admin panel if the user clicks anywhere outside of it
document.addEventListener('click', function(event) {
    const adminPanel = document.getElementById('admin-panel');
    const adminIcon = document.querySelector('.admin-icon');

    // Check if the click is outside the panel and outside the icon, and the panel is currently shown
    if (!adminPanel.contains(event.target) && !adminIcon.contains(event.target) && adminPanel.classList.contains('show')) {
        adminPanel.classList.remove('show');
    }
});
</script>

</body>
</html>