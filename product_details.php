<?php
// product_details.php
require 'includes/db.php';
session_start();

if (!isset($_GET['id'])) {
    header('Location: shop.php');
    exit;
}

$product_id = (int)$_GET['id'];

// Fetch product details
$stmt = $pdo->prepare("SELECT * FROM products WHERE product_id = ?");
$stmt->execute([$product_id]);
$product = $stmt->fetch();

if (!$product) {
    echo "<h3>Product not found.</h3>";
    exit;
}

// Store product in recently viewed session
if (!isset($_SESSION['recently_viewed'])) {
    $_SESSION['recently_viewed'] = [];
}

if (!in_array($product_id, $_SESSION['recently_viewed'])) {
    array_unshift($_SESSION['recently_viewed'], $product_id);
    if (count($_SESSION['recently_viewed']) > 5) {
        array_pop($_SESSION['recently_viewed']);
    }
}

// Fetch reviews
$review_stmt = $pdo->prepare("SELECT r.*, o.name 
    FROM reviews r 
    JOIN orders o ON r.order_id = o.order_id 
    Join order_items oi ON r.order_id = oi.order_item_id
    WHERE oi.product_id = ?");
$review_stmt->execute([$product_id]);
$reviews = $review_stmt->fetchAll();

$avg_rating = 0;
if (count($reviews) > 0) {
    $sum = array_sum(array_column($reviews, 'rating'));
    $avg_rating = round($sum / count($reviews), 1);
}

// Fetch recommended products
$like_stmt = $pdo->query("SELECT * FROM products ORDER BY RAND() LIMIT 4");
$likes = $like_stmt->fetchAll();

// Fetch recently viewed
$recent_ids = array_diff($_SESSION['recently_viewed'], [$product_id]);
$recent = [];
if (!empty($recent_ids)) {
    $recent_ids = array_values($recent_ids); // Reindex the array to ensure it’s numerically indexed
    $placeholders = implode(',', array_fill(0, count($recent_ids), '?'));
    $recent_stmt = $pdo->prepare("SELECT * FROM products WHERE product_id IN ($placeholders)");
    $recent_stmt->execute($recent_ids);
    $recent = $recent_stmt->fetchAll();
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($product['product_name']) ?> - BambooLink</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body {
            background-color: #91BF83;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            color: #333;
        }
        .star { color: #FFD700; font-size: 1.2rem; }
        .quantity-btn {
            width: 40px;
            height: 40px;
            font-size: 18px;
            background-color: #6A9274;
            color: white;
        }
        .product-img {
            max-height: 350px;
            object-fit: contain;
            border-radius: 12px;
            border: 1px solid #ccc;
        }
        .card img {
            max-height: 150px;
            object-fit: contain;
            margin-top: 15px;
        }
        .card p {
            font-size: 0.95rem;
            padding: 0.5rem;
            color: #333;
        }
        .section-title {
            border-bottom: 2px solid #6A9274;
            padding-bottom: 0.3rem;
            margin-top: 3rem;
            margin-bottom: 1rem;
        }
        .review-card {
            background-color: #fff;
            border-left: 4px solid #6A9274;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }
        .btn-primary-custom {
            background-color: #6A9274;
            color: white;
        }
    </style>
</head>
<body>
<div class="container py-5">
    <a href="shop.php" class="btn btn-secondary mb-4" style="background-color: #6A9274;">
        <i class="bi bi-arrow-left"></i> Back to Shop
    </a>

    <div class="row g-4 align-items-center mb-5">
        <div class="col-md-5 text-center">
            <img src="images/<?= htmlspecialchars($product['product_image']) ?>" class="img-fluid product-img" alt="<?= htmlspecialchars($product['product_name']) ?>">
        </div>
        <div class="col-md-7">
            <h2 class="mb-3"><?= htmlspecialchars($product['product_name']) ?></h2>
            <h4 class="text-success mb-3">₱<?= number_format($product['price'], 2) ?></h4>
            <p><?= nl2br(htmlspecialchars($product['description'])) ?></p>
            <p><strong>Stock:</strong> <?= $product['stock'] ?></p>
            <p><strong>Rating:</strong>
                <?php for ($i = 1; $i <= 5; $i++): ?>
                    <span class="star"><?= $i <= $avg_rating ? '★' : '☆' ?></span>
                <?php endfor; ?>
                (<?= $avg_rating ?>/5)
            </p>

            <div class="d-flex align-items-center mb-3">
                <button type="button" class="btn quantity-btn me-2" onclick="updateQty(-1)">-</button>
                <input type="text" id="quantity" name="orderQuantity" value="1" class="form-control text-center" style="width: 70px;">
                <button type="button" class="btn quantity-btn ms-2" onclick="updateQty(1)">+</button>
            </div>

            <form method="POST" action="add_to_cart.php" class="d-inline">
                <input type="hidden" name="product_id" value="<?= $product_id ?>">
                <input type="hidden" name="quantity" id="qty_input" value="1">
                <button type="submit" class="btn btn-success me-2">Add to Cart</button>
            </form>
            <a href="order_now.php?product_id=<?= $product['product_id'] ?>" class="btn btn-primary-custom">Order Now</a>
        </div>
    </div>

    <h4 class="section-title">Customer Reviews</h4>
    <?php if (count($reviews) > 0): ?>
        <?php foreach ($reviews as $review): ?>
            <div class="p-3 mb-3 review-card">
                <strong><?= htmlspecialchars($review['name']) ?>:</strong>
                <?php for ($i = 1; $i <= 5; $i++): ?>
                    <span class="star"><?= $i <= $review['rating'] ? '★' : '☆' ?></span>
                <?php endfor; ?>
                <p class="mt-2"><?= htmlspecialchars($review['comment']) ?></p>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <p class="text-muted">No reviews yet.</p>
    <?php endif; ?>

    <h4 class="section-title">You May Also Like</h4>
    <div class="row g-3">
        <?php foreach ($likes as $item): ?>
            <div class="col-6 col-md-3">
                <div class="card h-100 text-center">
                    <a href="product_details.php?id=<?= $item['product_id'] ?>" class="text-decoration-none">
                        <img src="images/<?= htmlspecialchars($item['product_image']) ?>" class="card-img-top" alt="">
                        <div class="card-body">
                            <p class="card-text"><?= htmlspecialchars($item['product_name']) ?><br><strong>₱<?= number_format($item['price'], 2) ?></strong></p>
                        </div>
                    </a>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <h4 class="section-title">Recently Viewed</h4>
    <div class="row g-3">
        <?php if (!empty($recent)): ?>
            <?php foreach ($recent as $item): ?>
                <div class="col-6 col-md-3">
                    <div class="card h-100 text-center">
                        <a href="product_details.php?id=<?= $item['product_id'] ?>" class="text-decoration-none">
                            <img src="images/<?= htmlspecialchars($item['product_image']) ?>" class="card-img-top" alt="">
                            <div class="card-body">
                                <p class="card-text"><?= htmlspecialchars($item['product_name']) ?><br><strong>₱<?= number_format($item['price'], 2) ?></strong></p>
                            </div>
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="col-12">
                <p class="text-muted">No recently viewed products yet.</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
function updateQty(change) {
    const input = document.getElementById('quantity');
    let qty = parseInt(input.value);
    if (!isNaN(qty)) {
        qty += change;
        if (qty < 1) qty = 1;
        input.value = qty;
        document.getElementById('qty_input').value = qty;
    }
}
</script>
</body>
</html>
