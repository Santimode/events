<?php
require_once '../includes/db.php';

$message = '';
$messageType = '';
$tokenValid = false;
$userId = null;

// 1. Verify the token from the URL
if (isset($_GET['token']) && !empty(trim($_GET['token']))) {
    $token = trim($_GET['token']);

    // Check if token exists AND has not expired
    $stmt = $pdo->prepare("SELECT id FROM users WHERE reset_token = ? AND reset_expires_at > NOW()");
    $stmt->execute([$token]);
    $user = $stmt->fetch();

    if ($user) {
        $tokenValid = true;
        $userId = $user['id'];
    } else {
        $message = "This password reset link is invalid or has expired. Please request a new one.";
        $messageType = "danger";
    }
} else {
    $message = "No reset token provided.";
    $messageType = "warning";
}

// 2. Handle the new password submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['user_id'])) {
    $password = $_POST['password'];
    $confirmPassword = $_POST['confirm_password'];
    $userIdToUpdate = (int)$_POST['user_id'];
    
    // Safety check: re-verify token via a hidden input to ensure they didn't just POST to this file
    $submittedToken = $_POST['token'];
    $checkStmt = $pdo->prepare("SELECT id FROM users WHERE id = ? AND reset_token = ? AND reset_expires_at > NOW()");
    $checkStmt->execute([$userIdToUpdate, $submittedToken]);
    
    if (!$checkStmt->fetch()) {
        $message = "Session expired or invalid token. Please request a new link.";
        $messageType = "danger";
        $tokenValid = false;
    } elseif (empty($password) || empty($confirmPassword)) {
        $message = "Please fill in all fields.";
        $messageType = "danger";
        $tokenValid = true; // Keep form visible
    } elseif ($password !== $confirmPassword) {
        $message = "Passwords do not match.";
        $messageType = "danger";
        $tokenValid = true; // Keep form visible
    } else {
        // Success! Hash the new password and clear the reset token
        $passwordHash = password_hash($password, PASSWORD_DEFAULT);
        $updateStmt = $pdo->prepare("UPDATE users SET password_hash = ?, reset_token = NULL, reset_expires_at = NULL WHERE id = ?");
        
        if ($updateStmt->execute([$passwordHash, $userIdToUpdate])) {
            $message = "Your password has been successfully reset. You can now log in.";
            $messageType = "success";
            $tokenValid = false; // Hide the form
        } else {
            $message = "Failed to reset password. Please try again.";
            $messageType = "danger";
            $tokenValid = true;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Set New Password - Event Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-5">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0">Set New Password</h4>
                </div>
                <div class="card-body">
                    <?php if ($message): ?>
                        <div class="alert alert-<?php echo $messageType; ?> alert-dismissible fade show">
                            <?php echo $message; ?>
                        </div>
                    <?php endif; ?>

                    <?php if ($tokenValid): ?>
                        <form action="reset_password.php?token=<?php echo htmlspecialchars($token); ?>" method="POST">
                            <input type="hidden" name="user_id" value="<?php echo $userId; ?>">
                            <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">
                            
                            <div class="mb-3">
                                <label for="password" class="form-label">New Password</label>
                                <input type="password" class="form-control" id="password" name="password" required>
                            </div>
                            
                            <div class="mb-4">
                                <label for="confirm_password" class="form-label">Confirm New Password</label>
                                <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                            </div>
                            
                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary">Update Password</button>
                            </div>
                        </form>
                    <?php endif; ?>

                    <?php if (!$tokenValid && $messageType !== 'danger'): ?>
                        <div class="text-center mt-3">
                            <a href="login.php" class="btn btn-outline-primary">Go to Login</a>
                        </div>
                    <?php elseif (!$tokenValid && $messageType === 'danger'): ?>
                        <div class="text-center mt-3">
                            <a href="forgot_password.php" class="btn btn-outline-secondary">Request New Reset Link</a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>