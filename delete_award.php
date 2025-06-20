<?php
session_start();
require_once 'db_connect2.php';

// Verify admin is logged in
if (!isset($_SESSION['admin_logged_in'])) {
    header("HTTP/1.1 403 Forbidden");
    exit("Unauthorized access");
}

// Verify POST request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'])) {
    $id = (int)$_POST['id'];
    
    try {
        // Soft delete (recommended)
        $stmt = $pdo->prepare("UPDATE awards SET is_active = FALSE WHERE id = ?");
        $stmt->execute([$id]);
        
        // Or hard delete (permanent removal)
        // $stmt = $pdo->prepare("DELETE FROM awards WHERE id = ?");
        // $stmt->execute([$id]);
        
        header("Location: awards.php?success=Award+deleted");
        exit();
    } catch (PDOException $e) {
        error_log("Delete error: " . $e->getMessage());
        header("Location: awards.php?error=Delete+failed");
        exit();
    }
}

header("Location: awards.php");
exit();
?>