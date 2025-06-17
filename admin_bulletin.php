<?php
session_start();
require_once 'db_connect1.php';

// Check admin authentication
if (!isset($_SESSION['admin'])) {
    header("Location: admin_login.php");
    exit;
}

// Handle delete
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    $stmt = $pdo->prepare("UPDATE announcements SET is_active = FALSE WHERE id = ?");
    $stmt->execute([$id]);
    header("Location: admin_bulletin.php");
    exit;
}

// Fetch all announcements (including inactive)
$stmt = $pdo->query("SELECT * FROM announcements ORDER BY date_posted DESC");
$announcements = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Admin Bulletin</title>
    <!-- Include your CSS -->
</head>
<body>
    <!-- Navigation -->
    
    <div class="bulletin-container">
        <h1>Manage Announcements</h1>
        
        <div class="announcement-grid">
            <?php foreach ($announcements as $announcement): ?>
                <div class="announcement-card <?= $announcement['is_urgent'] ? 'urgent' : '' ?>">
                    <div class="announcement-header">
                        <h2><?= htmlspecialchars($announcement['title']) ?></h2>
                        <span><?= $announcement['date_posted'] ?></span>
                    </div>
                    <div class="announcement-body">
                        <p><?= nl2br(htmlspecialchars($announcement['content'])) ?></p>
                        <p>- <?= htmlspecialchars($announcement['author']) ?></p>
                        <p>Status: <?= $announcement['is_active'] ? 'Active' : 'Inactive' ?></p>
                        <div class="admin-actions">
                            <a href="edit_announcement.php?id=<?= $announcement['id'] ?>">Edit</a>
                            <a href="?delete=<?= $announcement['id'] ?>" onclick="return confirm('Are you sure?')">Delete</a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</body>
</html>