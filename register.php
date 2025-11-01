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

// Handle registration form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $user_type = trim($_POST['user_type']);
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);
    $confirm_password = trim($_POST['confirm_password']);
    $full_name = trim($_POST['full_name']);
    
    // Validation
    if (empty($username) || empty($email) || empty($password) || empty($full_name) || empty($user_type)) {
        $error_message = "All fields are required!";
    } elseif ($password !== $confirm_password) {
        $error_message = "Passwords do not match!";
    } elseif (strlen($password) < 6) {
        $error_message = "Password must be at least 6 characters long!";
    } elseif (strlen($password) > 50) {
        $error_message = "Password must be less than 50 characters!";
    } elseif (strlen($username) > 50) {
        $error_message = "Username must be less than 50 characters!";
    } elseif (strlen($email) > 100) {
        $error_message = "Email must be less than 100 characters!";
    } elseif (strlen($full_name) > 100) {
        $error_message = "Full name must be less than 100 characters!";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_message = "Please enter a valid email address!";
    } else {
        // Check if database connection is available
        if (isset($pdo)) {
            try {
                // Check if username or email already exists
                $check_stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
                $check_stmt->execute([$username, $email]);
                
                if ($check_stmt->fetch()) {
                    $error_message = "Username or email already exists!";
                } else {
                    // Insert new user
                    $insert_stmt = $pdo->prepare("INSERT INTO users (user_type, username, email, password, full_name) VALUES (?, ?, ?, ?, ?)");
                    
                    if ($insert_stmt->execute([$user_type, $username, $email, $password, $full_name])) {
                        $success_message = "Registration successful! You can now login.";
                        // Clear form fields
                        $_POST = array();
                    } else {
                        $error_message = "Registration failed. Please try again.";
                    }
                }
            } catch(PDOException $e) {
                $error_message = "Database error. Please try again.";
            }
        } else {
            // Demo registration for testing without database
            $success_message = "Registration successful! You can now login with your credentials.";
            $_POST = array();
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
  <title>SGGSWeb - Student Portal Registration</title>
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

/* ========== Register Section ========== */
.register-section {
  flex: 1;
  display: flex;
  flex-direction: column;
  background: #fff;
  min-width: 500px;
  overflow: hidden;
  position: relative;
  padding: 0;
}

/* Make the entire register section scrollable */
.register-section {
  overflow-y: auto;
}

/* Custom scrollbar for register section */
.register-section::-webkit-scrollbar {
  width: 8px;
}

.register-section::-webkit-scrollbar-track {
  background: #f8f9fa;
  border-left: 1px solid #e9ecef;
}

.register-section::-webkit-scrollbar-thumb {
  background: linear-gradient(135deg, #b10023 0%, #830000 100%);
  border-radius: 4px;
}

.register-section::-webkit-scrollbar-thumb:hover {
  background: linear-gradient(135deg, #830000 0%, #5a0000 100%);
}

.auth-container {
  width: 100%;
  max-width: 600px;
  text-align: center;
  margin: 0 auto;
  padding: 40px;
  overflow-y: visible;
}

.register-title {
  font-size: 2.2rem;
  font-weight: 700;
  color: #b10023;
  margin-bottom: 0.5rem;
}

.register-subtitle {
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
input[type="email"],
input[type="password"],
select {
  width: 100%;
  padding: 14px 16px;
  border: 2px solid #e5e5e5;
  border-radius: 10px;
  font-size: 1rem;
  transition: all 0.3s ease;
  font-family: "Gabarito", sans-serif;
  background: #fafafa;
}

input:focus,
select:focus {
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

.login-section {
  margin-top: 25px;
  border-top: 1px solid #eee;
  padding-top: 20px;
  font-size: 1rem;
  color: #666;
  line-height: 1.5;
}

.login-section a {
  color: #b10023;
  font-weight: 600;
  text-decoration: none;
  transition: all 0.3s ease;
}

.login-section a:hover {
  text-decoration: underline;
  color: #830000;
}

/* User Type Options */
.user-type-options {
  display: flex;
  gap: 12px;
  margin-bottom: 1.5rem;
}

.user-type-option {
  flex: 1;
  padding: 16px 12px;
  border: 2px solid #e5e5e5;
  border-radius: 10px;
  text-align: center;
  cursor: pointer;
  transition: all 0.3s ease;
  background: #fafafa;
  font-weight: 500;
}

.user-type-option:hover {
  border-color: #b10023;
  background: #f8f8f8;
  transform: translateY(-2px);
}

.user-type-option.selected {
  border-color: #b10023;
  background: #b10023;
  color: white;
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(177, 0, 35, 0.2);
}

.user-type-option input {
  display: none;
}

/* Password Strength */
.password-strength {
  margin-top: 8px;
  font-size: 0.85rem;
  height: 18px;
}

.strength-weak { color: #e74c3c; font-weight: 600; }
.strength-medium { color: #f39c12; font-weight: 600; }
.strength-strong { color: #27ae60; font-weight: 600; }

/* Character Count */
.char-count {
  font-size: 0.8rem;
  color: #666;
  text-align: right;
  margin-top: 4px;
}

.char-count.warning {
  color: #e74c3c;
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
  
  .register-section {
    min-width: 450px;
  }
  
  .auth-container {
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

  .welcome-section, .register-section {
    min-width: auto;
    width: 100%;
  }

  .welcome-section {
    min-height: 250px;
    padding: 30px 25px;
  }

  .register-section {
    padding: 0;
    overflow-y: visible;
  }

  .auth-container {
    padding: 30px 25px;
    max-width: none;
  }

  .welcome-title {
    font-size: 2.4rem;
  }

  .register-title {
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
  
  .auth-container {
    padding: 20px;
  }
  
  .register-title {
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
  input[type="email"],
  input[type="password"],
  select {
    padding: 12px 14px;
  }
  
  .user-type-options {
    flex-direction: column;
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
        <h1 class="welcome-title">Join Our Portal</h1>
        <p class="welcome-subtitle">Create your account to access student resources and manage your academic journey</p>
        <div class="school-logo">SGGS</div>
      </div>
    </div>

    <!-- Register Section (Right Side) -->
    <div class="register-section">
      <div class="auth-container">
        <div class="register-header">
          <h2 class="register-title">Create Account</h2>
          <p class="register-subtitle">Fill in your details to get started</p>
        </div>

        <?php if (!empty($error_message)): ?>
          <div class="error-message"><?php echo htmlspecialchars($error_message); ?></div>
        <?php endif; ?>

        <?php if (!empty($success_message)): ?>
          <div class="success-message"><?php echo htmlspecialchars($success_message); ?></div>
        <?php endif; ?>

        <!-- Registration Form -->
        <form method="POST" action="" class="form-container" id="registerForm">
          <div class="form-group">
            <label for="full_name">Full Name</label>
            <input type="text" id="full_name" name="full_name" placeholder="Enter your full name" 
                   value="<?php echo isset($_POST['full_name']) ? htmlspecialchars($_POST['full_name']) : ''; ?>" 
                   maxlength="100" required>
            <div class="char-count" id="fullNameCount">0/100</div>
          </div>

          <div class="form-group">
            <label for="username">Username</label>
            <input type="text" id="username" name="username" placeholder="Choose a username" 
                   value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>" 
                   maxlength="50" required>
            <div class="char-count" id="usernameCount">0/50</div>
          </div>

          <div class="form-group">
            <label for="email">Email Address</label>
            <input type="email" id="email" name="email" placeholder="Enter your email address" 
                   value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" 
                   maxlength="100" required>
            <div class="char-count" id="emailCount">0/100</div>
          </div>

          <div class="form-group">
            <label>Account Type</label>
            <div class="user-type-options">
              <label class="user-type-option <?php echo (isset($_POST['user_type']) && $_POST['user_type'] == 'student') ? 'selected' : 'selected'; ?>">
                <input type="radio" name="user_type" value="student" <?php echo (isset($_POST['user_type']) && $_POST['user_type'] == 'student') ? 'checked' : 'checked'; ?>>
                Student
              </label>
              <label class="user-type-option <?php echo (isset($_POST['user_type']) && $_POST['user_type'] == 'parent') ? 'selected' : ''; ?>">
                <input type="radio" name="user_type" value="parent" <?php echo (isset($_POST['user_type']) && $_POST['user_type'] == 'parent') ? 'checked' : ''; ?>>
                Parent
              </label>
            </div>
          </div>

          <div class="form-group">
            <label for="password">Password</label>
            <input type="password" id="password" name="password" placeholder="Create a password (min. 6 characters)" 
                   maxlength="50" required>
            <div class="password-strength" id="passwordStrength"></div>
            <div class="char-count" id="passwordCount">0/50</div>
          </div>

          <div class="form-group">
            <label for="confirm_password">Confirm Password</label>
            <input type="password" id="confirm_password" name="confirm_password" placeholder="Re-enter your password" 
                   maxlength="50" required>
            <div class="char-count" id="confirmPasswordCount">0/50</div>
          </div>
          
          <button type="submit" class="btn-submit">Create Account</button>
          
          <div class="login-section">
            <p>Already have an account? <a href="login.php">Log in here</a></p>
          </div>
        </form>
      </div>
    </div>
  </div>

  <script>
    // User type selection
    document.querySelectorAll('.user-type-option').forEach(option => {
      option.addEventListener('click', function() {
        document.querySelectorAll('.user-type-option').forEach(opt => {
          opt.classList.remove('selected');
        });
        this.classList.add('selected');
        this.querySelector('input').checked = true;
      });
    });

    // Character count functionality
    function setupCharCount(inputId, countId, maxLength) {
      const input = document.getElementById(inputId);
      const count = document.getElementById(countId);
      
      input.addEventListener('input', function() {
        const length = this.value.length;
        count.textContent = `${length}/${maxLength}`;
        
        if (length > maxLength * 0.8) {
          count.classList.add('warning');
        } else {
          count.classList.remove('warning');
        }
      });
      
      // Initialize count
      count.textContent = `${input.value.length}/${maxLength}`;
    }

    // Setup character counters
    setupCharCount('full_name', 'fullNameCount', 100);
    setupCharCount('username', 'usernameCount', 50);
    setupCharCount('email', 'emailCount', 100);
    setupCharCount('password', 'passwordCount', 50);
    setupCharCount('confirm_password', 'confirmPasswordCount', 50);

    // Password strength indicator
    document.getElementById('password').addEventListener('input', function() {
      const password = this.value;
      const strengthIndicator = document.getElementById('passwordStrength');
      let strength = '';
      
      if (password.length === 0) {
        strength = '';
      } else if (password.length < 6) {
        strength = '<span class="strength-weak">Weak - at least 6 characters required</span>';
      } else if (password.length < 8) {
        strength = '<span class="strength-medium">Medium - good password</span>';
      } else {
        strength = '<span class="strength-strong">Strong - excellent password</span>';
      }
      
      strengthIndicator.innerHTML = strength;
    });

    // Password confirmation validation
    document.getElementById('confirm_password').addEventListener('input', function() {
      const password = document.getElementById('password').value;
      const confirmPassword = this.value;
      
      if (confirmPassword && password !== confirmPassword) {
        this.style.borderColor = '#e74c3c';
        this.style.background = '#fef7f7';
      } else {
        this.style.borderColor = '#e5e5e5';
        this.style.background = '#fafafa';
      }
    });

    // Form validation
    document.getElementById('registerForm').addEventListener('submit', function(e) {
      const password = document.getElementById('password').value;
      const confirmPassword = document.getElementById('confirm_password').value;
      const username = document.getElementById('username').value;
      const email = document.getElementById('email').value;
      const fullName = document.getElementById('full_name').value;
      
      if (password.length < 6) {
        e.preventDefault();
        alert('Password must be at least 6 characters long!');
        document.getElementById('password').focus();
        return false;
      }
      
      if (password.length > 50) {
        e.preventDefault();
        alert('Password must be less than 50 characters!');
        document.getElementById('password').focus();
        return false;
      }
      
      if (password !== confirmPassword) {
        e.preventDefault();
        alert('Passwords do not match!');
        document.getElementById('confirm_password').focus();
        return false;
      }
      
      if (username.length > 50) {
        e.preventDefault();
        alert('Username must be less than 50 characters!');
        document.getElementById('username').focus();
        return false;
      }
      
      if (email.length > 100) {
        e.preventDefault();
        alert('Email must be less than 100 characters!');
        document.getElementById('email').focus();
        return false;
      }
      
      if (fullName.length > 100) {
        e.preventDefault();
        alert('Full name must be less than 100 characters!');
        document.getElementById('full_name').focus();
        return false;
      }
    });

    // Enhanced enter key support
    document.addEventListener('keypress', function(e) {
      if (e.key === 'Enter') {
        const focused = document.activeElement;
        if (focused && focused.form && focused.form.id === 'registerForm') {
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
      
      // Auto-focus first input
      document.getElementById('full_name').focus();
    });
  </script>
</body>
</html>