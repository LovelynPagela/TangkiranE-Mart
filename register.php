<?php
session_start();
require 'includes/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    // 1. Check if passwords match
    if ($password !== $confirm_password) {
        echo "<script>alert('Passwords do not match.');window.location='register.php';</script>";
        exit;
    }

    // 2. Check password strength
    $uppercase = preg_match('@[A-Z]@', $password);
    $lowercase = preg_match('@[a-z]@', $password);
    $number    = preg_match('@[0-9]@', $password);
    $specialChars = preg_match('@[^\w]@', $password);

    if (!$uppercase || !$lowercase || !$number || !$specialChars || strlen($password) < 8) {
        echo "<script>alert('Password must be at least 8 characters long and include uppercase, lowercase, number, and special character.');window.location='register.php';</script>";
        exit;
    }

    // 3. Check if username already exists (case-sensitive)
    $stmt = $pdo->prepare("SELECT * FROM users WHERE BINARY username = ?");
    $stmt->execute([$username]);
    if ($stmt->rowCount() > 0) {
        echo "<script>alert('Username already exists. Please choose a different one.');window.location='register.php';</script>";
        exit;
    }

    // 4. Hash password and insert
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("INSERT INTO users (username, password, role) VALUES (?, ?, 'customer')");
    $stmt->execute([$username, $hashed_password]);

    // 5. Set session and redirect
    $_SESSION['user'] = [
        'username' => $username,
        'role' => 'customer'
    ];
    header('Location: index.php');
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet"/>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="styles.css">
    <style>
    body {
      font-family: 'Segoe UI', sans-serif;
    }

    .register-container {
      max-width: 450px;
      margin: 50px auto;
      padding: 30px;
      background: white;
      border-radius: 16px;
      box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
    }

    .password-rules p {
      font-size: 14px;
      margin-bottom: 4px;
    }

    .password-rules p.valid {
      color: green;
    }

    .password-rules p.invalid {
      color: red;
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
                    <li><a href="index.php#bamboo_products_catalogs">Product Catalog</a></li>
                    <li><a href="shop.php">Shop</a></li>
                    
                </ul>
            </nav>
        </section>
    </header>
<div class="container">
  <div class="register-container">
  <!--<img src="images/logo2.png" alt="Logo" class="login-logo" style="margin-left: 80px;">-->
    <h3 class="text-center mb-4">Create Account</h3>
    <form action="register.php" method="POST" id="registerForm">
      <div class="mb-3">
        <label for="username" class="form-label">Username</label>
        <input type="text" name="username" class="form-control" id="username" required>
      </div>

      <div class="mb-3 input-group">
        <label for="password" class="form-label w-100">Password</label>
        <input type="password" name="password" class="form-control" id="password" required>
      </div>

      <div class="password-rules mb-3">
        <p id="length" class="invalid">✔ At least 8 characters</p>
        <p id="uppercase" class="invalid">✔ At least 1 uppercase letter</p>
        <p id="lowercase" class="invalid">✔ At least 1 lowercase letter</p>
        <p id="number" class="invalid">✔ At least 1 number</p>
        <p id="special" class="invalid">✔ At least 1 special character</p>
      </div>

      <div class="mb-3">
        <label for="confirm_password" class="form-label">Confirm Password</label>
        <input type="password" name="confirm_password" class="form-control" id="confirm_password" required>
      </div>

      <button type="submit" class="btn btn-success w-100">Register</button>
    </form>
    <div class="text-center mt-3">
      Already have an account? <a href="login.php">Login</a>
    </div>
  </div>
</div>
<script>
  const password = document.getElementById("password");
  const confirmPassword = document.getElementById("confirm_password");
  const togglePassword = document.getElementById("togglePassword");
  const form = document.getElementById("registerForm");

  const length = document.getElementById("length");
  const uppercase = document.getElementById("uppercase");
  const lowercase = document.getElementById("lowercase");
  const number = document.getElementById("number");
  const special = document.getElementById("special");

  password.addEventListener("input", function () {
    const val = password.value;

    length.className = val.length >= 8 ? "valid" : "invalid";
    uppercase.className = /[A-Z]/.test(val) ? "valid" : "invalid";
    lowercase.className = /[a-z]/.test(val) ? "valid" : "invalid";
    number.className = /[0-9]/.test(val) ? "valid" : "invalid";
    special.className = /[^A-Za-z0-9]/.test(val) ? "valid" : "invalid";
  });

  togglePassword.addEventListener("click", () => {
    const type = password.getAttribute("type") === "password" ? "text" : "password";
    password.setAttribute("type", type);
    togglePassword.classList.toggle("bi-eye");
    togglePassword.classList.toggle("bi-eye-slash");
  });

  form.addEventListener("submit", function (e) {
    if (
      length.className !== "valid" ||
      uppercase.className !== "valid" ||
      lowercase.className !== "valid" ||
      number.className !== "valid" ||
      special.className !== "valid"
    ) {
      alert("Password does not meet all requirements.");
      e.preventDefault();
    } else if (password.value !== confirmPassword.value) {
      alert("Passwords do not match.");
      e.preventDefault();
    }
  });
</script>

</body>
</html>

