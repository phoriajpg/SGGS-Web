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
    die("Connection failed: " . $conn->connect_error);
}

// Check if user is logged in and get user role
$user_role = isset($_SESSION['user_type']) ? $_SESSION['user_type'] : 'student';
$is_admin = ($user_role === 'admin');
$is_student = ($user_role === 'student');

// Handle new bulletin submission (admin only)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit_bulletin']) && $is_admin) {
    $title = trim($_POST['title']);
    $content = trim($_POST['content']);
    $category = $_POST['category'];
    $author_name = trim($_POST['author_name']);
    $author_role = $_POST['author_role'];
    
    if (!empty($title) && !empty($content) && !empty($author_name)) {
        $stmt = $conn->prepare("INSERT INTO bulletins (title, content, category, author_name, author_role, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
        $stmt->bind_param("sssss", $title, $content, $category, $author_name, $author_role);
        
        if ($stmt->execute()) {
            $success_message = "Bulletin posted successfully!";
        } else {
            $error_message = "Error posting bulletin: " . $conn->error;
        }
        $stmt->close();
    } else {
        $error_message = "Please fill in all required fields.";
    }
}

// Handle bulletin deletion (admin only)
if (isset($_GET['delete_bulletin']) && $is_admin) {
    $bulletin_id = intval($_GET['delete_bulletin']);
    
    $delete_stmt = $conn->prepare("DELETE FROM bulletins WHERE id = ?");
    $delete_stmt->bind_param("i", $bulletin_id);
    
    if ($delete_stmt->execute()) {
        $success_message = "Bulletin deleted successfully!";
    } else {
        $error_message = "Error deleting bulletin: " . $conn->error;
    }
    $delete_stmt->close();
    
    // Redirect to avoid resubmission
    header("Location: bulletin.php");
    exit();
}

// Handle filters and search
$search_query = '';
$category_filter = '';
$sort_by = 'newest';

if ($_SERVER['REQUEST_METHOD'] == 'GET') {
    $search_query = isset($_GET['search']) ? trim($_GET['search']) : '';
    $category_filter = isset($_GET['category']) ? $_GET['category'] : '';
    $sort_by = isset($_GET['sort']) ? $_GET['sort'] : 'newest';
}

// Build query for bulletins
$sql = "SELECT * FROM bulletins WHERE 1=1";
$params = [];
$types = "";

if (!empty($search_query)) {
    $sql .= " AND (title LIKE ? OR content LIKE ?)";
    $search_term = "%$search_query%";
    $params[] = $search_term;
    $params[] = $search_term;
    $types .= "ss";
}

if (!empty($category_filter)) {
    $sql .= " AND category = ?";
    $params[] = $category_filter;
    $types .= "s";
}

// Add sorting
switch ($sort_by) {
    case 'oldest':
        $sql .= " ORDER BY created_at ASC";
        break;
    case 'category':
        $sql .= " ORDER BY category ASC, created_at DESC";
        break;
    default: // newest
        $sql .= " ORDER BY created_at DESC";
        break;
}

$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();
$bulletins = $result->fetch_all(MYSQLI_ASSOC);

// Get unique categories for filter
$categories_result = $conn->query("SELECT DISTINCT category FROM bulletins ORDER BY category");
$categories = $categories_result->fetch_all(MYSQLI_ASSOC);

// Get recent announcements for sidebar
$recent_stmt = $conn->query("SELECT id, title, created_at FROM bulletins ORDER BY created_at DESC LIMIT 5");
$recent_bulletins = $recent_stmt->fetch_all(MYSQLI_ASSOC);

// Get bulletin statistics
$stats_stmt = $conn->query("SELECT COUNT(*) as total_bulletins, COUNT(DISTINCT category) as total_categories FROM bulletins");
$stats = $stats_stmt->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <link href="https://fonts.googleapis.com/css2?family=Gabarito:wght@400..900&display=swap" rel="stylesheet" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
  <title>SGGSWeb - Student Bulletin</title>
  <style>
    /* ========== Base Styles ========== */
    html {
      margin: 0;
      padding: 0;
      scroll-padding-top: 80px;
    }

    body {
      font-family: "Gabarito", sans-serif;
      margin: 0;
      padding: 0;
      height: auto;
      background-color: #f5f5f5;
      display: flex;
      flex-direction: column;
      align-items: center;
      overflow-x: hidden;
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

    .navbar a {
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

    .navbar a:hover {
      background-color: #B10023;
      color: white;
    }

    .label {
      font-weight: 700;
      font-size: 35px;
      display: inline;
    }

    /* ========== User Role Badge ========== */
    .user-role-badge {
      background: #B10023;
      color: white;
      padding: 6px 12px;
      border-radius: 20px;
      font-size: 0.8rem;
      font-weight: 600;
      margin-left: 10px;
      text-transform: uppercase;
    }

    /* ========== Main Content ========== */
    .main-content {
      width: 100%;
      display: flex;
      flex-direction: column;
      align-items: center;
      padding-top: 120px;
      min-height: 100vh;
    }

    /* ========== Wider Bulletin Container ========== */
    .bulletin-container {
      max-width: 2500px;
      margin: 0 auto;
      padding: 0 20px;
      display: grid;
      grid-template-columns: 1fr 350px;
      gap: 30px;
    }

    .bulletin-header {
      text-align: center;
      margin-bottom: 40px;
      grid-column: 1 / -1;
    }

    .bulletin-header h1 {
      font-size: 3.2rem;
      color: #B10023;
      margin-bottom: 10px;
    }

    .bulletin-header p {
      font-size: 1.3rem;
      color: #555;
      max-width: 800px;
      margin: 0 auto;
    }

    /* ========== Wider Bulletin Actions ========== */
    .bulletin-actions {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 30px;
      background: white;
      padding: 25px;
      border-radius: 12px;
      box-shadow: 0 2px 8px rgba(0,0,0,0.1);
      grid-column: 1 / -1;
    }

    .new-bulletin-btn {
      background: #B10023;
      color: white;
      padding: 14px 28px;
      border: none;
      border-radius: 8px;
      font-family: 'Gabarito', sans-serif;
      font-size: 1.1rem;
      font-weight: 600;
      cursor: pointer;
      transition: background 0.3s;
      white-space: nowrap;
    }

    .new-bulletin-btn:hover {
      background: #830000;
    }

    .bulletin-filters {
      display: flex;
      gap: 15px;
      align-items: center;
      flex-wrap: wrap;
    }

    .search-input {
      padding: 10px 18px;
      border: 2px solid #ddd;
      border-radius: 6px;
      font-family: 'Gabarito', sans-serif;
      font-size: 1.05rem;
      width: 500px;
    }

    .filter-select {
      padding: 10px 18px;
      border: 2px solid #ddd;
      border-radius: 6px;
      font-family: 'Gabarito', sans-serif;
      background: white;
      cursor: pointer;
      min-width: 160px;
      font-size: 1.05rem;
    }

    .search-btn {
      background: #B10023;
      color: white;
      padding: 10px 18px;
      border: none;
      border-radius: 6px;
      font-family: 'Gabarito', sans-serif;
      cursor: pointer;
      transition: background 0.3s;
      font-size: 1.05rem;
    }

    .search-btn:hover {
      background: #830000;
    }

    /* ========== Wider Bulletin List ========== */
    .bulletins-section {
      grid-column: 1;
    }

    .bulletins-list {
      background: white;
      border-radius: 12px;
      box-shadow: 0 2px 8px rgba(0,0,0,0.1);
      overflow: hidden;
    }

    .bulletin-item {
      padding: 30px;
      border-bottom: 1px solid #eee;
      transition: all 0.3s ease;
      position: relative;
    }

    .bulletin-item:hover {
      background: #f8f9fa;
      transform: translateY(-2px);
      box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    }

    .bulletin-item:last-child {
      border-bottom: none;
    }

    .bulletin-header-info {
      display: flex;
      justify-content: space-between;
      align-items: flex-start;
      margin-bottom: 20px;
      gap: 20px;
    }

    .bulletin-title {
      font-size: 1.6rem;
      font-weight: 600;
      color: #333;
      margin: 0;
      flex: 1;
      line-height: 1.4;
    }

    .bulletin-meta {
      display: flex;
      flex-wrap: wrap;
      gap: 12px;
      align-items: center;
    }

    .bulletin-category {
      background: #B10023;
      color: white;
      padding: 6px 14px;
      border-radius: 20px;
      font-size: 0.9rem;
      font-weight: 500;
      white-space: nowrap;
    }

    .bulletin-content {
      margin-bottom: 20px;
      line-height: 1.7;
      color: #555;
      font-size: 1.1rem;
    }

    .bulletin-stats {
      display: flex;
      gap: 25px;
      align-items: center;
      flex-wrap: wrap;
    }

    .stat {
      display: flex;
      align-items: center;
      gap: 8px;
      color: #666;
      font-size: 1rem;
    }

    .stat i {
      width: 18px;
      text-align: center;
    }

    .author-info {
      display: flex;
      align-items: center;
      gap: 10px;
      color: #666;
      font-size: 1rem;
    }

    .author-avatar {
      width: 28px;
      height: 28px;
      background: #B10023;
      color: white;
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 0.8rem;
      font-weight: bold;
    }

    .author-role {
      padding: 4px 10px;
      border-radius: 12px;
      font-size: 0.8rem;
      font-weight: 600;
      text-transform: uppercase;
    }

    .role-teacher { background: #007bff; color: white; }
    .role-admin { background: #28a745; color: white; }
    .role-student { background: #6c757d; color: white; }

    /* ========== Admin Controls ========== */
    .admin-controls {
      position: absolute;
      top: 20px;
      right: 20px;
      display: flex;
      gap: 10px;
    }

    .delete-btn {
      background: #dc3545;
      color: white;
      border: none;
      border-radius: 6px;
      padding: 8px 12px;
      cursor: pointer;
      transition: background 0.3s;
      font-size: 0.9rem;
    }

    .delete-btn:hover {
      background: #c82333;
    }

    .no-bulletins {
      text-align: center;
      padding: 80px 30px;
      background: white;
      border-radius: 12px;
      box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    }

    .no-bulletins h3 {
      color: #666;
      margin-bottom: 20px;
      font-size: 1.7rem;
    }

    .no-bulletins p {
      color: #888;
      margin-bottom: 30px;
      font-size: 1.2rem;
      line-height: 1.6;
    }

    /* ========== Wider Sidebar ========== */
    .sidebar {
      display: flex;
      flex-direction: column;
      gap: 25px;
    }

    .sidebar-widget {
      background: white;
      border-radius: 12px;
      box-shadow: 0 2px 8px rgba(0,0,0,0.1);
      padding: 25px;
    }

    .widget-title {
      color: #B10023;
      margin-bottom: 25px;
      font-size: 1.4rem;
      border-bottom: 2px solid #B10023;
      padding-bottom: 12px;
    }

    .categories-list {
      list-style: none;
      padding: 0;
      margin: 0;
    }

    .category-item {
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding: 14px 0;
      border-bottom: 1px solid #eee;
    }

    .category-item:last-child {
      border-bottom: none;
    }

    .category-name {
      color: #333;
      text-decoration: none;
      font-weight: 500;
      font-size: 1.05rem;
    }

    .category-count {
      background: #f8f9fa;
      color: #666;
      padding: 4px 10px;
      border-radius: 12px;
      font-size: 0.9rem;
    }

    .recent-list {
      list-style: none;
      padding: 0;
      margin: 0;
    }

    .recent-item {
      padding: 12px 0;
      border-bottom: 1px solid #eee;
    }

    .recent-item:last-child {
      border-bottom: none;
    }

    .recent-text {
      color: #333;
      font-size: 1rem;
      line-height: 1.5;
    }

    .recent-time {
      color: #666;
      font-size: 0.9rem;
      margin-top: 6px;
    }

    /* ========== Wider Modal Styles ========== */
    .modal {
      display: none;
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background: rgba(0,0,0,0.5);
      z-index: 1000;
      align-items: center;
      justify-content: center;
    }

    .modal-content {
      background: white;
      padding: 35px;
      border-radius: 12px;
      width: 90%;
      max-width: 700px;
      max-height: 90vh;
      overflow-y: auto;
    }

    .modal-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 30px;
    }

    .modal-title {
      font-size: 1.7rem;
      color: #B10023;
      margin: 0;
    }

    .close-modal {
      background: none;
      border: none;
      font-size: 1.7rem;
      cursor: pointer;
      color: #666;
    }

    .form-group {
      margin-bottom: 25px;
    }

    .form-label {
      display: block;
      margin-bottom: 10px;
      font-weight: 600;
      color: #333;
      font-size: 1.1rem;
    }

    .form-input, .form-select, .form-textarea {
      width: 100%;
      padding: 14px;
      border: 2px solid #ddd;
      border-radius: 6px;
      font-family: 'Gabarito', sans-serif;
      font-size: 1.05rem;
      box-sizing: border-box;
    }

    .form-textarea {
      min-height: 140px;
      resize: vertical;
    }

    .form-input:focus, .form-select:focus, .form-textarea:focus {
      outline: none;
      border-color: #B10023;
    }

    .submit-btn {
      background: #B10023;
      color: white;
      padding: 14px 35px;
      border: none;
      border-radius: 6px;
      font-family: 'Gabarito', sans-serif;
      font-size: 1.1rem;
      font-weight: 600;
      cursor: pointer;
      transition: background 0.3s;
    }

    .submit-btn:hover {
      background: #830000;
    }

    /* ========== Messages ========== */
    .alert {
      padding: 18px;
      border-radius: 6px;
      margin-bottom: 25px;
      grid-column: 1 / -1;
      font-size: 1.1rem;
    }

    .alert-success {
      background: #d4edda;
      color: #155724;
      border: 1px solid #c3e6cb;
    }

    .alert-error {
      background: #f8d7da;
      color: #721c24;
      border: 1px solid #f5c6cb;
    }

    /* ========== Wider Stats ========== */
    .stats-grid {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 18px;
    }

    .stat-item {
      text-align: center;
      padding: 20px;
      background: #f8f9fa;
      border-radius: 8px;
    }

    .stat-number {
      font-size: 1.8rem;
      font-weight: bold;
      color: #B10023;
      margin-bottom: 5px;
    }

    .stat-label {
      font-size: 1rem;
      color: #666;
    }

    /* ========== Responsive Design ========== */
    @media (max-width: 1400px) {
      .bulletin-container {
        max-width: 95vw;
        padding: 0 25px;
      }
    }

    @media (max-width: 1200px) {
      .bulletin-container {
        max-width: 98vw;
        padding: 0 20px;
      }
      
      .search-input {
        width: 240px;
      }
    }

    @media (max-width: 968px) {
      .bulletin-container {
        grid-template-columns: 1fr;
        gap: 25px;
        padding: 0 15px;
      }
      
      .bulletin-actions {
        flex-direction: column;
        gap: 20px;
        align-items: stretch;
      }
      
      .bulletin-filters {
        justify-content: center;
      }
      
      .bulletins-section {
        grid-column: 1;
      }
    }

    @media (max-width: 768px) {
      .bulletin-header h1 {
        font-size: 2.7rem;
      }
      
      .bulletin-header-info {
        flex-direction: column;
        gap: 15px;
      }
      
      .bulletin-meta {
        align-self: flex-start;
      }
      
      .bulletin-stats {
        flex-wrap: wrap;
        gap: 15px;
      }
      
      .bulletin-filters {
        flex-direction: column;
        gap: 12px;
      }
      
      .search-input, .filter-select {
        width: 100%;
      }
      
      .bulletin-title {
        font-size: 1.4rem;
      }
      
      .bulletin-container {
        padding: 0 15px;
      }
      
      .admin-controls {
        position: static;
        margin-top: 15px;
        justify-content: flex-end;
      }
    }

    @media (max-width: 480px) {
      .bulletin-container {
        padding: 0 10px;
      }
      
      .bulletin-item {
        padding: 25px;
      }
      
      .sidebar-widget {
        padding: 20px;
      }
    }
  </style>
</head>
<body>
  <div id="home" class="container">
    <!-- Navbar -->
    <nav class="navbar">
      <div class="nav-box">
        <div class="nav-links">
          <?php if ($is_admin): ?>
            <a href="admin_dashboard.php"><span class="label">Dashboard</span></a>
            <a href="bulletin.php"><span class="label">Bulletin</span></a>
            <a href="events.php"><span class="label">Events</span></a>
            <a href="awards.php"><span class="label">Awards</span></a>
            <a href="qna.php"><span class="label">Q&A</span></a>
          <?php else: ?>
            <a href="student.html"><span class="label">Home</span></a>
            <a href="bulletin.php"><span class="label">Bulletin</span></a>
            <a href="events.php"><span class="label">Events</span></a>
            <a href="awards.php"><span class="label">Awards</span></a>
          <?php endif; ?>
          <a href="logout.php"><span class="label">Log Out</span></a>
        </div>
      </div>
    </nav>

    <!-- Main Content -->
    <div class="main-content">
      <div class="bulletin-container">
        <!-- Header -->
        <div class="bulletin-header">
          <h1>Student Bulletin Board</h1>
          <p>Stay updated with the latest announcements, events, and important information</p>
        </div>

        <!-- Messages -->
        <?php if (isset($success_message)): ?>
          <div class="alert alert-success"><?php echo $success_message; ?></div>
        <?php endif; ?>
        
        <?php if (isset($error_message)): ?>
          <div class="alert alert-error"><?php echo $error_message; ?></div>
        <?php endif; ?>

        <!-- Bulletin Actions (Admin Only) -->
        <?php if ($is_admin): ?>
        <div class="bulletin-actions">
          <button class="new-bulletin-btn" onclick="openBulletinModal()">
            <i class="fas fa-plus"></i> New Bulletin
          </button>
          
          <div class="bulletin-filters">
            <form method="GET" action="bulletin.php" style="display: flex; gap: 10px; align-items: center; flex-wrap: wrap;">
              <input type="text" name="search" placeholder="Search bulletins..." class="search-input" 
                     value="<?php echo htmlspecialchars($search_query); ?>">
              
              <select name="category" class="filter-select">
                <option value="">All Categories</option>
                <?php foreach ($categories as $category): ?>
                  <option value="<?php echo htmlspecialchars($category['category']); ?>" 
                          <?php echo ($category_filter == $category['category']) ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($category['category']); ?>
                  </option>
                <?php endforeach; ?>
              </select>
              
              <select name="sort" class="filter-select">
                <option value="newest" <?php echo ($sort_by == 'newest') ? 'selected' : ''; ?>>Newest First</option>
                <option value="oldest" <?php echo ($sort_by == 'oldest') ? 'selected' : ''; ?>>Oldest First</option>
                <option value="category" <?php echo ($sort_by == 'category') ? 'selected' : ''; ?>>By Category</option>
              </select>
              
              <button type="submit" class="search-btn">
                <i class="fas fa-search"></i> Search
              </button>
            </form>
          </div>
        </div>
        <?php else: ?>
        <!-- Student View - Only Search & Filters -->
        <div class="bulletin-actions">          
          <div class="bulletin-filters">
            <form method="GET" action="bulletin.php" style="display: flex; gap: 10px; align-items: center; flex-wrap: wrap;">
              <input type="text" name="search" placeholder="Search bulletins..." class="search-input" 
                     value="<?php echo htmlspecialchars($search_query); ?>">
              
              <select name="category" class="filter-select">
                <option value="">All Categories</option>
                <?php foreach ($categories as $category): ?>
                  <option value="<?php echo htmlspecialchars($category['category']); ?>" 
                          <?php echo ($category_filter == $category['category']) ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($category['category']); ?>
                  </option>
                <?php endforeach; ?>
              </select>
              
              <select name="sort" class="filter-select">
                <option value="newest" <?php echo ($sort_by == 'newest') ? 'selected' : ''; ?>>Newest First</option>
                <option value="oldest" <?php echo ($sort_by == 'oldest') ? 'selected' : ''; ?>>Oldest First</option>
                <option value="category" <?php echo ($sort_by == 'category') ? 'selected' : ''; ?>>By Category</option>
              </select>
              
              <button type="submit" class="search-btn">
                <i class="fas fa-search"></i> Search
              </button>
            </form>
          </div>
        </div>
        <?php endif; ?>

        <!-- Bulletin List -->
        <div class="bulletins-section">
          <?php if (empty($bulletins)): ?>
            <div class="no-bulletins">
              <h3>No bulletins found</h3>
              <p>Try adjusting your search terms or be the first to post a bulletin!</p>
              <?php if ($is_admin): ?>
                <button class="new-bulletin-btn" onclick="openBulletinModal()">
                  Post First Bulletin
                </button>
              <?php endif; ?>
            </div>
          <?php else: ?>
            <div class="bulletins-list">
              <?php foreach ($bulletins as $bulletin): ?>
                <div class="bulletin-item">
                  <?php if ($is_admin): ?>
                  <div class="admin-controls">
                    <button class="delete-btn" onclick="confirmDelete(<?php echo $bulletin['id']; ?>)">
                      <i class="fas fa-trash"></i> Delete
                    </button>
                  </div>
                  <?php endif; ?>
                  
                  <div class="bulletin-header-info">
                    <h3 class="bulletin-title">
                      <?php echo htmlspecialchars($bulletin['title']); ?>
                    </h3>
                    <div class="bulletin-meta">
                      <span class="bulletin-category">
                        <?php echo htmlspecialchars($bulletin['category']); ?>
                      </span>
                    </div>
                  </div>
                  
                  <div class="bulletin-content">
                    <?php echo nl2br(htmlspecialchars($bulletin['content'])); ?>
                  </div>
                  
                  <div class="bulletin-stats">
                    <div class="stat">
                      <i class="fas fa-clock"></i>
                      <span><?php echo date('M j, Y g:i A', strtotime($bulletin['created_at'])); ?></span>
                    </div>
                    <div class="author-info">
                      <div class="author-avatar">
                        <?php echo strtoupper(substr($bulletin['author_name'], 0, 1)); ?>
                      </div>
                      <span><?php echo htmlspecialchars($bulletin['author_name']); ?></span>
                      <span class="author-role role-<?php echo $bulletin['author_role']; ?>">
                        <?php echo ucfirst($bulletin['author_role']); ?>
                      </span>
                    </div>
                  </div>
                </div>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>
        </div>

        <!-- Sidebar -->
        <div class="sidebar">
          <!-- Categories -->
          <div class="sidebar-widget">
            <h3 class="widget-title">Categories</h3>
            <ul class="categories-list">
              <?php foreach ($categories as $category): ?>
                <li class="category-item">
                  <a href="bulletin.php?category=<?php echo urlencode($category['category']); ?>" class="category-name">
                    <?php echo htmlspecialchars($category['category']); ?>
                  </a>
                  <span class="category-count">
                    <?php 
                    $count_stmt = $conn->prepare("SELECT COUNT(*) FROM bulletins WHERE category = ?");
                    $count_stmt->bind_param("s", $category['category']);
                    $count_stmt->execute();
                    $count_result = $count_stmt->get_result();
                    $count = $count_result->fetch_array()[0];
                    echo $count;
                    ?>
                  </span>
                </li>
              <?php endforeach; ?>
            </ul>
          </div>

          <!-- Recent Announcements -->
          <div class="sidebar-widget">
            <h3 class="widget-title">Recent Announcements</h3>
            <ul class="recent-list">
              <?php foreach ($recent_bulletins as $recent): ?>
                <li class="recent-item">
                  <div class="recent-text">
                    <?php echo htmlspecialchars($recent['title']); ?>
                  </div>
                  <div class="recent-time">
                    <?php echo date('M j, g:i A', strtotime($recent['created_at'])); ?>
                  </div>
                </li>
              <?php endforeach; ?>
            </ul>
          </div>

          <!-- Bulletin Stats -->
          <div class="sidebar-widget">
            <h3 class="widget-title">Bulletin Stats</h3>
            <div class="stats-grid">
              <div class="stat-item">
                <div class="stat-number"><?php echo $stats['total_bulletins']; ?></div>
                <div class="stat-label">Total Bulletins</div>
              </div>
              <div class="stat-item">
                <div class="stat-number"><?php echo $stats['total_categories']; ?></div>
                <div class="stat-label">Categories</div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- New Bulletin Modal (Admin Only) -->
    <?php if ($is_admin): ?>
    <div id="bulletinModal" class="modal">
      <div class="modal-content">
        <div class="modal-header">
          <h3 class="modal-title">Create New Bulletin</h3>
          <button class="close-modal" onclick="closeBulletinModal()">&times;</button>
        </div>
        
        <form method="POST" action="bulletin.php">
          <div class="form-group">
            <label class="form-label">Category *</label>
            <select name="category" class="form-select" required>
              <option value="">Select a category</option>
              <option value="General">General</option>
              <option value="Academic">Academic</option>
              <option value="Events">Events</option>
              <option value="Sports">Sports</option>
              <option value="Clubs">Clubs</option>
              <option value="Important">Important</option>
              <option value="Other">Other</option>
            </select>
          </div>
          
          <div class="form-group">
            <label class="form-label">Title *</label>
            <input type="text" name="title" class="form-input" placeholder="Enter bulletin title" required>
          </div>
          
          <div class="form-group">
            <label class="form-label">Content *</label>
            <textarea name="content" class="form-textarea" placeholder="Write your bulletin content here..." required></textarea>
          </div>
          
          <div class="form-group">
            <label class="form-label">Your Name *</label>
            <input type="text" name="author_name" class="form-input" placeholder="Enter your name" required>
          </div>
          
          <div class="form-group">
            <label class="form-label">Your Role *</label>
            <select name="author_role" class="form-select" required>
              <option value="admin">Admin</option>
              <option value="teacher">Teacher</option>
            </select>
          </div>
          
          <button type="submit" name="submit_bulletin" class="submit-btn">Post Bulletin</button>
        </form>
      </div>
    </div>
    <?php endif; ?>
  </div>

  <script>
// Enhanced modal functions with debugging
function openBulletinModal() {
    console.log('openBulletinModal function called');
    
    const modal = document.getElementById('bulletinModal');
    console.log('Modal element found:', modal);
    
    if (modal) {
        modal.style.display = 'flex';
        console.log('Modal display set to flex');
        
        // Add a visual indicator that modal opened
        modal.style.border = '5px solid #00ff00';
        setTimeout(() => {
            modal.style.border = 'none';
        }, 1000);
    } else {
        console.error('Modal element not found!');
        alert('Modal not found. Check console for details.');
    }
}

function closeBulletinModal() {
    console.log('closeBulletinModal function called');
    const modal = document.getElementById('bulletinModal');
    if (modal) {
        modal.style.display = 'none';
    }
}

// Close modal when clicking outside
window.onclick = function(event) {
    const modal = document.getElementById('bulletinModal');
    console.log('Window click event, target:', event.target);
    console.log('Modal element:', modal);
    
    if (event.target === modal) {
        console.log('Clicked outside modal - closing');
        closeBulletinModal();
    }
}

// Test if button is working
document.addEventListener('DOMContentLoaded', function() {
    console.log('DOM loaded - checking admin button');
    
    const newBulletinBtn = document.querySelector('.new-bulletin-btn');
    console.log('New Bulletin Button found:', newBulletinBtn);
    
    if (newBulletinBtn) {
        // Add click event listener as backup
        newBulletinBtn.addEventListener('click', function(e) {
            console.log('Button clicked via event listener');
            openBulletinModal();
        });
        
        // Test if button is visible and clickable
        console.log('Button styles:', window.getComputedStyle(newBulletinBtn));
    }
    
    // Test modal directly
    console.log('Modal element on load:', document.getElementById('bulletinModal'));
});

// Delete confirmation
function confirmDelete(bulletinId) {
    if (confirm('Are you sure you want to delete this bulletin? This action cannot be undone.')) {
        window.location.href = 'bulletin.php?delete_bulletin=' + bulletinId;
    }
}

// Auto-hide messages after 5 seconds
setTimeout(function() {
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
        alert.style.opacity = '0';
        alert.style.transition = 'opacity 0.5s ease';
        setTimeout(() => alert.remove(), 500);
    });
}, 5000);

// Enhanced search functionality
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.querySelector('.search-input');
    const filterSelects = document.querySelectorAll('.filter-select');
    
    // Add real-time search if needed (optional enhancement)
    if (searchInput) {
        searchInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                this.closest('form').submit();
            }
        });
    }
    
    // Auto-submit on filter change (optional enhancement)
    filterSelects.forEach(select => {
        select.addEventListener('change', function() {
            // Only auto-submit if it's not the category filter in the modal
            if (!this.closest('.modal-content')) {
                this.closest('form').submit();
            }
        });
    });
});
  </script>
</body>
</html>