<?php
session_start();
require 'includes/db.php';

// Get product ID from URL or form (you can adjust to use POST if needed)
$productId = $_GET['product_id'] ?? null;

if (!$productId) {
    // Invalid access
    header('Location: shop.php');
    exit;
}

// If not logged in, save intended product and redirect to login
if (!isset($_SESSION['user']) || !isset($_SESSION['user']['user_id'])) {
    $_SESSION['order_now_product'] = $productId;
    header('Location: login.php');
    exit;
}

// If logged in, pass product ID to checkout via session
$_SESSION['order_now_product'] = $productId;
header('Location: checkout.php');
exit;
?>
