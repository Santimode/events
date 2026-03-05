<?php
// Database configuration
$host = 'localhost';
$dbname = 'event_management_system';
$username = 'root'; // XAMPP default username
$password = '';     // XAMPP default password (empty)

// Data Source Name
$dsn = "mysql:host=$host;dbname=$dbname;charset=utf8mb4";

// PDO options
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, // Throw exceptions on errors
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,       // Fetch associative arrays by default
    PDO::ATTR_EMULATE_PREPARES   => false,                  // Use real prepared statements
];

try {
    // Create a new PDO instance
    $pdo = new PDO($dsn, $username, $password, $options);
} catch (PDOException $e) {
    // If connection fails, stop execution and display an error
    // Note: In a production environment, you should log this error instead of displaying it to the user.
    die("Database connection failed: " . $e->getMessage());
}
?>