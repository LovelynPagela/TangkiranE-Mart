<?php
require '../includes/db.php';
require '../includes/auth.php';

if (!is_admin()) {
    header('Location: ../login.php');
    exit;
}


$reviewQuery = $pdo->prepare("
    SELECT r.rating, r.comment, r.created_at,
       o.name,
       p.product_name
FROM reviews r
JOIN orders o ON r.order_id = o.order_id
JOIN users u ON r.user_id = u.user_id
JOIN order_items oi ON oi.order_id = o.order_id
JOIN products p ON oi.product_id = p.product_id
ORDER BY r.created_at DESC

");
$reviewQuery->execute();
$reviews = $reviewQuery->fetchAll();


?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard - BambooLink</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">

<style>
    body {
        background-color: #91BF83;
        margin: 0;
    }

    .sidebar {
        height: 100vh;
        background-color: #2e7d32;
        padding-top: 20px;
        position: fixed;
        width: 250px;
        transition: transform 0.3s ease;
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

    .main-content {
        margin-left: 250px;
        padding: 30px;
        transition: margin-left 0.3s ease;
    }

    .sidebar-hidden {
        transform: translateX(-250px);
    }

    .main-content.full-width {
        margin-left: 0;
    }

    .toggle-btn {
        display: none;
        background-color: #2e7d32;
        color: white;
        border: none;
        font-size: 20px;
        padding: 10px;
        position: fixed;
        top: 10px;
        left: 10px;
        z-index: 1001;
    }

    @media (max-width: 768px) {
        .sidebar {
            transform: translateX(-250px);
        }

        .toggle-btn {
            display: block;
        }

        .main-content {
            margin-left: 0;
        }
    }
</style>

</head>
<body>
<button class="toggle-btn" onclick="toggleSidebar()">☰</button>
<div class="sidebar" id="sidebar">
    <div class="text-center mb-4">
        <img src="../images/user.png" alt="Admin" class="rounded-circle" width="80">
        <p class="text-white mt-2 fw-bold">Admin</p>
    </div>
    <a href="admin_dashboard.php" class="active"><i class="fas fa-tachometer-alt me-2"></i> Dashboard</a>
    <a href="manage_products.php"><i class="fas fa-box-open me-2"></i> Manage Products</a>
    <a href="manage_categories.php"><i class="fas fa-tags me-2"></i> Manage Categories</a>
    <a href="manage_orders.php"><i class="fas fa-receipt me-2"></i> Manage Orders</a>
    <a href="manage_gallery.php"><i class="fas fa-images me-2"></i> Manage Gallery</a>
    <a href="account.php"><i class="fas fa-user-cog me-2"></i> My Account</a>
    <a href="../logout.php"><i class="fas fa-sign-out-alt me-2"></i> Logout</a>
</div>

<div class="main-content">

    <h3 class="mb-4">Dashboard Overview</h3>

    <div class="row g-4">
        <div class="col-md-4">
            <div class="card p-3">
                <h5 class="card-title">Total Products</h5>
                <p class="fs-3 text-success">
                    <?php
                    $stmt = $pdo->query("SELECT COUNT(*) FROM products");
                    echo $stmt->fetchColumn();
                    ?>
                </p>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card p-3">
                <h5 class="card-title">Total Orders</h5>
                <p class="fs-3 text-primary">
                    <?php
                    $stmt = $pdo->query("SELECT COUNT(*) FROM orders");
                    echo $stmt->fetchColumn();
                    ?>
                </p>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card p-3">
                <h5 class="card-title">Registered Customers</h5>
                <p class="fs-3 text-warning">
                    <?php
                    $stmt = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'customer'");
                    echo $stmt->fetchColumn();
                    ?>
                </p>
            </div>
        </div>
    </div>

    <?php
$salesQuery = $pdo->prepare("
    SELECT p.product_name, SUM(oi.quantity) AS total_sold
    FROM order_items oi
    JOIN orders o ON oi.order_id = o.order_id
    JOIN products p ON oi.product_id = p.product_id
    WHERE o.order_status = 'completed'
    GROUP BY p.product_id
    ORDER BY total_sold DESC
    LIMIT 5
");
$salesQuery->execute();

$productNames = [];
$salesCounts = [];

while ($row = $salesQuery->fetch()) {
    $productNames[] = $row['product_name'];
    $salesCounts[] = $row['total_sold'];
}

?>
<div class="card p-3 mt-5">
    <h5 class="card-title">Top 5 Products by Sales</h5>
    <canvas id="salesChart" height="150"></canvas>
</div>

<h4 class="mt-5">Customer Reviews</h4>
<div class="table-responsive mt-3">
    <table class="table table-striped table-bordered bg-white">
        <thead class="table-success">
            <tr>
                <th>Customer Name</th>
                <th>Product</th>
                <th>Rating</th>
                <th>Comment</th>
                <th>Date</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($reviews as $review): ?>
                <tr>
                    <td><?= ucwords(strtolower($review['name'])) ?></td>
                    <td><?= htmlspecialchars($review['product_name']) ?></td>
                    <td>
                        <?php
                        for ($i = 1; $i <= 5; $i++) {
                            echo $i <= $review['rating'] ? '<i class="fas fa-star text-warning"></i>' : '<i class="far fa-star text-muted"></i>';
                        }
                        ?>
                    </td>
                    <td><?= htmlspecialchars($review['comment']) ?></td>
                    <td><?= date("F j, Y", strtotime($review['created_at'])) ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>


</div>

<script>
    function toggleSidebar() {
        const sidebar = document.getElementById('sidebar');
        const mainContent = document.querySelector('.main-content');
        sidebar.classList.toggle('sidebar-hidden');
        mainContent.classList.toggle('full-width');
    }
</script>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    const ctx = document.getElementById('salesChart').getContext('2d');
    const salesChart = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: <?php echo json_encode($productNames); ?>,
            datasets: [{
                label: 'Units Sold',
                data: <?php echo json_encode($salesCounts); ?>,
                backgroundColor: '#388e3c'
            }]
        },
        options: {
            responsive: true,
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        precision: 0
                    }
                }
            }
        }
    });
</script>

</body>
</html>
