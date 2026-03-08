<?php
session_start();
require_once '../includes/db.php';

// 1. STRICT ACCESS CONTROL
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'organizer') {
    header("Location: ../index.php");
    exit;
}

// 2. REQUIRE AN EVENT ID
if (!isset($_GET['event_id'])) {
    die("Error: No event selected for scanning. Please go back to your dashboard.");
}

$eventId = (int)$_GET['event_id'];
$organizerId = $_SESSION['user_id'];

// 3. VERIFY OWNERSHIP (Make sure this Organizer actually owns this event)
$eventStmt = $pdo->prepare("SELECT title FROM events WHERE id = ? AND organizer_id = ?");
$eventStmt->execute([$eventId, $organizerId]);
$event = $eventStmt->fetch();

if (!$event) {
    die("Error: You do not have permission to scan tickets for this event.");
}

$message = '';
$messageType = '';

// 4. PROCESS THE SCANNED TICKET
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['ticket_id'])) {
    $scannedTicket = trim($_POST['ticket_id']);

    // Look up the ticket for this specific event
    $ticketStmt = $pdo->prepare("
        SELECT r.id, r.is_checked_in, r.check_in_time, u.first_name, u.last_name 
        FROM registrations r
        JOIN users u ON r.user_id = u.id
        WHERE r.ticket_id = ? AND r.event_id = ?
    ");
    $ticketStmt->execute([$scannedTicket, $eventId]);
    $registration = $ticketStmt->fetch();

    if (!$registration) {
        $message = "❌ INVALID TICKET: <strong>" . htmlspecialchars($scannedTicket) . "</strong> does not exist for this event.";
        $messageType = "danger";
    } elseif ($registration['is_checked_in'] == 1) {
        $time = date('g:i A', strtotime($registration['check_in_time']));
        $message = "⚠️ ALREADY SCANNED: <strong>" . htmlspecialchars($registration['first_name'] . ' ' . $registration['last_name']) . "</strong> checked in at " . $time . ".";
        $messageType = "warning";
    } else {
        // Ticket is valid! Check them in.
        $updateStmt = $pdo->prepare("UPDATE registrations SET is_checked_in = 1, check_in_time = NOW() WHERE id = ?");
        $updateStmt->execute([$registration['id']]);
        $message = "✅ SUCCESS: <strong>" . htmlspecialchars($registration['first_name'] . ' ' . $registration['last_name']) . "</strong> is checked in!";
        $messageType = "success";
    }
}

// --- INJECT HEADER ---
require_once '../includes/header.php';
?>

<div class="container my-5">
    <div class="row justify-content-center">
        <div class="col-md-8 col-lg-6 text-center">
            
            <h2 class="mb-1">QR Scanner</h2>
            <p class="text-muted mb-4">Event: <strong><?php echo htmlspecialchars($event['title']); ?></strong></p>

            <div class="mb-3 d-flex justify-content-between">
                <a href="dashboard.php" class="btn btn-outline-secondary btn-sm">&larr; Back to Dashboard</a>
            </div>

            <?php if ($message): ?>
                <div class="alert alert-<?php echo $messageType; ?> alert-dismissible fade show text-start shadow-sm" role="alert" style="font-size: 1.1rem;">
                    <?php echo $message; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <div class="card shadow border-primary mb-4">
                <div class="card-body p-2">
                    <div id="reader" width="100%"></div>
                </div>
            </div>

            <form id="scanForm" action="scan.php?event_id=<?php echo $eventId; ?>" method="POST">
                <input type="hidden" name="ticket_id" id="scanned_ticket_id">
            </form>

        </div>
    </div>
</div>

<script src="https://unpkg.com/html5-qrcode" type="text/javascript"></script>

<script>
    // This function runs the moment the camera detects a QR code
    function onScanSuccess(decodedText, decodedResult) {
        
        // 1. Play a pleasant "beep" sound (Optional but highly recommended for feedback!)
        let audio = new Audio('https://assets.mixkit.co/active_storage/sfx/2869/2869-preview.mp3');
        audio.play();

        // 2. Stop the scanner instantly so it doesn't scan the same code 50 times in one second
        html5QrcodeScanner.clear();

        // 3. Put the TKT-XXXX-XXXX text into our hidden input field
        document.getElementById('scanned_ticket_id').value = decodedText;

        // 4. Submit the form to PHP!
        document.getElementById('scanForm').submit();
    }

    function onScanFailure(error) {
        // Handle scan failure, usually better to ignore and keep scanning.
    }

    // Initialize the Scanner (fps = frames per second, qrbox = the size of the scanning square)
    let html5QrcodeScanner = new Html5QrcodeScanner(
        "reader",
        { fps: 10, qrbox: {width: 250, height: 250} },
        /* verbose= */ false);
    
    html5QrcodeScanner.render(onScanSuccess, onScanFailure);
</script>

<?php 
// --- INJECT FOOTER ---
require_once '../includes/footer.php'; 
?>