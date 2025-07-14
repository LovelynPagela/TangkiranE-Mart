<?php
require 'includes/db.php';
require 'includes/auth.php';

    $products = $pdo->query("SELECT * FROM products ORDER BY created_at DESC")->fetchAll();

?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SHOP</title>
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
<h2 class="mb-4">CSU-G SHOP</h2>
    <div id="search" style="align-items: center; justify-content: center; display: flex">
        <form class="d-flex" method="get" action="search_results.php">
            <input class="form-control me-2" type="search" name="search" style="width: 200px" placeholder="Search products..." aria-label="Search" >
            <button class="btn btn-success" type="submit">Search</button>
        </form>
    </div>
    
<div class="container">
    
    <div class="row">
        <?php if ($products): foreach ($products as $product): ?>
        <div class="col-md-3 mb-4">
            <div class="card h-100">
                <a href="product_details.php?id=<?= $product['product_id'] ?>">
                    <img src="images/<?= $product['product_image'] ?>" class="card-img-top" id="image" alt="<?= $product['product_name'] ?>">
                </a>
                <div class="card-body">
                    <h5 class="card-title"><?= $product['product_name'] ?></h5>
                    <p class="card-text">₱<?= number_format($product['price'], 2) ?></p>
                    <form method="POST" action="add_to_cart.php" style="display:inline;">
                        <input type="hidden" name="product_id" value="<?= $product['product_id'] ?>">
                        <input type="hidden" name="quantity" value="1"> <!-- Default quantity 1 -->
                        <button type="submit" class="btn btn-success" style="margin-bottom: 5px">Add to Cart</button>
                        <a href="order_now.php?product_id=<?= $product['product_id'] ?>" class="btn" style="background-color: #6A9274; color: white;">Order now</a>
                    </form>
                    
                </div>
            </div>
        </div>
        <?php endforeach; else: ?>
        <p>No products found.</p>
        <?php endif; ?>
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
