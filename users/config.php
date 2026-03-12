<?php
// C:\xampp\htdocs\mariyam_fashion\config.php

// =========================================================
// 1. DATABASE CONNECTION VARIABLES (DEFINE THESE!)
//    Change 'mariyam_fashion' to your actual database name if it's different.
//    If you set a password for 'root' in XAMPP/MySQL, enter it here.
// =========================================================
$db_host = 'localhost';   
$db_username = 'mariyamf';    
$db_password = 'Es)0Abi774An;G';        
$dbname = 'mariyamf_mariyam_fashion'; 


// =========================================================
// 2. SESSION START (Must be the first executable code, 
//    but variable definitions can precede it)
// =========================================================
session_start(); 


// =========================================================
// 3. DATABASE CONNECTION ATTEMPT
// =========================================================
try {
    $pdo = new PDO("mysql:host=$db_host;dbname=$dbname;charset=utf8", $db_username, $db_password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // This will display a helpful error message if the connection fails
    die("<h3>🛑 Database Connection Failed!</h3><p>Check credentials in config.php or ensure MySQL is running in XAMPP.</p><p>Error: " . $e->getMessage() . "</p>");
}


// =========================================================
// 4. USER/SESSION MANAGEMENT (Requires successful $pdo connection)
// =========================================================

$logged_in_user = null;
$display_username = "Guest";

if (isset($_SESSION['user_id'])) {
    // Attempt to fetch user details to confirm session validity
    try {
        $stmt = $pdo->prepare("SELECT username, email FROM users WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $user_data = $stmt->fetch();
        
        if ($user_data) {
            $logged_in_user = $user_data;
            $display_username = htmlspecialchars($user_data['username']);
        } else {
            // User not found, clear session
            unset($_SESSION['user_id']);
        }
    } catch (PDOException $e) {
        // Handle database error during user fetch
        // Optionally log error instead of displaying it to the user
        // error_log("Error fetching user data: " . $e->getMessage());
        unset($_SESSION['user_id']);
    }
}
?>