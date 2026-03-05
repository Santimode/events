<?php
// Include the database connection
require_once '../includes/db.php';

$message = '';
$messageType = '';

// Check if a token was provided in the URL
if (isset($_GET['token']) && !empty(trim($_GET['token']))) {
    $token = trim($_GET['token']);

    // Look for a user with this exact verification token
    $stmt = $pdo->prepare("SELECT id, is_verified FROM users WHERE verification_token = ?");
    $stmt->execute([$token]);
    $user = $stmt->fetch();

    if ($user) {
        // User found. Check if they are already verified.
        if ($user['is_verified'] == 0) {
            
            // Update the user record to mark them as verified and clear the token for security
            $updateStmt = $pdo->prepare("UPDATE users SET is_verified = 1, verification_token = NULL WHERE id = ?");
            
            if ($updateStmt->execute([$user['id']])) {
                $message = "Success! Your email has been verified. You can now log in to the system.";
                $messageType = "success";
            } else {
                $message = "Something went wrong while updating your account. Please try again or contact support.";
                $messageType = "danger";
            }
        } else {
            $message = "Your account has already been verified.";
            $messageType = "info";
        }
    } else {
        // No user found with that token, or token was already cleared
        $message = "Invalid or expired verification link. Please check your email or register again.";
        $messageType = "danger";
    }
} else {
    $message = "No verification token was provided.";
    $messageType = "warning";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify Account - Event Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card shadow-sm text-center">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0">Account Verification</h4>
                </div>
                <div class="card-body py-5">
                    
                    <?php if ($message): ?>
                        <div class="alert alert-<?php echo $messageType; ?> mb-4" role="alert">
                            <?php echo $message; ?>
                        </div>
                    <?php endif; ?>

                    <?php if ($messageType === 'success' || $messageType === 'info'): ?>
                        <a href="login.php" class="btn btn-primary px-4">Go to Login</a>
                    <?php else: ?>
                        <a href="register.php" class="btn btn-outline-secondary px-4">Back to Registration</a>
                    <?php endif; ?>

                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>