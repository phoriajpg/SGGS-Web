<?php
session_start();
include 'db_config.php';

// Check if user is admin
$is_admin = isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'admin';

// Get question ID from URL
$question_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($question_id === 0) {
    header("Location: forum.php");
    exit();
}

try {
    // Get question details
    $question_stmt = $pdo->prepare("
        SELECT q.*, c.name as category_name, c.color as category_color
        FROM forum_questions q 
        LEFT JOIN forum_categories c ON q.category_id = c.id 
        WHERE q.id = ?
    ");
    $question_stmt->execute([$question_id]);
    $question = $question_stmt->fetch(PDO::FETCH_ASSOC);

    if (!$question) {
        $_SESSION['error'] = "Question not found.";
        header("Location: qna.php");
        exit();
    }

    // Increment view count
    $view_stmt = $pdo->prepare("UPDATE forum_questions SET views = views + 1 WHERE id = ?");
    $view_stmt->execute([$question_id]);

    // Get replies for this question
    $replies_stmt = $pdo->prepare("
        SELECT r.* 
        FROM forum_replies r 
        WHERE r.question_id = ? 
        ORDER BY r.is_official_answer DESC, r.created_at ASC
    ");
    $replies_stmt->execute([$question_id]);
    $replies = $replies_stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}

// Handle new reply submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['submit_reply'])) {
        $author_name = trim($_POST['author_name']);
        $author_role = $_POST['author_role'];
        $reply_content = trim($_POST['reply_content']);
        
        if (empty($author_name) || empty($reply_content)) {
            $_SESSION['error'] = "Please fill in all required fields.";
        } else {
            try {
                $insert_stmt = $pdo->prepare("
                    INSERT INTO forum_replies (question_id, content, author_name, author_role, created_at) 
                    VALUES (?, ?, ?, ?, NOW())
                ");
                $insert_stmt->execute([$question_id, $reply_content, $author_name, $author_role]);
                
                // Update replies count in the question
                $update_stmt = $pdo->prepare("UPDATE forum_questions SET replies_count = replies_count + 1 WHERE id = ?");
                $update_stmt->execute([$question_id]);
                
                $_SESSION['success'] = "Reply posted successfully!";
                header("Location: question.php?id=" . $question_id);
                exit();
            } catch (PDOException $e) {
                $_SESSION['error'] = "Error posting reply: " . $e->getMessage();
            }
        }
    }
    
    // Admin-only actions via POST
    if ($is_admin) {
        // Handle question deletion
        if (isset($_POST['delete_question'])) {
            try {
                // First delete all replies
                $delete_replies_stmt = $pdo->prepare("DELETE FROM forum_replies WHERE question_id = ?");
                $delete_replies_stmt->execute([$question_id]);
                
                // Then delete the question
                $delete_question_stmt = $pdo->prepare("DELETE FROM forum_questions WHERE id = ?");
                $delete_question_stmt->execute([$question_id]);
                
                $_SESSION['success'] = "Question and all replies deleted successfully!";
                header("Location: qna.php");
                exit();
            } catch (PDOException $e) {
                $_SESSION['error'] = "Error deleting question: " . $e->getMessage();
            }
        }
        
        // Handle reply deletion
        if (isset($_POST['delete_reply'])) {
            $reply_id = intval($_POST['reply_id']);
            
            try {
                $delete_stmt = $pdo->prepare("DELETE FROM forum_replies WHERE id = ?");
                $delete_stmt->execute([$reply_id]);
                
                // Update replies count
                $update_stmt = $pdo->prepare("UPDATE forum_questions SET replies_count = replies_count - 1 WHERE id = ?");
                $update_stmt->execute([$question_id]);
                
                $_SESSION['success'] = "Reply deleted successfully!";
                header("Location: question.php?id=" . $question_id);
                exit();
            } catch (PDOException $e) {
                $_SESSION['error'] = "Error deleting reply: " . $e->getMessage();
            }
        }
        
        // Handle category update
        if (isset($_POST['update_category'])) {
            $new_category_id = intval($_POST['category_id']);
            
            try {
                $update_stmt = $pdo->prepare("UPDATE forum_questions SET category_id = ? WHERE id = ?");
                $update_stmt->execute([$new_category_id, $question_id]);
                
                $_SESSION['success'] = "Question category updated successfully!";
                header("Location: question.php?id=" . $question_id);
                exit();
            } catch (PDOException $e) {
                $_SESSION['error'] = "Error updating category: " . $e->getMessage();
            }
        }
    }
}

// Handle GET actions
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // Handle marking as resolved
    if (isset($_GET['mark_resolved'])) {
        try {
            $resolve_stmt = $pdo->prepare("UPDATE forum_questions SET is_resolved = ? WHERE id = ?");
            $resolve_stmt->execute([1, $question_id]);
            
            $_SESSION['success'] = "Question marked as resolved!";
            header("Location: question.php?id=" . $question_id);
            exit();
        } catch (PDOException $e) {
            $_SESSION['error'] = "Error updating question: " . $e->getMessage();
        }
    }
    
    // Handle marking as unresolved
    if (isset($_GET['mark_unresolved'])) {
        try {
            $resolve_stmt = $pdo->prepare("UPDATE forum_questions SET is_resolved = ? WHERE id = ?");
            $resolve_stmt->execute([0, $question_id]);
            
            $_SESSION['success'] = "Question marked as unresolved!";
            header("Location: question.php?id=" . $question_id);
            exit();
        } catch (PDOException $e) {
            $_SESSION['error'] = "Error updating question: " . $e->getMessage();
        }
    }
    
    // Handle featuring
    if (isset($_GET['toggle_featured'])) {
        try {
            $feature_stmt = $pdo->prepare("UPDATE forum_questions SET is_featured = NOT is_featured WHERE id = ?");
            $feature_stmt->execute([$question_id]);
            
            $_SESSION['success'] = "Question featured status updated!";
            header("Location: question.php?id=" . $question_id);
            exit();
        } catch (PDOException $e) {
            $_SESSION['error'] = "Error updating question: " . $e->getMessage();
        }
    }
    
    // Handle marking official answer
    if (isset($_GET['mark_official'])) {
        $reply_id = intval($_GET['mark_official']);
        
        try {
            // First, unmark any previously official answers
            $unmark_stmt = $pdo->prepare("UPDATE forum_replies SET is_official_answer = 0 WHERE question_id = ?");
            $unmark_stmt->execute([$question_id]);
            
            // Then mark the selected reply as official
            $mark_stmt = $pdo->prepare("UPDATE forum_replies SET is_official_answer = 1 WHERE id = ?");
            $mark_stmt->execute([$reply_id]);
            
            $_SESSION['success'] = "Reply marked as official answer!";
            header("Location: question.php?id=" . $question_id);
            exit();
        } catch (PDOException $e) {
            $_SESSION['error'] = "Error updating reply: " . $e->getMessage();
        }
    }
    
    // Handle unmarking official answer
    if (isset($_GET['unmark_official'])) {
        try {
            $unmark_stmt = $pdo->prepare("UPDATE forum_replies SET is_official_answer = 0 WHERE question_id = ?");
            $unmark_stmt->execute([$question_id]);
            
            $_SESSION['success'] = "Official answer removed!";
            header("Location: question.php?id=" . $question_id);
            exit();
        } catch (PDOException $e) {
            $_SESSION['error'] = "Error updating reply: " . $e->getMessage();
        }
    }
}

// Get all categories for admin dropdown
if ($is_admin) {
    $categories_stmt = $pdo->query("SELECT * FROM forum_categories ORDER BY name");
    $all_categories = $categories_stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <link href="https://fonts.googleapis.com/css2?family=Gabarito:wght@400..900&display=swap" rel="stylesheet" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
  <title><?php echo htmlspecialchars($question['title']); ?> - SGGS Forum</title>
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
      grid-template-columns: 1fr;
      gap: 30px;
    }

    .forum-header {
      text-align: center;
      margin-bottom: 40px;
      grid-column: 1;
    }

    .forum-header h1 {
      font-size: 2.5rem;
      color: #B10023;
      margin-bottom: 10px;
    }

    /* ========== Admin Header Styles ========== */
    <?php if ($is_admin): ?>
    .forum-header {
      background: linear-gradient(135deg, #B10023, #dc3545);
      color: white;
      padding: 30px;
      border-radius: 15px;
      margin-bottom: 30px;
    }

    .forum-header h1 {
      color: white;
      font-size: 2.3rem;
    }

    .admin-quick-stats {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
      gap: 15px;
      margin-top: 25px;
    }

    .admin-quick-stat {
      background: rgba(255,255,255,0.1);
      padding: 15px;
      border-radius: 8px;
      text-align: center;
      backdrop-filter: blur(10px);
    }

    .admin-quick-number {
      font-size: 1.5rem;
      font-weight: bold;
      margin-bottom: 5px;
    }

    .admin-quick-label {
      font-size: 0.8rem;
      opacity: 0.9;
    }
    <?php endif; ?>

    /* ========== Question Card ========== */
    .question-card {
      background: white;
      border-radius: 12px;
      box-shadow: 0 2px 8px rgba(0,0,0,0.1);
      padding: 40px;
      margin-bottom: 30px;
      grid-column: 1;
      position: relative;
      <?php if ($is_admin): ?>display: flex; gap: 30px; align-items: flex-start;<?php endif; ?>
    }

    <?php if ($is_admin): ?>
    .question-content-area {
      flex: 1;
      min-width: 0;
    }
    <?php endif; ?>

    .question-header {
      display: flex;
      justify-content: space-between;
      align-items: flex-start;
      margin-bottom: 25px;
      gap: 20px;
    }

    .question-title {
      font-size: 2rem;
      font-weight: 600;
      color: #333;
      margin: 0;
      flex: 1;
      line-height: 1.4;
    }

    .question-meta {
      display: flex;
      flex-wrap: wrap;
      gap: 12px;
      align-items: center;
    }

    .question-category {
      background: var(--category-color);
      color: white;
      padding: 8px 18px;
      border-radius: 20px;
      font-size: 1rem;
      font-weight: 500;
      white-space: nowrap;
    }

    .question-badges {
      display: flex;
      gap: 10px;
    }

    .resolved-badge {
      background: #28a745;
      color: white;
      padding: 8px 15px;
      border-radius: 12px;
      font-size: 0.9rem;
      font-weight: 600;
      display: flex;
      align-items: center;
      gap: 6px;
    }

    .featured-badge {
      background: #ffc107;
      color: #333;
      padding: 8px 15px;
      border-radius: 12px;
      font-size: 0.9rem;
      font-weight: 600;
      display: flex;
      align-items: center;
      gap: 6px;
    }

    .question-content {
      margin-bottom: 30px;
      line-height: 1.7;
      color: #555;
      font-size: 1.2rem;
    }

    .question-stats {
      display: flex;
      gap: 30px;
      align-items: center;
      flex-wrap: wrap;
      margin-bottom: 25px;
      padding: 20px 0;
      border-top: 1px solid #eee;
      border-bottom: 1px solid #eee;
    }

    .stat {
      display: flex;
      align-items: center;
      gap: 10px;
      color: #666;
      font-size: 1.1rem;
    }

    .stat i {
      width: 18px;
      text-align: center;
    }

    .author-info {
      display: flex;
      align-items: center;
      gap: 12px;
      color: #666;
      font-size: 1.1rem;
    }

    .author-avatar {
      width: 32px;
      height: 32px;
      background: #B10023;
      color: white;
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 0.9rem;
      font-weight: bold;
    }

    /* ========== Admin Question Controls ========== */
    <?php if ($is_admin): ?>
    .admin-question-controls {
      flex: 0 0 300px;
      background: white;
      padding: 20px;
      border-radius: 8px;
      box-shadow: 0 2px 10px rgba(0,0,0,0.1);
      border: 1px solid #e9ecef;
      position: sticky;
      top: 120px;
    }

    .admin-control-group {
      display: flex;
      flex-direction: column;
      gap: 12px;
      margin-bottom: 20px;
    }

    .admin-control-title {
      font-size: 1rem;
      font-weight: 600;
      color: #333;
      margin-bottom: 8px;
      border-bottom: 2px solid #B10023;
      padding-bottom: 5px;
    }

    .admin-btn-group {
      display: flex;
      flex-direction: column;
      gap: 8px;
    }

    .admin-btn {
      background: var(--admin-color);
      color: white;
      border: none;
      border-radius: 6px;
      padding: 10px 15px;
      cursor: pointer;
      transition: all 0.3s;
      font-family: 'Gabarito', sans-serif;
      font-size: 0.9rem;
      font-weight: 600;
      display: flex;
      align-items: center;
      gap: 8px;
      text-decoration: none;
      text-align: center;
      justify-content: center;
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

    .admin-btn.success {
      background: #28a745;
    }

    .admin-btn.success:hover {
      background: #218838;
    }

    .admin-btn.warning {
      background: #ffc107;
      color: #333;
    }

    .admin-btn.warning:hover {
      background: #e0a800;
    }

    .admin-btn.danger {
      background: #dc3545;
    }

    .admin-btn.danger:hover {
      background: #c82333;
    }

    .admin-category-form {
      display: flex;
      flex-direction: column;
      gap: 10px;
    }

    .admin-select {
      padding: 8px 12px;
      border: 1px solid #ddd;
      border-radius: 4px;
      font-family: 'Gabarito', sans-serif;
      font-size: 0.9rem;
      width: 100%;
    }

    .admin-quick-actions {
      display: flex;
      flex-direction: column;
      gap: 8px;
    }

    .admin-quick-action {
      background: #f8f9fa;
      border: 1px solid #dee2e6;
      border-radius: 6px;
      padding: 10px 15px;
      font-size: 0.9rem;
      color: #495057;
      text-decoration: none;
      transition: all 0.3s;
      display: flex;
      align-items: center;
      gap: 8px;
    }

    .admin-quick-action:hover {
      background: #e9ecef;
      color: #495057;
      transform: translateY(-1px);
    }
    <?php endif; ?>

    .action-buttons {
      display: flex;
      gap: 20px;
      margin-top: 25px;
    }

    .btn {
      padding: 12px 24px;
      border: none;
      border-radius: 6px;
      font-family: 'Gabarito', sans-serif;
      font-size: 1.1rem;
      font-weight: 600;
      cursor: pointer;
      transition: all 0.3s ease;
      text-decoration: none;
      display: inline-flex;
      align-items: center;
      gap: 10px;
    }

    .btn-primary {
      background: #B10023;
      color: white;
    }

    .btn-primary:hover {
      background: #830000;
      transform: translateY(-2px);
    }

    .btn-outline {
      background: transparent;
      color: #B10023;
      border: 2px solid #B10023;
    }

    .btn-outline:hover {
      background: #B10023;
      color: white;
      transform: translateY(-2px);
    }

    /* ========== Replies Section ========== */
    .replies-section {
      grid-column: 1;
    }

    .section-title {
      font-size: 1.8rem;
      color: #B10023;
      margin-bottom: 25px;
      border-bottom: 2px solid #B10023;
      padding-bottom: 12px;
    }

    .replies-list {
      background: white;
      border-radius: 12px;
      box-shadow: 0 2px 8px rgba(0,0,0,0.1);
      overflow: hidden;
      margin-bottom: 30px;
    }

    .reply-item {
      padding: 30px;
      border-bottom: 1px solid #eee;
      transition: all 0.3s ease;
      position: relative;
    }

    .reply-item:hover {
      background: #f8f9fa;
    }

    .reply-item:last-child {
      border-bottom: none;
    }

    .official-answer {
      border-left: 4px solid #28a745;
      background: #f8fff8;
    }

    .reply-content {
      line-height: 1.7;
      color: #555;
      font-size: 1.1rem;
      margin-bottom: 20px;
    }

    .reply-meta {
      display: flex;
      justify-content: space-between;
      align-items: center;
      flex-wrap: wrap;
      gap: 20px;
    }

    .reply-author {
      display: flex;
      align-items: center;
      gap: 12px;
    }

    .author-role {
      padding: 6px 14px;
      border-radius: 15px;
      font-size: 0.9rem;
      font-weight: 600;
      text-transform: capitalize;
    }

    .role-student { background: #dc3545; color: white; }
    .role-teacher { background: #007bff; color: white; }
    .role-parent { background: #fd7e14; color: white; }
    .role-admin { background: #28a745; color: white; }

    .reply-time {
      color: #666;
      font-size: 1rem;
    }

    .official-badge {
      background: #28a745;
      color: white;
      padding: 8px 15px;
      border-radius: 12px;
      font-size: 0.9rem;
      font-weight: 600;
      display: flex;
      align-items: center;
      gap: 6px;
    }

    /* ========== Admin Reply Controls ========== */
    <?php if ($is_admin): ?>
    .admin-reply-controls {
      position: absolute;
      top: 20px;
      right: 20px;
      display: flex;
      gap: 8px;
      opacity: 0;
      transition: opacity 0.3s;
    }

    .reply-item:hover .admin-reply-controls {
      opacity: 1;
    }

    .reply-control-btn {
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
      text-decoration: none;
      color: inherit;
    }

    .reply-control-btn:hover {
      background: #e9ecef;
      transform: translateY(-1px);
    }

    .reply-control-btn.delete {
      color: #dc3545;
      border-color: #dc3545;
    }

    .reply-control-btn.delete:hover {
      background: #dc3545;
      color: white;
    }

    .reply-control-btn.official {
      color: #28a745;
      border-color: #28a745;
    }

    .reply-control-btn.official:hover {
      background: #28a745;
      color: white;
    }

    .reply-control-btn.unofficial {
      color: #6c757d;
      border-color: #6c757d;
    }

    .reply-control-btn.unofficial:hover {
      background: #6c757d;
      color: white;
    }
    <?php endif; ?>

    .no-replies {
      text-align: center;
      padding: 80px 40px;
      background: white;
      border-radius: 12px;
      box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    }

    .no-replies h3 {
      color: #666;
      margin-bottom: 20px;
      font-size: 1.8rem;
    }

    .no-replies p {
      color: #888;
      margin-bottom: 30px;
      font-size: 1.2rem;
      line-height: 1.6;
    }

    /* ========== Reply Form ========== */
    .reply-form {
      background: white;
      border-radius: 12px;
      box-shadow: 0 2px 8px rgba(0,0,0,0.1);
      padding: 40px;
      margin-top: 30px;
      grid-column: 1;
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
      padding: 15px;
      border: 2px solid #ddd;
      border-radius: 6px;
      font-family: 'Gabarito', sans-serif;
      font-size: 1.1rem;
      box-sizing: border-box;
    }

    .form-textarea {
      min-height: 200px;
      resize: vertical;
      line-height: 1.6;
    }

    .form-input:focus, .form-select:focus, .form-textarea:focus {
      outline: none;
      border-color: #B10023;
    }

    .form-row {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 20px;
    }

    .submit-btn {
      background: #B10023;
      color: white;
      padding: 15px 35px;
      border: none;
      border-radius: 6px;
      font-family: 'Gabarito', sans-serif;
      font-size: 1.2rem;
      font-weight: 600;
      cursor: pointer;
      transition: background 0.3s;
    }

    .submit-btn:hover {
      background: #830000;
    }

    /* ========== Messages ========== */
    .alert {
      padding: 20px;
      border-radius: 6px;
      margin-bottom: 30px;
      grid-column: 1;
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

    /* ========== Responsive Design ========== */
    @media (max-width: 1200px) {
      .forum-container {
        max-width: 100%;
        padding: 0 30px;
      }
    }

    @media (max-width: 968px) {
      .forum-container {
        grid-template-columns: 1fr;
        gap: 25px;
      }
      
      .question-header {
        flex-direction: column;
        gap: 20px;
      }
      
      .question-meta {
        align-self: flex-start;
      }
      
      .form-row {
        grid-template-columns: 1fr;
      }
      
      .question-card,
      .reply-form {
        padding: 30px;
      }
      
      <?php if ($is_admin): ?>
      .question-card {
        flex-direction: column;
      }
      
      .admin-question-controls {
        flex: none;
        width: 100%;
        position: static;
      }
      
      .admin-btn-group {
        flex-direction: row;
        flex-wrap: wrap;
      }
      
      .admin-btn {
        flex: 1;
        min-width: 120px;
      }
      
      .admin-reply-controls {
        position: static;
        opacity: 1;
        margin-top: 15px;
        justify-content: flex-end;
      }
      <?php endif; ?>
    }

    @media (max-width: 768px) {
      .forum-header h1 {
        font-size: 2rem;
      }
      
      .question-title {
        font-size: 1.6rem;
      }
      
      .question-content {
        font-size: 1.1rem;
      }
      
      .question-stats {
        flex-direction: column;
        align-items: flex-start;
        gap: 15px;
      }
      
      .reply-meta {
        flex-direction: column;
        align-items: flex-start;
        gap: 15px;
      }
      
      .action-buttons {
        flex-direction: column;
        align-items: flex-start;
      }
      
      .btn {
        width: 100%;
        justify-content: center;
      }
      
      .forum-container {
        padding: 0 20px;
      }
      
      .question-card,
      .reply-form {
        padding: 25px;
      }
      
      <?php if ($is_admin): ?>
      .admin-quick-stats {
        grid-template-columns: 1fr 1fr;
      }
      
      .admin-btn-group {
        flex-direction: column;
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
        <!-- Header -->

        <!-- Messages -->
        <?php if (isset($_SESSION['success'])): ?>
          <div class="alert alert-success"><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></div>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['error'])): ?>
          <div class="alert alert-error"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></div>
        <?php endif; ?>

        <!-- Question Card -->
        <div class="question-card <?php echo $question['is_resolved'] ? 'resolved-question' : ''; ?>">
          <?php if ($is_admin): ?>
          <!-- Question Content Area -->
          <div class="question-content-area">
          <?php endif; ?>
          
            <div class="question-header">
              <h1 class="question-title">
                <?php echo htmlspecialchars($question['title']); ?>
              </h1>
              <div class="question-meta">
                <?php if ($question['category_name']): ?>
                  <span class="question-category" style="--category-color: <?php echo $question['category_color']; ?>">
                    <?php echo htmlspecialchars($question['category_name']); ?>
                  </span>
                <?php endif; ?>
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
              <?php echo nl2br(htmlspecialchars($question['content'])); ?>
            </div>
            
            <div class="question-stats">
              <div class="stat">
                <i class="fas fa-eye"></i>
                <span><?php echo $question['views'] + 1; ?> views</span>
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
                <span><?php echo date('M j, Y g:i A', strtotime($question['created_at'])); ?></span>
              </div>
            </div>

            <?php if (!$is_admin): ?>
              <div class="action-buttons">
                <a href="qna.php" class="btn btn-outline">
                  <i class="fas fa-arrow-left"></i> Back to Forum
                </a>
              </div>
            <?php endif; ?>
          
          <?php if ($is_admin): ?>
          </div>
          <!-- Admin Question Controls Panel -->
          <div class="admin-question-controls">
            <div class="admin-control-group">
              <div class="admin-control-title">Question Actions</div>
              <div class="admin-btn-group">
                <?php if ($question['is_resolved']): ?>
                  <a href="?id=<?php echo $question_id; ?>&mark_unresolved" class="admin-btn warning">
                    <i class="fas fa-times"></i> Unresolve
                  </a>
                <?php else: ?>
                  <a href="?id=<?php echo $question_id; ?>&mark_resolved" class="admin-btn success">
                    <i class="fas fa-check"></i> Resolve
                  </a>
                <?php endif; ?>
                
                <a href="?id=<?php echo $question_id; ?>&toggle_featured" class="admin-btn <?php echo $question['is_featured'] ? 'secondary' : 'warning'; ?>">
                  <i class="fas fa-star"></i> <?php echo $question['is_featured'] ? 'Unfeature' : 'Feature'; ?>
                </a>
                
                <form method="POST" action="question.php?id=<?php echo $question_id; ?>" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this question and all its replies? This action cannot be undone.')">
                  <button type="submit" name="delete_question" class="admin-btn danger">
                    <i class="fas fa-trash"></i> Delete Question
                  </button>
                </form>
              </div>
            </div>
            
            <div class="admin-control-group">
              <div class="admin-control-title">Change Category</div>
              <form method="POST" action="question.php?id=<?php echo $question_id; ?>" class="admin-category-form">
                <select name="category_id" class="admin-select" required>
                  <?php foreach ($all_categories as $category): ?>
                    <option value="<?php echo $category['id']; ?>" <?php echo $category['id'] == $question['category_id'] ? 'selected' : ''; ?>>
                      <?php echo htmlspecialchars($category['name']); ?>
                    </option>
                  <?php endforeach; ?>
                </select>
                <button type="submit" name="update_category" class="admin-btn">
                  <i class="fas fa-sync"></i> Update Category
                </button>
              </form>
            </div>
            
            <div class="admin-control-group">
              <div class="admin-control-title">Quick Links</div>
              <div class="admin-quick-actions">
                <a href="qna.php" class="admin-quick-action">
                  <i class="fas fa-arrow-left"></i> Back to Forum
                </a>
                <a href="admin_dashboard.php" class="admin-quick-action">
                  <i class="fas fa-tachometer-alt"></i> Admin Dashboard
                </a>
              </div>
            </div>
          </div>
          <?php endif; ?>
        </div>

        <!-- Replies Section -->
        <div class="replies-section">
          <h3 class="section-title"><?php echo count($replies); ?> Answer(s)</h3>
          
          <?php if (empty($replies)): ?>
            <div class="no-replies">
              <h3>No answers yet</h3>
              <p>Be the first to answer this question and help the community!</p>
            </div>
          <?php else: ?>
            <div class="replies-list">
              <?php foreach ($replies as $reply): ?>
                <div class="reply-item <?php echo $reply['is_official_answer'] ? 'official-answer' : ''; ?>" id="reply-<?php echo $reply['id']; ?>">
                  <?php if ($is_admin): ?>
                  <!-- Admin Reply Controls -->
                  <div class="admin-reply-controls">
                    <?php if ($reply['is_official_answer']): ?>
                      <a href="?id=<?php echo $question_id; ?>&unmark_official" class="reply-control-btn unofficial" title="Remove Official Answer">
                        <i class="fas fa-times"></i>
                      </a>
                    <?php else: ?>
                      <a href="?id=<?php echo $question_id; ?>&mark_official=<?php echo $reply['id']; ?>" class="reply-control-btn official" title="Mark as Official Answer">
                        <i class="fas fa-check"></i>
                      </a>
                    <?php endif; ?>
                    
                    <form method="POST" action="question.php?id=<?php echo $question_id; ?>" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this reply? This action cannot be undone.')">
                      <input type="hidden" name="reply_id" value="<?php echo $reply['id']; ?>">
                      <button type="submit" name="delete_reply" class="reply-control-btn delete" title="Delete Reply">
                        <i class="fas fa-trash"></i>
                      </button>
                    </form>
                  </div>
                  <?php endif; ?>
                  
                  <div class="reply-content">
                    <?php echo nl2br(htmlspecialchars($reply['content'])); ?>
                  </div>
                  
                  <div class="reply-meta">
                    <div class="reply-author">
                      <div class="author-avatar">
                        <?php echo strtoupper(substr($reply['author_name'], 0, 1)); ?>
                      </div>
                      <span><?php echo htmlspecialchars($reply['author_name']); ?></span>
                      <span class="author-role role-<?php echo $reply['author_role']; ?>">
                        <?php echo ucfirst($reply['author_role']); ?>
                      </span>
                      
                      <?php if ($reply['is_official_answer']): ?>
                        <span class="official-badge">
                          <i class="fas fa-check-circle"></i> Official Answer
                        </span>
                      <?php endif; ?>
                    </div>
                    
                    <div class="reply-time">
                      <?php echo date('M j, Y g:i A', strtotime($reply['created_at'])); ?>
                    </div>
                  </div>

                  <?php if (!$reply['is_official_answer'] && $is_admin): ?>
                    <div class="action-buttons" style="margin-top: 20px;">
                      <a href="?id=<?php echo $question_id; ?>&mark_official=<?php echo $reply['id']; ?>" 
                         class="btn btn-primary"
                         onclick="return confirm('Mark this as the official answer?')">
                        <i class="fas fa-check"></i> Mark as Official Answer
                      </a>
                    </div>
                  <?php endif; ?>
                </div>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>
        </div>

        <!-- Reply Form -->
        <div class="reply-form">
          <h3 class="section-title">Post Your Answer</h3>
          <form method="POST" action="question.php?id=<?php echo $question_id; ?>">
            <div class="form-row">
              <div class="form-group">
                <label class="form-label">Your Name *</label>
                <input type="text" name="author_name" class="form-input" 
                       value="<?php echo isset($_SESSION['full_name']) ? $_SESSION['full_name'] : ''; ?>" 
                       placeholder="Enter your name" required>
              </div>
              <div class="form-group">
                <label class="form-label">Your Role *</label>
                <select name="author_role" class="form-select" required>
                  <?php if ($is_admin): ?>
                    <option value="admin" selected>Admin</option>
                    <option value="teacher">Teacher</option>
                  <?php else: ?>
                    <option value="parent" <?php echo (isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'parent') ? 'selected' : ''; ?>>Parent</option>
                  <?php endif; ?>
                </select>
              </div>
            </div>
            
            <div class="form-group">
              <label class="form-label">Your Answer *</label>
              <textarea name="reply_content" class="form-textarea" 
                        placeholder="Write your detailed answer here..." required></textarea>
            </div>
            
            <button type="submit" name="submit_reply" class="submit-btn">
              <i class="fas fa-paper-plane"></i> Post Answer
            </button>
          </form>
        </div>
      </div>
    </div>
  </div>

  <script>
    // Auto-close success message after 5 seconds
    <?php if (isset($_SESSION['success'])): ?>
      setTimeout(() => {
        const alert = document.querySelector('.alert-success');
        if (alert) alert.style.display = 'none';
      }, 5000);
    <?php endif; ?>

    // Smooth scrolling for anchor links
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
      anchor.addEventListener('click', function (e) {
        e.preventDefault();
        const target = document.querySelector(this.getAttribute('href'));
        if (target) {
          target.scrollIntoView({
            behavior: 'smooth',
            block: 'start'
          });
        }
      });
    });

    // Admin confirmation for destructive actions
    document.querySelectorAll('form').forEach(form => {
      form.addEventListener('submit', function(e) {
        if (this.querySelector('button[name="delete_question"]') || this.querySelector('button[name="delete_reply"]')) {
          if (!confirm('Are you sure you want to delete this? This action cannot be undone.')) {
            e.preventDefault();
          }
        }
      });
    });

    // Highlight official answer
    const officialAnswer = document.querySelector('.official-answer');
    if (officialAnswer) {
      officialAnswer.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }
  </script>
</body>
</html>