<?php
session_start();
require_once 'includes/db.php';

$message = '';
$messageType = '';

// Check if a logged-in user is trying to register for an event
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['register_event_id'])) {
    if (!isset($_SESSION['user_id'])) {
        $message = "You must be logged in to register for an event.";
        $messageType = "warning";
    } else {
        $eventId = (int)$_POST['register_event_id'];
        $userId = $_SESSION['user_id'];

        // Attempt to insert the registration
        try {
            $stmt = $pdo->prepare("INSERT INTO registrations (user_id, event_id) VALUES (?, ?)");
            $stmt->execute([$userId, $eventId]);
            $message = "Successfully registered for the event!";
            $messageType = "success";
        } catch (PDOException $e) {
            // Error code 23000 indicates a duplicate entry (due to the UNIQUE constraint we set)
            if ($e->getCode() == 23000) {
                $message = "You are already registered for this event.";
                $messageType = "info";
            } else {
                $message = "An error occurred during registration. Please try again.";
                $messageType = "danger";
            }
        }
    }
}

// Fetch all upcoming events
$eventsStmt = $pdo->query("SELECT * FROM events WHERE start_datetime >= NOW() ORDER BY start_datetime ASC");
$events = $eventsStmt->fetchAll();

// If user is logged in, fetch their registered event IDs so we can update the button UI
$userRegistrations = [];
if (isset($_SESSION['user_id'])) {
    $regStmt = $pdo->prepare("SELECT event_id FROM registrations WHERE user_id = ?");
    $regStmt->execute([$_SESSION['user_id']]);
    $userRegistrations = $regStmt->fetchAll(PDO::FETCH_COLUMN); // Returns a flat array of IDs
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upcoming Events - Event Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<nav class="navbar navbar-expand-lg navbar-dark bg-dark mb-4">
    <div class="container">
        <a class="navbar-brand" href="index.php">EventSys</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto">
                <?php if (isset($_SESSION['user_id'])): ?>
                    <li class="nav-item">
                        <span class="nav-link text-light">Welcome, <?php echo htmlspecialchars($_SESSION['first_name']); ?></span>
                    </li>
                    <li class="nav-item">
            <a class="nav-link" href="my_events.php">My Events</a>
        </li>
                    <?php if ($_SESSION['user_role'] === 'admin'): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="admin/dashboard.php">Admin Dashboard</a>
                        </li>
                    <?php endif; ?>
                    <li class="nav-item">
                        <a class="nav-link" href="auth/logout.php">Logout</a>
                    </li>
                <?php else: ?>
                    <li class="nav-item">
                        <a class="nav-link" href="auth/login.php">Login</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="auth/register.php">Register</a>
                    </li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>

<div class="container">
    <div class="row mb-4">
        <div class="col-12">
            <h2>Upcoming Events</h2>
            <p class="text-muted">Browse and register for our latest trainings, seminars, conferences, and webinars.</p>
        </div>
    </div>

    <?php if ($message): ?>
        <div class="alert alert-<?php echo $messageType; ?> alert-dismissible fade show" role="alert">
            <?php echo $message; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <div class="row">
        <?php if (count($events) > 0): ?>
            <?php foreach ($events as $event): ?>
                <div class="col-md-6 col-lg-4 mb-4">
                    <div class="card h-100 shadow-sm">
                        <div class="card-header bg-secondary text-white d-flex justify-content-between align-items-center">
                            <span class="badge bg-light text-dark"><?php echo htmlspecialchars($event['event_type']); ?></span>
                            <small><?php echo date('M d, Y g:i A', strtotime($event['start_datetime'])); ?></small>
                        </div>
                        <div class="card-body d-flex flex-column">
                            <h5 class="card-title"><?php echo htmlspecialchars($event['title']); ?></h5>
                            <p class="card-text text-truncate" style="max-height: 3rem;"><?php echo htmlspecialchars($event['description']); ?></p>
                            
                            <div class="mt-auto">
                                <?php if (!isset($_SESSION['user_id'])): ?>
                                    <a href="auth/login.php" class="btn btn-outline-primary w-100">Log in to Register</a>
                                <?php elseif (in_array($event['id'], $userRegistrations)): ?>
                                    <button class="btn btn-success w-100" disabled>You are Registered ✓</button>
                                <?php else: ?>
                                    <form action="index.php" method="POST">
                                        <input type="hidden" name="register_event_id" value="<?php echo $event['id']; ?>">
                                        <button type="submit" class="btn btn-primary w-100">Register Now</button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="col-12">
                <div class="alert alert-light text-center" role="alert">
                    No upcoming events at the moment. Please check back later!
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>