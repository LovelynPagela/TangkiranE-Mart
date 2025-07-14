<?php
require 'includes/db.php';
require 'includes/auth.php';

if (!is_logged_in()) {
    header('Location: login.php');
    exit;
}

$userId = $_SESSION['user']['user_id'];

// Handle quantity updates
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_qty'])) {
        $cartId = $_POST['cart_id'];
        $action = $_POST['action'];
        if ($action === 'increase') {
    // Get current quantity in cart and product stock
    $stmt = $pdo->prepare("
        SELECT c.quantity, p.stock 
        FROM carts c
        JOIN products p ON c.product_id = p.product_id
        WHERE c.cart_id = ? AND c.user_id = ?
    ");
    $stmt->execute([$cartId, $userId]);
    $row = $stmt->fetch();

    if ($row && $row['quantity'] < $row['stock']) {
        // Only increase if current quantity is less than stock
        $stmt = $pdo->prepare("UPDATE carts SET quantity = quantity + 1 WHERE cart_id = ? AND user_id = ?");
        $stmt->execute([$cartId, $userId]);
    }
} elseif ($action === 'decrease') {
    $stmt = $pdo->prepare("UPDATE carts SET quantity = quantity - 1 WHERE cart_id = ? AND user_id = ? AND quantity > 1");
    $stmt->execute([$cartId, $userId]);
}

        header('Location: cart.php');
        exit;
    }
}

// Handle remove
if (isset($_GET['remove'])) {
    $removeId = $_GET['remove'];
    $stmt = $pdo->prepare("DELETE FROM carts WHERE user_id = ? AND product_id = ?");
    $stmt->execute([$userId, $removeId]);

    header('Location: cart.php');
    exit;
}

// Fetch cart items
$total = 0;
$stmt = $pdo->prepare("
    SELECT c.cart_id, c.quantity, c.product_id, p.product_name, p.price, p.product_image
    FROM carts c
    JOIN products p ON c.product_id = p.product_id
    WHERE c.user_id = ?
");
$stmt->execute([$userId]);
$cart_items = $stmt->fetchAll();

?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0"> 
    <title>Your Cart - BambooLink</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body style="font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background-color: #91BF83; color: #333;">
<div class="container mt-5">
    <a href="shop.php" class="btn btn-secondary mb-3" style="background-color: #6A9274;" >
        <i class="bi bi-arrow-left"></i> Continue Shopping
    </a>

    <h2 class="mb-4">Your Shopping Cart</h2>

<?php if (!empty($cart_items)): ?>
<form action="checkout.php" method="POST" id="cartForm">
    <table class="table table-bordered align-middle">
        <thead>
            <tr>
                <th>Select</th>
                <th>Image</th>
                <th>Product</th>
                <th>Quantity</th>
                <th>Price</th>
                <th>Subtotal</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($cart_items as $item): 
                $subtotal = $item['price'] * $item['quantity'];
            ?>
            <tr>
                <td>
                    <input type="checkbox" name="selected_items[]" value="<?= $item['cart_id'] ?>" class="item-checkbox" data-subtotal="<?= $subtotal ?>">
                </td>
                <td>
                    <img src="images/<?= htmlspecialchars($item['product_image']) ?>" alt="<?= htmlspecialchars($item['product_name']) ?>" style="width: 60px; height: auto;">
                </td>
                <td><?= htmlspecialchars($item['product_name']) ?></td>
                <td>
                    <div class="d-flex align-items-center">
                        <form action="cart.php" method="POST" class="me-1">
                            <input type="hidden" name="cart_id" value="<?= $item['cart_id'] ?>">
                            <input type="hidden" name="action" value="decrease">
                            <button type="submit" name="update_qty" class="btn btn-sm btn-outline-secondary">−</button>
                        </form>
                        <span class="mx-2"><?= $item['quantity'] ?></span>
                        <form action="cart.php" method="POST" class="ms-1">
                            <input type="hidden" name="cart_id" value="<?= $item['cart_id'] ?>">
                            <input type="hidden" name="action" value="increase">
                            <button type="submit" name="update_qty" class="btn btn-sm btn-outline-secondary">+</button>
                        </form>
                    </div>
                </td>
                <td>₱<?= number_format($item['price'], 2) ?></td>
                <td>₱<?= number_format($subtotal, 2) ?></td>
                <td><a href="cart.php?remove=<?= $item['product_id'] ?>" class="btn btn-danger btn-sm">Remove</a></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <div class="text-end">
        <h4>Total: ₱<span id="totalAmount">0.00</span></h4>
        <!-- FIX: Button to submit the form -->
        <button type="submit" class="btn" style="background-color: #43a047; color: white" href="checkout.php">Proceed to Checkout</button>
    </div>
</form>
    <?php else: ?>
        <p>Your cart is empty.</p>
    <?php endif; ?>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const checkboxes = document.querySelectorAll('.item-checkbox');
    const totalAmountSpan = document.getElementById('totalAmount');

    function updateTotal() {
        let total = 0;
        checkboxes.forEach(checkbox => {
            if (checkbox.checked) {
                total += parseFloat(checkbox.getAttribute('data-subtotal'));
            }
        });
        totalAmountSpan.textContent = total.toFixed(2);
    }

    checkboxes.forEach(cb => {
        cb.addEventListener('change', updateTotal);
    });

    updateTotal(); // Initial call in case some checkboxes are already checked
});
document.getElementById('cartForm').addEventListener('submit', function(e) {
        const anyChecked = Array.from(document.querySelectorAll('.item-checkbox')).some(checkbox => checkbox.checked);

        if (!anyChecked) {
            e.preventDefault();
            alert('Please select at least one item before proceeding to checkout.');
        }
    });
</script>

</body>
</html>
