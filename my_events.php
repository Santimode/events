<?php
session_start();
require_once 'includes/db.php';

// --- 1. STRICT ACCESS CONTROL ---
if (!isset($_SESSION['user_id'])) {
    header("Location: auth/login.php");
    exit;
}

$userId = $_SESSION['user_id'];

// --- 2. FETCH USER'S TICKETS ---
// We join the registrations and events tables so we can show the event details alongside the ticket ID
$query = "
    SELECT r.ticket_id, r.registration_date, 
           e.title, e.start_datetime, e.location_type, e.location_details
    FROM registrations r
    INNER JOIN events e ON r.event_id = e.id
    WHERE r.user_id = ?
    ORDER BY e.start_datetime ASC
";
$stmt = $pdo->prepare($query);
$stmt->execute([$userId]);
$myTickets = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Tickets - Event Management System</title>
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
                    <a class="nav-link active" href="my_events.php">My Tickets</a>
                </li>
                <?php if ($_SESSION['user_role'] === 'organizer'): ?>
                    <li class="nav-item"><a class="nav-link" href="organizer/dashboard.php">Organizer Dashboard</a></li>
                <?php elseif ($_SESSION['user_role'] === 'admin'): ?>
                    <li class="nav-item"><a class="nav-link" href="admin/dashboard.php">Admin Dashboard</a></li>
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
            <h2>My Digital Tickets</h2>
            <p class="text-muted">Present these QR codes at the check-in desk for your physical events.</p>
        </div>
    </div>

    <div class="row">
        <?php if (count($myTickets) > 0): ?>
            <?php foreach ($myTickets as $ticket): ?>
                <div class="col-md-6 col-lg-4 mb-4">
                    <div class="card h-100 shadow-sm border-0">
                        <div class="card-header bg-primary text-white text-center py-3">
                            <h5 class="mb-0 text-truncate"><?php echo htmlspecialchars($ticket['title']); ?></h5>
                        </div>
                        <div class="card-body text-center d-flex flex-column align-items-center">
                            
                            <?php if ($ticket['ticket_id']): ?>
                                <div class="bg-white p-2 border rounded mb-3 d-inline-block">
                                    <img src="https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=<?php echo urlencode($ticket['ticket_id']); ?>" 
                                         alt="QR Code for <?php echo $ticket['ticket_id']; ?>" 
                                         class="img-fluid">
                                </div>
                                <h4 class="font-monospace text-dark mb-3"><?php echo htmlspecialchars($ticket['ticket_id']); ?></h4>
                            <?php else: ?>
                                <div class="alert alert-warning w-100">Legacy Ticket: No ID Generated</div>
                            <?php endif; ?>

                            <hr class="w-100">

                            <div class="text-start w-100">
                                <p class="mb-1"><strong>Date:</strong> <?php echo date('F j, Y, g:i a', strtotime($ticket['start_datetime'])); ?></p>
                                <p class="mb-1">
                                    <strong>Type:</strong> 
                                    <?php if($ticket['location_type'] === 'virtual'): ?>
                                        <span class="badge bg-info text-dark">Virtual</span>
                                    <?php else: ?>
                                        <span class="badge bg-warning text-dark">In-Person</span>
                                    <?php endif; ?>
                                </p>
                                <?php if ($ticket['location_details']): ?>
                                    <p class="mb-0 text-muted small"><i class="bi bi-geo-alt"></i> <?php echo htmlspecialchars($ticket['location_details']); ?></p>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="card-footer bg-light text-center text-muted small">
                            Registered on <?php echo date('M d, Y', strtotime($ticket['registration_date'])); ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="col-12">
                <div class="alert alert-light text-center py-5 border" role="alert">
                    <h5 class="text-muted">You haven't registered for any events yet.</h5>
                    <a href="index.php" class="btn btn-primary mt-3">Browse Upcoming Events</a>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>