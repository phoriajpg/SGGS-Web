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

// Get selected year from request or default to 2023
$selectedYear = isset($_GET['year']) ? intval($_GET['year']) : 2023;

// Query to get straight A students for selected year
$sql = "SELECT student_name, grade FROM student_grades WHERE year = ? AND is_straight_a = TRUE ORDER BY student_name";
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

// Get available years for dropdown
$yearsSql = "SELECT DISTINCT year FROM student_grades ORDER BY year DESC";
$yearsResult = $conn->query($yearsSql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>SGGS Academics</title>
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
            padding: 2px 16px;
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

        /* ========== Label Style ========== */
        .label {
            font-weight: 700;
            font-size: 35px;
            display: inline;
        }

    /* ========== Grades Container ========== */
    .grades-container {
      max-width: 1200px;
      margin: 120px auto 40px;
      padding: 20px;
      background-color: white;
      border-radius: 10px;
      box-shadow: 0 4px 15px rgba(0,0,0,0.1);
    }

    .grades-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 30px;
      padding-bottom: 15px;
      border-bottom: 2px solid #f0f0f0;
    }

    .grades-title {
      font-size: 2rem;
      color: var(--primary);
      margin: 0;
    }

    .semester-selector {
      padding: 8px 15px;
      border-radius: 6px;
      border: 1px solid #ddd;
      font-family: "Gabarito", sans-serif;
    }

    /* ========== Simplified Grades Table ========== */
    .grades-table {
      width: 100%;
      border-collapse: collapse;
      margin-top: 20px;
    }

    .grades-table th {
      background-color: var(--primary);
      color: white;
      padding: 12px;
      text-align: left;
    }

    .grades-table td {
      padding: 12px;
      border-bottom: 1px solid #eee;
    }

    .grades-table tr:hover {
      background-color: #f9f9f9;
    }

    .grade-A { color: #2ecc71; font-weight: bold; }
    .grade-B { color: #3498db; font-weight: bold; }
    .grade-C { color: #f39c12; font-weight: bold; }
    .grade-D { color: #e74c3c; font-weight: bold; }

    /* ========== Updated Stats Container ========== */
    .stats-container {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
      gap: 20px;
      margin-top: 40px;
    }

    .stat-card {
      font-size: 20px;
      background-color: white;
      border-radius: 8px;
      padding: 20px;
      box-shadow: 0 2px 10px rgba(0,0,0,0.05);
      border-top: 4px solid var(--primary);
      text-align: center;
    }

    .stat-value {
      font-size: 2.5rem;
      font-weight: 700;
      margin: 10px 0;
      color: var(--primary);
    }

    .straight-a-icon {
      font-size: 2.5rem;
      color: var(--accent);
      margin: 10px 0;
    }

    /* ========== Responsive ========== */
    @media (max-width: 768px) {
      .grades-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 15px;
      }
      
      .grades-table {
        display: block;
        overflow-x: auto;
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
        <a href="parent.html"><span class="label">Home</span></a>
        <a href="qna.php"><span class="label">Q&A</span></a>
        <a href="faq.html"><span class="label">FAQ</span></a>
        <a href="academics.php"><span class="label">Academics</span></a>
        <a href="index.html"><span class="label">Log Out</span></a>
      </div>
    </div>
  </nav>

  <!-- Grades Content -->
  <div class="grades-container">
    <div class="grades-header">
      <h1 class="grades-title">Top SPM Students</h1>
      <form method="get" action="academics.php">
        <select class="semester-selector" name="year" onchange="this.form.submit()">
          <?php while($year = $yearsResult->fetch_assoc()): ?>
            <option value="<?php echo $year['year']; ?>" <?php echo $year['year'] == $selectedYear ? 'selected' : ''; ?>>
              <?php echo $year['year']; ?>
            </option>
          <?php endwhile; ?>
        </select>
      </form>
    </div>

    <!-- Student Table -->
    <table class="grades-table">
  <thead>
    <tr>
      <th>Student Name</th>
      <th>Grade</th>
    </tr>
  </thead>
  <tbody>
    <?php 
    if ($result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            echo '<tr>';
            echo '<td>' . htmlspecialchars($row['student_name']) . '</td>';
            echo '<td class="grade-A">' . htmlspecialchars($row['grade']) . '</td>';
            echo '</tr>';
        }
    } else {
        echo '<tr><td colspan="2">No straight A students found for selected year</td></tr>';
    }
    ?>
  </tbody>
</table>

    <!-- Stats Container -->
    <div class="stats-container">
      <div class="stat-card">
        <h3>Straight A's</h3>
        <div class="straight-a-icon">
          <i class="fas fa-star"></i>
        </div>
        <p><?php echo $straightACount; ?> Student<?php echo $straightACount != 1 ? 's' : ''; ?></p>
      </div>
    </div>
  </div>

  <script>
    // No need for the countStraightAStudents function anymore as it's handled by PHP
    document.querySelector('.semester-selector').addEventListener('change', function() {
      console.log('Loading data for:', this.value);
    });
  </script>
</body>
</html>
<?php
$conn->close();
?>