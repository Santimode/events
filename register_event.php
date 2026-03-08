<?php
// register_event.php
session_start();
require_once 'includes/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['register_event_id'])) {
    
    // 1. Check if user is logged in
    if (!isset($_SESSION['user_id'])) {
        $_SESSION['flash_message'] = "You must be logged in to register for an event.";
        $_SESSION['flash_type'] = "warning";
        header("Location: index.php");
        exit;
    }

    $eventId = (int)$_POST['register_event_id'];
    $userId = $_SESSION['user_id'];

    // 2. Generate a Unique Ticket ID
    $uniqueSuffix = strtoupper(substr(uniqid(), -5)); 
    $randomPin = rand(1000, 9999);
    $ticketId = "TKT-" . $uniqueSuffix . "-" . $randomPin;

    // 3. Attempt to insert the registration
    try {
        $stmt = $pdo->prepare("INSERT INTO registrations (user_id, event_id, ticket_id) VALUES (?, ?, ?)");
        $stmt->execute([$userId, $eventId, $ticketId]);
        
        // Save success message and Ticket ID to the session
        $_SESSION['flash_message'] = "Successfully registered! Your Ticket ID is: <strong>" . $ticketId . "</strong>";
        $_SESSION['flash_type'] = "success";
        
    } catch (PDOException $e) {
        // Error code 23000 indicates a duplicate entry 
        if ($e->getCode() == 23000) {
            $_SESSION['flash_message'] = "You are already registered for this event.";
            $_SESSION['flash_type'] = "info";
        } else {
            $_SESSION['flash_message'] = "An error occurred during registration. Please try again.";
            $_SESSION['flash_type'] = "danger";
        }
    }
}

// 4. Instantly redirect back to the homepage
header("Location: index.php");
exit;