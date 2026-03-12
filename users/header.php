<?php
// header.php
// Requires config.php to be included first!
?>
<style>
    /* ------------------------------------------- */
    /* CSS Styles (Moved to a separate CSS file in a real app) */
    /* ------------------------------------------- */
    :root {
        --primary-orange: #ff6a00;
        --text-dark: #333;
        --text-light: #666;
        --border-color: #ddd;
        --link-blue: #007bff;
    }
    * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Arial', sans-serif; }
    body { background-color: #fff; color: var(--text-dark); line-height: 1.6; }
    a { text-decoration: none; color: var(--text-dark); }
    .header {
        display: flex; justify-content: space-between; align-items: center;
        padding: 15px 30px; border-bottom: 1px solid var(--border-color);
        background-color: #fff;
    }
    .logo { display: flex; align-items: center; margin-right: 15px; height: 40px; }
    .logo img { height: 100%; width: auto; object-fit: contain; margin-right: 5px; }
    .logo-text { font-size: 24px; font-weight: 700; color: var(--primary-orange); }
    .main-nav { display: flex; gap: 20px; margin-right: auto; padding-left: 20px; }
    .nav-link { font-size: 16px; padding: 5px 0; font-weight: 500; color: var(--text-light); transition: color 0.3s; }
    .nav-link.active { font-weight: 600; color: var(--text-dark); }
    /* User Profile Styles */
    .user-profile { position: relative; cursor: pointer; color: var(--text-light); padding: 5px; border-radius: 5px; display: flex; align-items: center; }
    .profile-icon { font-size: 28px; margin-right: 5px; }
    .dropdown-arrow { font-size: 12px; }
    .dropdown-menu {
        position: absolute; top: 100%; right: 0; background-color: #fff; border: 1px solid var(--border-color);
        border-radius: 4px; box-shadow: 0 4px 12px rgba(0,0,0,0.1); min-width: 150px;
        z-index: 100; display: none; padding: 10px 0; margin-top: 5px;
    }
    .dropdown-menu.visible { display: block; }
    .dropdown-menu a { display: block; padding: 8px 15px; color: var(--text-dark); font-size: 14px; }
    .dropdown-menu a:hover { background-color: #f5f5f5; }
    .dropdown-menu .signout-link {
        color: #dc3545; border-top: 1px solid #eee; margin-top: 5px; padding-top: 10px;
    }
</style>

<header class="header">
    <div class="logo">
        <img src="upload/777.png" alt="Ghorer Bazar Logo">
        <span class="logo-text">GHORER BAZAR</span>
    </div>
    
    <nav class="main-nav">
        <a href="/mariyam_fashion/shop.php" class="nav-link">Shop</a>
        <a href="/mariyam_fashion/orders.php" class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'orders.php' ? 'active' : ''); ?>">Orders</a>
        
        <?php if ($logged_in_user && $logged_in_user['role'] == 'admin'): ?>
            <a href="/mariyam_fashion/admin/index.php" class="nav-link">Admin Panel</a>
        <?php endif; ?>
    </nav>
    
    <div class="user-profile" id="userProfileToggle">
        <?php if ($logged_in_user): ?>
            <i class="fa-regular fa-circle-user profile-icon"></i>
            <i class="fa-solid fa-chevron-down dropdown-arrow"></i>
            
            <div class="dropdown-menu" id="profileDropdown">
                <a href="/mariyam_fashion/profile.php" style="font-weight: bold; color: var(--link-blue);"><?php echo $logged_in_user['display_username']; ?></a>
                <a href="/mariyam_fashion/addresses.php">Addresses</a>
                <a href="/mariyam_fashion/settings.php">Settings</a>
                
                <a href="/mariyam_fashion/logout.php" class="signout-link">Sign Out</a>
            </div>
        <?php else: ?>
            <a href="/mariyam_fashion/login.php" class="nav-link" style="color: var(--primary-orange); font-weight: 600;">Sign In</a>
        <?php endif; ?>
    </div>
</header>

<script>
    // --- JAVASCRIPT FOR DROPDOWN TOGGLE (Only runs if the dropdown exists) ---
    const profileToggle = document.getElementById('userProfileToggle');
    const dropdownMenu = document.getElementById('profileDropdown');

    if (profileToggle && dropdownMenu) {
        profileToggle.addEventListener('click', function(event) {
            dropdownMenu.classList.toggle('visible');
            event.stopPropagation();
        });

        document.addEventListener('click', function() {
            if (dropdownMenu.classList.contains('visible')) {
                dropdownMenu.classList.remove('visible');
            }
        });
    }
</script>