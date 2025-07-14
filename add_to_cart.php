<?php
session_start();
require 'includes/db.php';

// Check if the user is logged in
if (!isset($_SESSION['user']) || !isset($_SESSION['user']['user_id'])) {
    // If not logged in, redirect to login page
    header('Location: login.php');
    exit;
}

// Ensure it's a POST request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get user ID from session
    $userId = $_SESSION['user']['user_id'];

    // Get product ID and quantity from POST
    $productId = isset($_POST['product_id']) ? (int) $_POST['product_id'] : 0;
    $quantity = isset($_POST['quantity']) ? max(1, (int) $_POST['quantity']) : 1;

    if ($productId > 0) {
        // Check if product already exists in cart
        $stmt = $pdo->prepare("SELECT * FROM carts WHERE user_id = ? AND product_id = ?");
        $stmt->execute([$userId, $productId]);
        $existing = $stmt->fetch();

        if ($existing) {
            // Update the existing cart quantity
            $stmt = $pdo->prepare("UPDATE carts SET quantity = quantity + ? WHERE user_id = ? AND product_id = ?");
            $stmt->execute([$quantity, $userId, $productId]);
        } else {
            // Insert new product to cart
            $stmt = $pdo->prepare("INSERT INTO carts (user_id, product_id, quantity) VALUES (?, ?, ?)");
            $stmt->execute([$userId, $productId, $quantity]);
        }

        // Redirect to cart or product page
        header('Location: cart.php');
        exit;
    } else {
        echo "Invalid product ID.";
    }
} else {
    echo "Invalid request method.";
}
?>
