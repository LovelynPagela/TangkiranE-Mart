<?php
require 'includes/db.php';
require 'includes/auth.php';

if (!is_logged_in()) {
    header('Location: login.php');
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
    $firstname = ucwords(strtolower(trim($_POST['firstname'])));
    $lastname = ucwords(strtolower(trim($_POST['lastname'])));
    $username = trim($_POST['username']);
    $current_password = trim($_POST['current_password']);
    $new_password = trim($_POST['new_password']);
    $confirm_password = trim($_POST['confirm_password']);

    // Default to current password hash (unchanged)
    $updatedPassword = $user['password'];

    // Only validate password if current_password field is filled
    if (!empty($current_password)) {
        if (!password_verify($current_password, $user['password'])) {
            $errors[] = "Current password is incorrect.";
        } else {
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

                if (empty($errors)) {
                    $updatedPassword = password_hash($new_password, PASSWORD_DEFAULT);
                }
            } else {
                $errors[] = "Please enter a new password if you want to change it.";
            }
        }
    }

    // Update names/username even if password doesn't change
    if (empty($errors)) {
        $stmt = $pdo->prepare("UPDATE users SET firstname = ?, lastname = ?, username = ?, password = ? WHERE user_id = ?");
        $stmt->execute([$firstname, $lastname, $username, $updatedPassword, $userId]);

        // Update session data
        $_SESSION['user']['firstname'] = $firstname;
        $_SESSION['user']['lastname'] = $lastname;
        $_SESSION['user']['username'] = $username;

        $user['firstname'] = $firstname;
        $user['lastname'] = $lastname;
        $user['username'] = $username;
        $user['password'] = $updatedPassword;

        $success = true;
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>My Account - BambooLink</title>
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
        <a href="index.php" class="btn btn-secondary mb-3" style="background-color: #6A9274;" >
        <i class="bi bi-arrow-left"></i> Back to Home
    </a>

    <div class="text-center mb-4">
        <img src="images/user.png" width="100" class="rounded-circle mb-2" alt="User Icon">
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
