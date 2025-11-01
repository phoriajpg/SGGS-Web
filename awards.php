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

// Initialize user_id
$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 1;

// Handle new award submission (admin only)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit_award']) && $is_admin) {
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $category = $_POST['category'];
    $recipient_name = trim($_POST['recipient_name']);
    $recipient_type = $_POST['recipient_type'];
    $award_date = $_POST['award_date'];
    $presented_by = trim($_POST['presented_by']);
    
    if (!empty($title) && !empty($recipient_name) && !empty($award_date)) {
        $stmt = $conn->prepare("INSERT INTO awards (title, description, category, recipient_name, recipient_type, award_date, presented_by, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())");
        $stmt->bind_param("sssssss", $title, $description, $category, $recipient_name, $recipient_type, $award_date, $presented_by);
        
        if ($stmt->execute()) {
            $success_message = "Award added successfully!";
        } else {
            $error_message = "Error adding award: " . $conn->error;
        }
        $stmt->close();
    } else {
        $error_message = "Please fill in all required fields.";
    }
}

// Handle award deletion (admin only)
if (isset($_GET['delete_award']) && $is_admin) {
    $award_id = intval($_GET['delete_award']);
    
    $delete_stmt = $conn->prepare("DELETE FROM awards WHERE id = ?");
    $delete_stmt->bind_param("i", $award_id);
    
    if ($delete_stmt->execute()) {
        $success_message = "Award deleted successfully!";
    } else {
        $error_message = "Error deleting award: " . $conn->error;
    }
    $delete_stmt->close();
    
    // Redirect to avoid resubmission
    header("Location: awards.php");
    exit();
}

// Handle filters and search
$search_query = '';
$category_filter = '';
$recipient_filter = '';
$year_filter = '';
$sort_by = 'recent';

if ($_SERVER['REQUEST_METHOD'] == 'GET') {
    $search_query = isset($_GET['search']) ? trim($_GET['search']) : '';
    $category_filter = isset($_GET['category']) ? $_GET['category'] : '';
    $recipient_filter = isset($_GET['recipient_type']) ? $_GET['recipient_type'] : '';
    $year_filter = isset($_GET['year']) ? $_GET['year'] : '';
    $sort_by = isset($_GET['sort']) ? $_GET['sort'] : 'recent';
}

// Build query for awards
$sql = "SELECT * FROM awards WHERE 1=1";
$params = [];
$types = "";

if (!empty($search_query)) {
    $sql .= " AND (title LIKE ? OR description LIKE ? OR recipient_name LIKE ? OR presented_by LIKE ?)";
    $search_term = "%$search_query%";
    $params[] = $search_term;
    $params[] = $search_term;
    $params[] = $search_term;
    $params[] = $search_term;
    $types .= "ssss";
}

if (!empty($category_filter)) {
    $sql .= " AND category = ?";
    $params[] = $category_filter;
    $types .= "s";
}

if (!empty($recipient_filter)) {
    $sql .= " AND recipient_type = ?";
    $params[] = $recipient_filter;
    $types .= "s";
}

if (!empty($year_filter)) {
    $sql .= " AND YEAR(award_date) = ?";
    $params[] = $year_filter;
    $types .= "s";
}

// Add sorting
switch ($sort_by) {
    case 'oldest':
        $sql .= " ORDER BY award_date ASC";
        break;
    case 'title':
        $sql .= " ORDER BY title ASC";
        break;
    case 'recipient':
        $sql .= " ORDER BY recipient_name ASC";
        break;
    default: // recent
        $sql .= " ORDER BY award_date DESC, created_at DESC";
        break;
}

$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();
$awards = $result->fetch_all(MYSQLI_ASSOC);

// Get unique categories for filter
$categories_result = $conn->query("SELECT DISTINCT category FROM awards ORDER BY category");
$categories = $categories_result->fetch_all(MYSQLI_ASSOC);

// Get unique years for filter
$years_result = $conn->query("SELECT DISTINCT YEAR(award_date) as year FROM awards ORDER BY year DESC");
$years = $years_result->fetch_all(MYSQLI_ASSOC);

// Get recent awards for sidebar
$recent_stmt = $conn->query("SELECT id, title, recipient_name, award_date FROM awards ORDER BY award_date DESC, created_at DESC LIMIT 5");
$recent_awards = $recent_stmt->fetch_all(MYSQLI_ASSOC);

// Get award statistics
$stats_stmt = $conn->query("SELECT 
    COUNT(*) as total_awards,
    COUNT(DISTINCT recipient_name) as total_recipients,
    COUNT(DISTINCT category) as total_categories,
    COUNT(DISTINCT YEAR(award_date)) as total_years
    FROM awards");
$stats = $stats_stmt->fetch_assoc();

// Get top recipients
$top_recipients_stmt = $conn->query("SELECT recipient_name, COUNT(*) as award_count FROM awards GROUP BY recipient_name ORDER BY award_count DESC LIMIT 5");
$top_recipients = $top_recipients_stmt->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <link href="https://fonts.googleapis.com/css2?family=Gabarito:wght@400..900&display=swap" rel="stylesheet" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
  <title>SGGSWeb - Awards & Achievements</title>
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

    /* ========== Awards Container ========== */
    .awards-container {
      max-width: 1800px;
      margin: 0 auto;
      padding: 0 20px;
      display: grid;
      grid-template-columns: 1fr 350px;
      gap: 30px;
    }

    .awards-header {
      text-align: center;
      margin-bottom: 40px;
      grid-column: 1 / -1;
    }

    .awards-header h1 {
      font-size: 3.2rem;
      color: #B10023;
      margin-bottom: 10px;
    }

    .awards-header p {
      font-size: 1.3rem;
      color: #555;
      max-width: 800px;
      margin: 0 auto;
    }

    /* ========== Awards Actions ========== */
    .awards-actions {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 25px;
      background: white;
      padding: 25px;
      border-radius: 12px;
      box-shadow: 0 2px 8px rgba(0,0,0,0.1);
      grid-column: 1 / -1;
    }

    .new-award-btn {
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

    .new-award-btn:hover {
      background: #830000;
    }

    .awards-filters {
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
      width: 240px;
    }

    .filter-select {
      padding: 10px 18px;
      border: 2px solid #ddd;
      border-radius: 6px;
      font-family: 'Gabarito', sans-serif;
      background: white;
      cursor: pointer;
      min-width: 150px;
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

    /* ========== Awards Grid ========== */
    .awards-section {
      grid-column: 1;
    }

    .awards-grid {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(500px, 1fr));
      gap: 25px;
    }

    .award-card {
      background: white;
      border-radius: 12px;
      box-shadow: 0 2px 8px rgba(0,0,0,0.1);
      overflow: hidden;
      transition: all 0.3s ease;
      position: relative;
      min-height: 280px;
    }

    .award-card:hover {
      transform: translateY(-5px);
      box-shadow: 0 8px 25px rgba(0,0,0,0.15);
    }

    .award-header {
      padding: 25px 25px 15px;
      border-bottom: 1px solid #eee;
      background: linear-gradient(135deg, #B10023, #830000);
      color: white;
    }

    .award-title {
      font-size: 1.5rem;
      font-weight: 600;
      margin: 0 0 12px 0;
      line-height: 1.4;
    }

    .award-meta {
      display: flex;
      flex-wrap: wrap;
      gap: 10px;
      align-items: center;
    }

    .award-category {
      background: rgba(255,255,255,0.2);
      color: white;
      padding: 6px 14px;
      border-radius: 20px;
      font-size: 0.9rem;
      font-weight: 500;
      white-space: nowrap;
      backdrop-filter: blur(10px);
    }

    .award-date {
      background: rgba(255,255,255,0.2);
      color: white;
      padding: 6px 14px;
      border-radius: 20px;
      font-size: 0.9rem;
      font-weight: 500;
      backdrop-filter: blur(10px);
    }

    .award-body {
      padding: 20px 25px;
    }

    .award-description {
      margin-bottom: 20px;
      line-height: 1.6;
      color: #555;
      font-size: 1.1rem;
    }

    .award-details {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 15px;
      margin-bottom: 20px;
    }

    .award-detail {
      display: flex;
      align-items: center;
      gap: 10px;
      color: #666;
      font-size: 1rem;
    }

    .award-detail i {
      width: 18px;
      text-align: center;
      color: #B10023;
    }

    .recipient-type {
      display: inline-block;
      padding: 4px 10px;
      border-radius: 12px;
      font-size: 0.8rem;
      font-weight: 600;
      text-transform: uppercase;
    }

    .type-student { background: #007bff; color: white; }
    .type-faculty { background: #28a745; color: white; }
    .type-staff { background: #6c757d; color: white; }
    .type-department { background: #ffc107; color: black; }
    .type-team { background: #17a2b8; color: white; }

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

    .no-awards {
      text-align: center;
      padding: 80px 30px;
      background: white;
      border-radius: 12px;
      box-shadow: 0 2px 8px rgba(0,0,0,0.1);
      grid-column: 1;
    }

    .no-awards h3 {
      color: #666;
      margin-bottom: 20px;
      font-size: 1.7rem;
    }

    .no-awards p {
      color: #888;
      margin-bottom: 30px;
      font-size: 1.2rem;
      line-height: 1.6;
    }

    /* ========== Sidebar ========== */
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
      margin-bottom: 5px;
    }

    .recent-recipient {
      color: #666;
      font-size: 0.9rem;
      font-weight: 500;
    }

    .recipients-list {
      list-style: none;
      padding: 0;
      margin: 0;
    }

    .recipient-item {
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding: 12px 0;
      border-bottom: 1px solid #eee;
    }

    .recipient-item:last-child {
      border-bottom: none;
    }

    .recipient-name {
      color: #333;
      font-weight: 500;
      font-size: 1rem;
    }

    .award-count {
      background: #B10023;
      color: white;
      padding: 4px 10px;
      border-radius: 12px;
      font-size: 0.9rem;
      font-weight: 600;
    }

    /* ========== Modal Styles ========== */
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
      min-height: 120px;
      resize: vertical;
    }

    .form-row {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 20px;
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

    /* ========== Stats ========== */
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
      .awards-container {
        max-width: 95vw;
        padding: 0 25px;
      }
      
      .awards-grid {
        grid-template-columns: repeat(auto-fill, minmax(450px, 1fr));
      }
    }

    @media (max-width: 1200px) {
      .awards-container {
        max-width: 98vw;
        padding: 0 20px;
      }
      
      .awards-grid {
        grid-template-columns: repeat(auto-fill, minmax(420px, 1fr));
      }
      
      .search-input {
        width: 220px;
      }
    }

    @media (max-width: 968px) {
      .awards-container {
        grid-template-columns: 1fr;
        gap: 25px;
        padding: 0 15px;
      }
      
      .awards-actions {
        flex-direction: column;
        gap: 20px;
        align-items: stretch;
      }
      
      .awards-filters {
        justify-content: center;
      }
      
      .awards-section {
        grid-column: 1;
      }
      
      .awards-grid {
        grid-template-columns: 1fr;
      }
    }

    @media (max-width: 768px) {
      .awards-header h1 {
        font-size: 2.7rem;
      }
      
      .awards-filters {
        flex-direction: column;
        gap: 12px;
      }
      
      .search-input, .filter-select {
        width: 100%;
      }
      
      .form-row {
        grid-template-columns: 1fr;
      }
      
      .award-details {
        grid-template-columns: 1fr;
      }
      
      .awards-container {
        padding: 0 15px;
      }
      
      .admin-controls {
        position: static;
        margin-top: 15px;
        justify-content: flex-end;
      }
    }

    @media (max-width: 480px) {
      .awards-container {
        padding: 0 10px;
      }
      
      .award-header,
      .award-body {
        padding: 20px;
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
      <div class="awards-container">
        <!-- Header -->
        <div class="awards-header">
          <h1>Awards & Achievements</h1>
          <p>Celebrating excellence and recognizing outstanding accomplishments in our community</p>
        </div>

        <!-- Messages -->
        <?php if (isset($success_message)): ?>
          <div class="alert alert-success"><?php echo $success_message; ?></div>
        <?php endif; ?>
        
        <?php if (isset($error_message)): ?>
          <div class="alert alert-error"><?php echo $error_message; ?></div>
        <?php endif; ?>

        <!-- Awards Actions -->
        <div class="awards-actions">
          <?php if ($is_admin): ?>
            <button class="new-award-btn" onclick="openAwardModal()">
              <i class="fas fa-trophy"></i> Add New Award
            </button>
          <?php else: ?>
          <?php endif; ?>
          
          <div class="awards-filters">
            <form method="GET" action="awards.php" style="display: flex; gap: 10px; align-items: center; flex-wrap: wrap;">
              <input type="text" name="search" placeholder="Search awards..." class="search-input" 
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
              
              <select name="recipient_type" class="filter-select">
                <option value="">All Types</option>
                <option value="student" <?php echo ($recipient_filter == 'student') ? 'selected' : ''; ?>>Student</option>
                <option value="faculty" <?php echo ($recipient_filter == 'faculty') ? 'selected' : ''; ?>>Faculty</option>
                <option value="staff" <?php echo ($recipient_filter == 'staff') ? 'selected' : ''; ?>>Staff</option>
                <option value="department" <?php echo ($recipient_filter == 'department') ? 'selected' : ''; ?>>Department</option>
                <option value="team" <?php echo ($recipient_filter == 'team') ? 'selected' : ''; ?>>Team</option>
              </select>
              
              <select name="year" class="filter-select">
                <option value="">All Years</option>
                <?php foreach ($years as $year): ?>
                  <option value="<?php echo htmlspecialchars($year['year']); ?>" 
                          <?php echo ($year_filter == $year['year']) ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($year['year']); ?>
                  </option>
                <?php endforeach; ?>
              </select>
              
              <select name="sort" class="filter-select">
                <option value="recent" <?php echo ($sort_by == 'recent') ? 'selected' : ''; ?>>Most Recent</option>
                <option value="oldest" <?php echo ($sort_by == 'oldest') ? 'selected' : ''; ?>>Oldest First</option>
                <option value="title" <?php echo ($sort_by == 'title') ? 'selected' : ''; ?>>By Title</option>
                <option value="recipient" <?php echo ($sort_by == 'recipient') ? 'selected' : ''; ?>>By Recipient</option>
              </select>
              
              <button type="submit" class="search-btn">
                <i class="fas fa-search"></i> Search
              </button>
            </form>
          </div>
        </div>

        <!-- Awards List -->
        <div class="awards-section">
          <?php if (empty($awards)): ?>
            <div class="no-awards">
              <h3>No awards found</h3>
              <p>Try adjusting your search terms or check back later for new achievements!</p>
              <?php if ($is_admin): ?>
                <button class="new-award-btn" onclick="openAwardModal()">
                  Add First Award
                </button>
              <?php endif; ?>
            </div>
          <?php else: ?>
            <div class="awards-grid">
              <?php foreach ($awards as $award): ?>
                <div class="award-card">
                  <?php if ($is_admin): ?>
                  <div class="admin-controls">
                    <button class="delete-btn" onclick="confirmDelete(<?php echo $award['id']; ?>)">
                      <i class="fas fa-trash"></i> Delete
                    </button>
                  </div>
                  <?php endif; ?>
                  
                  <div class="award-header">
                    <h3 class="award-title">
                      <?php echo htmlspecialchars($award['title']); ?>
                    </h3>
                    <div class="award-meta">
                      <span class="award-category">
                        <?php echo htmlspecialchars($award['category']); ?>
                      </span>
                      <span class="award-date">
                        <i class="fas fa-calendar"></i>
                        <?php echo date('M j, Y', strtotime($award['award_date'])); ?>
                      </span>
                    </div>
                  </div>
                  
                  <div class="award-body">
                    <?php if (!empty($award['description'])): ?>
                      <div class="award-description">
                        <?php echo nl2br(htmlspecialchars($award['description'])); ?>
                      </div>
                    <?php endif; ?>
                    
                    <div class="award-details">
                      <div class="award-detail">
                        <i class="fas fa-user"></i>
                        <span><strong>Recipient:</strong> <?php echo htmlspecialchars($award['recipient_name']); ?></span>
                      </div>
                      
                      <div class="award-detail">
                        <i class="fas fa-tag"></i>
                        <span><strong>Type:</strong> 
                          <span class="recipient-type type-<?php echo $award['recipient_type']; ?>">
                            <?php echo ucfirst($award['recipient_type']); ?>
                          </span>
                        </span>
                      </div>
                      
                      <?php if (!empty($award['presented_by'])): ?>
                      <div class="award-detail">
                        <i class="fas fa-handshake"></i>
                        <span><strong>Presented by:</strong> <?php echo htmlspecialchars($award['presented_by']); ?></span>
                      </div>
                      <?php endif; ?>
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
            <h3 class="widget-title">Award Categories</h3>
            <ul class="categories-list">
              <?php foreach ($categories as $category): ?>
                <li class="category-item">
                  <a href="awards.php?category=<?php echo urlencode($category['category']); ?>" class="category-name">
                    <?php echo htmlspecialchars($category['category']); ?>
                  </a>
                  <span class="category-count">
                    <?php 
                    $count_stmt = $conn->prepare("SELECT COUNT(*) FROM awards WHERE category = ?");
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

          <!-- Recent Awards -->
          <div class="sidebar-widget">
            <h3 class="widget-title">Recent Awards</h3>
            <ul class="recent-list">
              <?php foreach ($recent_awards as $recent): ?>
                <li class="recent-item">
                  <div class="recent-text">
                    <?php echo htmlspecialchars($recent['title']); ?>
                  </div>
                  <div class="recent-recipient">
                    <?php echo htmlspecialchars($recent['recipient_name']); ?>
                  </div>
                  <div style="color: #666; font-size: 0.9rem; margin-top: 5px;">
                    <?php echo date('M j, Y', strtotime($recent['award_date'])); ?>
                  </div>
                </li>
              <?php endforeach; ?>
            </ul>
          </div>

          <!-- Top Recipients -->
          <div class="sidebar-widget">
            <h3 class="widget-title">Top Achievers</h3>
            <ul class="recipients-list">
              <?php foreach ($top_recipients as $recipient): ?>
                <li class="recipient-item">
                  <span class="recipient-name"><?php echo htmlspecialchars($recipient['recipient_name']); ?></span>
                  <span class="award-count"><?php echo $recipient['award_count']; ?></span>
                </li>
              <?php endforeach; ?>
            </ul>
          </div>

          <!-- Awards Stats -->
          <div class="sidebar-widget">
            <h3 class="widget-title">Awards Overview</h3>
            <div class="stats-grid">
              <div class="stat-item">
                <div class="stat-number"><?php echo $stats['total_awards']; ?></div>
                <div class="stat-label">Total Awards</div>
              </div>
              <div class="stat-item">
                <div class="stat-number"><?php echo $stats['total_recipients']; ?></div>
                <div class="stat-label">Recipients</div>
              </div>
              <div class="stat-item">
                <div class="stat-number"><?php echo $stats['total_categories']; ?></div>
                <div class="stat-label">Categories</div>
              </div>
              <div class="stat-item">
                <div class="stat-number"><?php echo $stats['total_years']; ?></div>
                <div class="stat-label">Years</div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- New Award Modal (Admin Only) -->
    <?php if ($is_admin): ?>
    <div id="awardModal" class="modal">
      <div class="modal-content">
        <div class="modal-header">
          <h3 class="modal-title">Add New Award</h3>
          <button class="close-modal" onclick="closeAwardModal()">&times;</button>
        </div>
        
        <form method="POST" action="awards.php">
          <div class="form-group">
            <label class="form-label">Award Title *</label>
            <input type="text" name="title" class="form-input" placeholder="Enter award title" required>
          </div>
          
          <div class="form-group">
            <label class="form-label">Description</label>
            <textarea name="description" class="form-textarea" placeholder="Describe the achievement..."></textarea>
          </div>
          
          <div class="form-row">
            <div class="form-group">
              <label class="form-label">Category *</label>
              <select name="category" class="form-select" required>
                <option value="">Select category</option>
                <option value="Academic Excellence">Academic Excellence</option>
                <option value="Sports">Sports</option>
                <option value="Arts & Culture">Arts & Culture</option>
                <option value="Research">Research</option>
                <option value="Community Service">Community Service</option>
                <option value="Leadership">Leadership</option>
                <option value="Innovation">Innovation</option>
                <option value="Teaching Excellence">Teaching Excellence</option>
                <option value="Staff Excellence">Staff Excellence</option>
                <option value="Other">Other</option>
              </select>
            </div>
            
            <div class="form-group">
              <label class="form-label">Award Date *</label>
              <input type="date" name="award_date" class="form-input" required>
            </div>
          </div>
          
          <div class="form-row">
            <div class="form-group">
              <label class="form-label">Recipient Name *</label>
              <input type="text" name="recipient_name" class="form-input" placeholder="Enter recipient name" required>
            </div>
            
            <div class="form-group">
              <label class="form-label">Recipient Type *</label>
              <select name="recipient_type" class="form-select" required>
                <option value="">Select type</option>
                <option value="student">Student</option>
                <option value="faculty">Faculty</option>
                <option value="staff">Staff</option>
                <option value="department">Department</option>
                <option value="team">Team</option>
              </select>
            </div>
          </div>
          
          <div class="form-group">
            <label class="form-label">Presented By</label>
            <input type="text" name="presented_by" class="form-input" placeholder="Organization or person presenting the award">
          </div>
          
          <button type="submit" name="submit_award" class="submit-btn">Add Award</button>
        </form>
      </div>
    </div>
    <?php endif; ?>
  </div>

  <script>
// Enhanced modal functions with debugging
function openAwardModal() {
    console.log('openAwardModal function called');
    
    const modal = document.getElementById('awardModal');
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

function closeAwardModal() {
    console.log('closeAwardModal function called');
    const modal = document.getElementById('awardModal');
    if (modal) {
        modal.style.display = 'none';
    }
}

// Close modal when clicking outside
window.onclick = function(event) {
    const modal = document.getElementById('awardModal');
    console.log('Window click event, target:', event.target);
    console.log('Modal element:', modal);
    
    if (event.target === modal) {
        console.log('Clicked outside modal - closing');
        closeAwardModal();
    }
}

// Test if button is working
document.addEventListener('DOMContentLoaded', function() {
    console.log('DOM loaded - checking admin button');
    
    const newAwardBtn = document.querySelector('.new-award-btn');
    console.log('New Award Button found:', newAwardBtn);
    
    if (newAwardBtn) {
        // Add click event listener as backup
        newAwardBtn.addEventListener('click', function(e) {
            console.log('Button clicked via event listener');
            openAwardModal();
        });
        
        // Test if button is visible and clickable
        console.log('Button styles:', window.getComputedStyle(newAwardBtn));
    }
    
    // Test modal directly
    console.log('Modal element on load:', document.getElementById('awardModal'));
});

// Delete confirmation
function confirmDelete(awardId) {
    if (confirm('Are you sure you want to delete this award? This action cannot be undone.')) {
        window.location.href = 'awards.php?delete_award=' + awardId;
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