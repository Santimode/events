<?php
session_start();
require_once 'includes/db.php';

// 1. MUST BE LOGGED IN
if (!isset($_SESSION['user_id'])) {
    header("Location: auth/login.php");
    exit;
}

$userId = $_SESSION['user_id'];
$message = '';
$messageType = '';

// 2. PROCESS FORM SUBMISSIONS
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    
    // --- ACTION A: UPDATE GENERAL INFO ---
    if ($_POST['action'] === 'update_info') {
        $first = trim($_POST['first_name']);
        $middle = trim($_POST['middle_initial']);
        $last = trim($_POST['last_name']);
        $suffix = trim($_POST['suffix']);
        
        if (empty($first) || empty($last)) {
            $message = "First and Last names are required.";
            $messageType = "danger";
        } else {
            $stmt = $pdo->prepare("UPDATE users SET first_name = ?, middle_initial = ?, last_name = ?, suffix = ? WHERE id = ?");
            if ($stmt->execute([$first, $middle, $last, $suffix, $userId])) {
                $_SESSION['first_name'] = $first; // Update session name immediately
                $message = "Profile information updated successfully!";
                $messageType = "success";
            }
        }
    }

    // --- ACTION B: UPLOAD PROFILE PICTURE ---
    if ($_POST['action'] === 'upload_picture') {
        if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] === UPLOAD_ERR_OK) {
            $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif'];
            $fileName = $_FILES['profile_picture']['name'];
            $fileSize = $_FILES['profile_picture']['size'];
            $tmpName = $_FILES['profile_picture']['tmp_name'];
            
            $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
            
            if (!in_array($fileExtension, $allowedExtensions)) {
                $message = "Invalid file type. Only JPG, PNG, and GIF are allowed.";
                $messageType = "danger";
            } elseif ($fileSize > 2097152) { 
                $message = "File is too large. Maximum size is 2MB.";
                $messageType = "danger";
            } else {
                $newFileName = 'user_' . $userId . '_' . time() . '.' . $fileExtension;
                $uploadDir = 'uploads/profile-picture/';
                
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0755, true);
                }

                if (move_uploaded_file($tmpName, $uploadDir . $newFileName)) {
                    $stmt = $pdo->prepare("UPDATE users SET profile_picture = ? WHERE id = ?");
                    $stmt->execute([$newFileName, $userId]);
                    $message = "Profile picture updated!";
                    $messageType = "success";
                } else {
                    $message = "Failed to move uploaded file.";
                    $messageType = "danger";
                }
            }
        } else {
            $message = "Please select a valid image file.";
            $messageType = "danger";
        }
    }

    // --- ACTION C: CHANGE PASSWORD ---
    if ($_POST['action'] === 'change_password') {
        $currentPassword = $_POST['current_password'];
        $newPassword = $_POST['new_password'];
        $confirmPassword = $_POST['confirm_password'];

        $stmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $user = $stmt->fetch();

        if (!password_verify($currentPassword, $user['password'])) {
            $message = "Current password is incorrect.";
            $messageType = "danger";
        } elseif ($newPassword !== $confirmPassword) {
            $message = "New passwords do not match.";
            $messageType = "warning";
        } elseif (strlen($newPassword) < 8) {
            $message = "New password must be at least 8 characters.";
            $messageType = "warning";
        } else {
            $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
            $stmt->execute([$hashedPassword, $userId]);
            $message = "Password successfully changed!";
            $messageType = "success";
        }
    }
}

// 3. FETCH LATEST USER DATA
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$userId]);
$currentUser = $stmt->fetch();

// --- INJECT HEADER ---
require_once 'includes/header.php';
?>

<div class="container my-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <h2 class="mb-4">Account Settings</h2>

            <?php if ($message): ?>
                <div class="alert alert-<?php echo $messageType; ?> alert-dismissible fade show">
                    <?php echo $message; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <div class="card shadow-sm mb-4">
                <div class="card-body d-flex align-items-center">
                    <div class="me-4">
                        <?php 
                            $avatar = $currentUser['profile_picture'];
                            $imgPath = "uploads/profile-picture/" . $avatar;
                            
                            // Adjust path depending on where profile.php is located relative to uploads
                            if (!file_exists($imgPath) || empty($avatar) || $avatar === 'default.png') {
                                $imgPath = "https://ui-avatars.com/api/?name=" . urlencode($currentUser['first_name'] . '+' . $currentUser['last_name']) . "&background=random";
                            } else {
                                // Add a cache-busting timestamp so new uploads show immediately
                                $imgPath .= "?t=" . time(); 
                            }
                        ?>
                        <img src="<?php echo $imgPath; ?>" alt="Profile Picture" class="rounded-circle" style="width: 100px; height: 100px; object-fit: cover; border: 3px solid #dee2e6;">
                    </div>
                    <form action="profile.php" method="POST" enctype="multipart/form-data" class="w-100">
                        <input type="hidden" name="action" value="upload_picture">
                        <div class="mb-2">
                            <label class="form-label fw-bold">Update Profile Picture</label>
                            <input class="form-control form-control-sm" type="file" name="profile_picture" accept=".jpg,.jpeg,.png,.gif" required>
                            <div class="form-text">Max size 2MB. JPG, PNG, or GIF.</div>
                        </div>
                        <button type="submit" class="btn btn-sm btn-primary">Upload Image</button>
                    </form>
                </div>
            </div>

            <div class="card shadow-sm mb-4">
                <div class="card-header bg-white fw-bold">General Information</div>
                <div class="card-body">
                    <form action="profile.php" method="POST">
                        <input type="hidden" name="action" value="update_info">
                        
                        <div class="mb-3">
                            <label class="form-label text-muted">Email Address (Cannot be changed)</label>
                            <input type="email" class="form-control" value="<?php echo htmlspecialchars($currentUser['email']); ?>" disabled>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">First Name *</label>
                                <input type="text" class="form-control" name="first_name" value="<?php echo htmlspecialchars($currentUser['first_name']); ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Middle Initial</label>
                                <input type="text" class="form-control" name="middle_initial" value="<?php echo htmlspecialchars($currentUser['middle_initial']); ?>" maxlength="5">
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-8">
                                <label class="form-label">Last Name *</label>
                                <input type="text" class="form-control" name="last_name" value="<?php echo htmlspecialchars($currentUser['last_name']); ?>" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Suffix</label>
                                <input type="text" class="form-control" name="suffix" value="<?php echo htmlspecialchars($currentUser['suffix']); ?>" placeholder="e.g. Jr, III">
                            </div>
                        </div>

                        <button type="submit" class="btn btn-success">Save Information</button>
                    </form>
                </div>
            </div>

            <div class="card shadow-sm mb-5">
                <div class="card-header bg-white fw-bold text-danger">Security</div>
                <div class="card-body">
                    <form action="profile.php" method="POST">
                        <input type="hidden" name="action" value="change_password">
                        
                        <div class="mb-3">
                            <label class="form-label">Current Password *</label>
                            <input type="password" class="form-control" name="current_password" required>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">New Password *</label>
                                <input type="password" class="form-control" name="new_password" minlength="8" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Confirm New Password *</label>
                                <input type="password" class="form-control" name="confirm_password" minlength="8" required>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-danger">Change Password</button>
                    </form>
                </div>
            </div>

        </div>
    </div>
</div>

<?php 
// --- INJECT FOOTER ---
require_once 'includes/footer.php'; 
?>