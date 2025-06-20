<?php
session_start();
require_once 'db_connect1.php';

// Security headers
header("X-Frame-Options: DENY");
header("X-Content-Type-Options: nosniff");
header("X-XSS-Protection: 1; mode=block");

// Check if admin is logged in
if (!isset($_SESSION['admin_logged_in'])) {
    header("HTTP/1.1 403 Forbidden");
    exit("Access Denied");
}

// Check if the request is POST and has an ID
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'])) {
    $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
    
    if ($id === false || $id === null) {
        header("Location: bulletin.php?error=Invalid+announcement+ID");
        exit();
    }

    try {
        // Prepare and execute the delete statement
        $stmt = $pdo->prepare("DELETE FROM announcements WHERE id = ?");
        $stmt->execute([$id]);
        
        // Check if any row was affected
        if ($stmt->rowCount() > 0) {
            header("Location: bulletin.php?success=Announcement+deleted+successfully");
            exit();
        } else {
            header("Location: bulletin.php?error=Failed+to+delete+announcement");
            exit();
        }
    } catch (PDOException $e) {
        error_log("Database error: " . $e->getMessage());
        header("Location: bulletin.php?error=Database+error+occurred");
        exit();
    }
} else {
    header("HTTP/1.1 405 Method Not Allowed");
    exit("Method not allowed");
}
?>