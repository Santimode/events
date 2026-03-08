<?php
// generate_qr.php
session_start();

// Strict security: Only logged-in users can generate QR codes
if (!isset($_SESSION['user_id'])) {
    exit;
}

// Include the QR library
require_once 'includes/phpqrcode/qrlib.php';

if (isset($_GET['ticket_id']) && !empty($_GET['ticket_id'])) {
    $ticketId = $_GET['ticket_id'];
    
    // We can embed the pure Ticket ID, or a full check-in URL. 
    // Let's use the Ticket ID for now.
    $qrData = $ticketId;
    
    // Output directly to the browser (false), Error Correction Level (L, M, Q, H), Pixel Size (4), Margin (2)
    QRcode::png($qrData, false, QR_ECLEVEL_L, 4, 2);
} else {
    // Output a blank image or error if no ID is provided
    header('Content-Type: image/png');
    $im = imagecreatetruecolor(100, 100);
    $bg = imagecolorallocate($im, 255, 255, 255);
    imagefill($im, 0, 0, $bg);
    imagepng($im);
    imagedestroy($im);
}
?>