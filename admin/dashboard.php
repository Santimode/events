<?php
session_start();
require_once '../includes/db.php';

// --- 1. STRICT ACCESS CONTROL ---
// Kick out anyone who is not logged in OR is not an admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: ../index.php");
    exit;
}

$message = '';
$messageType = '';

// --- 2. HANDLE FORM SUBMISSIONS (CREATE & DELETE) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // CREATE EVENT
    if (isset($_POST['action']) && $_POST['action'] === 'create') {
        $title = trim($_POST['title']);
        $description = trim($_POST['description']);
        $event_type = $_POST['event_type'];
        $start_datetime = $_POST['start_datetime'];
        $capacity = (int)$_POST['capacity'];

        if (empty($title) || empty($event_type) || empty($start_datetime)) {
            $message = "Please fill in all required fields.";
            $messageType = "danger";
        } else {
            $stmt = $pdo->prepare("INSERT INTO events (title, description, event_type, start_datetime, capacity) VALUES (?, ?, ?, ?, ?)");
            if ($stmt->execute([$title, $description, $event_type, $start_datetime, $capacity])) {
                $message = "Event successfully created!";
                $messageType = "success";
            } else {
                $message = "Failed to create event.";
                $messageType = "danger";
            }
        }
    }

    // DELETE EVENT
    if (isset($_POST['action']) && $_POST['action'] === 'delete') {
        $event_id = (int)$_POST['event_id'];
        // Because we used ON DELETE CASCADE in our database schema, 
        // deleting the event will automatically delete its registrations!
        $stmt = $pdo->prepare("DELETE FROM events WHERE id = ?");
        if ($stmt->execute([$event_id])) {
            $message = "Event deleted successfully.";
            $messageType = "success";
        } else {
            $message = "Failed to delete event.";
            $messageType = "danger";
        }
    }
}

// --- 3. FETCH ALL EVENTS WITH REGISTRATION COUNTS ---
// We use a subquery to count how many people have registered for each event
$query = "
    SELECT e.*, 
    (SELECT COUNT(*) FROM registrations r WHERE r.event_id = e.id) as attendee_count 
    FROM events e 
    ORDER BY e.start_datetime DESC
";
$events = $pdo->query($query)->fetchAll();
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

<nav class="navbar navbar-expand-lg navbar-dark bg-dark mb-4">
    <div class="container-fluid">
        <a class="navbar-brand" href="dashboard.php">EventSys Admin</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#adminNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="adminNav">
            <ul class="navbar-nav ms-auto">
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
    <div class="row">
        <div class="col-md-4 mb-4">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Create New Event</h5>
                </div>
                <div class="card-body">
                    <?php if ($message): ?>
                        <div class="alert alert-<?php echo $messageType; ?> alert-dismissible fade show" role="alert">
                            <?php echo $message; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>

                    <form action="dashboard.php" method="POST">
                        <input type="hidden" name="action" value="create">
                        
                        <div class="mb-3">
                            <label class="form-label">Event Title *</label>
                            <input type="text" class="form-control" name="title" required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Event Type *</label>
                            <select class="form-select" name="event_type" required>
                                <option value="Training">Training</option>
                                <option value="Seminar">Seminar</option>
                                <option value="Conference">Conference</option>
                                <option value="Webinar">Webinar</option>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Start Date & Time *</label>
                            <input type="datetime-local" class="form-control" name="start_datetime" required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Capacity (0 for unlimited)</label>
                            <input type="number" class="form-control" name="capacity" value="0" min="0">
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Description</label>
                            <textarea class="form-control" name="description" rows="3"></textarea>
                        </div>
                        
                        <button type="submit" class="btn btn-primary w-100">Create Event</button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-md-8">
            <div class="card shadow-sm">
                <div class="card-header bg-dark text-white">
                    <h5 class="mb-0">Manage Events</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover table-striped mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Title</th>
                                    <th>Type</th>
                                    <th>Date & Time</th>
                                    <th>Attendees</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (count($events) > 0): ?>
                                    <?php foreach ($events as $event): ?>
                                        <tr>
                                            <td><strong><?php echo htmlspecialchars($event['title']); ?></strong></td>
                                            <td><span class="badge bg-secondary"><?php echo htmlspecialchars($event['event_type']); ?></span></td>
                                            <td><?php echo date('M d, Y g:i A', strtotime($event['start_datetime'])); ?></td>
                                            <td>
                                                <?php echo $event['attendee_count']; ?> 
                                                <?php echo $event['capacity'] > 0 ? '/ ' . $event['capacity'] : ''; ?>
                                            </td>
                                            <td>
                                                <a href="attendees.php?id=<?php echo $event['id']; ?>" class="btn btn-sm btn-info text-white me-1">View Attendees</a>
                                                
                                                <form action="dashboard.php" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this event? This will also remove all registrations.');">
                                                    <input type="hidden" name="action" value="delete">
                                                    <input type="hidden" name="event_id" value="<?php echo $event['id']; ?>">
                                                    <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                                                </form>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="5" class="text-center py-4">No events found. Create one to get started!</td>
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