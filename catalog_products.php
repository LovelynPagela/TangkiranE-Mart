<?php
require 'includes/db.php';
session_start();

$categoryId = $_GET['category'] ?? null;

if (!$categoryId) {
    header("Location: index.php");
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM categories WHERE category_id = ?");
$stmt->execute([$categoryId]);
$category = $stmt->fetch();

if (!$category) {
    echo "Category not found.";
    exit;
}

$productStmt = $pdo->prepare("SELECT * FROM products WHERE category_id = ?");
$productStmt->execute([$categoryId]);
$products = $productStmt->fetchAll();
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $category['category_name'] ?> - Products</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!--<link rel="stylesheet" href="styles.css">-->
</head>
<body style="font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background-color: #91BF83; color: #333;">
<div class="container mt-5">
    <a href="index.php#bamboo_products_catalogs" class="btn mb-4" style="background-color: #6A9274; color: white">← Back to Catalog</a>
    <h2>Products under "<?= $category['category_name'] ?>"</h2>
    <div class="row mt-4">
        <?php if (!empty($products)): ?>
            <?php foreach ($products as $product): ?>
            <div class="col-md-3 mb-4">
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
        <?php else: ?>
            <p>No products found under this category.</p>
        <?php endif; ?>
    </div>
</div>
</body>
</html>
