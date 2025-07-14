<?php
// admin/manage_gallery.php
session_start();
require '../includes/db.php';

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header('Location: ../login.php');
    exit;
}

// Handle multiple image uploads
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['images'])) {
    $allowedMimeTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

    $files = $_FILES['images'];

    for ($i = 0; $i < count($files['name']); $i++) {
        if ($files['error'][$i] === UPLOAD_ERR_OK) {
            $tmpName = $files['tmp_name'][$i];
            $originalName = $files['name'][$i];
            $fileMime = mime_content_type($tmpName);
            $fileExt = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));

            if (in_array($fileMime, $allowedMimeTypes) && in_array($fileExt, $allowedExtensions)) {
                $filename = 'gallery_' . time() . '_' . uniqid() . '.' . $fileExt;
                $destination = '../images/' . $filename;

                if (move_uploaded_file($tmpName, $destination)) {
                    $stmt = $pdo->prepare("INSERT INTO gallery (image_path) VALUES (?)");
                    $stmt->execute([$filename]);
                }
            }
        }
    }

    echo "<script>alert('Image(s) uploaded successfully.'); window.location='manage_gallery.php';</script>";
    exit;
}

// Handle delete
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    $stmt = $pdo->prepare("SELECT image_path FROM gallery WHERE gallery_id = ?");
    $stmt->execute([$id]);
    $img = $stmt->fetch();
    if ($img) {
        @unlink('../images/' . $img['image_path']);
        $pdo->prepare("DELETE FROM gallery WHERE gallery_id = ?")->execute([$id]);
    }
    header('Location: manage_gallery.php');
    exit;
}

$gallery = $pdo->query("SELECT * FROM gallery ORDER BY uploaded_at DESC")->fetchAll();
?>
<!DOCTYPE html>
<html>
<head>
    <title>Manage Gallery - BambooLink</title>
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
    <a href="manage_categories.php"><i class="fas fa-tags me-2"></i> Manage Categories</a>
    <a href="manage_orders.php"><i class="fas fa-receipt me-2"></i> Manage Orders</a>
    <a href="manage_gallery.php" class="active"><i class="fas fa-images me-2"></i> Manage Gallery</a>
    <a href="account.php"><i class="fas fa-user-cog me-2"></i> My Account</a>
    <a href="../logout.php"><i class="fas fa-sign-out-alt me-2"></i> Logout</a>
</div>
<div class="container mt-5">
    <h2>Upload Gallery Images</h2>
    <form method="post" enctype="multipart/form-data">
        <div class="mb-3">
            <label for="images" class="form-label">Select Images</label>
            <input type="file" name="images[]" class="form-control" accept="image/*" multiple required>
            <div class="form-text">You can upload multiple images at once. Only JPG, PNG, GIF, and WEBP are allowed.</div>
        </div>
        <button class="btn btn-success">Upload</button>
    </form>
</div>

<div class="container mt-5">
    <h2>Manage Gallery</h2>
    <div class="row">
        <?php foreach ($gallery as $item): ?>
        <div class="col-md-3 mb-4">
            <div class="card h-100">
                <img src="../images/<?= htmlspecialchars($item['image_path']) ?>" class="card-img-top" alt="Gallery Image">
                <div class="card-body">
                    <p class="card-text text-muted">Uploaded: <?= date('M d, Y', strtotime($item['uploaded_at'])) ?></p>
                    <a href="manage_gallery.php?delete=<?= $item['gallery_id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Delete this image?')">
                        <i class="bi bi-trash"></i> Delete
                    </a>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>
</body>
</html>
