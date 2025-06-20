<?php
session_start();
require_once 'db_connect2.php';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = $_POST['title'];
    $description = $_POST['description'];
    $category = $_POST['category'];
    $award_date = $_POST['award_date'];
    $recipient = $_POST['recipient'];
    $is_featured = isset($_POST['is_featured']) ? 1 : 0;
    
    // Handle file upload
    $image_path = '';
    if (isset($_FILES['award_image']) && $_FILES['award_image']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = 'uploads/awards/';
        $file_name = uniqid() . '_' . basename($_FILES['award_image']['name']);
        $target_path = $upload_dir . $file_name;
        
        if (move_uploaded_file($_FILES['award_image']['tmp_name'], $target_path)) {
            $image_path = $target_path;
        }
    }
    
    try {
        $stmt = $pdo->prepare("INSERT INTO awards (title, description, category, award_date, recipient, image_path, is_featured) 
                              VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$title, $description, $category, $award_date, $recipient, $image_path, $is_featured]);
        
        header("Location: awards.php?success=1");
        exit();
    } catch (PDOException $e) {
        $error = "Error adding award: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Add New Award</title>
    <!-- Add your admin panel styles here -->
</head>
<body>
    <div class="user-controls">
    <?php if ($is_admin): ?>
        <a href="add_award.php" class="btn btn-primary">
            <i class="fas fa-plus"></i> Add New Award
        </a>
        <a href="add_event.php" class="btn btn-secondary">
            <i class="fas fa-calendar-plus"></i> Add Event
        </a>
    <?php endif; ?>
    
    <?php if (isset($_SESSION['user_id'])): ?>
        <a href="logout.php" class="btn btn-outline">
            <i class="fas fa-sign-out-alt"></i> Logout
        </a>
    <?php else: ?>
        <a href="login.php" class="btn btn-primary">
            <i class="fas fa-sign-in-alt"></i> Login
        </a>
    <?php endif; ?>
</div>
    
    <?php if (isset($error)): ?>
        <div class="error"><?= $error ?></div>
    <?php endif; ?>
    
    <form method="POST" enctype="multipart/form-data">
        <div>
            <label>Title:</label>
            <input type="text" name="title" required>
        </div>
        
        <div>
            <label>Description:</label>
            <textarea name="description" required></textarea>
        </div>
        
        <div>
            <label>Category:</label>
            <select name="category" required>
                <option value="academic">Academic</option>
                <option value="sports">Sports</option>
                <option value="arts">Arts</option>
                <option value="other">Other</option>
            </select>
        </div>
        
        <div>
            <label>Award Date:</label>
            <input type="date" name="award_date" required>
        </div>
        
        <div>
            <label>Recipient (optional):</label>
            <input type="text" name="recipient">
        </div>
        
        <div>
            <label>Featured Award:</label>
            <input type="checkbox" name="is_featured">
        </div>
        
        <div>
            <label>Award Image:</label>
            <input type="file" name="award_image" accept="image/*">
        </div>
        
        <button type="submit">Add Award</button>
    </form>
</body>
</html>