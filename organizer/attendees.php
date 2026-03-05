<?php
session_start();
require_once '../includes/db.php';

// --- 1. STRICT ACCESS CONTROL ---
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'organizer') {
    header("Location: ../index.php");
    exit;
}

$organizerId = $_SESSION['user_id'];

// --- 2. VALIDATE THE EVENT ID ---
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: dashboard.php");
    exit;
}

$event_id = (int)$_GET['id'];

// --- 3. SECURITY: FETCH EVENT & VERIFY OWNERSHIP ---
// This prevents Organizer A from viewing Organizer B's event by guessing the URL ID
$eventStmt = $pdo->prepare("SELECT title, start_datetime, capacity FROM events WHERE id = ? AND organizer_id = ?");
$eventStmt->execute([$event_id, $organizerId]);
$event = $eventStmt->fetch();

if (!$event) {
    die("<div style='font-family: sans-serif; padding: 20px; text-align: center;'>Event not found or you do not have permission to view this event's attendees. <br><br><a href='dashboard.php'>&larr; Return to Dashboard</a></div>");
}

// --- 4. FETCH ATTENDEES & TICKET IDS ---
$query = "
    SELECT u.first_name, u.middle_initial, u.last_name, u.suffix, u.email, r.registration_date, r.ticket_id 
    FROM users u
    INNER JOIN registrations r ON u.id = r.user_id
    WHERE r.event_id = ?
    ORDER BY u.last_name ASC, u.first_name ASC
";
$attendeesStmt = $pdo->prepare($query);
$attendeesStmt->execute([$event_id]);
$attendees = $attendeesStmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Attendees - <?php echo htmlspecialchars($event['title']); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<nav class="navbar navbar-expand-lg navbar-dark bg-success mb-4">
    <div class="container-fluid">
        <a class="navbar-brand" href="dashboard.php">EventSys Organizer</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#organizerNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="organizerNav">
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

<div class="container">
    <div class="row mb-3 align-items-center">
        <div class="col-md-8">
            <h2>Attendees for: <span class="text-success"><?php echo htmlspecialchars($event['title']); ?></span></h2>
            <p class="text-muted mb-0">
                Scheduled for: <?php echo date('F j, Y, g:i a', strtotime($event['start_datetime'])); ?> | 
                Total Registered: <strong><?php echo count($attendees); ?></strong> 
                <?php echo $event['capacity'] > 0 ? '/ ' . $event['capacity'] : ''; ?>
            </p>
        </div>
        <div class="col-md-4 text-md-end mt-3 mt-md-0">
            <a href="dashboard.php" class="btn btn-outline-secondary">&larr; Back to Dashboard</a>
        </div>
    </div>

    <div class="card shadow-sm border-success">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-striped mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>#</th>
                            <th>Ticket ID</th>
                            <th>Attendee Name</th>
                            <th>Email Address</th>
                            <th>Registration Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($attendees) > 0): ?>
                            <?php $counter = 1; ?>
                            <?php foreach ($attendees as $attendee): ?>
                                <tr>
                                    <td class="text-muted"><?php echo $counter++; ?></td>
                                    
                                    <td>
                                        <span class="badge bg-dark font-monospace" style="font-size: 0.9em;">
                                            <?php echo htmlspecialchars($attendee['ticket_id'] ?? 'N/A'); ?>
                                        </span>
                                    </td>
                                    
                                    <td>
                                        <strong>
                                        <?php 
                                            $nameParts = array_filter([
                                                $attendee['first_name'], 
                                                $attendee['middle_initial'], 
                                                $attendee['last_name'], 
                                                $attendee['suffix']
                                            ]);
                                            echo htmlspecialchars(implode(' ', $nameParts)); 
                                        ?>
                                        </strong>
                                    </td>

                                    <td><a href="mailto:<?php echo htmlspecialchars($attendee['email']); ?>" class="text-decoration-none"><?php echo htmlspecialchars($attendee['email']); ?></a></td>
                                    <td><?php echo date('M d, Y g:i A', strtotime($attendee['registration_date'])); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" class="text-center py-5 text-muted">
                                    No one has registered for this event yet.
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