<?php
require '../includes/db.php';
require '../includes/auth.php';

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header('Location: ../login.php');
    exit;
}

// Fetch orders by actual database status
function fetch_orders_by_status($pdo, $status) {
    $stmt = $pdo->prepare("SELECT * FROM orders WHERE order_status = ? ORDER BY created_at DESC");
    $stmt->execute([$status]);
    return $stmt->fetchAll();
}

// Fetch order items for each order
function fetch_order_items($pdo, $orderId) {
    $stmt = $pdo->prepare("SELECT oi.*, p.product_name FROM order_items oi JOIN products p ON oi.product_id = p.product_id WHERE oi.order_id = ?");
    $stmt->execute([$orderId]);
    return $stmt->fetchAll();
}

// Handle status updates
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'], $_POST['order_id'])) {
        $orderId = $_POST['order_id'];

        if ($_POST['action'] === 'accept') {
            $pdo->prepare("UPDATE orders SET order_status = 'confirmed' WHERE order_id = ?")->execute([$orderId]);
        } elseif ($_POST['action'] === 'reject') {
            $pdo->prepare("UPDATE orders SET order_status = 'rejected' WHERE order_id = ?")->execute([$orderId]);
        } elseif ($_POST['action'] === 'complete' && isset($_POST['pickup_date'])) {
            $pickupDate = $_POST['pickup_date'];
            $pdo->prepare("UPDATE orders SET order_status = 'completed', pickup_date = ? WHERE order_id = ?")->execute([$pickupDate, $orderId]);
        }
        
        if ($_POST['action'] === 'save_pickup_dates' && isset($_POST['pickup_date_from']) && isset($_POST['pickup_date_to'])) {
            $pickupFrom = $_POST['pickup_date_from'];
            $pickupTo = $_POST['pickup_date_to'];
            $pdo->prepare("UPDATE orders SET pickup_date_from = ?, pickup_date_to = ? WHERE order_id = ?")
                ->execute([$pickupFrom, $pickupTo, $orderId]);
        } elseif ($_POST['action'] === 'complete') {
            $pdo->prepare("UPDATE orders SET order_status = 'Completed' WHERE order_id = ?")
                ->execute([$orderId]);
        }

    }
    header('Location: manage_orders.php');
    exit;
}

// Fetch orders grouped by status
$newOrders = fetch_orders_by_status($pdo, 'pending');
$acceptedOrders = fetch_orders_by_status($pdo, 'confirmed');
$completedOrders = fetch_orders_by_status($pdo, 'completed');
?>

<!DOCTYPE html>
<html>
<head>
    <title>Manage Orders - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
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
    margin-left: 23%;
    max-width: 950px;
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
    <a href="manage_orders.php"  class="active"><i class="fas fa-receipt me-2"></i> Manage Orders</a>
    <a href="manage_gallery.php"><i class="fas fa-images me-2"></i> Manage Gallery</a>
    <a href="account.php"><i class="fas fa-user-cog me-2"></i> My Account</a>
    <a href="../logout.php"><i class="fas fa-sign-out-alt me-2"></i> Logout</a>
</div>
<div class="container mt-5">
    <h2>Manage Orders</h2>

    <!-- New Orders Table -->
    <h4 class="mt-4">New Orders</h4>
    <?php if (count($newOrders) > 0): ?>
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>Order ID</th>
                    <th>Date Ordered</th>
                    <th>Customer</th>
                    <th>Contact</th>
                    <th>Address</th>
                    <th>Note</th>
                    <th>Total</th>
                    <th>Products</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($newOrders as $order): ?>
                    <tr>
                        <td><?= $order['order_id'] ?></td>
                        <td><?= $order['created_at'] ?></td>
                        <td><?= htmlspecialchars($order['name']) ?></td>
                        <td><?= htmlspecialchars($order['contact']) ?></td>
                        <td><?= htmlspecialchars($order['address']) ?></td>
                        <td><?= htmlspecialchars($order['note']) ?></td>
                        <td>₱<?= number_format($order['total_amount'], 2) ?></td>
                        <td>
                            <ul class="mb-0">
                                <?php foreach (fetch_order_items($pdo, $order['order_id']) as $item): ?>
                                    <li><?= $item['product_name'] ?> (<?= $item['quantity'] ?> × ₱<?= number_format($item['price'], 2) ?>)</li>
                                <?php endforeach; ?>
                            </ul>
                        </td>
                        <td>
                            <form method="POST" class="d-inline">
                                <input type="hidden" name="order_id" value="<?= $order['order_id'] ?>">
                                <button type="submit" name="action" value="accept" class="btn btn-success btn-sm" style="margin-bottom: 5px;">Confirm</button>
                                <button type="submit" name="action" value="reject" class="btn btn-danger btn-sm">Reject</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <p>No new orders.</p>
    <?php endif; ?>

    <!-- Accepted Orders -->
    <h4 class="mt-5">Accepted Orders</h4>
    <?php if (count($acceptedOrders) > 0): ?>
    <table class="table table-bordered">
        <thead class="table-dark">
            <tr>
                <th>Order ID</th>
                <th>Customer</th>
                <th>Contact</th>
                <th>Address</th>
                <th>Total Amount</th>
                <th>Pickup Date From</th>
                <th>Pickup Date To</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($acceptedOrders as $order): ?>
            <tr>
                <td>#<?= $order['order_id'] ?></td>
                <td><?= htmlspecialchars($order['name']) ?></td>
                <td><?= htmlspecialchars($order['contact']) ?></td>
                <td><?= htmlspecialchars($order['address']) ?></td>
                <td>₱<?= number_format($order['total_amount'], 2) ?></td>
                <td>
                    <form method="POST" style="display:inline-block;">
                        <input type="hidden" name="order_id" value="<?= $order['order_id'] ?>">
                        <input type="date" name="pickup_date_from" class="form-control" value="<?= $order['pickup_date_from'] ?? '' ?>" required>
                </td>
                <td>
                        <input type="date" name="pickup_date_to" class="form-control" value="<?= $order['pickup_date_to'] ?? '' ?>" required>
                </td>
                <td>
                        <?php if (!empty($order['pickup_date_from']) && !empty($order['pickup_date_to'])): ?>
                            <button type="submit" name="action" value="complete" class="btn btn-primary mt-2">Mark as Completed</button>
                        <?php else: ?>
                            <button type="submit" name="action" value="save_pickup_dates" class="btn btn-secondary mt-2">Save Pickup Dates</button>
                        <?php endif; ?>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>

    <?php else: ?>
        <p>No accepted orders.</p>
    <?php endif; ?>

    <!-- Completed Orders Table -->
    <h4 class="mt-5">Completed Orders</h4>
    <?php if (count($completedOrders) > 0): ?>
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>Order ID</th>
                    <th>Date Ordered</th>
                    <th>Pickup Date</th>
                    <th>Customer</th>
                    <th>Contact</th>
                    <th>Address</th>
                    <th>Total</th>
                    <th>Products</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($completedOrders as $order): ?>
                    <tr>
                        <td><?= $order['order_id'] ?></td>
                        <td><?= $order['created_at'] ?></td>
                        <td><?= date('Y-m-d', strtotime($order['pickup_date_from'])) ?>
                                    to
                                    <?= date('Y-m-d', strtotime($order['pickup_date_to'])) ?></td>
                        <td><?= htmlspecialchars($order['name']) ?></td>
                        <td><?= htmlspecialchars($order['contact']) ?></td>
                        <td><?= htmlspecialchars($order['address']) ?></td>
                        <td>₱<?= number_format($order['total_amount'], 2) ?></td>
                        <td>
                            <ul class="mb-0">
                                <?php foreach (fetch_order_items($pdo, $order['order_id']) as $item): ?>
                                    <li><?= $item['product_name'] ?> (<?= $item['quantity'] ?> × ₱<?= number_format($item['price'], 2) ?>)</li>
                                <?php endforeach; ?>
                            </ul>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <p>No completed orders.</p>
    <?php endif; ?>
</div>
</body>
</html>
