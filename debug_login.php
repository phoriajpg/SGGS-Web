<?php
session_start();

// SIMPLE SESSION CHECK - Only redirect if we have BOTH user_type AND username
if (isset($_SESSION['user_type']) && isset($_SESSION['username'])) {
    // Add a small delay to see what's happening
    echo "<!-- Redirecting to dashboard -->";
    switch($_SESSION['user_type']) {
        case 'admin':
            header("Location: admin_dashboard.php");
            exit();
        case 'parent':
            header("Location: parent_dashboard.php");
            exit();
        case 'student':
            header("Location: student_dashboard.php");
            exit();
    }
}

// Include database
include 'db_config.php';

// Handle login
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $user_type = $_POST['user_type'];
    $password = trim($_POST['password']);

    $stmt = $pdo->prepare("SELECT * FROM users WHERE user_type = ? AND password = ?");
    $stmt->execute([$user_type, $password]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        // Set session variables
        $_SESSION['user_type'] = $user['user_type'];
        $_SESSION['full_name'] = $user['full_name'];
        $_SESSION['username'] = $user['username'];
        
        // Redirect to appropriate dashboard
        switch($user_type) {
            case 'admin':
                header("Location: admin_dashboard.php");
                exit();
            case 'parent':
                header("Location: parent_dashboard.php");
                exit();
            case 'student':
                header("Location: student_dashboard.php");
                exit();
        }
    } else {
        $error_message = "Invalid password for selected user type!";
    }
}
?>

<!-- Your existing HTML form remains exactly the same -->
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <link href="https://fonts.googleapis.com/css2?family=Gabarito:wght@400..900&display=swap" rel="stylesheet" />
  <title>SGGSWeb - Login</title>
  <style>
    /* Add this to see if page loads */
    .debug-warning {
        background: yellow;
        color: black;
        padding: 10px;
        margin: 10px;
        border: 2px solid red;
        font-weight: bold;
    }
    
    /* Your existing styles below */
    body {
      font-family: "Gabarito", sans-serif;
      margin: 0;
      padding: 0;
      background-color: #B10023;
    }
    /* ... rest of your styles ... */
  </style>
</head>
<body>
  <!-- Debug warning -->
  <div class="debug-warning">
    🔧 LOGIN PAGE LOADED - If you see this, redirects are working properly
  </div>

  <!-- Your existing navbar and form -->
  <nav class="navbar">...</nav>
  
  <div class="main-content">
    <div class="login-container">
      <!-- Your existing form content -->
    </div>
  </div>
</body>
</html>