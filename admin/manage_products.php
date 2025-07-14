<?php
// admin/manage_product_catalog.php
session_start();
require '../includes/db.php';

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header('Location: ../login.php');
    exit;
}
function formatProductName($name) {
    return ucwords(strtolower(trim($name)));
}

// Handle delete
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    $stmt = $pdo->prepare("SELECT image FROM products WHERE product_id = ?");
    $stmt->execute([$id]);
    $product = $stmt->fetch();
    if ($product && file_exists('../images/' . $product['image'])) {
        unlink('../images/' . $product['image']);
    }
    $pdo->prepare("DELETE FROM products WHERE product_id = ?")->execute([$id]);
    header('Location: manage_products.php');
    exit;
}

// Handle add or update product
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = formatProductName($_POST['product_name']); 
    $desc = $_POST['description'];
    $price = $_POST['price'];
    $stock = $_POST['stock'];
    $cat = $_POST['category_id'];
    $imgName = '';

    if (!empty($_FILES['image']['name'])) {
        $imgName = 'catalog_' . time() . '_' . basename($_FILES['image']['name']);
        move_uploaded_file($_FILES['image']['tmp_name'], '../images/' . $imgName);
    }

    if (isset($_POST['edit_id'])) {
        $id = $_POST['edit_id'];
        $sql = "UPDATE products SET product_name=?, category_id=?, description=?, price=?, stock=?" . ($imgName ? ", image=?" : "") . " WHERE product_id=?";
        $params = [$name, $cat, $desc, $price, $stock];
        if ($imgName) $params[] = $imgName;
        $params[] = $id;
        $pdo->prepare($sql)->execute($params);
    } else {
        $stmt = $pdo->prepare("INSERT INTO products (product_name, category_id, description, price, stock, product_image) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$name, $cat, $desc, $price, $stock, $imgName]);
    }
    header('Location: manage_products.php');
    exit;
}

// Load existing data
$categories = $pdo->query("SELECT * FROM categories ORDER BY category_name ASC")->fetchAll();
$products = $pdo->query("SELECT p.*, c.category_name FROM products p LEFT JOIN categories c ON p.category_id = c.category_id ORDER BY p.created_at DESC")->fetchAll();
$productToEdit = null;
if (isset($_GET['edit'])) {
    $id = $_GET['edit'];
    $stmt = $pdo->prepare("SELECT * FROM products WHERE product_id = ?");
    $stmt->execute([$id]);
    $productToEdit = $stmt->fetch();
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Manage Product Catalog - Bamboo Hub</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
     <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <style>
    body {
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; 
    background-color: #91BF83;
    margin: 0;
    padding: 0;
}

.wrapper {
    margin-left: 250px;
    padding: 20px;
}

.container {
    background: #fff;
    padding: 30px;
    border-radius: 12px;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
    margin-left: 30%;
    max-width: 700px;
}

.sidebar {
    height: 100vh;
    background-color: #2e7d32;
    padding-top: 20px;
    position: fixed;
    width: 250px;
    top: 0;
    left: 0;
    overflow-y: auto;
}

.sidebar a {
    color: #ecf0f1;
    padding: 12px 20px;
    display: block;
    text-decoration: none;
    font-weight: 500;
}

.sidebar a:hover, .sidebar a.active {
    background-color: #6A9274;
    color: white;
}

.table img {
    border-radius: 6px;
}

@media (max-width: 768px) {
    .sidebar {
        width: 100%;
        height: auto;
        position: relative;
    }

    .wrapper {
        margin-left: 0;
        padding: 10px;
    }

    .btn-success {
        width: 100%;
    }
}

</style>
</head>
<body>
    <div class="sidebar">
    <div class="text-center mb-4">
        <img src="../images/user.png" alt="Admin" class="rounded-circle" width="80">
        <p class="text-white mt-2 fw-bold">Admin</p>
    </div>
    <a href="dashboard.php"><i class="fas fa-tachometer-alt me-2"></i> Dashboard</a>
    <a href="manage_products.php" class="active"><i class="fas fa-box-open me-2"></i> Manage Products</a>
    <a href="manage_categories.php"><i class="fas fa-tags me-2"></i> Manage Categories</a>
    <a href="manage_orders.php"><i class="fas fa-receipt me-2"></i> Manage Orders</a>
    <a href="manage_gallery.php"><i class="fas fa-images me-2"></i> Manage Gallery</a>
    <a href="account.php"><i class="fas fa-user-cog me-2"></i> My Account</a>
    <a href="../logout.php"><i class="fas fa-sign-out-alt me-2"></i> Logout</a>
</div>
<div class="container mt-5">
    <h2><?= $productToEdit ? 'Edit Product' : 'Add Product' ?></h2>
    <form method="post" enctype="multipart/form-data">
        <?php if ($productToEdit): ?>
            <input type="hidden" name="edit_id" value="<?= $productToEdit['product_id'] ?>">
        <?php endif; ?>
        <div class="mb-3">
            <label class="form-label">Product Name</label>
            <input type="text" name="product_name" class="form-control" required value="<?= $productToEdit['product_name'] ?? '' ?>">
        </div>
        <div class="mb-3">
            <label class="form-label">Category</label>
            <select name="category_id" class="form-select" required>
                <?php foreach ($categories as $c): ?>
                    <option value="<?= $c['category_id'] ?>" <?= isset($productToEdit['category_id']) && $productToEdit['category_id'] == $c['category_id'] ? 'selected' : '' ?>><?= $c['category_name'] ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="mb-3">
            <label class="form-label">Description</label>
            <textarea name="description" class="form-control" required><?= $productToEdit['description'] ?? '' ?></textarea>
        </div>
        <div class="mb-3">
            <label class="form-label">Price (₱)</label>
            <input type="number" step="0.01" name="price" class="form-control" required value="<?= $productToEdit['price'] ?? '' ?>">
        </div>
        <div class="mb-3">
            <label class="form-label">Stock</label>
            <input type="number" name="stock" class="form-control" required value="<?= $productToEdit['stock'] ?? '' ?>">
        </div>
        <div class="mb-3">
            <label class="form-label">Image</label>
            <input type="file" name="image" class="form-control">
            <?php if (!empty($productToEdit['image'])): ?>
                <small>Current: <?= $productToEdit['image'] ?></small>
            <?php endif; ?>
        </div>
        <button class="btn btn-success">Save</button>
        <a href="manage_products.php" class="btn btn-secondary">Cancel</a>
    </form>

    <hr>
    <h2 class="mt-5">Product Catalog</h2>
    <table class="table table-bordered">
        <thead>
            <tr>
                <th>Image</th>
                <th>Name</th>
                <th>Category</th>
                <th>Price</th>
                <th>Stock</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($products as $p): ?>
            <tr>
                <td><img src="../images/<?= $p['product_image'] ?>" width="60"></td>
                <td><?= formatProductName($p['product_name']) ?></td>
                <td><?= $p['category_name'] ?></td>
                <td>₱<?= number_format($p['price'], 2) ?></td>
                <td><?= $p['stock'] ?></td>
                <td>
                    <a href="manage_products.php?edit=<?= $p['product_id'] ?>" class="btn btn-primary btn-sm">Edit</a>
                    <a href="manage_products.php?delete=<?= $p['product_id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Delete this product?')">Delete</a>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
</body>
</html>