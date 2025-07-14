<?php
// index.php
require 'includes/db.php';
require 'includes/auth.php';

// Fetch categories to display as product catalog
$catalogs = $pdo->query("SELECT * FROM categories WHERE image IS NOT NULL ORDER BY category_name ASC")->fetchAll();

// If category is clicked, show its products
$categoryId = $_GET['category'] ?? null;
$products = [];
if ($categoryId) {
    $stmt = $pdo->prepare("SELECT * FROM products WHERE category_id = ?");
    $stmt->execute([$categoryId]);
    $products = $stmt->fetchAll();
}
// Get 1 latest review per customer
$reviewStmt = $pdo->query("
    SELECT r.*, o.name, GROUP_CONCAT(p.product_name SEPARATOR ', ') AS products
    FROM reviews r
    JOIN orders o ON r.order_id = o.order_id
    JOIN users u ON r.user_id = u.user_id
    JOIN order_items oi ON r.order_id = oi.order_id
    JOIN products p ON oi.product_id = p.product_id
    GROUP BY r.user_id
    ORDER BY r.created_at DESC
    LIMIT 10
");
$reviews = $reviewStmt->fetchAll();


?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HOME</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
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
            <!--<img src="images/logo2.png" alt="Logo" class="login-logo" > -->
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
            <li><a href="#bamboo_products_catalogs">Product Catalog</a></li>
            <li><a href="shop.php">Shop</a></li>
            
        </ul>
    </nav>
</section>
</header>
<section>
<div id="bannerCarousel" class="carousel slide" data-bs-ride="carousel">
  <div class="carousel-indicators">
    <button type="button" data-bs-target="#bannerCarousel" data-bs-slide-to="0" class="active"></button>
    <button type="button" data-bs-target="#bannerCarousel" data-bs-slide-to="1"></button>
    <button type="button" data-bs-target="#bannerCarousel" data-bs-slide-to="2"></button>
  </div>

  <div class="carousel-inner">
    <div class="carousel-item active">
      <img src="images/bamboo1.png" class="d-block w-100" alt="Bamboo Product 1">
    </div>
    <div class="carousel-item">
      <img src="images/bamboo2.png" class="d-block w-100" alt="Bamboo Product 2">
    </div>
    <div class="carousel-item">
      <img src="images/Bamboo.jpg" class="d-block w-100" alt="Bamboo Product 3">
    </div>
  </div>

  <button class="carousel-control-prev" type="button" data-bs-target="#bannerCarousel" data-bs-slide="prev">
    <span class="carousel-control-prev-icon"></span>
  </button>
  <button class="carousel-control-next" type="button" data-bs-target="#bannerCarousel" data-bs-slide="next">
    <span class="carousel-control-next-icon"></span>
  </button>
</div>
</section>

        <h2 class="text-center mt-5">Featured CSU-Gonzaga Products</h2>
        <div class="row" id="featured">
            <?php
            $featured = $pdo->query("SELECT * FROM products ORDER BY created_at DESC LIMIT 6")->fetchAll();
            foreach ($featured as $product): ?>
            <div class="col-md-2 mb-4">
                <div class="card h-100">
                    <a href="product_details.php?id=<?= $product['product_id'] ?>">
                        <img src="images/<?= $product['product_image'] ?>" class="card-img-top" alt="<?= $product['product_name'] ?>">
                    </a>
                    <div class="card-body">
                        <h5 class="card-title"><?= $product['product_name'] ?></h5>
                        <p class="card-text">₱<?= number_format($product['price'], 2) ?></p>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <div  style="align-items: center; justify-content: center; display: flex;">
            <a href="shop.php" class="btn btn-success" style="width: 200px">Shop Now</a>
        </div>
<div class="mt-5" id="bamboo_products_catalogs">
    <?php if (!$categoryId): ?>
        <h2 class="text-center">Browse CSU-G Catalog</h2>
        <div class="row">
            <?php foreach ($catalogs as $cat): ?>
            <div class="col-md-3 mb-4">
            <a href="catalog_products.php?category=<?= $cat['category_id'] ?>" class="text-decoration-none text-dark">
                    <div class="card h-100">
                        <img src="images/<?= $cat['image'] ?>" class="card-img-top" alt="<?= $cat['category_name'] ?>">
                        <div class="card-body text-center">
                            <h5><?= $cat['category_name'] ?></h5>
                        </div>
                    </div>
                </a>
            </div>
            <?php endforeach; ?>
</div>
    <?php else: ?>
        <h2 class="mb-4">Products in Selected Category</h2>
        <div class="row">
            <?php if (!empty($products)): foreach ($products as $product): ?>
            <div class="col-md-3 mb-4">
                <div class="card h-100">
                    <img src="images/<?= $product['image'] ?>" class="card-img-top" alt="<?= $product['product_name'] ?>">
                    <div class="card-body">
                        <h5 class="card-title"><?= $product['product_name'] ?></h5>
                        <p class="card-text">₱<?= number_format($product['price'], 2) ?></p>
                        <a href="cart.php?id=<?= $product['product_id'] ?>" class="btn btn-success">Add to Cart</a>
                    </div>
                </div>
            </div>
            <?php endforeach; else: ?>
            <p>No products found in this category.</p>
            <?php endif; ?>
        </div>
        <div class="text-end">
            <a href="index.php#bamboo_products_catalogs" class="btn btn-secondary">Back to Catalog</a>
        </div>
    <?php endif; ?>
</div>
<div class="">
    
    <div class="container">
        <div class="row">
            <h2 class="text-center">Customer Testimonials</h2>
            <?php foreach ($reviews as $review): ?>
            <div class="col-md-3 mb-4">
               
                <div class="card h-100 shadow">
                    <div class="card-body">
                        <h5 class="card-title"><?= htmlspecialchars($review['name']) ?></h5>
                        <p>
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                <span style="color: <?= $i <= $review['rating'] ? '#ffc107' : '#e4e5e9' ?>">★</span>
                            <?php endfor; ?>
                        </p>
                        <p class="card-text"><?= htmlspecialchars($review['comment']) ?></p>
                        <small class="text-muted"><?= date("F j, Y", strtotime($review['created_at'])) ?></small>
                        <p class="text-muted">Ordered: <?= htmlspecialchars($review['products']) ?></p>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
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
<footer style="background-color: #2e7d32; color: white; margin: 0; height: 800px;">
    <div class="container">
        <div class="row">
            <!-- Contact Info -->
            <div class="col-md-6 mb-3">
                <h5>Contact Us</h5>
                <p><strong>Phone:</strong> <a href="tel:+639123456789" class="text-white">+63 912 345 6789</a></p>
                <p><strong>Email:</strong> <a href="mailto:bamboolink@csugonzaga.edu.ph" class="text-white">bamboolink@csugonzaga.edu.ph</a></p>
           
                <h5>Follow Us</h5>
                <p>
                    <a href="https://www.facebook.com/CSUGBambooLink" target="_blank" class="text-white text-decoration-none">Facebook Page</a><br>
                    <a href="https://www.instagram.com/CSUGBambooLink" target="_blank" class="text-white text-decoration-none">Instagram</a><br>
                    <a href="https://twitter.com/CSUGBambooLink" target="_blank" class="text-white text-decoration-none">Twitter</a>
                </p>
            </div>

            <!-- Support -->
            <div class="col-md-4 mb-3">
                <h5>Customer Support</h5>
                <p>Need help? Reach out via our contact form or chat with us during office hours (Mon-Fri, 8 AM - 5 PM).</p>
                <a href="contact_support.php" class="btn btn-outline-light btn-sm">Contact Support</a>
            </div>
            <hr class="bg-light">
            <div class="text-center">
            &copy; <?= date('Y') ?> TANGKIRAN e-MART. All rights reserved.
        </div>
        </div>
        
        
    </div>
</footer>

</body>
</html>
