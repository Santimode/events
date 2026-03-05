<?php
session_start();
require_once 'includes/db.php';

// Check for flash messages set by register_event.php
$message = '';
$messageType = '';
if (isset($_SESSION['flash_message'])) {
    $message = $_SESSION['flash_message'];
    $messageType = $_SESSION['flash_type'];
    // Clear it so it doesn't show up again on the next page reload
    unset($_SESSION['flash_message']);
    unset($_SESSION['flash_type']);
}

// Fetch all upcoming events
$eventsStmt = $pdo->query("SELECT * FROM events WHERE start_datetime >= NOW() ORDER BY start_datetime ASC");
$events = $eventsStmt->fetchAll();

// If user is logged in, fetch their registered event IDs
$userRegistrations = [];
if (isset($_SESSION['user_id'])) {
    $regStmt = $pdo->prepare("SELECT event_id FROM registrations WHERE user_id = ?");
    $regStmt->execute([$_SESSION['user_id']]);
    $userRegistrations = $regStmt->fetchAll(PDO::FETCH_COLUMN); 
}

// --- MAGIC HAPPENS HERE: Inject the top HTML and Navbar ---
require_once 'includes/header.php';
?>

<div class="container my-4">
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
                            
                            <?php if(isset($event['location_type']) && $event['location_type'] === 'virtual'): ?>
                                <span class="badge bg-info text-dark">Virtual</span>
                            <?php else: ?>
                                <span class="badge bg-warning text-dark">In-Person</span>
                            <?php endif; ?>
                        </div>
                        <div class="card-body d-flex flex-column">
                            <h5 class="card-title"><?php echo htmlspecialchars($event['title']); ?></h5>
                            <p class="card-text text-truncate" style="max-height: 3rem;"><?php echo htmlspecialchars($event['description']); ?></p>
                            
                            <p class="card-text mb-4">
                                <small class="text-muted">
                                    <strong>Date:</strong> <?php echo date('M d, Y g:i A', strtotime($event['start_datetime'])); ?>
                                </small>
                            </p>
                            
                            <div class="mt-auto">
                                <?php if (!isset($_SESSION['user_id'])): ?>
                                    <a href="auth/login.php" class="btn btn-outline-primary w-100">Log in to Register</a>
                                <?php elseif (in_array($event['id'], $userRegistrations)): ?>
                                    <button class="btn btn-success w-100" disabled>You are Registered ✓</button>
                                <?php else: ?>
                                    <form action="register_event.php" method="POST">
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

<?php 
// --- MAGIC HAPPENS HERE: Inject the bottom HTML and JS ---
require_once 'includes/footer.php'; 
?>