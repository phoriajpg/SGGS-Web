<?php
session_start();

// Database configuration
$host = 'localhost';
$dbname = 'sggs';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    // If database connection fails, show error but don't stop execution
    $error_message = "Database connection failed. Please try again later.";
}

// If user is already logged in, redirect to their dashboard
if (isset($_SESSION['user_type'])) {
    switch($_SESSION['user_type']) {
        case 'admin':
            header("Location: admin_dashboard.php");
            exit();
        case 'parent':
            header("Location: parent.html");
            exit();
        case 'student':
            header("Location: student.php");
            exit();
    }
}

$error_message = '';
$success_message = '';

// Handle login form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    // Validation
    if (empty($username) || empty($password)) {
        $error_message = "All fields are required!";
    } else {
        // Check if database connection is available
        if (isset($pdo)) {
            try {
                // Find user by username - PLAIN TEXT PASSWORD COMPARISON
                $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? AND password = ?");
                $stmt->execute([$username, $password]);
                $user = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($user) {
                    // Login successful - set session and redirect
                    $_SESSION['user_type'] = $user['user_type'];
                    $_SESSION['full_name'] = $user['full_name'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['email'] = $user['email'];
                    
                    // Redirect to appropriate dashboard
                    switch($user['user_type']) {
                        case 'admin':
                            header("Location: admin_dashboard.php");
                            exit();
                        case 'parent':
                            header("Location: parent.html");
                            exit();
                        case 'student':
                            header("Location: student.php");
                            exit();
                    }
                } else {
                    $error_message = "Invalid username or password!";
                }
            } catch(PDOException $e) {
                $error_message = "Database error. Please try again.";
            }
        } else {
            // Demo login for testing without database
            $demo_users = [
                'admin' => ['password' => 'admin123', 'type' => 'admin'],
                'student1' => ['password' => 'student123', 'type' => 'student'],
                'parent1' => ['password' => 'parent123', 'type' => 'parent']
            ];
            
            if (isset($demo_users[$username]) && $demo_users[$username]['password'] === $password) {
                $_SESSION['user_type'] = $demo_users[$username]['type'];
                $_SESSION['username'] = $username;
                $_SESSION['full_name'] = ucfirst($username);
                
                // Redirect to appropriate dashboard
                switch($demo_users[$username]['type']) {
                    case 'admin':
                        header("Location: admin_dashboard.php");
                        exit();
                    case 'parent':
                        header("Location: parent.html");
                        exit();
                    case 'student':
                        header("Location: student.php");
                        exit();
                }
            } else {
                $error_message = "Invalid username or password!";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <link href="https://fonts.googleapis.com/css2?family=Gabarito:wght@400..900&display=swap" rel="stylesheet" />
  <title>SGGSWeb - Student Portal Login</title>
  <style>
/* ========== Base Styles ========== */
* {
  margin: 0;
  padding: 0;
  box-sizing: border-box;
}

html {
  scroll-padding-top: 80px;
  scroll-behavior: smooth;
}

/* ========== Base Styles ========== */
body {
  font-family: "Gabarito", sans-serif;
  margin: 0;
  padding: 0;
  height: auto;
  background-color: #830000;
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

/* ========== Messages ========== */
.error-message {
  background: #fee;
  color: #c33;
  padding: 12px;
  border-radius: 6px;
  margin-bottom: 1.2rem;
  border: 1px solid #fcc;
  font-size: 0.9rem;
  text-align: center;
}

.success-message {
  background: #efe;
  color: #363;
  padding: 12px;
  border-radius: 6px;
  margin-bottom: 1.2rem;
  border: 1px solid #cfc;
  font-size: 0.9rem;
  text-align: center;
}

/* ========== Container ========== */
.floating-split-container {
  display: flex;
  flex-direction: row;
  width: 95%;
  max-width: 1200px;
  background: rgba(255, 255, 255, 0.95);
  border-radius: 20px;
  box-shadow: 0 20px 60px rgba(0, 0, 0, 0.25);
  overflow: hidden;
  margin-top: 80px;
  height: calc(100vh - 120px);
  min-height: 600px;
  backdrop-filter: blur(8px);
  transition: transform 0.3s ease;
}

/* ========== Welcome Section ========== */
.welcome-section {
  flex: 1;
  background: linear-gradient(135deg, #b10023 0%, #5a0000 100%);
  color: white;
  display: flex;
  flex-direction: column;
  justify-content: center;
  align-items: center;
  text-align: center;
  padding: 40px;
  position: relative;
  overflow: hidden;
  min-width: 450px;
}

.welcome-section::before {
  content: '';
  position: absolute;
  top: -50%;
  left: -50%;
  width: 200%;
  height: 200%;
  background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);
  animation: float 6s ease-in-out infinite;
}

@keyframes float {
  0%, 100% { transform: translateY(0px) rotate(0deg); }
  50% { transform: translateY(-20px) rotate(180deg); }
}

.welcome-content {
  position: relative;
  z-index: 2;
}

.welcome-title {
  font-size: 2.8rem;
  font-weight: 800;
  margin-bottom: 1rem;
  text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
  line-height: 1.2;
}

.welcome-subtitle {
  font-size: 1.2rem;
  opacity: 0.9;
  font-weight: 500;
  max-width: 400px;
  margin: 0 auto 1.5rem;
  line-height: 1.5;
}

.school-logo {
  font-size: 1.8rem;
  font-weight: bold;
  margin-top: 30px;
  opacity: 0.9;
  letter-spacing: 2px;
}

/* ========== Login Section ========== */
.login-section {
  flex: 1;
  display: flex;
  flex-direction: column;
  justify-content: center;
  align-items: center;
  padding: 40px;
  background: #fff;
  min-width: 500px;
  overflow: hidden;
}

.auth-container {
  width: 100%;
  max-width: 400px;
  text-align: center;
  margin: 0 auto;
}

.login-title {
  font-size: 2.2rem;
  font-weight: 700;
  color: #b10023;
  margin-bottom: 0.5rem;
}

.login-subtitle {
  color: #666;
  margin-bottom: 2rem;
  font-size: 1.1rem;
  line-height: 1.4;
}

/* Form Container */
.form-container {
  width: 100%;
  padding: 0 5px;
}

/* Inputs */
.form-group {
  margin-bottom: 1.5rem;
  text-align: left;
}

input[type="text"],
input[type="password"] {
  width: 100%;
  padding: 14px 16px;
  border: 2px solid #e5e5e5;
  border-radius: 10px;
  font-size: 1rem;
  transition: all 0.3s ease;
  font-family: "Gabarito", sans-serif;
  background: #fafafa;
}

input:focus {
  border-color: #b10023;
  box-shadow: 0 0 0 3px rgba(177, 0, 35, 0.1);
  outline: none;
  background: white;
}

/* Buttons */
.btn-submit {
  width: 100%;
  padding: 16px;
  background: linear-gradient(135deg, #b10023 0%, #830000 100%);
  color: #fff;
  border: none;
  border-radius: 10px;
  font-size: 1.1rem;
  font-weight: 600;
  cursor: pointer;
  box-shadow: 0 4px 15px rgba(177, 0, 35, 0.3);
  transition: all 0.3s ease;
  font-family: "Gabarito", sans-serif;
  margin-top: 10px;
  letter-spacing: 0.5px;
}

.btn-submit:hover {
  transform: translateY(-2px);
  box-shadow: 0 6px 20px rgba(177, 0, 35, 0.4);
}

.btn-submit:active {
  transform: translateY(0);
}

.forgot-password {
  text-align: right;
  margin-top: -8px;
  margin-bottom: 18px;
}

.forgot-password a {
  color: #b10023;
  font-size: 0.9rem;
  text-decoration: none;
  transition: all 0.3s ease;
}

.forgot-password a:hover {
  text-decoration: underline;
  color: #830000;
}

.signup-section {
  margin-top: 25px;
  border-top: 1px solid #eee;
  padding-top: 20px;
  font-size: 1rem;
  color: #666;
  line-height: 1.5;
}

.signup-section a {
  color: #b10023;
  font-weight: 600;
  text-decoration: none;
  transition: all 0.3s ease;
}

.signup-section a:hover {
  text-decoration: underline;
  color: #830000;
}

/* Demo Info */
.demo-info {
  background: #f9fafc;
  border-left: 4px solid #b10023;
  padding: 1rem 1.2rem;
  border-radius: 8px;
  margin-top: 1.5rem;
  text-align: left;
  font-size: 0.9rem;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
}

.demo-info h4 {
  color: #b10023;
  font-size: 1rem;
  margin-bottom: 0.6rem;
}

/* Form Focus States */
.form-group:focus-within label {
  color: #b10023;
}

/* ========== Responsive Design ========== */
@media (max-width: 1024px) {
  .floating-split-container {
    width: 98%;
  }
  
  .welcome-section {
    min-width: 400px;
    padding: 30px;
  }
  
  .login-section {
    min-width: 450px;
    padding: 30px;
  }
}

@media (max-width: 900px) {
  body {
    padding: 80px 0 20px;
    align-items: flex-start;
    overflow-y: auto;
  }

  .floating-split-container {
    flex-direction: column;
    width: 95%;
    height: auto;
    min-height: auto;
    margin-top: 60px;
  }

  .welcome-section, .login-section {
    min-width: auto;
    padding: 30px 25px;
    width: 100%;
  }

  .welcome-section {
    min-height: 250px;
  }

  .welcome-title {
    font-size: 2.4rem;
  }

  .login-title {
    font-size: 2rem;
  }
  
  .auth-container {
    max-width: 100%;
  }
}

@media (max-width: 480px) {
  .nav-links {
    gap: 8px;
  }
  
  .navbar a,
  .navbar .dropdown-toggle {
    padding: 6px 10px;
    font-size: 14px;
  }
  
  .welcome-title {
    font-size: 2rem;
  }
  
  .login-section {
    padding: 20px;
  }
  
  .login-title {
    font-size: 1.8rem;
  }
  
  .floating-split-container {
    width: 100%;
    border-radius: 15px;
  }
  
  .form-group {
    margin-bottom: 1.2rem;
  }
  
  input[type="text"],
  input[type="password"] {
    padding: 12px 14px;
  }
}
  </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar">
      <div class="nav-box">
        <div class="nav-links">
          <a href="index.html"><span class="label">Home</span></a>
          <div class="dropdown">
            <span class="dropdown-toggle"><span class="label">About SGGS</span>
            <div class="dropdown-menu">
              <a href="administrators.html">Our Administrators</a>
              <a href="#core-values">Our Core Values</a>
              <a href="#mission">Our School Vision and Mission</a>
            </div>
          </div>
          <a href="contact.html"><span class="label">Contact</span></a>
          <a href="login.php"><span class="label">Log In</span></a>
        </div>
      </div>
    </nav>

  <!-- Floating Split Container -->
  <div class="floating-split-container">
    <!-- Welcome Section (Left Side) -->
    <div class="welcome-section">
      <div class="welcome-content">
        <h1 class="welcome-title">Welcome to Student Portal</h1>
        <p class="welcome-subtitle">Login to access your account and manage your academic journey</p>
        <div class="school-logo">SGGS</div>
      </div>
    </div>

    <!-- Login Section (Right Side) -->
    <div class="login-section">
      <div class="auth-container">
        <div class="login-header">
          <h2 class="login-title">Login</h2>
          <p class="login-subtitle">Enter your account details to continue</p>
        </div>

        <?php if (!empty($error_message)): ?>
          <div class="error-message"><?php echo htmlspecialchars($error_message); ?></div>
        <?php endif; ?>

        <?php if (!empty($success_message)): ?>
          <div class="success-message"><?php echo htmlspecialchars($success_message); ?></div>
        <?php endif; ?>

        <!-- Login Form -->
        <form method="POST" action="" class="form-container" id="loginForm">
          <div class="form-group">
            <label for="username">Username</label>
            <input type="text" id="username" name="username" placeholder="Enter your username" value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>" required>
          </div>
          
          <div class="form-group">
            <label for="password">Password</label>
            <input type="password" id="password" name="password" placeholder="Enter your password" required>
          </div>
          
          <div class="forgot-password">
            <a href="forgot_password.php">Forgot Password?</a>
          </div>
          
          <button type="submit" class="btn-submit">Login</button>
          
          <div class="signup-section">
            <p>Don't have an account? <a href="register.php">Sign up here</a></p>
          </div>
        </form>

      </div>
    </div>
  </div>

  <script>
    // Enter key support
    document.getElementById('password').addEventListener('keypress', function(e) {
      if (e.key === 'Enter') {
        document.getElementById('loginForm').dispatchEvent(new Event('submit'));
      }
    });

    // Enhanced enter key support for form navigation
    document.addEventListener('keypress', function(e) {
      if (e.key === 'Enter') {
        const focused = document.activeElement;
        if (focused && focused.form && focused.form.id === 'loginForm') {
          if (focused.type !== 'submit') {
            e.preventDefault();
            // Find next input field
            const formElements = Array.from(focused.form.elements);
            const currentIndex = formElements.indexOf(focused);
            const nextElement = formElements[currentIndex + 1];
            
            if (nextElement && (nextElement.tagName === 'INPUT' || nextElement.tagName === 'SELECT')) {
              nextElement.focus();
            }
          }
        }
      }
    });

    // Add subtle floating animation to the container
    document.addEventListener('DOMContentLoaded', function() {
      const container = document.querySelector('.floating-split-container');
      
      container.addEventListener('mouseenter', function() {
        this.style.transform = 'translateY(-5px)';
      });
      
      container.addEventListener('mouseleave', function() {
        this.style.transform = 'translateY(0)';
      });
      
      // Auto-focus username input
      document.getElementById('username').focus();
    });
  </script>
</body>
</html>