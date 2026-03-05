<?php
// Load Postmark SDK
use Postmark\PostmarkClient;
use Postmark\Models\PostmarkException;

require '../vendor/autoload.php';
require_once '../includes/db.php';
require_once '../includes/config.php';

$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);

    if (empty($email)) {
        $message = "Please enter your email address.";
        $messageType = "danger";
    } else {
        // 1. Check if the email exists in our database
        $stmt = $pdo->prepare("SELECT id, first_name FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        // 2. Security Best Practice: Always show the same success message 
        // whether the email exists or not, to prevent attackers from guessing emails.
        $message = "If an account with that email exists, we have sent a password reset link.";
        $messageType = "success";

        if ($user) {
            // 3. Generate a secure token
            $token = bin2hex(random_bytes(32));

            // 4. Save the token and set expiration to 1 hour from now using MySQL's DATE_ADD
            $updateStmt = $pdo->prepare("UPDATE users SET reset_token = ?, reset_expires_at = DATE_ADD(NOW(), INTERVAL 1 HOUR) WHERE id = ?");
            
            if ($updateStmt->execute([$token, $user['id']])) {
                
                // 5. Send the email via Postmark
                $resetLink = BASE_URL . "/auth/reset_password.php?token=" . $token;
                
               try {
                    // Force a 15-second timeout instead of 60
                    $client = new PostmarkClient(POSTMARK_TOKEN, 15); 
                    
                    // Bypass local XAMPP cURL hang (Note: Remove this line when deploying to a live server!)
                    $client::$VERIFY_SSL = false; 
                    
                    $htmlBody = "Hi " . htmlspecialchars($user['first_name']) . ",<br><br>You requested a password reset. Click the link below to set a new password. This link will expire in 1 hour.<br><br><a href='$resetLink'>$resetLink</a><br><br>If you did not request this, please ignore this email.";
                    
                    $textBody = "Hi " . $user['first_name'] . ",\n\nYou requested a password reset. Paste this link into your browser to set a new password. This link will expire in 1 hour:\n\n$resetLink\n\nIf you did not request this, please ignore this email.";

                    // Simplified SDK call (Postmark uses "outbound" by default, so we don't need all the nulls)
                    $client->sendEmail(
                        POSTMARK_SENDER, 
                        $email, 
                        "Password Reset - Event Management System", 
                        $htmlBody, 
                        $textBody
                    );

                    // If it succeeds, show the success message
                    $message = "If an account with that email exists, we have sent a password reset link.";
                    $messageType = "success";

                } catch (PostmarkException $ex) {
                    $message = "Postmark Error: " . $ex->message;
                    $messageType = "danger";
                } catch (Exception $e) {
                    $message = "General Error: " . $e->getMessage();
                    $messageType = "danger";
                }
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - Event Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-5">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0">Reset Password</h4>
                </div>
                <div class="card-body">
                    <?php if ($message): ?>
                        <div class="alert alert-<?php echo $messageType; ?> alert-dismissible fade show">
                            <?php echo $message; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <p class="text-muted">Enter your registered email address, and we will send you a link to reset your password.</p>

                    <form action="forgot_password.php" method="POST">
                        <div class="mb-3">
                            <label for="email" class="form-label">Email address</label>
                            <input type="email" class="form-control" id="email" name="email" required>
                        </div>
                        <div class="d-grid mb-3">
                            <button type="submit" class="btn btn-primary">Send Reset Link</button>
                        </div>
                    </form>
                    
                    <div class="text-center">
                        <a href="login.php" class="text-decoration-none">&larr; Back to Login</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>