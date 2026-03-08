<?php
session_start();
require_once 'includes/db.php';

// 1. STRICT ACCESS CONTROL
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'attendee') {
    header("Location: index.php");
    exit;
}

$userId = $_SESSION['user_id'];

// 2. FETCH ATTENDEE's TICKETS
$query = "
    SELECT e.title, e.start_datetime, e.location_type, e.location_details, r.ticket_id, r.registration_date 
    FROM registrations r
    INNER JOIN events e ON r.event_id = e.id
    WHERE r.user_id = ?
    ORDER BY e.start_datetime ASC
";
$stmt = $pdo->prepare($query);
$stmt->execute([$userId]);
$myTickets = $stmt->fetchAll();

// --- INJECT HEADER ---
require_once 'includes/header.php';
?>

<div class="container my-5">
    <div class="row mb-4">
        <div class="col-12">
            <h2>My Tickets</h2>
            <p class="text-muted">Present your QR code at the venue for check-in.</p>
        </div>
    </div>

    <div class="row">
        <?php if (count($myTickets) > 0): ?>
            <?php foreach ($myTickets as $ticket): ?>
                <div class="col-md-6 mb-4">
                    <div class="card shadow-sm h-100 border-primary">
                        <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                            <h5 class="mb-0 text-truncate"><?php echo htmlspecialchars($ticket['title']); ?></h5>
                        </div>
                        <div class="card-body d-flex align-items-center">
                            
                            <div class="me-4 text-center">
                                <?php if (!empty($ticket['ticket_id'])): ?>
                                    <img src="generate_qr.php?ticket_id=<?php echo urlencode($ticket['ticket_id']); ?>" alt="QR Code" class="img-thumbnail mb-2" style="width: 120px; height: 120px;">
                                    <br>
                                    <span class="badge bg-dark font-monospace"><?php echo htmlspecialchars($ticket['ticket_id']); ?></span>
                                <?php else: ?>
                                    <div class="text-muted border p-4 mb-2" style="width: 120px; height: 120px; font-size: 0.8rem;">No ID<br>Generated</div>
                                <?php endif; ?>
                            </div>

                            <div>
                                <p class="mb-1">
                                    <strong>Date:</strong><br> 
                                    <?php echo date('F j, Y \a\t g:i A', strtotime($ticket['start_datetime'])); ?>
                                </p>
                                <p class="mb-0">
                                    <strong>Format:</strong><br> 
                                    <?php if($ticket['location_type'] === 'virtual'): ?>
                                        <span class="badge bg-info text-dark mb-1">Virtual</span><br>
                                        <a href="<?php echo htmlspecialchars($ticket['location_details']); ?>" target="_blank" class="text-decoration-none">Join Meeting &rarr;</a>
                                    <?php else: ?>
                                        <span class="badge bg-warning text-dark mb-1">In-Person</span><br>
                                        <small class="text-muted"><?php echo htmlspecialchars($ticket['location_details']); ?></small>
                                    <?php endif; ?>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="col-12">
                <div class="alert alert-light text-center py-5" role="alert">
                    You haven't registered for any events yet.<br>
                    <a href="index.php" class="btn btn-primary mt-3">Browse Events</a>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php 
// --- INJECT FOOTER ---
require_once 'includes/footer.php'; 
?>