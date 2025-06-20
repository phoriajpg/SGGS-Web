<?php
session_start();
require_once 'db_connect2.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_logged_in'])) {
    header("Location: awards.php?error=Unauthorized access");
    exit();
}

// Check if form was submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate and sanitize input
    $student_name = htmlspecialchars(trim($_POST['student_name']));
    $award_name = htmlspecialchars(trim($_POST['award_name']));
    $achievement = htmlspecialchars(trim($_POST['achievement']));
    $award_date = $_POST['award_date'];
    $category = htmlspecialchars(trim($_POST['category']));
    
    // Validate required fields
    if (empty($student_name) || empty($award_name) || empty($achievement) || empty($award_date) || empty($category)) {
        header("Location: awards.php?error=All fields are required");
        exit();
    }
    
    // Validate date format
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $award_date)) {
        header("Location: awards.php?error=Invalid date format");
        exit();
    }
    
    // Insert into database
    try {
        $stmt = $pdo->prepare("INSERT INTO awards (student_name, award_name, achievement, award_date, category) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$student_name, $award_name, $achievement, $award_date, $category]);
        
        // Success - redirect back to awards page
        header("Location: awards.php?success=Award added successfully");
        exit();
    } catch (PDOException $e) {
        error_log("Database error: " . $e->getMessage());
        header("Location: awards.php?error=Error adding award");
        exit();
    }
} else {
    // Not a POST request - redirect
    header("Location: awards.php");
    exit();
}
?>