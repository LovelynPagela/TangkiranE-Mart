<?php
require 'includes/db.php';
require 'includes/auth.php';

if (!is_logged_in()) {
    header('Location: login.php');
    exit;
}

$userId = $_SESSION['user']['user_id'];

// Check if a review exists for the given order_id
function has_review($pdo, $order_id) {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM reviews WHERE order_id = ?");
    $stmt->execute([$order_id]);
    return $stmt->fetchColumn() > 0;
}

// Fetch all orders by status
function fetch_orders($pdo, $userId, $status) {
    $stmt = $pdo->prepare("
        SELECT * FROM orders 
        WHERE user_id = ? AND order_status = ? 
        ORDER BY created_at DESC
    ");
    $stmt->execute([$userId, $status]);
    return $stmt->fetchAll();
}

// Fetch order items for a specific order
function fetch_order_items($pdo, $order_id) {
    $stmt = $pdo->prepare("
        SELECT oi.*, p.product_name, p.product_image
        FROM order_items oi
        JOIN products p ON oi.product_id = p.product_id
        WHERE oi.order_id = ?
    ");
    $stmt->execute([$order_id]);
    return $stmt->fetchAll();
}

// Define the order status groups
$orderGroups = [
    'New' => 'pending',
    'Accepted' => 'confirmed',
    'Completed' => 'completed'
];
?>

<!DOCTYPE html>
<html>
<head>
    <title>My Orders - BambooLink</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body style="font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background-color: #91BF83; color: #333;">
<div class="container mt-5">
        <a href="shop.php" class="btn btn-secondary mb-3" style="background-color: #6A9274;" >
        <i class="bi bi-arrow-left"></i> Continue Shopping
    </a>

    <h2>My Orders</h2>

    <?php foreach ($orderGroups as $label => $statusValue): 
    $orders = fetch_orders($pdo, $userId, $statusValue);
?>
    <div class="mt-4">
        <h4><?= $label ?> Orders</h4>
        <?php if (empty($orders)): ?>
            <div class="alert alert-info">No <?= strtolower($label) ?> orders found.</div>
        <?php else: ?>
            <?php foreach ($orders as $order): 
                $items = fetch_order_items($pdo, $order['order_id']);
            ?>
                <div class="card mb-4">
                    <div class="card-header">
                        Order ID: <?= $order['order_id'] ?> |
                        Status: <strong><?= ucfirst($order['order_status']) ?></strong> |
                        Date: <?= $order['created_at'] ?>
                    </div>
                    <div class="card-body">
                        <p><strong>Delivery Method:</strong> <?= htmlspecialchars($order['delivery_method']) ?></p>
                        <p><strong>Note:</strong> <?= htmlspecialchars($order['note']) ?: 'None' ?></p>
                        <p><strong>Total Amount:</strong> ₱<?= number_format($order['total_amount'], 2) ?></p>

                        <?php if ($label === 'Accepted'): ?>
                            <?php if (!empty($order['pickup_date_from']) && !empty($order['pickup_date_to'])): ?>
                                <p>
                                    <strong>Pickup Date:</strong>
                                    <?= date('Y-m-d', strtotime($order['pickup_date_from'])) ?>
                                    to
                                    <?= date('Y-m-d', strtotime($order['pickup_date_to'])) ?>
                                    |
                                    <a href="order_details.php?order_id=<?= $order['order_id'] ?>" target="_blank" class="btn btn-sm btn-primary">View Details</a>
                                </p>
                            <?php else: ?>
                                <p><strong>Pickup Date:</strong> Not yet set</p>
                            <?php endif; ?>
                        <?php endif; ?>

                        <h6>Items:</h6>
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>Product</th>
                                    <th>Image</th>
                                    <th>Quantity</th>
                                    <th>Price</th>
                                    <th>Subtotal</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($items as $item): 
                                    $subtotal = $item['quantity'] * $item['price'];
                                ?>
                                    <tr>
                                        <td><?= htmlspecialchars($item['product_name']) ?></td>
                                        <td><img src="images/<?= htmlspecialchars($item['product_image']) ?>" width="50"></td>
                                        <td><?= $item['quantity'] ?></td>
                                        <td>₱<?= number_format($item['price'], 2) ?></td>
                                        <td>₱<?= number_format($subtotal, 2) ?></td>
                                        <td><?= ucfirst($order['order_status']) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                        <?php if ($label === 'Completed'): ?> 
                            <?php if (has_review($pdo, $order['order_id'])): ?>
                                <p class="mt-3 text-success"><strong>Already Reviewed</strong></p>
                            <?php else: ?>
                                <form action="review.php" method="GET" class="mt-3">
                                    <input type="hidden" name="order_id" value="<?= $order['order_id'] ?>">
                                    <button type="submit" class="btn btn-success">Order Received & Review</button>
                                </form>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
<?php endforeach; ?>

</div>
</body>
</html>
