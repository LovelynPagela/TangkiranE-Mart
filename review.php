<?php 
require 'includes/db.php';
require 'includes/auth.php';

if (!is_logged_in()) {
    header('Location: login.php');
    exit;
}

$orderId = $_GET['order_id'] ?? null;
$userId = $_SESSION['user']['user_id'];

function has_review($pdo, $order_id) {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM reviews WHERE order_id = ?");
    $stmt->execute([$order_id]);
    return $stmt->fetchColumn() > 0;
}

if (has_review($pdo, $orderId)) {
    echo "<div class='alert alert-success'>You have already reviewed this order.</div>";
    echo "<a href='my_orders.php' class='btn btn-primary mt-3'>Back to My Orders</a>";
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $rating = $_POST['rating'];
    $comment = trim($_POST['comment']);

    $stmt = $pdo->prepare("INSERT INTO reviews (user_id, order_id, rating, comment, created_at) VALUES (?, ?, ?, ?, NOW())");
    $stmt->execute([$userId, $orderId, $rating, $comment]);

    header("Location: my_orders.php");
    exit;
}

$stmt = $pdo->prepare("
    SELECT oi.product_id, p.product_name 
    FROM order_items oi
    JOIN products p ON oi.product_id = p.product_id
    WHERE oi.order_id = ?
");
$stmt->execute([$orderId]);
$products = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Review Order</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; 
            background-color: #91BF83; 
            color: #333;
        }
        .review-box {
            background-color: white;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            width: 100%;
            max-width: 500px;
        }

        .star-rating {
            display: flex;
            flex-direction: row-reverse;
            justify-content: center;
        }

        .star-rating input[type="radio"] {
            display: none;
        }

        .star-rating label {
            font-size: 2rem;
            color: #ccc;
            cursor: pointer;
        }

        .star-rating label:hover,
        .star-rating label:hover ~ label,
        .star-rating input[type="radio"]:checked ~ label {
            color: #f5b301;
        }
    </style>
</head>
<body>    

<div class="review-box"> 
    <h2 class="text-center mb-4">Leave a Review</h2>
    <form method="POST">
        <div class="mb-3 text-center">
            <label class="form-label d-block">Rating:</label>
            <div class="star-rating">
                <?php for ($i = 5; $i >= 1; $i--): ?>
                    <input type="radio" name="rating" id="star<?= $i ?>" value="<?= $i ?>" required>
                    <label for="star<?= $i ?>">★</label>
                <?php endfor; ?>
            </div>
        </div>
        <div class="mb-3">
            <label for="comment" class="form-label">Comment:</label>
            <textarea name="comment" id="comment" class="form-control" rows="4" placeholder="Write your experience..." required></textarea>
        </div>
        <div class="text-center">
            <button type="submit" class="btn btn-primary">Submit Review</button>
            <a href="my_orders.php" class="btn btn-secondary" style="background-color: #6A9274;">Cancel</a>
        </div>
    </form>
</div>

</body>
</html>
