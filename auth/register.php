<?php
// Include the database connection
require_once '../includes/db.php';

$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    // 1. Basic validation
    if (empty($name) || empty($email) || empty($password)) {
        $message = "Please fill in all fields.";
        $messageType = "danger";
    } else {
        // 2. Check if the email already exists
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        
        if ($stmt->fetch()) {
            $message = "An account with this email already exists.";
            $messageType = "warning";
        } else {
            // 3. Hash the password and create a verification token
            $passwordHash = password_hash($password, PASSWORD_DEFAULT);
            $token = bin2hex(random_bytes(32)); // Generates a secure random 64-character string

            // 4. Insert the user into the database
            $insertStmt = $pdo->prepare("INSERT INTO users (name, email, password_hash, verification_token) VALUES (?, ?, ?, ?)");
            
            if ($insertStmt->execute([$name, $email, $passwordHash, $token])) {
                
                // 5. Send Verification Email via Postmark API
                $postmarkToken = 'YOUR_POSTMARK_SERVER_TOKEN'; // Replace with your token
                $senderEmail = 'sender@yourdomain.com';        // Replace with your verified Postmark sender
                
                // The link the user will click (adjust the path if your folder structure differs)
                $verifyLink = "http://localhost/event-sys/auth/verify.php?token=" . $token;
                
                $emailData = [
                    'From' => $senderEmail,
                    'To' => $email,
                    'Subject' => 'Verify your account for the Event Management System',
                    'HtmlBody' => "Hi $name,<br><br>Thanks for registering! Please verify your account by clicking the link below:<br><br><a href='$verifyLink'>$verifyLink</a><br><br>If you did not request this, please ignore this email.",
                    'MessageStream' => 'outbound'
                ];

                $ch = curl_init('https://api.postmarkapp.com/email');
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_HTTPHEADER, [
                    'Accept: application/json',
                    'Content-Type: application/json',
                    'X-Postmark-Server-Token: ' . $postmarkToken
                ]);
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($emailData));
                
                $response = curl_exec($ch);
                $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                curl_close($ch);

                // 6. Provide feedback to the user
                if ($httpCode == 200) {
                    $message = "Registration successful! Please check your email to verify your account.";
                    $messageType = "success";
                } else {
                    // Registration worked, but email failed
                    $message = "Registered, but we couldn't send the verification email. Please contact support.";
                    $messageType = "warning";
                    // In a real app, you'd log the $response here to debug Postmark errors
                }
            } else {
                $message = "Something went wrong during registration. Please try again.";
                $messageType = "danger";
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
    <title>Register - Event Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0">Create an Account</h4>
                </div>
                <div class="card-body">
                    
                    <?php if ($message): ?>
                        <div class="alert alert-<?php echo $messageType; ?> alert-dismissible fade show" role="alert">
                            <?php echo $message; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>

                    <form action="register.php" method="POST">
                        <div class="mb-3">
                            <label for="name" class="form-label">Full Name</label>
                            <input type="text" class="form-control" id="name" name="name" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="email" class="form-label">Email address</label>
                            <input type="email" class="form-control" id="email" name="email" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="password" class="form-label">Password</label>
                            <input type="password" class="form-control" id="password" name="password" required>
                        </div>
                        
                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary">Register</button>
                        </div>
                    </form>
                    <div class="mt-3 text-center">
                        <p>Already have an account? <a href="login.php">Log in here</a>.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>