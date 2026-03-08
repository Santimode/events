<?php
session_start();
require_once '../includes/db.php';

// --- 1. STRICT ACCESS CONTROL ---
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'organizer') {
    header("Location: ../index.php");
    exit;
}

$message = '';
$messageType = '';
$organizerId = $_SESSION['user_id'];

// --- 2. HANDLE FORM SUBMISSIONS (CREATE EVENT) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create') {
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $event_type = $_POST['event_type'];
    $location_type = $_POST['location_type'];
    $location_details = trim($_POST['location_details']);
    $start_datetime = $_POST['start_datetime'];
    $capacity = (int)$_POST['capacity'];

    if (empty($title) || empty($event_type) || empty($start_datetime)) {
        $message = "Please fill in all required fields.";
        $messageType = "danger";
    } else {
        $stmt = $pdo->prepare("INSERT INTO events (title, description, event_type, location_type, location_details, start_datetime, capacity, organizer_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        
        if ($stmt->execute([$title, $description, $event_type, $location_type, $location_details, $start_datetime, $capacity, $organizerId])) {
            $message = "Event successfully created!";
            $messageType = "success";
        } else {
            $message = "Failed to create event.";
            $messageType = "danger";
        }
    }
}

// --- 3. FETCH ONLY THIS ORGANIZER'S EVENTS ---
$query = "
    SELECT e.*, 
    (SELECT COUNT(*) FROM registrations r WHERE r.event_id = e.id) as attendee_count 
    FROM events e 
    WHERE e.organizer_id = ?
    ORDER BY e.start_datetime DESC
";
$stmt = $pdo->prepare($query);
$stmt->execute([$organizerId]);
$myEvents = $stmt->fetchAll();

// --- INJECT HEADER ---
require_once '../includes/header.php';
?>

<div class="container-fluid px-4 my-4">
    <div class="row">
        
        <div class="col-md-4 mb-4">
            <div class="card shadow-sm border-success">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0">Create New Event</h5>
                </div>
                <div class="card-body">
                    <?php if ($message): ?>
                        <div class="alert alert-<?php echo $messageType; ?> alert-dismissible fade show" role="alert">
                            <?php echo $message; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <form action="dashboard.php" method="POST">
                        <input type="hidden" name="action" value="create">
                        
                        <div class="mb-3">
                            <label class="form-label">Event Title *</label>
                            <input type="text" class="form-control" name="title" required>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Category *</label>
                                <select class="form-select" name="event_type" required>
                                    <option value="Training">Training</option>
                                    <option value="Seminar">Seminar</option>
                                    <option value="Conference">Conference</option>
                                    <option value="Webinar">Webinar</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Format *</label>
                                <select class="form-select" name="location_type" id="location_type" required onchange="updateLocationLabel()">
                                    <option value="physical">In-Person (Physical)</option>
                                    <option value="virtual">Online (Virtual)</option>
                                </select>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label" id="location_label">Venue Address</label>
                            <input type="text" class="form-control" name="location_details" placeholder="e.g. Main Hall / Zoom Link">
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-7">
                                <label class="form-label">Start Date & Time *</label>
                                <input type="datetime-local" class="form-control" name="start_datetime" required>
                            </div>
                            <div class="col-md-5">
                                <label class="form-label">Capacity</label>
                                <input type="number" class="form-control" name="capacity" value="0" min="0" placeholder="0 = unli">
                            </div>
                        </div>
                        
                        <div class="mb-4">
                            <label class="form-label">Description</label>
                            <textarea class="form-control" name="description" rows="3"></textarea>
                        </div>
                        
                        <button type="submit" class="btn btn-success w-100">Publish Event</button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-md-8">
            <div class="card shadow-sm border-dark">
                <div class="card-header bg-dark text-white">
                    <h5 class="mb-0">My Hosted Events</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover table-striped mb-0 align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>Title</th>
                                    <th>Format</th>
                                    <th>Date & Time</th>
                                    <th>Attendees</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (count($myEvents) > 0): ?>
                                    <?php foreach ($myEvents as $event): ?>
                                        <tr>
                                            <td><strong><?php echo htmlspecialchars($event['title']); ?></strong></td>
                                            <td>
                                                <?php if($event['location_type'] === 'virtual'): ?>
                                                    <span class="badge bg-info text-dark">Virtual</span>
                                                <?php else: ?>
                                                    <span class="badge bg-warning text-dark">In-Person</span>
                                                <?php endif; ?>
                                            </td>
                                            <td><?php echo date('M d, Y g:i A', strtotime($event['start_datetime'])); ?></td>
                                            <td>
                                                <?php echo $event['attendee_count']; ?> 
                                                <?php echo $event['capacity'] > 0 ? '/ ' . $event['capacity'] : ''; ?>
                                            </td>
                                            <td>
                                                <div class="d-flex gap-2">
                                                    <a href="attendees.php?id=<?php echo $event['id']; ?>" class="btn btn-sm btn-outline-primary">Manage Attendees</a>
                                                    <a href="scan.php?event_id=<?php echo $event['id']; ?>" class="btn btn-sm btn-primary">📷 Scan Tickets</a>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="5" class="text-center py-5">
                                            <p class="text-muted mb-0">You haven't created any events yet. Publish your first event using the form!</p>
                                        </td>
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

<script>
    // A tiny UX script to swap the location label based on physical vs virtual
    function updateLocationLabel() {
        const type = document.getElementById('location_type').value;
        const label = document.getElementById('location_label');
        if (type === 'virtual') {
            label.innerText = 'Meeting URL (Zoom, Meet, etc.)';
        } else {
            label.innerText = 'Venue Address';
        }
    }
</script>

<?php 
// --- INJECT FOOTER ---
require_once '../includes/footer.php'; 
?>