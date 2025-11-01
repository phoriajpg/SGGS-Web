<?php
session_start();
include 'db_config.php';

// Check if user is admin
$is_admin = isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'admin';

// Handle new question submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['submit_question'])) {
        $category_id = intval($_POST['category_id']);
        $title = trim($_POST['title']);
        $content = trim($_POST['content']);
        $author_name = trim($_POST['author_name']);
        $author_email = trim($_POST['author_email']);
        
        if (!empty($title) && !empty($content) && !empty($author_name)) {
            $stmt = $pdo->prepare("INSERT INTO forum_questions (category_id, title, content, author_name, author_email) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$category_id, $title, $content, $author_name, $author_email]);
            
            $success_message = "Your question has been posted successfully!";
        } else {
            $error_message = "Please fill in all required fields.";
        }
    }
    
    // Admin-only actions
    if ($is_admin) {
        // Handle question deletion
        if (isset($_POST['delete_question'])) {
            $question_id = intval($_POST['question_id']);
            $stmt = $pdo->prepare("DELETE FROM forum_questions WHERE id = ?");
            $stmt->execute([$question_id]);
            $success_message = "Question deleted successfully!";
        }
        
        // Handle question featuring
        if (isset($_POST['toggle_featured'])) {
            $question_id = intval($_POST['question_id']);
            $stmt = $pdo->prepare("UPDATE forum_questions SET is_featured = NOT is_featured WHERE id = ?");
            $stmt->execute([$question_id]);
            $success_message = "Question featured status updated!";
        }
        
        // Handle question resolution
        if (isset($_POST['toggle_resolved'])) {
            $question_id = intval($_POST['question_id']);
            $stmt = $pdo->prepare("UPDATE forum_questions SET is_resolved = NOT is_resolved WHERE id = ?");
            $stmt->execute([$question_id]);
            $success_message = "Question resolution status updated!";
        }
        
        // Handle category management
        if (isset($_POST['add_category'])) {
            $category_name = trim($_POST['category_name']);
            $category_color = $_POST['category_color'];
            
            if (!empty($category_name)) {
                $stmt = $pdo->prepare("INSERT INTO forum_categories (name, color) VALUES (?, ?)");
                $stmt->execute([$category_name, $category_color]);
                $success_message = "Category added successfully!";
            }
        }
        
        if (isset($_POST['delete_category'])) {
            $category_id = intval($_POST['category_id']);
            // First update questions to default category
            $stmt = $pdo->prepare("UPDATE forum_questions SET category_id = 1 WHERE category_id = ?");
            $stmt->execute([$category_id]);
            // Then delete category
            $stmt = $pdo->prepare("DELETE FROM forum_categories WHERE id = ?");
            $stmt->execute([$category_id]);
            $success_message = "Category deleted successfully!";
        }
    }
}

// Handle filters and search
$search_query = '';
$category_filter = '';
$sort_by = 'newest';

if ($_SERVER['REQUEST_METHOD'] == 'GET') {
    $search_query = isset($_GET['search']) ? trim($_GET['search']) : '';
    $category_filter = isset($_GET['category']) ? intval($_GET['category']) : '';
    $sort_by = isset($_GET['sort']) ? $_GET['sort'] : 'newest';
}

// Build query for questions
$sql = "SELECT fq.*, fc.name as category_name, fc.color as category_color 
        FROM forum_questions fq 
        LEFT JOIN forum_categories fc ON fq.category_id = fc.id 
        WHERE 1=1";
    
$params = [];
    
if (!empty($search_query)) {
    $sql .= " AND (fq.title LIKE ? OR fq.content LIKE ?)";
    $search_term = "%$search_query%";
    $params[] = $search_term;
    $params[] = $search_term;
}
    
if (!empty($category_filter)) {
    $sql .= " AND fq.category_id = ?";
    $params[] = $category_filter;
}

// Add sorting
switch ($sort_by) {
    case 'popular':
        $sql .= " ORDER BY fq.views DESC, fq.replies_count DESC";
        break;
    case 'unanswered':
        $sql .= " ORDER BY fq.replies_count ASC, fq.created_at DESC";
        break;
    case 'featured':
        $sql .= " ORDER BY fq.is_featured DESC, fq.created_at DESC";
        break;
    default: // newest
        $sql .= " ORDER BY fq.created_at DESC";
        break;
}

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$questions = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get all categories for filter dropdown
$categories_stmt = $pdo->query("SELECT * FROM forum_categories ORDER BY name");
$categories = $categories_stmt->fetchAll(PDO::FETCH_ASSOC);

// Get popular categories for sidebar
$popular_categories_stmt = $pdo->query("
    SELECT fc.*, COUNT(fq.id) as question_count 
    FROM forum_categories fc 
    LEFT JOIN forum_questions fq ON fc.id = fq.category_id 
    GROUP BY fc.id 
    ORDER BY question_count DESC 
    LIMIT 6
");
$popular_categories = $popular_categories_stmt->fetchAll(PDO::FETCH_ASSOC);

// Get recent activity for sidebar
$recent_activity_stmt = $pdo->query("
    SELECT fq.id, fq.title, fq.created_at, 'question' as type 
    FROM forum_questions fq 
    UNION ALL 
    SELECT fr.question_id as id, fq.title, fr.created_at, 'reply' as type 
    FROM forum_replies fr 
    JOIN forum_questions fq ON fr.question_id = fq.id 
    ORDER BY created_at DESC 
    LIMIT 8
");
$recent_activity = $recent_activity_stmt->fetchAll(PDO::FETCH_ASSOC);

// Get forum statistics for admin
if ($is_admin) {
    $stats_stmt = $pdo->query("
        SELECT 
            COUNT(*) as total_questions,
            SUM(replies_count) as total_replies,
            SUM(views) as total_views,
            COUNT(CASE WHEN is_featured = 1 THEN 1 END) as featured_questions,
            COUNT(CASE WHEN is_resolved = 1 THEN 1 END) as resolved_questions
        FROM forum_questions
    ");
    $forum_stats = $stats_stmt->fetch(PDO::FETCH_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <link href="https://fonts.googleapis.com/css2?family=Gabarito:wght@400..900&display=swap" rel="stylesheet" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
  <title>SGGSWeb - <?php echo $is_admin ? 'Admin Q&A' : 'Q&A Forum'; ?></title>
  <style>
    /* ========== Base Styles ========== */
    :root {
      --admin-color: #dc3545;
      --admin-hover: #c82333;
    }

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

    /* ========== Forum Container ========== */
    .forum-container {
      max-width: 1400px;
      margin: 0 auto;
      padding: 0 20px;
      display: grid;
      grid-template-columns: 1fr 350px;
      gap: 30px;
    }

    /* ========== NEW HEADER STYLES (from awards page) ========== */
    .forum-header {
      text-align: center;
      margin-bottom: 40px;
      grid-column: 1 / -1;
    }

    .forum-header h1 {
      font-size: 3.2rem;
      color: #B10023;
      margin-bottom: 10px;
    }

    .forum-header p {
      font-size: 1.3rem;
      color: #555;
      max-width: 800px;
      margin: 0 auto;
    }

    /* ========== Admin Header Styles ========== */
    <?php if ($is_admin): ?>
    .forum-header {
      background: none;
      color: inherit;
      padding: 0;
      border-radius: 0;
      margin-bottom: 40px;
    }

    .forum-header h1 {
      color: #B10023;
      font-size: 3.2rem;
    }

    .forum-header p {
      color: #555;
      font-size: 1.3rem;
    }

    .admin-stats-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
      gap: 20px;
      margin-top: 30px;
    }

    .admin-stat-card {
      background: white;
      padding: 20px;
      border-radius: 10px;
      text-align: center;
      box-shadow: 0 2px 8px rgba(0,0,0,0.1);
      border-left: 4px solid #B10023;
    }

    .admin-stat-number {
      font-size: 2.5rem;
      font-weight: bold;
      margin-bottom: 5px;
      color: #B10023;
    }

    .admin-stat-label {
      font-size: 0.9rem;
      color: #666;
    }
    <?php endif; ?>

    /* ========== Forum Actions ========== */
    .forum-actions {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 25px;
      background: white;
      padding: 20px;
      border-radius: 12px;
      box-shadow: 0 2px 8px rgba(0,0,0,0.1);
      grid-column: 1 / -1;
    }

    .ask-question-btn {
      background: #B10023;
      color: white;
      padding: 12px 24px;
      border: none;
      border-radius: 8px;
      font-family: 'Gabarito', sans-serif;
      font-size: 1rem;
      font-weight: 600;
      cursor: pointer;
      transition: background 0.3s;
      white-space: nowrap;
    }

    .ask-question-btn:hover {
      background: #830000;
    }

    /* ========== Admin Action Buttons ========== */
    <?php if ($is_admin): ?>
    .admin-actions {
      display: flex;
      gap: 15px;
      margin-left: auto;
    }

    .admin-btn {
      background: var(--admin-color);
      color: white;
      padding: 10px 20px;
      border: none;
      border-radius: 6px;
      font-family: 'Gabarito', sans-serif;
      font-size: 0.9rem;
      font-weight: 600;
      cursor: pointer;
      transition: all 0.3s;
      display: flex;
      align-items: center;
      gap: 8px;
    }

    .admin-btn:hover {
      background: var(--admin-hover);
      transform: translateY(-1px);
    }

    .admin-btn.secondary {
      background: #6c757d;
    }

    .admin-btn.secondary:hover {
      background: #545b62;
    }
    <?php endif; ?>

    .forum-filters {
      display: flex;
      gap: 15px;
      align-items: center;
      flex-wrap: wrap;
    }

    .search-input {
      padding: 8px 15px;
      border: 2px solid #ddd;
      border-radius: 6px;
      font-family: 'Gabarito', sans-serif;
      font-size: 1rem;
      width: 200px;
    }

    .filter-select {
      padding: 8px 15px;
      border: 2px solid #ddd;
      border-radius: 6px;
      font-family: 'Gabarito', sans-serif;
      background: white;
      cursor: pointer;
      min-width: 150px;
    }

    .search-btn {
      background: #B10023;
      color: white;
      padding: 8px 15px;
      border: none;
      border-radius: 6px;
      font-family: 'Gabarito', sans-serif;
      cursor: pointer;
      transition: background 0.3s;
    }

    .search-btn:hover {
      background: #830000;
    }

    /* ========== Questions List ========== */
    .questions-section {
      grid-column: 1;
    }

    .questions-list {
      background: white;
      border-radius: 12px;
      box-shadow: 0 2px 8px rgba(0,0,0,0.1);
      overflow: hidden;
    }

    .question-item {
      padding: 25px;
      border-bottom: 1px solid #eee;
      transition: all 0.3s ease;
      cursor: pointer;
      display: block;
      text-decoration: none;
      color: inherit;
      position: relative;
    }

    .question-item:hover {
      background: #f8f9fa;
      transform: translateY(-2px);
      box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    }

    .question-item:last-child {
      border-bottom: none;
    }

    .question-header {
      display: flex;
      justify-content: space-between;
      align-items: flex-start;
      margin-bottom: 15px;
      gap: 15px;
    }

    .question-title {
      font-size: 1.3rem;
      font-weight: 600;
      color: #333;
      margin: 0;
      flex: 1;
      line-height: 1.4;
    }

    .question-meta {
      display: flex;
      flex-wrap: wrap;
      gap: 10px;
      align-items: center;
    }

    .question-category {
      background: var(--category-color);
      color: white;
      padding: 4px 12px;
      border-radius: 20px;
      font-size: 0.8rem;
      font-weight: 500;
      white-space: nowrap;
    }

    .question-badges {
      display: flex;
      gap: 8px;
    }

    .resolved-badge {
      background: #28a745;
      color: white;
      padding: 4px 10px;
      border-radius: 12px;
      font-size: 0.75rem;
      font-weight: 600;
      display: flex;
      align-items: center;
      gap: 4px;
    }

    .featured-badge {
      background: #ffc107;
      color: #333;
      padding: 4px 10px;
      border-radius: 12px;
      font-size: 0.75rem;
      font-weight: 600;
      display: flex;
      align-items: center;
      gap: 4px;
    }

    .question-content {
      margin-bottom: 15px;
    }

    .question-preview {
      color: #555;
      line-height: 1.6;
      font-size: 1rem;
      display: -webkit-box;
      -webkit-line-clamp: 3;
      -webkit-box-orient: vertical;
      overflow: hidden;
    }

    .question-stats {
      display: flex;
      gap: 20px;
      align-items: center;
      flex-wrap: wrap;
    }

    .stat {
      display: flex;
      align-items: center;
      gap: 6px;
      color: #666;
      font-size: 0.9rem;
    }

    .stat i {
      width: 16px;
      text-align: center;
    }

    .author-info {
      display: flex;
      align-items: center;
      gap: 8px;
      color: #666;
      font-size: 0.9rem;
    }

    .author-avatar {
      width: 24px;
      height: 24px;
      background: #B10023;
      color: white;
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 0.7rem;
      font-weight: bold;
    }

    /* ========== Admin Question Controls ========== */
    <?php if ($is_admin): ?>
    .admin-question-controls {
      position: absolute;
      top: 20px;
      right: 20px;
      display: flex;
      gap: 8px;
      opacity: 0;
      transition: opacity 0.3s;
    }

    .question-item:hover .admin-question-controls {
      opacity: 1;
    }

    .control-btn {
      background: #f8f9fa;
      border: 1px solid #dee2e6;
      border-radius: 4px;
      padding: 6px 10px;
      cursor: pointer;
      transition: all 0.3s;
      font-size: 0.8rem;
      display: flex;
      align-items: center;
      gap: 4px;
    }

    .control-btn:hover {
      background: #e9ecef;
      transform: translateY(-1px);
    }

    .control-btn.delete {
      color: #dc3545;
      border-color: #dc3545;
    }

    .control-btn.delete:hover {
      background: #dc3545;
      color: white;
    }

    .control-btn.feature {
      color: #ffc107;
      border-color: #ffc107;
    }

    .control-btn.feature:hover {
      background: #ffc107;
      color: #333;
    }

    .control-btn.resolve {
      color: #28a745;
      border-color: #28a745;
    }

    .control-btn.resolve:hover {
      background: #28a745;
      color: white;
    }
    <?php endif; ?>

    .no-questions {
      text-align: center;
      padding: 60px 20px;
      background: white;
      border-radius: 12px;
      box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    }

    .no-questions h3 {
      color: #666;
      margin-bottom: 15px;
      font-size: 1.5rem;
    }

    .no-questions p {
      color: #888;
      margin-bottom: 25px;
      font-size: 1.1rem;
      line-height: 1.5;
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
      margin-bottom: 20px;
      font-size: 1.3rem;
      border-bottom: 2px solid #B10023;
      padding-bottom: 10px;
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
      padding: 12px 0;
      border-bottom: 1px solid #eee;
    }

    .category-item:last-child {
      border-bottom: none;
    }

    .category-name {
      display: flex;
      align-items: center;
      gap: 8px;
      color: #333;
      text-decoration: none;
      font-weight: 500;
    }

    .category-color {
      width: 12px;
      height: 12px;
      border-radius: 50%;
    }

    .category-count {
      background: #f8f9fa;
      color: #666;
      padding: 2px 8px;
      border-radius: 12px;
      font-size: 0.8rem;
    }

    /* ========== Admin Category Controls ========== */
    <?php if ($is_admin): ?>
    .admin-category-controls {
      display: flex;
      gap: 8px;
      margin-left: 10px;
    }

    .category-control-btn {
      background: none;
      border: none;
      cursor: pointer;
      padding: 4px;
      border-radius: 3px;
      transition: background 0.3s;
      font-size: 0.7rem;
    }

    .category-control-btn:hover {
      background: #f8f9fa;
    }

    .category-control-btn.delete {
      color: #dc3545;
    }
    <?php endif; ?>

    .activity-list {
      list-style: none;
      padding: 0;
      margin: 0;
    }

    .activity-item {
      padding: 10px 0;
      border-bottom: 1px solid #eee;
    }

    .activity-item:last-child {
      border-bottom: none;
    }

    .activity-text {
      color: #333;
      font-size: 0.9rem;
      line-height: 1.4;
    }

    .activity-time {
      color: #666;
      font-size: 0.8rem;
      margin-top: 5px;
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
      padding: 30px;
      border-radius: 12px;
      width: 90%;
      max-width: 600px;
      max-height: 90vh;
      overflow-y: auto;
    }

    .modal-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 25px;
    }

    .modal-title {
      font-size: 1.5rem;
      color: #B10023;
      margin: 0;
    }

    .close-modal {
      background: none;
      border: none;
      font-size: 1.5rem;
      cursor: pointer;
      color: #666;
    }

    .form-group {
      margin-bottom: 20px;
    }

    .form-label {
      display: block;
      margin-bottom: 8px;
      font-weight: 600;
      color: #333;
    }

    .form-input, .form-select, .form-textarea {
      width: 100%;
      padding: 12px;
      border: 2px solid #ddd;
      border-radius: 6px;
      font-family: 'Gabarito', sans-serif;
      font-size: 1rem;
      box-sizing: border-box;
    }

    .form-textarea {
      min-height: 120px;
      resize: vertical;
    }

    .form-input:focus, .form-select:focus, .form-textarea:focus {
      outline: none;
      border-color: #B10023;
    }

    .color-input {
      width: 60px !important;
      height: 40px;
      padding: 2px !important;
    }

    .submit-btn {
      background: #B10023;
      color: white;
      padding: 12px 30px;
      border: none;
      border-radius: 6px;
      font-family: 'Gabarito', sans-serif;
      font-size: 1rem;
      font-weight: 600;
      cursor: pointer;
      transition: background 0.3s;
    }

    .submit-btn:hover {
      background: #830000;
    }

    /* ========== Messages ========== */
    .alert {
      padding: 15px;
      border-radius: 6px;
      margin-bottom: 20px;
      grid-column: 1 / -1;
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

    /* ========== Responsive Design ========== */
    @media (max-width: 968px) {
      .forum-container {
        grid-template-columns: 1fr;
        gap: 25px;
      }
      
      .forum-actions {
        flex-direction: column;
        gap: 15px;
        align-items: stretch;
      }
      
      .forum-filters {
        justify-content: center;
      }
      
      .questions-section {
        grid-column: 1;
      }
      
      <?php if ($is_admin): ?>
      .admin-actions {
        margin-left: 0;
        justify-content: center;
      }
      
      .admin-question-controls {
        position: static;
        opacity: 1;
        margin-top: 15px;
        justify-content: flex-end;
      }
      <?php endif; ?>
    }

    @media (max-width: 768px) {
      .forum-header h1 {
        font-size: 2.5rem;
      }
      
      .question-header {
        flex-direction: column;
        gap: 10px;
      }
      
      .question-meta {
        align-self: flex-start;
      }
      
      .question-stats {
        flex-wrap: wrap;
        gap: 10px;
      }
      
      .forum-filters {
        flex-direction: column;
        gap: 10px;
      }
      
      .search-input, .filter-select {
        width: 100%;
      }
      
      .question-title {
        font-size: 1.1rem;
      }
      
      .question-preview {
        -webkit-line-clamp: 2;
      }
      
      <?php if ($is_admin): ?>
      .admin-stats-grid {
        grid-template-columns: 1fr 1fr;
      }
      <?php endif; ?>
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
            <a href="parent.html"><span class="label">Home</span></a>
            <a href="qna.php"><span class="label">Q&A</span></a>
            <a href="faq.php"><span class="label">FAQ</span></a>
            <a href="academics.php"><span class="label">Academics</span></a>
          <?php endif; ?>
          <a href="logout.php"><span class="label">Log Out</span></a>
        </div>
      </div>
    </nav>

    <!-- Main Content -->
    <div class="main-content">
      <div class="forum-container">
        <!-- NEW HEADER (matching awards page style) -->
        <div class="forum-header">
          <h1><?php echo $is_admin ? 'Admin Q&A Forum' : 'SGGS Q&A Forum'; ?></h1>
          <p><?php echo $is_admin ? 'Manage questions, categories, and forum content' : 'Ask questions, share knowledge, and connect with the SGGS community'; ?></p>
        </div>

        <!-- Messages -->
        <?php if (isset($success_message)): ?>
          <div class="alert alert-success"><?php echo $success_message; ?></div>
        <?php endif; ?>
        
        <?php if (isset($error_message)): ?>
          <div class="alert alert-error"><?php echo $error_message; ?></div>
        <?php endif; ?>

        <!-- Forum Actions -->
        <div class="forum-actions">
          <button class="ask-question-btn" onclick="openQuestionModal()">
            <i class="fas fa-plus"></i> Ask a Question
          </button>
          
          <?php if ($is_admin): ?>
          <div class="admin-actions">
            <button class="admin-btn secondary" onclick="openCategoryModal()">
              <i class="fas fa-tags"></i> Manage Categories
            </button>
            <button class="admin-btn" onclick="openBulkActionsModal()">
              <i class="fas fa-cog"></i> Bulk Actions
            </button>
          </div>
          <?php endif; ?>
          
          <div class="forum-filters">
            <form method="GET" action="qna.php" style="display: flex; gap: 10px; align-items: center; flex-wrap: wrap;">
              <input type="text" name="search" placeholder="Search questions..." class="search-input" 
                     value="<?php echo htmlspecialchars($search_query); ?>">
              
              <select name="category" class="filter-select">
                <option value="">All Categories</option>
                <?php foreach ($categories as $category): ?>
                  <option value="<?php echo $category['id']; ?>" 
                          <?php echo ($category_filter == $category['id']) ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($category['name']); ?>
                  </option>
                <?php endforeach; ?>
              </select>
              
              <select name="sort" class="filter-select">
                <option value="newest" <?php echo ($sort_by == 'newest') ? 'selected' : ''; ?>>Newest</option>
                <option value="popular" <?php echo ($sort_by == 'popular') ? 'selected' : ''; ?>>Most Popular</option>
                <option value="unanswered" <?php echo ($sort_by == 'unanswered') ? 'selected' : ''; ?>>Unanswered</option>
                <option value="featured" <?php echo ($sort_by == 'featured') ? 'selected' : ''; ?>>Featured</option>
              </select>
              
              <button type="submit" class="search-btn">
                <i class="fas fa-search"></i> Search
              </button>
            </form>
          </div>
        </div>

        <!-- Questions List -->
        <div class="questions-section">
          <?php if (empty($questions)): ?>
            <div class="no-questions">
              <h3>No questions found</h3>
              <p>Try adjusting your search terms or be the first to ask a question in our community!</p>
              <button class="ask-question-btn" onclick="openQuestionModal()">
                Ask First Question
              </button>
            </div>
          <?php else: ?>
            <div class="questions-list">
              <?php foreach ($questions as $question): ?>
                <a href="question.php?id=<?php echo $question['id']; ?>" class="question-item">
                  <?php if ($is_admin): ?>
                  <div class="admin-question-controls">
                    <form method="POST" action="qna.php" style="display: inline;" onsubmit="return confirmAction('delete')">
                      <input type="hidden" name="question_id" value="<?php echo $question['id']; ?>">
                      <button type="submit" name="delete_question" class="control-btn delete" title="Delete Question">
                        <i class="fas fa-trash"></i>
                      </button>
                    </form>
                    <form method="POST" action="qna.php" style="display: inline;">
                      <input type="hidden" name="question_id" value="<?php echo $question['id']; ?>">
                      <button type="submit" name="toggle_featured" class="control-btn feature" title="<?php echo $question['is_featured'] ? 'Unfeature' : 'Feature'; ?>">
                        <i class="fas fa-star"></i>
                      </button>
                    </form>
                    <form method="POST" action="qna.php" style="display: inline;">
                      <input type="hidden" name="question_id" value="<?php echo $question['id']; ?>">
                      <button type="submit" name="toggle_resolved" class="control-btn resolve" title="<?php echo $question['is_resolved'] ? 'Mark Unresolved' : 'Mark Resolved'; ?>">
                        <i class="fas fa-check"></i>
                      </button>
                    </form>
                  </div>
                  <?php endif; ?>
                  
                  <div class="question-header">
                    <h3 class="question-title">
                      <?php echo htmlspecialchars($question['title']); ?>
                    </h3>
                    <div class="question-meta">
                      <span class="question-category" style="--category-color: <?php echo $question['category_color']; ?>">
                        <?php echo htmlspecialchars($question['category_name']); ?>
                      </span>
                      <div class="question-badges">
                        <?php if ($question['is_resolved']): ?>
                          <span class="resolved-badge">
                            <i class="fas fa-check"></i> Resolved
                          </span>
                        <?php endif; ?>
                        <?php if ($question['is_featured']): ?>
                          <span class="featured-badge">
                            <i class="fas fa-star"></i> Featured
                          </span>
                        <?php endif; ?>
                      </div>
                    </div>
                  </div>
                  
                  <div class="question-content">
                    <div class="question-preview">
                      <?php echo nl2br(htmlspecialchars($question['content'])); ?>
                    </div>
                  </div>
                  
                  <div class="question-stats">
                    <div class="stat">
                      <i class="fas fa-eye"></i>
                      <span><?php echo $question['views']; ?> views</span>
                    </div>
                    <div class="stat">
                      <i class="fas fa-comments"></i>
                      <span><?php echo $question['replies_count']; ?> answers</span>
                    </div>
                    <div class="author-info">
                      <div class="author-avatar">
                        <?php echo strtoupper(substr($question['author_name'], 0, 1)); ?>
                      </div>
                      <span><?php echo htmlspecialchars($question['author_name']); ?></span>
                    </div>
                    <div class="stat">
                      <i class="fas fa-clock"></i>
                      <span><?php echo date('M j, Y', strtotime($question['created_at'])); ?></span>
                    </div>
                  </div>
                </a>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>
        </div>

        <!-- Sidebar -->
        <div class="sidebar">
          <!-- Popular Categories -->
          <div class="sidebar-widget">
            <h3 class="widget-title">Popular Categories</h3>
            <ul class="categories-list">
              <?php foreach ($popular_categories as $category): ?>
                <li class="category-item">
                  <a href="qna.php?category=<?php echo $category['id']; ?>" class="category-name">
                    <span class="category-color" style="background: <?php echo $category['color']; ?>"></span>
                    <?php echo htmlspecialchars($category['name']); ?>
                  </a>
                  <span class="category-count"><?php echo $category['question_count']; ?></span>
                  <?php if ($is_admin): ?>
                  <div class="admin-category-controls">
                    <form method="POST" action="qna.php" style="display: inline;" onsubmit="return confirmAction('delete category')">
                      <input type="hidden" name="category_id" value="<?php echo $category['id']; ?>">
                      <button type="submit" name="delete_category" class="category-control-btn delete" title="Delete Category">
                        <i class="fas fa-trash"></i>
                      </button>
                    </form>
                  </div>
                  <?php endif; ?>
                </li>
              <?php endforeach; ?>
            </ul>
          </div>

          <!-- Recent Activity -->
          <div class="sidebar-widget">
            <h3 class="widget-title">Recent Activity</h3>
            <ul class="activity-list">
              <?php foreach ($recent_activity as $activity): ?>
                <li class="activity-item">
                  <div class="activity-text">
                    <?php if ($activity['type'] == 'question'): ?>
                      <i class="fas fa-question-circle" style="color: #B10023;"></i>
                      New question: "<?php echo htmlspecialchars($activity['title']); ?>"
                    <?php else: ?>
                      <i class="fas fa-reply" style="color: #28a745;"></i>
                      New reply to: "<?php echo htmlspecialchars($activity['title']); ?>"
                    <?php endif; ?>
                  </div>
                  <div class="activity-time">
                    <?php echo date('M j, g:i A', strtotime($activity['created_at'])); ?>
                  </div>
                </li>
              <?php endforeach; ?>
            </ul>
          </div>

          <!-- Forum Stats -->
          <div class="sidebar-widget">
            <h3 class="widget-title">Forum Statistics</h3>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
              <div style="text-align: center; padding: 15px; background: #f8f9fa; border-radius: 8px;">
                <div style="font-size: 1.5rem; font-weight: bold; color: #B10023;">
                  <?php echo count($questions); ?>
                </div>
                <div style="font-size: 0.9rem; color: #666;">Questions</div>
              </div>
              <div style="text-align: center; padding: 15px; background: #f8f9fa; border-radius: 8px;">
                <div style="font-size: 1.5rem; font-weight: bold; color: #28a745;">
                  <?php 
                  $total_replies = array_sum(array_column($questions, 'replies_count'));
                  echo $total_replies > 0 ? $total_replies : '0'; 
                  ?>
                </div>
                <div style="font-size: 0.9rem; color: #666;">Answers</div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Ask Question Modal -->
    <div id="questionModal" class="modal">
      <div class="modal-content">
        <div class="modal-header">
          <h3 class="modal-title">Ask a Question</h3>
          <button class="close-modal" onclick="closeQuestionModal()">&times;</button>
        </div>
        
        <form method="POST" action="qna.php">
          <div class="form-group">
            <label class="form-label">Category *</label>
            <select name="category_id" class="form-select" required>
              <option value="">Select a category</option>
              <?php foreach ($categories as $category): ?>
                <option value="<?php echo $category['id']; ?>">
                  <?php echo htmlspecialchars($category['name']); ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
          
          <div class="form-group">
            <label class="form-label">Question Title *</label>
            <input type="text" name="title" class="form-input" placeholder="Enter your question title" required>
          </div>
          
          <div class="form-group">
            <label class="form-label">Your Question *</label>
            <textarea name="content" class="form-textarea" placeholder="Describe your question in detail..." required></textarea>
          </div>
          
          <div class="form-group">
            <label class="form-label">Your Name *</label>
            <input type="text" name="author_name" class="form-input" placeholder="Enter your name" required>
          </div>
          
          <div class="form-group">
            <label class="form-label">Email Address</label>
            <input type="email" name="author_email" class="form-input" placeholder="Enter your email (optional)">
          </div>
          
          <button type="submit" name="submit_question" class="submit-btn">Post Question</button>
        </form>
      </div>
    </div>

    <!-- Admin Modals -->
    <?php if ($is_admin): ?>
    <!-- Category Management Modal -->
    <div id="categoryModal" class="modal">
      <div class="modal-content">
        <div class="modal-header">
          <h3 class="modal-title">Manage Categories</h3>
          <button class="close-modal" onclick="closeCategoryModal()">&times;</button>
        </div>
        
        <div class="form-group">
          <h4>Add New Category</h4>
          <form method="POST" action="qna.php" style="display: flex; gap: 10px; align-items: end;">
            <div style="flex: 1;">
              <label class="form-label">Category Name</label>
              <input type="text" name="category_name" class="form-input" placeholder="Enter category name" required>
            </div>
            <div>
              <label class="form-label">Color</label>
              <input type="color" name="category_color" class="form-input color-input" value="#B10023" required>
            </div>
            <div>
              <button type="submit" name="add_category" class="submit-btn">Add</button>
            </div>
          </form>
        </div>
        
        <div class="form-group">
          <h4>Existing Categories</h4>
          <ul class="categories-list">
            <?php foreach ($categories as $category): ?>
              <li class="category-item">
                <div class="category-name">
                  <span class="category-color" style="background: <?php echo $category['color']; ?>"></span>
                  <?php echo htmlspecialchars($category['name']); ?>
                </div>
                <span class="category-count">
                  <?php 
                  $count_stmt = $pdo->prepare("SELECT COUNT(*) FROM forum_questions WHERE category_id = ?");
                  $count_stmt->execute([$category['id']]);
                  echo $count_stmt->fetchColumn();
                  ?>
                </span>
                <form method="POST" action="qna.php" style="display: inline;" onsubmit="return confirmAction('delete category')">
                  <input type="hidden" name="category_id" value="<?php echo $category['id']; ?>">
                  <button type="submit" name="delete_category" class="category-control-btn delete" title="Delete Category">
                    <i class="fas fa-trash"></i>
                  </button>
                </form>
              </li>
            <?php endforeach; ?>
          </ul>
        </div>
      </div>
    </div>

    <!-- Bulk Actions Modal -->
    <div id="bulkActionsModal" class="modal">
      <div class="modal-content">
        <div class="modal-header">
          <h3 class="modal-title">Bulk Actions</h3>
          <button class="close-modal" onclick="closeBulkActionsModal()">&times;</button>
        </div>
        
        <div class="form-group">
          <p>Select multiple questions to perform bulk actions:</p>
          <div style="display: flex; gap: 10px; flex-wrap: wrap;">
            <button class="admin-btn secondary" onclick="bulkFeature()">
              <i class="fas fa-star"></i> Feature Selected
            </button>
            <button class="admin-btn secondary" onclick="bulkUnfeature()">
              <i class="fas fa-star"></i> Unfeature Selected
            </button>
            <button class="admin-btn secondary" onclick="bulkResolve()">
              <i class="fas fa-check"></i> Mark Resolved
            </button>
            <button class="admin-btn" onclick="bulkDelete()">
              <i class="fas fa-trash"></i> Delete Selected
            </button>
          </div>
        </div>
        
        <div class="form-group">
          <h4>Quick Stats</h4>
          <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
            <div style="text-align: center; padding: 15px; background: #f8f9fa; border-radius: 8px;">
              <div style="font-size: 1.2rem; font-weight: bold; color: #B10023;">
                <?php echo $forum_stats['unresolved_questions'] = $forum_stats['total_questions'] - $forum_stats['resolved_questions']; ?>
              </div>
              <div style="font-size: 0.8rem; color: #666;">Unresolved</div>
            </div>
            <div style="text-align: center; padding: 15px; background: #f8f9fa; border-radius: 8px;">
              <div style="font-size: 1.2rem; font-weight: bold; color: #ffc107;">
                <?php echo $forum_stats['total_questions'] - $forum_stats['featured_questions']; ?>
              </div>
              <div style="font-size: 0.8rem; color: #666;">Not Featured</div>
            </div>
          </div>
        </div>
      </div>
    </div>
    <?php endif; ?>
  </div>

  <script>
    // Modal functions
    function openQuestionModal() {
      document.getElementById('questionModal').style.display = 'flex';
    }

    function closeQuestionModal() {
      document.getElementById('questionModal').style.display = 'none';
    }

    <?php if ($is_admin): ?>
    function openCategoryModal() {
      document.getElementById('categoryModal').style.display = 'flex';
    }

    function closeCategoryModal() {
      document.getElementById('categoryModal').style.display = 'none';
    }

    function openBulkActionsModal() {
      document.getElementById('bulkActionsModal').style.display = 'flex';
    }

    function closeBulkActionsModal() {
      document.getElementById('bulkActionsModal').style.display = 'none';
    }

    function confirmAction(action) {
      return confirm(`Are you sure you want to ${action}? This action cannot be undone.`);
    }

    function bulkFeature() {
      alert('Bulk feature functionality would be implemented here');
      closeBulkActionsModal();
    }

    function bulkUnfeature() {
      alert('Bulk unfeature functionality would be implemented here');
      closeBulkActionsModal();
    }

    function bulkResolve() {
      alert('Bulk resolve functionality would be implemented here');
      closeBulkActionsModal();
    }

    function bulkDelete() {
      if (confirm('Are you sure you want to delete the selected questions? This action cannot be undone.')) {
        alert('Bulk delete functionality would be implemented here');
        closeBulkActionsModal();
      }
    }
    <?php endif; ?>

    // Close modal when clicking outside
    window.onclick = function(event) {
      const modals = ['questionModal', 'categoryModal', 'bulkActionsModal'];
      modals.forEach(modalId => {
        const modal = document.getElementById(modalId);
        if (event.target === modal) {
          modal.style.display = 'none';
        }
      });
    }

    // Auto-close success message after 5 seconds
    <?php if (isset($success_message)): ?>
      setTimeout(() => {
        const alert = document.querySelector('.alert-success');
        if (alert) alert.style.display = 'none';
      }, 5000);
    <?php endif; ?>
  </script>
</body>
</html>