<?php
session_start();
include 'db_config.php';

// Handle search
$search_query = '';
$category_filter = '';
$questions = [];

if ($_SERVER['REQUEST_METHOD'] == 'GET') {
    $search_query = isset($_GET['search']) ? trim($_GET['search']) : '';
    $category_filter = isset($_GET['category']) ? intval($_GET['category']) : '';
    
    // Build query based on filters
    $sql = "SELECT fq.*, fc.name as category_name 
            FROM faq_questions fq 
            LEFT JOIN faq_categories fc ON fq.category_id = fc.id 
            WHERE 1=1";
    
    $params = [];
    
    if (!empty($search_query)) {
        $sql .= " AND (fq.question LIKE ? OR fq.answer LIKE ?)";
        $search_term = "%$search_query%";
        $params[] = $search_term;
        $params[] = $search_term;
    }
    
    if (!empty($category_filter)) {
        $sql .= " AND fq.category_id = ?";
        $params[] = $category_filter;
    }
    
    $sql .= " ORDER BY fq.is_featured DESC, fq.view_count DESC, fq.created_at DESC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $questions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Increment view count for searched questions
    if (!empty($questions)) {
        $question_ids = array_column($questions, 'id');
        $placeholders = str_repeat('?,', count($question_ids) - 1) . '?';
        $update_sql = "UPDATE faq_questions SET view_count = view_count + 1 WHERE id IN ($placeholders)";
        $update_stmt = $pdo->prepare($update_sql);
        $update_stmt->execute($question_ids);
    }
}

// Get all categories for filter dropdown
$categories_stmt = $pdo->query("SELECT * FROM faq_categories ORDER BY name");
$categories = $categories_stmt->fetchAll(PDO::FETCH_ASSOC);

// Get featured questions for the sidebar
$featured_stmt = $pdo->query("SELECT fq.*, fc.name as category_name 
                             FROM faq_questions fq 
                             LEFT JOIN faq_categories fc ON fq.category_id = fc.id 
                             WHERE fq.is_featured = TRUE 
                             ORDER BY fq.view_count DESC 
                             LIMIT 5");
$featured_questions = $featured_stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <link href="https://fonts.googleapis.com/css2?family=Gabarito:wght@400..900&display=swap" rel="stylesheet" />
  <title>SGGSWeb - FAQ</title>
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

    .label {
      font-weight: 700;
      font-size: 35px;
      display: inline;
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

    /* ========== FAQ Container ========== */
    .faq-container {
      max-width: 1200px;
      margin: 0 auto;
      padding: 0 20px;
      display: grid;
      grid-template-columns: 1fr 300px;
      gap: 40px;
    }
    
    .faq-header {
      text-align: center;
      margin-bottom: 50px;
      grid-column: 1 / -1;
    }
    
    .faq-header h1 {
      font-size: 3rem;
      color: #B10023;
      margin-bottom: 15px;
    }
    
    .faq-header p {
      font-size: 1.2rem;
      color: #555;
      max-width: 700px;
      margin: 0 auto;
    }

    /* ========== Search and Filters ========== */
    .search-filters {
      background: white;
      padding: 25px;
      border-radius: 12px;
      box-shadow: 0 4px 12px rgba(0,0,0,0.1);
      margin-bottom: 30px;
      grid-column: 1 / -1;
    }

    .search-container {
      display: grid;
      grid-template-columns: 1fr auto auto;
      gap: 15px;
      align-items: end;
    }

    .search-box {
      padding: 12px 20px;
      border: 2px solid #ddd;
      border-radius: 8px;
      font-size: 1rem;
      font-family: 'Gabarito', sans-serif;
      outline: none;
      transition: border-color 0.3s;
    }

    .search-box:focus {
      border-color: #B10023;
    }

    .filter-select {
      padding: 12px 20px;
      border: 2px solid #ddd;
      border-radius: 8px;
      font-size: 1rem;
      font-family: 'Gabarito', sans-serif;
      background: white;
      cursor: pointer;
    }

    .search-btn {
      padding: 12px 30px;
      background: #B10023;
      color: white;
      border: none;
      border-radius: 8px;
      font-size: 1rem;
      font-family: 'Gabarito', sans-serif;
      cursor: pointer;
      transition: background 0.3s;
    }

    .search-btn:hover {
      background: #830000;
    }

    /* ========== FAQ Items ========== */
    .faq-section {
      background: white;
      border-radius: 12px;
      box-shadow: 0 4px 12px rgba(0,0,0,0.1);
      overflow: hidden;
    }

    .faq-item {
      border-bottom: 1px solid #eee;
      transition: all 0.3s ease;
    }

    .faq-item:last-child {
      border-bottom: none;
    }

    .faq-question {
      padding: 20px 25px;
      display: flex;
      justify-content: space-between;
      align-items: center;
      cursor: pointer;
      font-weight: 600;
      font-size: 1.1rem;
      color: #333;
      background: white;
      transition: background 0.3s;
    }

    .faq-question:hover {
      background-color: #f9f9f9;
    }

    .faq-category {
      background: #B10023;
      color: white;
      padding: 4px 12px;
      border-radius: 20px;
      font-size: 0.8rem;
      font-weight: 500;
      margin-top: 5px;
      display: inline-block;
    }

    .faq-question i {
      transition: transform 0.3s ease;
      color: #B10023;
    }

    .faq-answer {
      padding: 0 25px;
      max-height: 0;
      overflow: hidden;
      transition: max-height 0.3s ease, padding 0.3s ease;
      color: #555;
      line-height: 1.6;
      background: #fafafa;
    }

    /* Active state */
    .faq-item.active .faq-question i {
      transform: rotate(180deg);
    }

    .faq-item.active .faq-answer {
      max-height: 500px;
      padding: 0 25px 25px;
    }

    .no-results {
      text-align: center;
      padding: 40px;
      color: #666;
      font-size: 1.1rem;
    }

    /* ========== Sidebar ========== */
    .sidebar {
      background: white;
      border-radius: 12px;
      box-shadow: 0 4px 12px rgba(0,0,0,0.1);
      padding: 25px;
      height: fit-content;
    }

    .sidebar h3 {
      color: #B10023;
      margin-bottom: 20px;
      font-size: 1.3rem;
      border-bottom: 2px solid #B10023;
      padding-bottom: 10px;
    }

    .featured-list {
      list-style: none;
      padding: 0;
      margin: 0;
    }

    .featured-item {
      padding: 12px 0;
      border-bottom: 1px solid #eee;
    }

    .featured-item:last-child {
      border-bottom: none;
    }

    .featured-item a {
      color: #333;
      text-decoration: none;
      font-weight: 500;
      transition: color 0.3s;
      display: block;
    }

    .featured-item a:hover {
      color: #B10023;
    }

    .featured-category {
      font-size: 0.8rem;
      color: #B10023;
      margin-top: 5px;
    }

    .stats {
      margin-top: 30px;
      padding-top: 20px;
      border-top: 1px solid #eee;
    }

    .stat-item {
      display: flex;
      justify-content: space-between;
      margin-bottom: 10px;
      color: #555;
    }

    /* ========== Contact Prompt ========== */
    .contact-prompt {
      text-align: center;
      margin-top: 50px;
      padding: 40px;
      background-color: #f8f8f8;
      border-radius: 12px;
      grid-column: 1 / -1;
    }

    .contact-prompt h3 {
      color: #B10023;
      margin-bottom: 15px;
    }

    .contact-btn {
      display: inline-block;
      padding: 12px 30px;
      background: #B10023;
      color: white;
      text-decoration: none;
      border-radius: 8px;
      font-weight: 600;
      transition: background 0.3s;
      margin-top: 15px;
    }

    .contact-btn:hover {
      background: #830000;
    }

    /* ========== Responsive Design ========== */
    @media (max-width: 968px) {
      .faq-container {
        grid-template-columns: 1fr;
        gap: 30px;
      }
      
      .search-container {
        grid-template-columns: 1fr;
      }
    }

    @media (max-width: 768px) {
      .faq-header h1 {
        font-size: 2.5rem;
      }
      
      .faq-question {
        padding: 15px 20px;
        font-size: 1rem;
      }
      
      .faq-answer {
        padding: 0 20px;
      }
      
      .faq-item.active .faq-answer {
        padding: 0 20px 20px;
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
            <a href="parent.html"><span class="label">Home</span></a>
            <a href="qna.php"><span class="label">Q&A</span></a>
            <a href="faq.php"><span class="label">FAQ</span></a>
            <a href="academics.php"><span class="label">Academics</span></a>
            <a href="index.html"><span class="label">Log Out</span></a>
        </div>
      </div>
    </nav>

    <!-- Main Content -->
    <div class="main-content">
      <div class="faq-container">
        <!-- Header -->
        <div class="faq-header">
          <h1>Frequently Asked Questions</h1>
          <p>Find answers to common questions about St. George's Girls School</p>
        </div>

        <!-- Search and Filters -->
        <div class="search-filters">
          <!-- FIXED: Changed action from qna.php to faq.php -->
          <form method="GET" action="faq.php">
            <div class="search-container">
              <div>
                <label for="search" style="display: block; margin-bottom: 8px; font-weight: 600;">Search Questions</label>
                <input type="text" id="search" name="search" class="search-box" 
                       placeholder="Type your question here..." value="<?php echo htmlspecialchars($search_query); ?>">
              </div>
              <div>
                <label for="category" style="display: block; margin-bottom: 8px; font-weight: 600;">Filter by Category</label>
                <select id="category" name="category" class="filter-select">
                  <option value="">All Categories</option>
                  <?php foreach ($categories as $category): ?>
                    <option value="<?php echo $category['id']; ?>" 
                            <?php echo ($category_filter == $category['id']) ? 'selected' : ''; ?>>
                      <?php echo htmlspecialchars($category['name']); ?>
                    </option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div>
                <button type="submit" class="search-btn">Search</button>
              </div>
            </div>
          </form>
        </div>

        <!-- Main FAQ Content -->
        <div class="faq-main">
          <div class="faq-section">
            <?php if (empty($questions)): ?>
              <div class="no-results">
                <h3>No questions found</h3>
                <p>Try adjusting your search terms or browse all categories.</p>
              </div>
            <?php else: ?>
              <?php foreach ($questions as $question): ?>
                <div class="faq-item" id="question-<?php echo $question['id']; ?>">
                  <div class="faq-question">
                    <div>
                      <span><?php echo htmlspecialchars($question['question']); ?></span>
                      <div class="faq-category"><?php echo htmlspecialchars($question['category_name']); ?></div>
                    </div>
                    <i>▼</i>
                  </div>
                  <div class="faq-answer">
                    <p><?php echo nl2br(htmlspecialchars($question['answer'])); ?></p>
                  </div>
                </div>
              <?php endforeach; ?>
            <?php endif; ?>
          </div>
        </div>

        <!-- Sidebar -->
        <div class="sidebar">
          <h3>Popular Questions</h3>
          <ul class="featured-list">
            <?php foreach ($featured_questions as $featured): ?>
              <li class="featured-item">
                <a href="javascript:void(0)" onclick="scrollToQuestion(<?php echo $featured['id']; ?>)">
                  <?php echo htmlspecialchars($featured['question']); ?>
                </a>
                <div class="featured-category"><?php echo htmlspecialchars($featured['category_name']); ?></div>
              </li>
            <?php endforeach; ?>
          </ul>
          
          <div class="stats">
            <h3>FAQ Stats</h3>
            <div class="stat-item">
              <span>Total Questions:</span>
              <span><?php echo count($questions); ?></span>
            </div>
            <div class="stat-item">
              <span>Categories:</span>
              <span><?php echo count($categories); ?></span>
            </div>
          </div>
        </div>

        <!-- Contact Prompt -->
        <div class="contact-prompt">
          <h3>Didn't find what you're looking for?</h3>
          <p>Our team is here to help answer any questions you may have.</p>
          <a href="contact.html" class="contact-btn">Contact Us</a>
        </div>
      </div>
    </div>

    <!-- Footer -->
    <!-- Include your existing footer here -->
  </div>

  <script>
    // FAQ accordion functionality
    document.addEventListener('DOMContentLoaded', function() {
      const faqItems = document.querySelectorAll('.faq-item');
      
      faqItems.forEach(item => {
        const question = item.querySelector('.faq-question');
        
        question.addEventListener('click', () => {
          // Close all other items
          faqItems.forEach(otherItem => {
            if (otherItem !== item) {
              otherItem.classList.remove('active');
            }
          });
          
          // Toggle current item
          item.classList.toggle('active');
        });
      });

      // Check if URL has search parameters and auto-open first result
      const urlParams = new URLSearchParams(window.location.search);
      const hasSearch = urlParams.has('search') || urlParams.has('category');
      
      if (hasSearch && faqItems.length > 0) {
        // Auto-open the first result when searching
        faqItems[0].classList.add('active');
      }
    });

    // Function to scroll to specific question
    function scrollToQuestion(questionId) {
      const questionElement = document.getElementById(`question-${questionId}`);
      if (questionElement) {
        // Close all other questions
        document.querySelectorAll('.faq-item').forEach(item => {
          item.classList.remove('active');
        });
        
        // Open the question
        questionElement.classList.add('active');
        
        // Scroll to it
        questionElement.scrollIntoView({ 
          behavior: 'smooth',
          block: 'center'
        });
      }
    }

    // Highlight search terms in results
    const searchQuery = "<?php echo addslashes($search_query); ?>";
    if (searchQuery.trim() !== '') {
      document.addEventListener('DOMContentLoaded', function() {
        const questions = document.querySelectorAll('.faq-question span');
        const answers = document.querySelectorAll('.faq-answer p');
        
        const highlightText = (element) => {
          const text = element.innerHTML;
          const regex = new RegExp(`(${searchQuery.replace(/[.*+?^${}()|[\]\\]/g, '\\$&')})`, 'gi');
          element.innerHTML = text.replace(regex, '<mark style="background-color: #ffeb3b; padding: 2px 4px; border-radius: 3px;">$1</mark>');
        };
        
        questions.forEach(highlightText);
        answers.forEach(highlightText);
      });
    }

    // Clear search functionality
    function clearSearch() {
      window.location.href = 'faq.php';
    }
  </script>
</body>
</html>