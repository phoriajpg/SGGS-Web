<?php
$host = 'localhost';      // Database host
$dbname = 'sggs_events';  // Database name
$username = 'root';     // Database username
$password = '';     // Database password

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    
    // Set PDO error mode to exception
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Set default fetch mode to associative array
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    
    // Prevent emulated prepared statements
    $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
    
} catch (PDOException $e) {
    // Log error to a secure location
    error_log("Database connection failed: " . $e->getMessage());
    
    // Display generic error message to user
    die("Could not connect to the database. Please try again later.");
}
?>