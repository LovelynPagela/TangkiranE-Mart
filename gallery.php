<?php
require 'includes/db.php';
require 'includes/auth.php';
$gallery = $pdo->query("SELECT * FROM gallery ORDER BY uploaded_at DESC")->fetchAll();
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gallery</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <link rel="stylesheet" href="styles.css">
        <style>
            .login-logo {
  width: 100px;  /* Resize as needed */
  height: auto;
  align-items: center;
}
    </style>
</head>
<body>
<header>
<nav class="navbar">
        <div>
            <!--<img src="images/logo2.png" alt="Logo" class="login-logo"> -->
            <a class="navbar-brand" href="index.php">TANGKIRAN e-MART</a>
        </div>
        <?php if (is_logged_in()): ?>
            <div style="position: relative; display: inline-block;">
                <img src="images/user.png" alt="User Icon" id="userIcon" style="height: 30px; cursor: pointer;">

                <!-- Toggle Form -->
                <div id="userMenu" style="display: none; position: absolute;top: 40px;right: 0; background-color: #f1f1f1;border: 1px solid #ccc;border-radius: 5px;padding: 10px;z-index: 1000;box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1); width: 160px;">
                    <a href="account.php" style="display: block; padding: 5px 10px; text-decoration: none; color: black;">My Profile</a>
                    <a href="my_orders.php" style="display: block; padding: 5px 10px; text-decoration: none; color: black;">My Orders</a>
                    <a href="logout.php" style="display: block; padding: 5px 10px; text-decoration: none; color: red;">Logout</a>
                </div>
                <a href="cart.php"> <img src="images/cart.png" alt="" style="height: 30px; cursor: pointer;"></a>
            </div>
        <?php else: ?>
        <div>
            <a href="login.php">Login</a>
          <a href="cart.php">🛒</a>
        <?php endif; ?>
        </div>
    </nav>
<section>
    <nav class="div2">
        <ul id="nav-links2">
            <li><a href="index.php">Home</a></li>
            <li><a href="gallery.php">Gallery</a></li>
            <li><a href="index.php#bamboo_products_catalogs">Product Catalog</a></li>
            <li><a href="shop.php">Shop</a></li>
            
        </ul>
    </nav>
</section>
</header>
<h2 class="mb-4">CSU-G Products and Creations</h2>
<div class="container mt-4">
    
    <div class="row">
        <?php foreach ($gallery as $g): ?>
        <div class="col-md-3 mb-3">
            <div class="card">
                <img src="images/<?= $g['image_path'] ?>" class="card-img-top" alt="Gallery Image">
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>
<script>
    const userIcon = document.getElementById('userIcon');
    const userMenu = document.getElementById('userMenu');

    userIcon.addEventListener('click', function () {
        userMenu.style.display = userMenu.style.display === 'none' || userMenu.style.display === '' ? 'block' : 'none';
    });

    // Optional: Hide menu when clicking outside
    document.addEventListener('click', function (event) {
        if (!userIcon.contains(event.target) && !userMenu.contains(event.target)) {
            userMenu.style.display = 'none';
        }
    });
</script>
</body>
</html>