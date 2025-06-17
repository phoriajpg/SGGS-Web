<?php
require_once 'db_connect1.php';

// Fetch all active announcements
try {
    $stmt = $pdo->query("SELECT * FROM announcements WHERE is_active = TRUE ORDER BY date_posted DESC");
    $announcements = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Error fetching announcements: " . $e->getMessage());
}

// Handle form submission for new announcements
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['title'])) {
    $title = $_POST['title'];
    $content = $_POST['content'];
    $author = $_POST['author'];
    $isUrgent = isset($_POST['is_urgent']) ? 1 : 0;
    
    try {
        $stmt = $pdo->prepare("INSERT INTO announcements (title, content, author, is_urgent) VALUES (?, ?, ?, ?)");
        $stmt->execute([$title, $content, $author, $isUrgent]);
        
        // Refresh to show the new announcement
        header("Location: bulletin.php");
        exit;
    } catch (PDOException $e) {
        $error = "Error creating announcement: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>SGGS Bulletin</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <link href="https://fonts.googleapis.com/css2?family=Gabarito:wght@400..900&display=swap" rel="stylesheet">
  <style>
    /* ========== Base Styles ========== */
    :root {
      --primary: #B10023;
      --primary-dark: #830000;
      --accent: #f1c40f;
      --text-dark: #2c3e50;
      --text-light: #f5f5f5;
    }
    
    html {
      scroll-behavior: smooth;
    }
    
    body {
      font-family: "Gabarito", sans-serif;
      margin: 0;
      padding: 0;
      background-color: #f5f5f5;
      color: var(--text-dark);
      line-height: 1.6;
    }

    /* ========== Navbar Styles ========== */
    .nav-box {
  background-color: white;
  padding: 10px 20px;
  height: 60px;
  display: inline-flex;
  align-items: center;
  border-top-left-radius: 12px;
  border-bottom-left-radius: 12px;
}


.navbar {
  position: fixed;
  top: 20px;
  left: 0;
  right: 0;
  height: 80px;
  display: flex;
  justify-content: flex-end;
  align-items: center;
  z-index: 1000;
  background-color: transparent;
}

.nav-links {
  display: flex;
  align-items: center;
  gap: 20px;
  margin-left: auto;
}

.navbar a,
.navbar .dropdown-toggle {
  padding: 8px 16px;
  color: #B10023;
  text-decoration: none;
  font-size: 16px;
  font-family: "Gabarito", sans-serif;
  border-radius: 10px;
  white-space: nowrap;
  transition: background-color 0.3s ease, color 0.3s ease;
  display: inline-block;
}

.navbar a:hover,
.navbar .dropdown-toggle:hover {
  background-color: #B10023;
  color: white;
}

/* ========== Dropdown Styles ========== */
.dropdown {
  position: relative;
}

.dropdown-menu {
  position: absolute;
  top: 110%;
  left: 0;
  background-color: #B10023;
  display: none;
  flex-direction: column;
  padding: 10px 0;
  border-radius: 8px;
  z-index: 999;
}

.dropdown:hover .dropdown-menu {
  display: flex;
}

.dropdown-menu a {
  padding: 10px 20px;
  color: white;
  text-decoration: none;
  font-size: 14px;
}

.dropdown-menu a:hover {
  background-color: #830000;
  border-radius: 4px;
}

/* ========== Dropdown Styles ========== */
.dropdown {
  position: relative;
}

.dropdown-menu {
  position: absolute;
  top: 110%;
  left: 0;
  background-color: #B10023;
  display: none;
  flex-direction: column;
  padding: 10px 0;
  border-radius: 8px;
  z-index: 999;
}

.dropdown:hover .dropdown-menu {
  display: flex;
}

.dropdown-menu a {
  padding: 10px 20px;
  color: white;
  text-decoration: none;
  font-size: 14px;
}

.dropdown-menu a:hover {
  background-color: #830000;
  border-radius: 4px;
}

    .label {
      font-weight: 700;
      font-size: 35px;
      display: inline;
    }

    /* ========== Bulletin Board Styles ========== */
    .bulletin-container {
      max-width: 1200px;
      margin: 120px auto 40px;
      padding: 20px;
    }

    .bulletin-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 30px;
    }

    .bulletin-title {
      font-size: 2.5rem;
      color: var(--primary);
      margin: 0;
    }

    .announcement-grid {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
      gap: 25px;
    }

    .announcement-card {
      background-color: white;
      border-radius: 10px;
      overflow: hidden;
      box-shadow: 0 3px 10px rgba(0,0,0,0.1);
      transition: transform 0.3s ease, box-shadow 0.3s ease;
    }

    .announcement-card:hover {
      transform: translateY(-5px);
      box-shadow: 0 10px 20px rgba(0,0,0,0.15);
    }

    .announcement-header {
      background-color: var(--primary);
      color: white;
      padding: 15px 20px;
      display: flex;
      justify-content: space-between;
      align-items: center;
    }

    .announcement-title {
      font-size: 1.3rem;
      margin: 0;
    }

    .announcement-date {
      font-size: 0.9rem;
      opacity: 0.8;
    }

    .announcement-body {
      padding: 20px;
    }

    .announcement-content {
      margin-bottom: 15px;
    }

    .announcement-author {
      font-style: italic;
      color: #666;
      text-align: right;
    }

    .urgent {
      border-left: 5px solid var(--accent);
    }

    /* ========== Responsive ========== */
    @media (max-width: 768px) {
      .bulletin-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 15px;
      }
      
      .announcement-grid {
        grid-template-columns: 1fr;
      }
      
      .nav-links {
        gap: 10px;
      }
      
      .label {
        font-size: 28px;
      }
    }
  </style>
</head>
<body>
  <!-- Navigation -->
  <nav class="navbar">
    <div class="nav-box">
      <div class="nav-links">
        <a href="student.html"><span class="label">Home</span></a>
            <a href="bulletin.html"><span class="label">Bulletin</span></a>
            <a href="events.php"><span class="label">Events</span></a>
            <a href="awards.html"><span class="label">Awards</span></a>
            <a href="index.html"><span class="label">Log Out</span></a>
      </div>
    </div>
  </nav>

  <div class="bulletin-container">
    <div class="bulletin-header">
      <h1 class="bulletin-title">SGGS Bulletin</h1>
      <button id="newPostBtn" class="new-post-btn">
        <i class="fas fa-plus"></i> New Post
      </button>
    </div>

    <!-- New Announcement Form (hidden by default) -->
    <div id="newPostForm" style="display: none; background: white; padding: 20px; border-radius: 10px; margin-bottom: 30px;">
      <h2>Create New Announcement</h2>
      <form method="POST">
        <div style="margin-bottom: 15px;">
          <label style="display: block; margin-bottom: 5px;">Title:</label>
          <input type="text" name="title" required style="width: 100%; padding: 8px;">
        </div>
        <div style="margin-bottom: 15px;">
          <label style="display: block; margin-bottom: 5px;">Content:</label>
          <textarea name="content" required style="width: 100%; padding: 8px; min-height: 100px;"></textarea>
        </div>
        <div style="margin-bottom: 15px;">
          <label style="display: block; margin-bottom: 5px;">Author:</label>
          <input type="text" name="author" required style="width: 100%; padding: 8px;">
        </div>
        <div style="margin-bottom: 15px;">
          <label>
            <input type="checkbox" name="is_urgent"> Urgent Announcement
          </label>
        </div>
        <button type="submit" style="background: var(--primary); color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer;">Post Announcement</button>
      </form>
    </div>

    <div class="announcement-grid">
      <?php if (empty($announcements)): ?>
        <p>No announcements found.</p>
      <?php else: ?>
        <?php foreach ($announcements as $announcement): 
          $date = new DateTime($announcement['date_posted']);
        ?>
          <div class="announcement-card <?= $announcement['is_urgent'] ? 'urgent' : '' ?>">
            <div class="announcement-header">
              <h2 class="announcement-title"><?= htmlspecialchars($announcement['title']) ?></h2>
              <span class="announcement-date"><?= $date->format('F j, Y') ?></span>
            </div>
            <div class="announcement-body">
              <p class="announcement-content"><?= nl2br(htmlspecialchars($announcement['content'])) ?></p>
              <p class="announcement-author">- <?= htmlspecialchars($announcement['author']) ?></p>
            </div>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </div>

  <script>
    // Toggle new post form
    document.getElementById('newPostBtn').addEventListener('click', function() {
      const form = document.getElementById('newPostForm');
      form.style.display = form.style.display === 'none' ? 'block' : 'none';
    });
  </script>
</body>
</html>