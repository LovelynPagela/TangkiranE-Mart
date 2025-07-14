<?php
require 'includes/db.php';
require 'includes/auth.php';

if (!is_logged_in()) {
    header('Location: login.php');
    exit;
}

$userId = $_SESSION['user']['user_id'];

// Handle both regular cart checkout and 'Order Now'
$selectedItems = $_POST['selected_items'] ?? [];

if (empty($selectedItems) && isset($_SESSION['order_now_product'])) {
    $productId = $_SESSION['order_now_product'];
    unset($_SESSION['order_now_product']);

     $stmt = $pdo->prepare("SELECT product_id, product_name, price, product_image FROM products WHERE product_id = ?");
    $stmt->execute([$productId]);
    $product = $stmt->fetch();

    if ($product) {
        $product['quantity'] = 1;
        $product['cart_id'] = null; // no cart ID involved
        $cart_items[] = $product;
        $total = $product['price']; // quantity is always 1
    }

    // Check if the product is already in the user's cart
    $stmt = $pdo->prepare("SELECT cart_id FROM carts WHERE user_id = ? AND product_id = ?");
    $stmt->execute([$userId, $productId]);
    $existingCart = $stmt->fetch();

    if ($existingCart) {
        $selectedItems[] = $existingCart['cart_id'];
    } else {
        // Add product to cart with quantity 1
        $stmt = $pdo->prepare("INSERT INTO carts (user_id, product_id, quantity) VALUES (?, ?, 1)");
        $stmt->execute([$userId, $productId]);
        $selectedItems[] = $pdo->lastInsertId();
    }
}

if (empty($selectedItems)) {
    echo "<div class='alert alert-warning'>No items selected for checkout.</div>";
    exit;
}

// Fetch product details of selected cart items
$placeholders = implode(',', array_fill(0, count($selectedItems), '?'));
$params = array_merge([$userId], $selectedItems);

$stmt = $pdo->prepare("
    SELECT c.cart_id, c.product_id, c.quantity, p.product_name, p.price, p.product_image
    FROM carts c
    JOIN products p ON c.product_id = p.product_id
    WHERE c.user_id = ? AND c.cart_id IN ($placeholders)
");
$stmt->execute($params);
$cart_items = $stmt->fetchAll();

$total = 0;
foreach ($cart_items as $item) {
    $total += $item['price'] * $item['quantity'];
}

// Order submission logic
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_order'])) {
    $delivery_method = $_POST['delivery_method'] ?? 'Pickup';
    $name = ucwords(strtolower(trim($_POST['name'])));
    $contact = $_POST['contact'];
    $address = $_POST['address'];
    $note = $_POST['note'] ?? '';

    // Insert order
    $stmt = $pdo->prepare("INSERT INTO orders (user_id, name, contact, address, delivery_method, note, total_amount) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([$userId, $name, $contact, $address, $delivery_method, $note, $total]);
    $orderId = $pdo->lastInsertId();

    // Insert order items
    $stmt = $pdo->prepare("INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)");
    foreach ($cart_items as $item) {
        $stmt->execute([
            $orderId,
            $item['product_id'],
            $item['quantity'],
            $item['price']
        ]);
    }
    // Example: $order_id already inserted, and $cart_items is the list of ordered products with quantity

foreach ($cart_items as $item) {
    $productId = $item['product_id'];
    $orderedQty = $item['quantity'];

    // Deduct the quantity from the product's stock
    $updateStockStmt = $pdo->prepare("UPDATE products SET stock = stock - ? WHERE product_id = ? AND stock >= ?");
    $updateStockStmt->execute([$orderedQty, $productId, $orderedQty]);
}


    // Remove from cart
    $cartIds = array_column($cart_items, 'cart_id');
    $placeholders = implode(',', array_fill(0, count($cartIds), '?'));
    $stmt = $pdo->prepare("DELETE FROM carts WHERE cart_id IN ($placeholders)");
    $stmt->execute($cartIds);

    header("Location: my_orders.php");
    exit;
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Checkout - BambooLink</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <style>
    body {
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; 
        background-color: #91BF83; 
        color: #333;
    }

    .container {
        max-width: 700px;
        background: #fff;
        padding: 30px;
        border-radius: 12px;
        box-shadow: 0 0 20px rgba(0, 0, 0, 0.05);
    }

    .form-label {
        font-weight: 500;
    }

    h4 {
        font-weight: 600;
        color: #333;
    }

    h5 {
        font-weight: 600;
        color: #2c3e50;
        margin-top: 25px;
    }

    .btn-success {
        width: 100%;
        padding: 10px;
        font-weight: bold;
        font-size: 16px;
    }
    .alert {
        font-size: 14px;
    }

    ul.mb-0 {
        padding-left: 18px;
    }

    @media (max-width: 576px) {
        .container {
            padding: 20px;
        }

        h4 {
            font-size: 18px;
        }

        .btn-success {
            font-size: 14px;
        }
    }
</style>

</head>
<body>
<div class="container mt-5">
        <a href="cart.php" class="btn btn-secondary mb-3" style="background-color: #6A9274;" >
            <i class="bi bi-arrow-left"></i> Back to Cart
        </a>
    <h2>Checkout</h2>

    <form action="checkout.php" method="POST">
        <div class="mb-3">
            <label for="name" class="form-label">Full Name</label>
            <input type="text" name="name" id="name" class="form-control" required>
        </div>

        <div class="mb-3">
            <label for="contact" class="form-label">Contact Number</label>
            <input type="text" name="contact" id="contact" class="form-control" required>
        </div>

        <div class="mb-3">
            <label for="address" class="form-label">Address</label>
            <textarea name="address" id="address" class="form-control" required></textarea>
        </div>

        <div class="mb-3">
            <label for="note" class="form-label">Note to Order (optional)</label>
            <textarea name="note" id="note" class="form-control"></textarea>
        </div>

        <div class="mb-3">
            <label class="form-label">Delivery Method</label>
            <input type="text" class="form-control" value="Pickup" readonly>
            <input type="hidden" name="delivery_method" value="Pickup">
        </div>

        <h4>Order Summary</h4>
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>Product</th>
                    <th>Image</th>
                    <th>Quantity</th>
                    <th>Price</th>
                    <th>Subtotal</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($cart_items as $item): 
                    $subtotal = $item['price'] * $item['quantity'];
                ?>
                <tr>
                    <td><?= htmlspecialchars($item['product_name']) ?></td>
                    <td><img src="images/<?= htmlspecialchars($item['product_image']) ?>" width="50" alt=""></td>
                    <td><?= $item['quantity'] ?></td>
                    <td>₱<?= number_format($item['price'], 2) ?></td>
                    <td>₱<?= number_format($subtotal, 2) ?></td>
                </tr>
                <!-- Hidden inputs to pass selected cart IDs again if needed -->
                <input type="hidden" name="selected_items[]" value="<?= $item['cart_id'] ?>">
                <?php endforeach; ?>
            </tbody>
        </table>

        <div class="text-end">
            <h5>Total Amount: ₱<?= number_format($total, 2) ?></h5>
        </div>

        <input type="hidden" name="total_amount" value="<?= $total ?>">

        <button type="submit" name="submit_order" class="btn" style="background-color: #43a047; color: white">Submit Order</button>
    </form>
</div>
</body>
</html>
