<?php
// 1. Load the Postmark Client and Exception classes
use Postmark\PostmarkClient;
use Postmark\Models\PostmarkException;

// Load Composer's autoloader
require '../vendor/autoload.php';

// Include the database connection
require_once '../includes/db.php';

// Include the secret configuration
require_once '../includes/config.php';

$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $firstName = trim($_POST['first_name']);
    $middleInitial = trim($_POST['middle_initial']);
    $lastName = trim($_POST['last_name']);
    $suffix = trim($_POST['suffix']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirmPassword = $_POST['confirm_password'];

    // Validate required fields
    if (empty($firstName) || empty($lastName) || empty($email) || empty($password) || empty($confirmPassword)) {
        $message = "Please fill in all required fields.";
        $messageType = "danger";
    } elseif ($password !== $confirmPassword) {
        $message = "Passwords do not match. Please try again.";
        $messageType = "danger";
    } else {
        // Check if the email already exists
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        
        if ($stmt->fetch()) {
            $message = "An account with this email already exists.";
            $messageType = "warning";
        } else {
            $passwordHash = password_hash($password, PASSWORD_DEFAULT);
            $token = bin2hex(random_bytes(32));

            // INSERT SEPARATED FIELDS INTO THE DATABASE
            $insertStmt = $pdo->prepare("INSERT INTO users (first_name, middle_initial, last_name, suffix, email, password_hash, verification_token) VALUES (?, ?, ?, ?, ?, ?, ?)");
            
            if ($insertStmt->execute([$firstName, $middleInitial, $lastName, $suffix, $email, $passwordHash, $token])) {
                
                $verifyLink = BASE_URL . "/auth/verify.php?token=" . $token;
                
                // --- START OFFICIAL POSTMARK SDK INTEGRATION ---
                try {
                    // Initialize the client with your Server Token
                    $client = new PostmarkClient(POSTMARK_TOKEN);

                    // Set your verified sender email here
                    $fromEmail = POSTMARK_SENDER;
                    $toEmail = $email;
                    $subject = "Verify your account for the Event Management System";
                    
                    $htmlBody = "Hi $firstName,<br><br>Thanks for registering! Please verify your account by clicking the link below:<br><br><a href='$verifyLink'>$verifyLink</a><br><br>If you did not request this, please ignore this email.";
                    $textBody = "Hi $firstName,\n\nThanks for registering! Please verify your account by copying and pasting this link into your browser:\n\n$verifyLink\n\nIf you did not request this, please ignore this email.";

                    // Send the email
                    $sendResult = $client->sendEmail(
                        $fromEmail, 
                        $toEmail, 
                        $subject, 
                        $htmlBody, 
                        $textBody,
                        null, // Tag
                        true, // Track opens
                        null, // Reply To
                        null, // CC
                        null, // BCC
                        null, // Headers
                        null, // Attachments
                        null, // Track links
                        null, // Metadata
                        "outbound" // Message Stream
                    );

                    $message = "Registration successful! Please check your email to verify your account.";
                    $messageType = "success";

                } catch (PostmarkException $ex) {
                    // Postmark specific errors (e.g., Unverified sender, inactive account)
                    $message = "Registered, but email failed. Postmark Error: " . $ex->message . " (Code: " . $ex->postmarkApiErrorCode . ")";
                    $messageType = "warning";
                } catch (Exception $e) {
                    // General PHP errors
                    $message = "Registered, but a general error occurred sending the email: " . $e->getMessage();
                    $messageType = "warning";
                }
                // --- END OFFICIAL POSTMARK SDK INTEGRATION ---

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

<div class="container mt-5 mb-5">
    <div class="row justify-content-center">
        <div class="col-md-8 col-lg-6">
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
                        
                        <div class="row mb-3">
                            <div class="col-md-5 mb-3 mb-md-0">
                                <label for="first_name" class="form-label">First Name *</label>
                                <input type="text" class="form-control" id="first_name" name="first_name" required>
                            </div>
                            <div class="col-md-2 mb-3 mb-md-0">
                                <label for="middle_initial" class="form-label">M.I.</label>
                                <input type="text" class="form-control" id="middle_initial" name="middle_initial" maxlength="2" placeholder="e.g. A.">
                            </div>
                            <div class="col-md-5">
                                <label for="last_name" class="form-label">Last Name *</label>
                                <input type="text" class="form-control" id="last_name" name="last_name" required>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-3 mb-3 mb-md-0">
                                <label for="suffix" class="form-label">Suffix</label>
                                <select class="form-select" id="suffix" name="suffix">
                                    <option value="">None</option>
                                    <option value="Jr.">Jr.</option>
                                    <option value="Sr.">Sr.</option>
                                    <option value="II">II</option>
                                    <option value="III">III</option>
                                    <option value="IV">IV</option>
                                </select>
                            </div>
                            <div class="col-md-9">
                                <label for="email" class="form-label">Email address *</label>
                                <input type="email" class="form-control" id="email" name="email" required>
                            </div>
                        </div>
                        
                        <div class="row mb-4">
                            <div class="col-md-6 mb-3 mb-md-0">
                                <label for="password" class="form-label">Password *</label>
                                <input type="password" class="form-control" id="password" name="password" required>
                            </div>
                            <div class="col-md-6">
                                <label for="confirm_password" class="form-label">Confirm Password *</label>
                                <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                            </div>
                        </div>
                        
                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary btn-lg">Register</button>
                        </div>
                    </form>
                    
                    <div class="mt-4 text-center">
                        <p class="mb-0">Already have an account? <a href="login.php" class="text-decoration-none fw-bold">Log in here</a>.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>