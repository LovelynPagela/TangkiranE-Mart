<?php
require '../includes/db.php';
require '../includes/auth.php';

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header('Location: ../login.php');
    exit;
}

$userId = $_SESSION['user']['user_id'];
$errors = [];
$success = false;

// Fetch current user info
$stmt = $pdo->prepare("SELECT username, password, firstname, lastname FROM users WHERE user_id = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch();

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $firstname = trim($_POST['firstname']);
    $lastname = trim($_POST['lastname']);
    $username = trim($_POST['username']);
    $current_password = trim($_POST['current_password']);
    $new_password = trim($_POST['new_password']);
    $confirm_password = trim($_POST['confirm_password']);

    // Check if fields actually changed
    $fields_changed = (
        $firstname !== $user['firstname'] ||
        $lastname !== $user['lastname'] ||
        $username !== $user['username'] ||
        !empty($new_password)
    );

    if ($fields_changed) {
        // Validate current password
        if (!password_verify($current_password, $user['password'])) {
            $errors[] = "Current password is incorrect.";
        }

        // Validate new password if provided
        if (!empty($new_password)) {
            if (
                strlen($new_password) < 8 ||
                !preg_match('/[A-Z]/', $new_password) ||
                !preg_match('/[0-9]/', $new_password)
            ) {
                $errors[] = "New password must be at least 8 characters long, contain an uppercase letter and a number.";
            }
            if ($new_password !== $confirm_password) {
                $errors[] = "New password and confirmation do not match.";
            }
        }

        if (empty($errors)) {
            $updatedPassword = !empty($new_password)
                ? password_hash($new_password, PASSWORD_DEFAULT)
                : $user['password'];

            // Update user in database
            $stmt = $pdo->prepare("UPDATE users SET firstname = ?, lastname = ?, username = ?, password = ? WHERE user_id = ?");
            $stmt->execute([$firstname, $lastname, $username, $updatedPassword, $userId]);

            // Update session data
            $_SESSION['user']['firstname'] = $firstname;
            $_SESSION['user']['lastname'] = $lastname;
            $_SESSION['user']['username'] = $username;

            // Update local user object
            $user['firstname'] = $firstname;
            $user['lastname'] = $lastname;
            $user['username'] = $username;
            $user['password'] = $updatedPassword;

            $success = true;
        }
    } else {
        $errors[] = "No changes detected.";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>My Account - BambooLink</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
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
    margin-left: 30%;
    max-width: 700px;
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
    <a href="manage_orders.php"><i class="fas fa-receipt me-2"></i> Manage Orders</a>
    <a href="manage_gallery.php"><i class="fas fa-images me-2"></i> Manage Gallery</a>
    <a href="account.php"><i class="fas fa-user-cog me-2" class="active"></i> My Account</a>
    <a href="../logout.php"><i class="fas fa-sign-out-alt me-2"></i> Logout</a>
</div>
<div class="container mt-5">
    <div class="text-center mb-4">
        <img src="../images/user.png" width="100" class="rounded-circle mb-2" alt="User Icon">
        <h4><?= htmlspecialchars($user['firstname'] . ' ' . $user['lastname']) ?></h4>
    </div>

    <form method="POST" action="account.php">
        <div class="row">
            <div class="col-md-6 mb-3">
                <label for="firstname" class="form-label">First Name</label>
                <input type="text" name="firstname" id="firstname" class="form-control" value="<?= htmlspecialchars($user['firstname']) ?>">
            </div>
            <div class="col-md-6 mb-3">
                <label for="lastname" class="form-label">Last Name</label>
                <input type="text" name="lastname" id="lastname" class="form-control" value="<?= htmlspecialchars($user['lastname']) ?>">
            </div>
        </div>

        <div class="mb-3">
            <label for="username" class="form-label">Username</label>
            <input type="text" name="username" id="username" class="form-control" value="<?= htmlspecialchars($user['username']) ?>">
        </div>

        <hr>
        <h5>Change Password</h5>

        <div class="mb-3">
            <label for="current_password" class="form-label">Current Password <span class="text-danger">*</span></label>
            <input type="password" name="current_password" id="current_password" class="form-control">
        </div>

        <div class="mb-3">
            <label for="new_password" class="form-label">New Password <small class="text-muted">(Min 8 chars, 1 capital, 1 number)</small></label>
            <input type="password" name="new_password" id="new_password" class="form-control">
        </div>

        <div class="mb-3">
            <label for="confirm_password" class="form-label">Confirm New Password</label>
            <input type="password" name="confirm_password" id="confirm_password" class="form-control">
        </div>

            <?php if ($success): ?>
        <div class="alert alert-success">Your account has been updated successfully.</div>
    <?php endif; ?>

    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger">
            <ul class="mb-0">
                <?php foreach ($errors as $error): ?>
                    <li><?= htmlspecialchars($error) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>
        <button type="submit" class="btn btn-success">Save Changes</button>
    </form>
</div>
</body>
</html>
