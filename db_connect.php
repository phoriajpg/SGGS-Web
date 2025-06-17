<?php
$host = 'localhost';
$dbname = 'sggs_events'; // Make sure this matches your actual DB name
$username = 'root';      // Default XAMPP username
$password = '';          // Default XAMPP password (empty)

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}
?>