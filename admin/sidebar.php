
 
     <!-- sidebar.php -->
<style>
    body {
        margin:0;
        font-family: 'Segoe UI', sans-serif;
        display: flex;
        background: #f0f4ff;
        color: #333;
    }

    /* Sidebar */
.sidebar {
    width: 220px;
    background: linear-gradient(180deg,#001F3F,#0056b3);
    color: #fff;
    height: 100vh;
    position: fixed;
    top: 0;
    left: 0;
    padding-top: 60px;
    box-shadow: 2px 0 10px rgba(0,0,0,0.2);
    transition: all 0.3s ease;
    overflow-y: auto; /* make sidebar scrollable */
    overflow-x: hidden; /* prevent horizontal scroll */
    z-index: 1000;
}
    .sidebar h2 {
        text-align: center;
        color: #00BFFF;
        margin-bottom: 20px;
        position: absolute;
        top: 0;
        width: 100%;
        padding: 15px 0;
        border-bottom: 1px solid rgba(255,255,255,0.2);
        font-size: 18px;
        letter-spacing: 1px;
        font-weight: bold;
    }
    .sidebar ul {list-style:none; padding:0; margin:0;}
    .sidebar ul li {margin-bottom: 5px; position: relative;}
    .sidebar ul li a {
        display:flex;
        align-items:center;
        color:#fff;
        padding:12px 20px;
        text-decoration:none;
        font-size: 15px;
        border-radius: 8px 0 0 8px;
        transition:0.3s;
        cursor:pointer;
    }
    .sidebar ul li a i {
        margin-right:12px;
        width:25px;
        text-align:center;
        font-size:16px;
    }
    .sidebar ul li a:hover {background: rgba(255,255,255,0.15);}
    .sidebar ul li a.active {
        background: #00BFFF;
        color: #001F3F;
        font-weight: bold;
    }

    /* Submenu */
    .submenu-list {
        display: none;
        list-style: none;
        padding-left: 35px;
        margin: 5px 0;
        background: rgba(255,255,255,0.05);
        border-left: 2px solid #00BFFF;
    }
    .submenu-list li a {
        font-size: 14px;
        padding:8px 15px;
        border-radius:5px;
    }
    .submenu.active .submenu-list {
        display: block;
    }

    /* Main Content */
    .main {
        margin-left:220px;
        padding:30px;
        flex-grow:1;
        width: calc(100% - 220px);
    }

    /* Top Bar */
   .top-bar {
    display: flex;
    justify-content: flex-end; /* changed from flex-start to flex-end */
    align-items: center;
    background: #fff;
    padding: 10px 20px;
    border-radius: 8px;
    box-shadow: 0 4px 10px rgba(0,0,0,0.1);
    margin-bottom: 20px;
}

    .top-bar form {
        margin:0;
        padding:0;
    }
    .top-bar form button {
        background:#FF4B5C;
        color:#fff;
        border:none;
        padding:8px 15px;
        font-weight:bold;
        border-radius:6px;
        cursor:pointer;
        transition:0.3s;
    }
    .top-bar form button:hover {background:#e63946;}
    .top-bar i {margin-right:6px;}

    .dashboard-header {
        display:flex;
        justify-content:space-between;
        align-items:center;
        margin-bottom:30px;
    }
    .dashboard-header h1 {color:#001F3F;}
    .dashboard-header p {color:#555;}
</style>

<div class="sidebar">
    <h2>Admin Panel</h2>
    <ul>
        <li><a href="admin_dashboard.php" class="active"><i class="fas fa-chart-line"></i> Dashboard</a></li>
        
        <!-- Manage Products with Submenu -->
        <li class="submenu">
            <a class="toggle-submenu"><i class="fas fa-box"></i> Manage Products ▾</a>
            <ul class="submenu-list">
                <li><a href="add_products.php"><i class="fas fa-plus-circle"></i> Add Product</a></li>
                <li><a href="upcoming_product.php"><i class="fas fa-clock"></i> Upcoming Product</a></li>
            </ul>
        </li>

        <li><a href="orders.php"><i class="fas fa-shopping-cart"></i> Orders</a></li>
        <li><a href="categories.php"><i class="fas fa-tags"></i> Categories</a></li>
    </ul>
</div>

<div class="main">
    <div class="top-bar">
        <!-- Logout button aligned left -->
        <form action="logout.php" method="post">
            <button type="submit"><i class="fas fa-sign-out-alt"></i> Logout</button>
        </form>
    </div>

<script>
    // Toggle submenu
    document.querySelectorAll(".toggle-submenu").forEach(toggle => {
        toggle.addEventListener("click", function() {
            this.parentElement.classList.toggle("active");
        });
    });
</script>
