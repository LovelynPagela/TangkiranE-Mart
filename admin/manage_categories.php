<?php
// admin/manage_category.php
session_start();
require '../includes/db.php';



// Handle category delete
if (isset($_GET['delete_category'])) {
    $catId = $_GET['delete_category'];
    $pdo->prepare("DELETE FROM categories WHERE category_id = ?")->execute([$catId]);
    header('Location: manage_category.php');
    exit;
}

// Capitalize first letter of each word
function formatCategoryName($name) {
    return ucwords(strtolower(trim($name)));
}

// Handle add or update category
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['category_name']) && !isset($_POST['edit_id'])) {
        $newCat = formatCategoryName($_POST['category_name']);

        // Check if category exists (case insensitive)
        $check = $pdo->prepare("SELECT COUNT(*) FROM categories WHERE LOWER(category_name) = ?");
        $check->execute([strtolower($newCat)]);
        if ($check->fetchColumn() > 0) {
            echo "<script>alert('Category already exists!'); window.location='manage_categories.php';</script>";
            exit;
        }

        $image = '';
        if (!empty($_FILES['image']['name'])) {
            $image = 'catalog_' . time() . '_' . basename($_FILES['image']['name']);
            move_uploaded_file($_FILES['image']['tmp_name'], '../images/' . $image);
        }
        $pdo->prepare("INSERT INTO categories (category_name, image) VALUES (?, ?)")->execute([$newCat, $image]);
    }
    if (isset($_POST['edit_id']) && isset($_POST['new_name'])) {
        $id = $_POST['edit_id'];
        $name = formatCategoryName($_POST['new_name']);

        $image = '';
        if (!empty($_FILES['new_image']['name'])) {
            $image = 'catalog_' . time() . '_' . basename($_FILES['new_image']['name']);
            move_uploaded_file($_FILES['new_image']['tmp_name'], '../images/' . $image);
            $pdo->prepare("UPDATE categories SET category_name = ?, image = ? WHERE category_id = ?")
                ->execute([$name, $image, $id]);
        } else {
            $pdo->prepare("UPDATE categories SET category_name = ? WHERE category_id = ?")
                ->execute([$name, $id]);
        }
    }
}

$categories = $pdo->query("SELECT * FROM categories ORDER BY category_name ASC")->fetchAll();
?>
<!DOCTYPE html>
<html>
<head>
    <title>Manage Categories - Bamboo Hub</title>
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
    <a href="manage_products.php"><i class="fas fa-box-open me-2"></i> Manage Products</a>
    <a href="manage_categories.php" class="active"><i class="fas fa-tags me-2"></i> Manage Categories</a>
    <a href="manage_orders.php"><i class="fas fa-receipt me-2"></i> Manage Orders</a>
    <a href="manage_gallery.php"><i class="fas fa-images me-2"></i> Manage Gallery</a>
    <a href="account.php"><i class="fas fa-user-cog me-2"></i> My Account</a>
    <a href="../logout.php"><i class="fas fa-sign-out-alt me-2"></i> Logout</a>
</div>
<div class="container mt-5">
    <h2>Manage Categories</h2>
    <form method="post" enctype="multipart/form-data" class="mb-3">
        <div class="row">
            <div class="col-md-4">
                <input type="text" name="category_name" class="form-control" placeholder="New category name" required>
            </div>
            <div class="col-md-4">
                <input type="file" name="image" class="form-control" required>
            </div>
            <div class="col-md-2">
                <button class="btn btn-primary">Add</button>
            </div>
        </div>
    </form>
    <table class="table table-bordered">
        <thead>
            <tr>
                <th>Category Image</th>
                <th>Category Name</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($categories as $c): ?>
            <tr>
                <td><img src="../images/<?= $c['image'] ?>" alt="Category Image" width="60"></td>
                <td>
                    <form method="post" enctype="multipart/form-data" class="d-flex align-items-center">
                        <input type="hidden" name="edit_id" value="<?= $c['category_id'] ?>">
                        <input type="text" name="new_name" value="<?= htmlspecialchars($c['category_name']) ?>" class="form-control me-2">
                        <input type="file" name="new_image" class="form-control me-2">
                        <button class="btn btn-sm btn-warning">Update</button>
                    </form>
                </td>
                <td>
                    <a href="?delete_category=<?= $c['category_id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Delete this category?')">Delete</a>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
</body>
</html>
