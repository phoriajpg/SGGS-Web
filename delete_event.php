<?php
session_start();
require_once 'db_connect.php';

// Debug: Log session status
error_log("Delete attempt - Admin logged in: " . (isset($_SESSION['admin_logged_in']) ? 'Yes' : 'No'));

if (!isset($_SESSION['admin_logged_in'])) {
    error_log("Unauthorized delete attempt");
    header("HTTP/1.1 403 Forbidden");
    exit("Unauthorized access");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'])) {
    $id = (int)$_POST['id'];
    error_log("Attempting to delete event ID: $id");
    
    try {
        // Try HARD DELETE first for testing
        $stmt = $pdo->prepare("DELETE FROM events WHERE id = ?");
        $deleted = $stmt->execute([$id]);
        
        error_log("Delete query executed. Rows affected: " . $stmt->rowCount());
        
        if ($stmt->rowCount() > 0) {
            header("Location: awards.php?success=Award+deleted+successfully");
        } else {
            header("Location: awards.php?error=Failed+to+delete+award");
        }
        exit();
    } catch (PDOException $e) {
        error_log("Delete error: " . $e->getMessage());
        header("Location: events.php?error=Database+error+".urlencode($e->getMessage()));
        exit();
    }
}

error_log("Invalid delete request method or missing ID");
header("Location: events.php?error=Invalid+request");
exit();
?>