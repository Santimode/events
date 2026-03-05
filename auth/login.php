<?php
// Start the session at the very beginning of the script
session_start();

// Include the database connection
require_once '../includes/db.php';

$message = '';
$messageType = '';

// If the user is already logged in, redirect them away from the login page
if (isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    // 1. Basic validation
    if (empty($email) || empty($password)) {
        $message = "Please enter both email and password.";
        $messageType = "danger";
    } else {
        // 2. Fetch the user from the database
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        // 3. Verify the user exists AND the password matches the hash
        if ($user && password_verify($password, $user['password_hash'])) {
            
            // 4. Check if the user has verified their email via Postmark
            if ($user['is_verified'] == 1) {
                
                // 5. Success! Set session variables
                $_SESSION['user_id'] = $user['id'];
                // Store first name for casual greetings, or assemble a full name for the session
                $_SESSION['first_name'] = $user['first_name'];
                $_SESSION['user_role'] = $user['role'];

                // 6. Redirect based on user role
                if ($user['role'] === 'admin') {
                    header("Location: ../admin/dashboard.php");
                } else {
                    header("Location: ../index.php"); // Main event listing
                }
                exit; // Always exit after a header redirect
                
            } else {
                $message = "Please verify your email address before logging in. Check your inbox.";
                $messageType = "warning";
            }
        } else {
            // Generic error message for security (don't reveal if email exists or password is wrong)
            $message = "Invalid email or password.";
            $messageType = "danger";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Event Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-5">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0">Log In</h4>
                </div>
                <div class="card-body">
                    
                    <?php if ($message): ?>
                        <div class="alert alert-<?php echo $messageType; ?> alert-dismissible fade show" role="alert">
                            <?php echo $message; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>

                    <form action="login.php" method="POST">
                        <div class="mb-3">
                            <label for="email" class="form-label">Email address</label>
                            <input type="email" class="form-control" id="email" name="email" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="password" class="form-label">Password</label>
                            <input type="password" class="form-control" id="password" name="password" required>
                        </div>
                        
                        <div class="d-grid mb-3">
                            <button type="submit" class="btn btn-primary">Log In</button>
                        </div>
                    </form>
                    
                    <div class="text-center">
                        <p class="mb-0">Don't have an account? <a href="register.php">Register here</a>.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>