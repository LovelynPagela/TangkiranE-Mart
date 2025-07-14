<?php
session_start();
require 'includes/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];

    $stmt = $pdo->prepare("SELECT * FROM users WHERE BINARY username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user'] = [
            'user_id' => $user['user_id'],
            'username' => $user['username'],
            'role' => $user['role']
        ];

        if ($user['role'] === 'admin') {
            header('Location: admin/dashboard.php');
        } else {
            header('Location: shop.php');
        }
        exit;
    } else {
        echo "<script>alert('Invalid credentials.');window.location='login.php';</script>";
    }
}
if (isset($_SESSION['order_now_product'])) {
    $productId = $_SESSION['order_now_product'];
    unset($_SESSION['order_now_product']);
    header("Location: checkout.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="styles.css">
      <style>
    body {
      font-family: 'Segoe UI', sans-serif;
    }

    .login-container {
      max-width: 450px;
      margin: 50px auto;
      padding: 30px;
      background: white;
      border-radius: 16px;
      box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
    }

    .toggle-password {
      cursor: pointer;
      position: absolute;
      right: 15px;
      top: 50%;
      transform: translateY(-50%);
    }

    .input-group {
      position: relative;
    }
    .login-logo {
  width: 100px;  /* Resize as needed */
  height: auto;
  align-items: center;
}
  </style>
</head>
<body>
    <header>
        <nav class="navbar">
            <div>
              <!--<img src="images/logo2.png" alt="Logo" class="login-logo"> -->
                <a class="navbar-brand" href="index.php">TANGKIRAN e-MART</a>
            </div>
            <div>
                <a href="login.php">Login</a>
                <a href="cart.php">🛒</a>
            </div>
        </nav>
        <section>
            <nav class="div2">
                <ul id="nav-links2">
                    <li><a href="index.php">Home</a></li>
                    <li><a href="gallery.php">Gallery</a></li>
                    <li><a href="#bamboo_products_catalogs">Product Catalog</a></li>
                    <li><a href="shop.php">Shop</a></li>
                    
                </ul>
            </nav>
        </section>
    </header>
<div class="container">
  
    <div class="login-container">
      <!--<img src="images/logo2.png" alt="Logo" class="login-logo" style="margin-left: 80px;">-->
      <h3 class="text-center mb-4">Login</h3>
      <form action="login.php" method="POST">
        <div class="mb-3">
          <label for="username" class="form-label">Username</label>
          <input type="text" class="form-control" id="username" name="username" required />
        </div>

        <div class="mb-3 input-group">
          <label for="password" class="form-label w-100">Password</label>
          <input type="password" class="form-control" id="password" name="password" required />
        </div>

        <button type="submit" class="btn btn-success w-100">Login</button>
      </form>
      <div class="text-center mt-3">
        No account yet? <a href="register.php">Register here</a>
      </div>
    </div>
  </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
