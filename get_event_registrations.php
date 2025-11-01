<?php
session_start();

// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "sggs";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die(json_encode(['success' => false, 'message' => 'Database connection failed']));
}

// Check if user is admin
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'admin') {
    die(json_encode(['success' => false, 'message' => 'Unauthorized']));
}

// Get event ID from request
$event_id = isset($_GET['event_id']) ? intval($_GET['event_id']) : 0;

if ($event_id <= 0) {
    die(json_encode(['success' => false, 'message' => 'Invalid event ID']));
}

// Get registered students for the event
$students_stmt = $conn->prepare("
    SELECT u.id, u.username, u.email, u.user_type, er.registered_at 
    FROM event_registrations er 
    JOIN users u ON er.user_id = u.id 
    WHERE er.event_id = ? 
    ORDER BY er.registered_at DESC
");
$students_stmt->bind_param("i", $event_id);
$students_stmt->execute();
$students_result = $students_stmt->get_result();
$students = $students_result->fetch_all(MYSQLI_ASSOC);

header('Content-Type: application/json');
echo json_encode([
    'success' => true,
    'students' => $students
]);

$students_stmt->close();
$conn->close();
?>