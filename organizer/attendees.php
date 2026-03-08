<?php
session_start();
require_once '../includes/db.php';

// 1. STRICT ACCESS CONTROL
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'organizer') {
    header("Location: ../index.php");
    exit;
}

// 2. REQUIRE AN EVENT ID IN THE URL
if (!isset($_GET['id'])) {
    die("Error: No event selected. Please go back to your dashboard.");
}

$eventId = (int)$_GET['id'];
$organizerId = $_SESSION['user_id'];

// 3. VERIFY OWNERSHIP (Security check!)
$eventStmt = $pdo->prepare("SELECT title, start_datetime FROM events WHERE id = ? AND organizer_id = ?");
$eventStmt->execute([$eventId, $organizerId]);
$event = $eventStmt->fetch();

if (!$event) {
    die("Error: You do not have permission to view attendees for this event.");
}

// 4. FETCH ALL REGISTRATIONS FOR THIS EVENT
$attendeeStmt = $pdo->prepare("
    SELECT r.ticket_id, r.registration_date, r.is_checked_in, r.check_in_time, 
           u.first_name, u.last_name, u.email 
    FROM registrations r
    JOIN users u ON r.user_id = u.id
    WHERE r.event_id = ?
    ORDER BY r.is_checked_in DESC, u.last_name ASC
");
$attendeeStmt->execute([$eventId]);
$attendees = $attendeeStmt->fetchAll();

// 5. CALCULATE QUICK STATS
$totalRegistered = count($attendees);
$totalCheckedIn = 0;
foreach ($attendees as $a) {
    if ($a['is_checked_in'] == 1) {
        $totalCheckedIn++;
    }
}

// --- INJECT HEADER ---
require_once '../includes/header.php';
?>

<div class="container my-5">
    
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
        <div>
            <h2 class="mb-1"><?php echo htmlspecialchars($event['title']); ?> - Master List</h2>
            <p class="text-muted mb-0">Date: <?php echo date('F j, Y \a\t g:i A', strtotime($event['start_datetime'])); ?></p>
        </div>
        <div class="d-flex gap-2">
            <a href="dashboard.php" class="btn btn-outline-secondary">&larr; Dashboard</a>
            <a href="scan.php?event_id=<?php echo $eventId; ?>" class="btn btn-primary">📷 Open Scanner</a>
        </div>
    </div>

    <div class="row mb-4">
        <div class="col-md-6 mb-3 mb-md-0">
            <div class="card text-white bg-primary shadow-sm h-100 border-0">
                <div class="card-body d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="card-title text-uppercase text-white-50 fw-bold mb-1">Total Registered</h6>
                        <h2 class="mb-0 fw-bold"><?php echo $totalRegistered; ?></h2>
                    </div>
                    <span class="display-4 opacity-50">🎟️</span>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card text-white bg-success shadow-sm h-100 border-0">
                <div class="card-body d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="card-title text-uppercase text-white-50 fw-bold mb-1">Checked In (At Door)</h6>
                        <h2 class="mb-0 fw-bold"><?php echo $totalCheckedIn; ?></h2>
                    </div>
                    <span class="display-4 opacity-50">✅</span>
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow-sm border-dark">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-striped mb-0 align-middle">
                    <thead class="table-dark">
                        <tr>
                            <th>Ticket ID</th>
                            <th>Attendee Name</th>
                            <th>Email</th>
                            <th>Registration Date</th>
                            <th>Check-in Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($totalRegistered > 0): ?>
                            <?php foreach ($attendees as $attendee): ?>
                                <tr>
                                    <td>
                                        <span class="badge bg-secondary font-monospace fs-6">
                                            <?php echo htmlspecialchars($attendee['ticket_id'] ?? 'N/A'); ?>
                                        </span>
                                    </td>
                                    <td><strong><?php echo htmlspecialchars($attendee['last_name'] . ', ' . $attendee['first_name']); ?></strong></td>
                                    <td><a href="mailto:<?php echo htmlspecialchars($attendee['email']); ?>" class="text-decoration-none"><?php echo htmlspecialchars($attendee['email']); ?></a></td>
                                    <td><small class="text-muted"><?php echo date('M d, Y', strtotime($attendee['registration_date'])); ?></small></td>
                                    <td>
                                        <?php if ($attendee['is_checked_in'] == 1): ?>
                                            <span class="badge bg-success py-2 px-3">
                                                ✓ Checked In at <?php echo date('g:i A', strtotime($attendee['check_in_time'])); ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="badge bg-light text-muted border py-2 px-3">Pending Arrival</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" class="text-center py-5">
                                    <p class="text-muted mb-0">No attendees have registered for this event yet.</p>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php 
// --- INJECT FOOTER ---
require_once '../includes/footer.php'; 
?>