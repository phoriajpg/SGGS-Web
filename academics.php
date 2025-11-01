<?php
// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "sggs_top_spm";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get selected year from request or default to current year
$selectedYear = isset($_GET['year']) ? intval($_GET['year']) : date('Y');

// First, let's check the actual table structure
$tableCheck = $conn->query("DESCRIBE student_grades");
$columns = [];
while($row = $tableCheck->fetch_assoc()) {
    $columns[] = $row['Field'];
}

// Build query based on actual columns
$selectColumns = "student_name, grade";
if (in_array('subjects', $columns)) {
    $selectColumns .= ", subjects";
}
if (in_array('achievements', $columns)) {
    $selectColumns .= ", achievements";
}

// Query to get straight A students for selected year
$sql = "SELECT $selectColumns FROM student_grades WHERE year = ? AND is_straight_a = TRUE ORDER BY student_name";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $selectedYear);
$stmt->execute();
$result = $stmt->get_result();

// Query to count straight A students
$countSql = "SELECT COUNT(*) as straight_a_count FROM student_grades WHERE year = ? AND is_straight_a = TRUE";
$countStmt = $conn->prepare($countSql);
$countStmt->bind_param("i", $selectedYear);
$countStmt->execute();
$countResult = $countStmt->get_result();
$countData = $countResult->fetch_assoc();
$straightACount = $countData['straight_a_count'];

// Get overall statistics
$statsSql = "SELECT 
    COUNT(DISTINCT year) as total_years,
    (SELECT COUNT(*) FROM student_grades WHERE is_straight_a = TRUE) as total_straight_a_students,
    (SELECT COUNT(DISTINCT student_name) FROM student_grades) as unique_students
    FROM student_grades";
$statsResult = $conn->query($statsSql);
$statsData = $statsResult->fetch_assoc();

// Get available years for dropdown
$yearsSql = "SELECT DISTINCT year FROM student_grades ORDER BY year DESC";
$yearsResult = $conn->query($yearsSql);

// Get grade distribution for selected year
$gradeSql = "SELECT grade, COUNT(*) as count FROM student_grades WHERE year = ? GROUP BY grade ORDER BY grade";
$gradeStmt = $conn->prepare($gradeSql);
$gradeStmt->bind_param("i", $selectedYear);
$gradeStmt->execute();
$gradeResult = $gradeStmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>SGGS Academic Excellence - Top SPM Achievers</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <link href="https://fonts.googleapis.com/css2?family=Gabarito:wght@400..900&display=swap" rel="stylesheet">
  <style>
    /* ========== Base Styles ========== */
    :root {
      --primary: #B10023;
      --primary-dark: #830000;
      --primary-light: #ffebee;
      --accent: #f1c40f;
      --accent-dark: #f39c12;
      --success: #2ecc71;
      --info: #3498db;
      --warning: #e67e22;
      --text-dark: #2c3e50;
      --text-light: #7f8c8d;
      --bg-light: #f8f9fa;
      --border: #e9ecef;
    }
    
    html {
      scroll-behavior: smooth;
    }
    
    body {
      font-family: "Gabarito", sans-serif;
      margin: 0;
      padding: 0;
      background: #f5f5f5;
      color: var(--text-dark);
      line-height: 1.6;
      min-height: 100vh;
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

    /* ========== Main Container ========== */
    .main-container {
      max-width: 1400px;
      margin: 120px auto 40px;
      grid-template-columns: 1fr 300px;
      gap: 40px;
    }

    /* ========== Hero Section ========== */
    .hero-section {
      text-align: center;
      margin-bottom: 50px;
      grid-column: 1 / -1;
    }

    .hero-title {
      font-size: 3rem;
      color: #B10023;
      margin-bottom: 15px;
    }

    .hero-subtitle {
      font-size: 1.2rem;
      color: #555;
      max-width: 700px;
      margin: 0 auto;
    }

    /* ========== Grades Container ========== */
    .grades-container {
      background-color: white;
      border-radius: 20px;
      padding: 40px;
      box-shadow: 0 10px 30px rgba(0,0,0,0.1);
      margin-bottom: 30px;
    }

    .grades-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 30px;
      padding-bottom: 20px;
      border-bottom: 2px solid var(--border);
    }

    .grades-title {
      font-size: 2.2rem;
      color: var(--primary);
      margin: 0;
      font-weight: 700;
    }

    .year-selector {
      padding: 12px 20px;
      border-radius: 10px;
      border: 2px solid var(--border);
      font-family: "Gabarito", sans-serif;
      font-size: 1rem;
      font-weight: 600;
      background: white;
      cursor: pointer;
      transition: all 0.3s ease;
    }

    .year-selector:focus {
      outline: none;
      border-color: var(--primary);
      box-shadow: 0 0 0 3px rgba(177, 0, 35, 0.1);
    }

    /* ========== Enhanced Grades Table ========== */
    .grades-table-container {
      overflow-x: auto;
      border-radius: 12px;
      box-shadow: 0 4px 15px rgba(0,0,0,0.05);
    }

    .grades-table {
      width: 100%;
      border-collapse: collapse;
      margin: 0;
    }

    .grades-table th {
      background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
      color: white;
      padding: 18px 20px;
      text-align: left;
      font-weight: 600;
      font-size: 1.1rem;
    }

    .grades-table th:first-child {
      border-top-left-radius: 12px;
    }

    .grades-table th:last-child {
      border-top-right-radius: 12px;
    }

    .grades-table td {
      padding: 18px 20px;
      border-bottom: 1px solid var(--border);
      transition: background-color 0.3s ease;
    }

    .grades-table tr:hover {
      background-color: var(--primary-light);
      transform: scale(1.01);
    }

    .grades-table tr:last-child td {
      border-bottom: none;
    }

    .student-name {
      font-weight: 600;
      color: var(--text-dark);
    }

    .grade-badge {
      padding: 6px 12px;
      border-radius: 20px;
      font-weight: 700;
      font-size: 0.9rem;
      display: inline-block;
    }

    .grade-A { 
      background-color: #d4edda; 
      color: #155724; 
      border: 2px solid #c3e6cb;
    }

    .achievements {
      color: var(--text-light);
      font-size: 0.9rem;
      margin-top: 5px;
    }

    .subjects {
      font-size: 0.85rem;
      color: var(--text-light);
      margin-top: 5px;
    }

    /* ========== Enhanced Stats Container ========== */
    .stats-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
      gap: 25px;
      margin-top: 40px;
    }

    .stat-card {
      background: white;
      border-radius: 15px;
      padding: 30px;
      box-shadow: 0 5px 20px rgba(0,0,0,0.08);
      text-align: center;
      transition: transform 0.3s ease, box-shadow 0.3s ease;
      border-top: 5px solid var(--primary);
    }

    .stat-card:hover {
      transform: translateY(-5px);
      box-shadow: 0 10px 30px rgba(0,0,0,0.15);
    }

    .stat-card.highlight {
      background: linear-gradient(135deg, var(--accent) 0%, var(--accent-dark) 100%);
      color: white;
    }

    .stat-card.highlight .stat-value,
    .stat-card.highlight .stat-label {
      color: white;
    }

    .stat-icon {
      font-size: 3rem;
      margin-bottom: 15px;
      opacity: 0.8;
    }

    .stat-value {
      font-size: 2.8rem;
      font-weight: 800;
      margin: 10px 0;
      color: var(--primary);
    }

    .stat-label {
      font-size: 1.1rem;
      color: var(--text-light);
      font-weight: 600;
    }

    /* ========== Grade Distribution ========== */
    .distribution-section {
      background: white;
      border-radius: 20px;
      padding: 40px;
      box-shadow: 0 10px 30px rgba(0,0,0,0.1);
      margin-top: 30px;
    }

    .distribution-title {
      font-size: 1.8rem;
      color: var(--primary);
      margin-bottom: 25px;
      text-align: center;
      font-weight: 700;
    }

    .distribution-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
      gap: 15px;
    }

    .distribution-item {
      text-align: center;
      padding: 20px;
      background: var(--bg-light);
      border-radius: 12px;
      transition: transform 0.3s ease;
    }

    .distribution-item:hover {
      transform: scale(1.05);
    }

    .distribution-grade {
      font-size: 1.5rem;
      font-weight: 700;
      margin-bottom: 5px;
    }

    .distribution-count {
      font-size: 1.8rem;
      font-weight: 800;
      color: var(--primary);
    }

    /* ========== Empty State ========== */
    .empty-state {
      text-align: center;
      padding: 60px 40px;
      color: var(--text-light);
    }

    .empty-state i {
      font-size: 4rem;
      margin-bottom: 20px;
      opacity: 0.5;
    }

    .empty-state h3 {
      font-size: 1.5rem;
      margin-bottom: 10px;
      color: var(--text-dark);
    }

    /* ========== Loading Animation ========== */
    .loading {
      display: inline-block;
      width: 20px;
      height: 20px;
      border: 3px solid #f3f3f3;
      border-top: 3px solid var(--primary);
      border-radius: 50%;
      animation: spin 1s linear infinite;
    }

    @keyframes spin {
      0% { transform: rotate(0deg); }
      100% { transform: rotate(360deg); }
    }

    /* ========== Responsive Design ========== */
    @media (max-width: 768px) {
      .hero-title {
        font-size: 2.2rem;
      }
      
      .grades-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 20px;
      }
      
      .grades-table th,
      .grades-table td {
        padding: 12px 15px;
      }
      
      .stats-grid {
        grid-template-columns: 1fr;
      }
      
      .nav-links {
        gap: 10px;
      }
      
      .label {
        font-size: 28px;
      }
      
      .main-container {
        margin: 100px auto 20px;
        padding: 0 15px;
      }
    }

    @media (max-width: 480px) {
      .hero-section {
        padding: 30px 20px;
      }
      
      .grades-container {
        padding: 25px;
      }
      
      .stat-card {
        padding: 20px;
      }
    }
  </style>
</head>
<body>
  <!-- Navigation -->
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
  <div class="main-container">
    <!-- Hero Section -->
    <section class="hero-section">
      <h1 class="hero-title">Academic Excellence</h1>
      <p class="hero-subtitle">Celebrating Our Top SPM Achievers</p>
    </section>

    <!-- Grades Container -->
    <div class="grades-container">
      <div class="grades-header">
        <h1 class="grades-title">Straight A+ Achievers</h1>
        <form method="get" action="academics.php" id="yearForm">
          <select class="year-selector" name="year" id="yearSelect">
            <?php 
            if ($yearsResult->num_rows > 0) {
                while($year = $yearsResult->fetch_assoc()) {
                    echo '<option value="' . $year['year'] . '" ' . 
                         ($year['year'] == $selectedYear ? 'selected' : '') . '>' . 
                         $year['year'] . ' SPM Results</option>';
                }
            } else {
                echo '<option value="' . date('Y') . '">' . date('Y') . ' SPM Results</option>';
            }
            ?>
          </select>
        </form>
      </div>

      <!-- Student Table -->
      <div class="grades-table-container">
        <table class="grades-table">
          <thead>
            <tr>
              <th>Student Name</th>
              <th>Overall Grade</th>
              <?php if (in_array('subjects', $columns)): ?>
                <th>Subjects</th>
              <?php endif; ?>
              <?php if (in_array('achievements', $columns)): ?>
                <th>Achievements</th>
              <?php endif; ?>
            </tr>
          </thead>
          <tbody>
            <?php 
            if ($result->num_rows > 0) {
                while($row = $result->fetch_assoc()) {
                    echo '<tr>';
                    echo '<td><div class="student-name">' . htmlspecialchars($row['student_name']) . '</div></td>';
                    echo '<td><span class="grade-badge grade-A">' . htmlspecialchars($row['grade']) . '</span></td>';
                    
                    if (in_array('subjects', $columns)) {
                        echo '<td><div class="subjects">' . 
                             (isset($row['subjects']) ? htmlspecialchars($row['subjects']) : 'All Subjects') . 
                             '</div></td>';
                    }
                    
                    if (in_array('achievements', $columns)) {
                        echo '<td><div class="achievements">' . 
                             (isset($row['achievements']) ? htmlspecialchars($row['achievements']) : 'Straight A+ Achiever') . 
                             '</div></td>';
                    }
                    echo '</tr>';
                }
            } else {
                $colspan = 2;
                if (in_array('subjects', $columns)) $colspan++;
                if (in_array('achievements', $columns)) $colspan++;
                
                echo '<tr><td colspan="' . $colspan . '" class="empty-state">';
                echo '<i class="fas fa-graduation-cap"></i>';
                echo '<h3>No Straight A+ Students</h3>';
                echo '<p>No students achieved straight A+ in ' . $selectedYear . '</p>';
                echo '</td></tr>';
            }
            ?>
          </tbody>
        </table>
      </div>

      <!-- Enhanced Stats Container -->
      <div class="stats-grid">
        <div class="stat-card highlight">
          <div class="stat-icon">
            <i class="fas fa-star"></i>
          </div>
          <div class="stat-value"><?php echo $straightACount; ?></div>
          <div class="stat-label">Straight A+ Students</div>
        </div>
        
        <div class="stat-card">
          <div class="stat-icon">
            <i class="fas fa-calendar-alt"></i>
          </div>
          <div class="stat-value"><?php echo $statsData['total_years'] ?? '1'; ?></div>
          <div class="stat-label">Years of Excellence</div>
        </div>
        
        <div class="stat-card">
          <div class="stat-icon">
            <i class="fas fa-users"></i>
          </div>
          <div class="stat-value"><?php echo $statsData['total_straight_a_students'] ?? $straightACount; ?></div>
          <div class="stat-label">Total A+ Achievers</div>
        </div>
      </div>
    </div>

    <!-- Grade Distribution Section -->
    <?php if ($gradeResult->num_rows > 0): ?>
    <div class="distribution-section">
      <h2 class="distribution-title">Grade Distribution - <?php echo $selectedYear; ?></h2>
      <div class="distribution-grid">
        <?php 
        while($grade = $gradeResult->fetch_assoc()) {
            echo '<div class="distribution-item">';
            echo '<div class="distribution-grade">' . htmlspecialchars($grade['grade']) . '</div>';
            echo '<div class="distribution-count">' . $grade['count'] . '</div>';
            echo '<div class="stat-label">Students</div>';
            echo '</div>';
        }
        ?>
      </div>
    </div>
    <?php endif; ?>
  </div>

  <script>
    // Auto-submit form when year changes
    document.getElementById('yearSelect').addEventListener('change', function() {
      document.getElementById('yearForm').submit();
    });

    // Add animation to stats cards on scroll
    const observerOptions = {
      threshold: 0.1,
      rootMargin: '0px 0px -50px 0px'
    };

    const observer = new IntersectionObserver(function(entries) {
      entries.forEach(entry => {
        if (entry.isIntersecting) {
          entry.target.style.opacity = '1';
          entry.target.style.transform = 'translateY(0)';
        }
      });
    }, observerOptions);

    // Observe stat cards
    document.querySelectorAll('.stat-card').forEach(card => {
      card.style.opacity = '0';
      card.style.transform = 'translateY(20px)';
      card.style.transition = 'opacity 0.6s ease, transform 0.6s ease';
      observer.observe(card);
    });
  </script>
</body>
</html>
<?php
$conn->close();
?>