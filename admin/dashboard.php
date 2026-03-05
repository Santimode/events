<?php
session_start();
require_once '../includes/db.php';

// --- 1. STRICT ACCESS CONTROL ---
// Kick out anyone who is not logged in OR is not a system admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: ../index.php");
    exit;
}

$message = '';
$messageType = '';

// --- 2. HANDLE ROLE UPDATES ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_role') {
    $targetUserId = (int)$_POST['user_id'];
    $newRole = $_POST['new_role'];
    
    // Security check: Prevent the admin from accidentally demoting themselves!
    if ($targetUserId === $_SESSION['user_id']) {
        $message = "You cannot change your own role. Ask another admin to do it.";
        $messageType = "warning";
    } else {
        $updateStmt = $pdo->prepare("UPDATE users SET role = ? WHERE id = ?");
        if ($updateStmt->execute([$newRole, $targetUserId])) {
            $message = "User role successfully updated!";
            $messageType = "success";
        } else {
            $message = "Failed to update user role.";
            $messageType = "danger";
        }
    }
}

// --- 3. FETCH MASTER DATA ---
// Fetch all users
$usersStmt = $pdo->query("SELECT id, first_name, last_name, email, role, created_at FROM users ORDER BY created_at DESC");
$allUsers = $usersStmt->fetchAll();

// Fetch all events with their organizer's name
$eventsQuery = "
    SELECT e.id, e.title, e.start_datetime, e.location_type, e.event_type, 
           u.first_name AS org_first, u.last_name AS org_last
    FROM events e 
    LEFT JOIN users u ON e.organizer_id = u.id 
    ORDER BY e.start_datetime DESC
";
$eventsStmt = $pdo->query($eventsQuery);
$allEvents = $eventsStmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Event Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<nav class="navbar navbar-expand-lg navbar-dark bg-primary mb-4">
    <div class="container-fluid">
        <a class="navbar-brand" href="dashboard.php">EventSys Admin Overview</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#adminNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="adminNav">
            <ul class="navbar-nav ms-auto">
                <li class="nav-item">
                    <span class="nav-link text-light">System Admin: <?php echo htmlspecialchars($_SESSION['first_name']); ?></span>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="../index.php">View Public Site</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="../auth/logout.php">Logout</a>
                </li>
            </ul>
        </div>
    </div>
</nav>

<div class="container-fluid px-4">
    
    <?php if ($message): ?>
        <div class="alert alert-<?php echo $messageType; ?> alert-dismissible fade show" role="alert">
            <?php echo $message; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="row">
        <div class="col-lg-7 mb-4">
            <div class="card shadow-sm border-primary">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">User Management</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive" style="max-height: 500px; overflow-y: auto;">
                        <table class="table table-hover table-striped mb-0 align-middle">
                            <thead class="table-light sticky-top">
                                <tr>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Joined</th>
                                    <th>Role Configuration</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($allUsers as $user): ?>
                                    <tr>
                                        <td><strong><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></strong></td>
                                        <td><?php echo htmlspecialchars($user['email']); ?></td>
                                        <td><small class="text-muted"><?php echo date('M d, Y', strtotime($user['created_at'])); ?></small></td>
                                        <td>
                                            <form action="dashboard.php" method="POST" class="d-flex align-items-center">
                                                <input type="hidden" name="action" value="update_role">
                                                <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                                
                                                <select name="new_role" class="form-select form-select-sm me-2 w-auto" 
                                                    <?php echo ($user['id'] === $_SESSION['user_id']) ? 'disabled' : ''; ?>>
                                                    <option value="attendee" <?php echo $user['role'] === 'attendee' ? 'selected' : ''; ?>>Attendee</option>
                                                    <option value="organizer" <?php echo $user['role'] === 'organizer' ? 'selected' : ''; ?>>Organizer</option>
                                                    <option value="admin" <?php echo $user['role'] === 'admin' ? 'selected' : ''; ?>>Admin</option>
                                                </select>
                                                
                                                <button type="submit" class="btn btn-sm btn-outline-primary" 
                                                    <?php echo ($user['id'] === $_SESSION['user_id']) ? 'disabled' : ''; ?>>
                                                    Save
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-5 mb-4">
            <div class="card shadow-sm border-secondary">
                <div class="card-header bg-secondary text-white">
                    <h5 class="mb-0">Global Event Oversight</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive" style="max-height: 500px; overflow-y: auto;">
                        <table class="table table-hover mb-0">
                            <thead class="table-light sticky-top">
                                <tr>
                                    <th>Event Title</th>
                                    <th>Hosted By</th>
                                    <th>Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (count($allEvents) > 0): ?>
                                    <?php foreach ($allEvents as $event): ?>
                                        <tr>
                                            <td>
                                                <strong><?php echo htmlspecialchars($event['title']); ?></strong><br>
                                                <span class="badge bg-light text-dark border"><?php echo $event['event_type']; ?></span>
                                                <span class="badge <?php echo $event['location_type'] === 'virtual' ? 'bg-info' : 'bg-warning'; ?> text-dark">
                                                    <?php echo ucfirst($event['location_type']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?php 
                                                    echo $event['org_first'] 
                                                        ? htmlspecialchars($event['org_first'] . ' ' . $event['org_last']) 
                                                        : '<span class="text-danger"><em>System (No Organizer)</em></span>'; 
                                                ?>
                                            </td>
                                            <td><small><?php echo date('M d, y', strtotime($event['start_datetime'])); ?></small></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="3" class="text-center py-4 text-muted">No events have been created yet.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>