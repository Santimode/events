<?php
session_start();
require_once 'includes/db.php';

// --- 1. STRICT ACCESS CONTROL ---
// Kick out anyone who is not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: auth/login.php");
    exit;
}

$message = '';
$messageType = '';
$userId = $_SESSION['user_id'];

// --- 2. HANDLE REGISTRATION CANCELLATION ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'cancel') {
    $eventIdToCancel = (int)$_POST['event_id'];

    // Delete the specific registration for this user and this event
    $cancelStmt = $pdo->prepare("DELETE FROM registrations WHERE user_id = ? AND event_id = ?");
    if ($cancelStmt->execute([$userId, $eventIdToCancel])) {
        $message = "You have successfully canceled your registration.";
        $messageType = "success";
    } else {
        $message = "Something went wrong while trying to cancel. Please try again.";
        $messageType = "danger";
    }
}

// --- 3. FETCH USER'S REGISTERED EVENTS ---
// Join events and registrations tables to get the details of what this specific user signed up for
$query = "
    SELECT e.*, r.registration_date 
    FROM events e
    INNER JOIN registrations r ON e.id = r.event_id
    WHERE r.user_id = ?
    ORDER BY e.start_datetime ASC
";
$stmt = $pdo->prepare($query);
$stmt->execute([$userId]);
$myEvents = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Events - Event Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<nav class="navbar navbar-expand-lg navbar-dark bg-dark mb-4">
    <div class="container">
        <a class="navbar-brand" href="index.php">EventSys</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto">
                <li class="nav-item">
                    <span class="nav-link text-light">Welcome, <?php echo htmlspecialchars($_SESSION['first_name']); ?></span>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="index.php">Browse Events</a>
                </li>
                <?php if ($_SESSION['user_role'] === 'admin'): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="admin/dashboard.php">Admin Dashboard</a>
                    </li>
                <?php endif; ?>
                <li class="nav-item">
                    <a class="nav-link" href="auth/logout.php">Logout</a>
                </li>
            </ul>
        </div>
    </div>
</nav>

<div class="container">
    <div class="row mb-4">
        <div class="col-12">
            <h2>My Registered Events</h2>
            <p class="text-muted">Manage the trainings, seminars, conferences, and webinars you are attending.</p>
        </div>
    </div>

    <?php if ($message): ?>
        <div class="alert alert-<?php echo $messageType; ?> alert-dismissible fade show" role="alert">
            <?php echo $message; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <div class="card shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-striped mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Event Title</th>
                            <th>Type</th>
                            <th>Date & Time</th>
                            <th>Date Registered</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($myEvents) > 0): ?>
                            <?php foreach ($myEvents as $event): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($event['title']); ?></strong></td>
                                    <td><span class="badge bg-secondary"><?php echo htmlspecialchars($event['event_type']); ?></span></td>
                                    <td><?php echo date('M d, Y g:i A', strtotime($event['start_datetime'])); ?></td>
                                    <td><?php echo date('M d, Y', strtotime($event['registration_date'])); ?></td>
                                    <td>
                                        <form action="my_events.php" method="POST" onsubmit="return confirm('Are you sure you want to cancel your registration for this event?');">
                                            <input type="hidden" name="action" value="cancel">
                                            <input type="hidden" name="event_id" value="<?php echo $event['id']; ?>">
                                            <button type="submit" class="btn btn-sm btn-outline-danger">Cancel Registration</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" class="text-center py-5">
                                    <p class="text-muted mb-3">You haven't registered for any events yet.</p>
                                    <a href="index.php" class="btn btn-primary">Browse Upcoming Events</a>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>