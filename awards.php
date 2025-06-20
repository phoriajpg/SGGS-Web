<?php
session_start();
require_once 'db_connect2.php';

// Security headers
header("X-Frame-Options: DENY");
header("X-Content-Type-Options: nosniff");
header("X-XSS-Protection: 1; mode=block");

// Display messages
$success = '';
$error = '';
if (isset($_GET['success'])) {
    $success = match($_GET['success']) {
        '1' => 'Award added successfully!',
        'Award+was+successfully+removed' => 'Award deleted successfully!',
        default => 'Operation completed successfully!'
    };
}
if (isset($_GET['error'])) {
    $error = match($_GET['error']) {
        '1' => 'Error adding award. Please try again.',
        'Could+not+remove+award' => 'Error deleting award. Please try again.',
        default => 'An error occurred. Please try again.'
    };
}

// Handle logout
if (isset($_GET['logout'])) {
    session_unset();
    session_destroy();
    header("Location: awards.php");
    exit();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['student_name']) && isset($_SESSION['admin_logged_in'])) {
    $student_name = htmlspecialchars(trim($_POST['student_name']));
    $award_name = htmlspecialchars(trim($_POST['award_name']));
    $achievement = htmlspecialchars(trim($_POST['achievement']));
    $award_date = $_POST['award_date'];
    $category = htmlspecialchars(trim($_POST['category']));
    
    try {
        $stmt = $pdo->prepare("INSERT INTO awards (student_name, award_name, achievement, award_date, category) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$student_name, $award_name, $achievement, $award_date, $category]);
        header("Location: awards.php?success=1");
        exit();
    } catch (PDOException $e) {
        error_log("Database error: " . $e->getMessage());
        header("Location: awards.php?error=1");
        exit();
    }
}

// Fetch active awards
try {
    $stmt = $pdo->query("SELECT * FROM awards WHERE is_active = TRUE ORDER BY award_date DESC");
    $awards = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    $awards = [];
}

// Admin login handling
$login_error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['username'])) {
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    
    // In production, use database-stored credentials
    $admin_username = 'admin';
    $admin_password = 'password123'; // Replace with hashed password in production
    
    if ($username === $admin_username && $password === $admin_password) {
        $_SESSION['admin_logged_in'] = true;
        $_SESSION['admin_username'] = $username;
        header("Location: awards.php");
        exit();
    } else {
        $login_error = "Invalid credentials";
    }
}

$show_admin_controls = isset($_SESSION['admin_logged_in']);
$show_login_button = !isset($_SESSION['admin_logged_in']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="School Awards Gallery">
    <title>SGGS Awards Gallery</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Gabarito:wght@400..900&family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #B10023;
            --primary-light: #e63946;
            --primary-dark: #830000;
            --accent: #f1c40f;
            --accent-dark: #f39c12;
            --dark: #2c3e50;
            --light: #f8f9fa;
            --gray: #6c757d;
            --light-gray: #e9ecef;
            --white: #ffffff;
            --success: #28a745;
            --danger: #dc3545;
            --warning: #fd7e14;
            --info: #17a2b8;
            --light-gray-btn: #3a3a4a;
            --light-gray-btn-dark: #2c2c38;
        }
        
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }
        
        body {
            font-family: 'Gabarito', sans-serif;
            background-color: var(--light);
            color: var(--dark);
            line-height: 1.6;
            padding-top: 120px;
        }
        
        /* ========== Navbar Styles ========== */
        .nav-box {
            background-color: white;
  padding: 10px 20px;
  height: 80px;
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

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem;
        }
        
        .header-actions {
            display: flex;
            gap: 1rem;
            align-items: center;
        }
        
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            flex-wrap: wrap;
            gap: 1rem;
        }
        
        .page-title {
            color: var(--primary);
            font-size: 2rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
        
        .alert {
            padding: 1rem;
            margin-bottom: 1.5rem;
            border-radius: 0.5rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .alert-success {
            background-color: rgba(40, 167, 69, 0.15);
            color: var(--success);
            border-left: 4px solid var(--success);
        }

        .alert-error {
            background-color: rgba(220, 53, 69, 0.15);
            color: var(--danger);
            border-left: 4px solid var(--danger);
        }

        .awards-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
            gap: 2rem;
            margin-top: 1.5rem;
        }

        .award-card {
            background: var(--white);
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
            overflow: hidden;
            transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
            border: 1px solid rgba(0, 0, 0, 0.05);
            display: flex;
            flex-direction: column;
            height: 100%;
        }

        .award-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.12);
            border-color: rgba(177, 0, 35, 0.2);
        }

        .award-card.special {
            border-left: 4px solid var(--accent);
        }

        .award-header {
            padding: 1.5rem;
            border-bottom: 1px solid var(--light-gray);
            position: relative;
            background-color: rgba(177, 0, 35, 0.03);
        }

        .award-title {
            font-size: 1.3rem;
            font-weight: 700;
            color: var(--primary-dark);
            margin: 0 0 0.5rem 0;
            line-height: 1.4;
            padding-right: 2.5rem;
        }

        .special-badge {
            position: absolute;
            top: 1.5rem;
            right: 1.5rem;
            background-color: var(--accent);
            color: var(--dark);
            padding: 0.35rem 0.9rem;
            border-radius: 6px;
            font-size: 0.8rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            display: flex;
            align-items: center;
            gap: 0.4rem;
        }

        .award-date {
            font-size: 0.85rem;
            color: var(--gray);
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .award-date i {
            color: var(--primary-light);
        }

        .award-body {
            padding: 1.5rem;
            flex-grow: 1;
        }

        .award-student {
            font-size: 1.1rem;
            font-weight: 600;
            color: var(--primary);
            margin-bottom: 0.75rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .award-student i {
            color: var(--primary-light);
        }

        .award-achievement {
            color: var(--dark);
            line-height: 1.7;
            margin-bottom: 1.5rem;
            font-size: 0.95rem;
        }

        .award-category {
            display: inline-block;
            background-color: rgba(177, 0, 35, 0.1);
            color: var(--primary-dark);
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
        }

        /* Awards Card Footer - Updated Version */
.award-footer {
    padding: 1.25rem 1.5rem;
    border-top: 1px solid var(--light-gray);
    display: flex;
    justify-content: flex-end; /* Pushes button to the far right */
    align-items: center;
    background-color: rgba(0, 0, 0, 0.02);
}

.award-author {
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.award-author-avatar {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    object-fit: cover;
}

.award-author-info {
    display: flex;
    flex-direction: column;
}

.award-author-name {
    font-weight: 600;
    font-size: 0.9rem;
    color: var(--dark);
}

.award-date {
    font-size: 0.75rem;
    color: var(--gray);
}

.award-actions {
    display: flex;
    gap: 0.5rem;
}

/* Button Styles - Matching Bulletin */


.award-actions .btn.btn-danger:hover {
    background-color: #c82333 !important;
    transform: translateY(-1px) !important;
    box-shadow: 0 2px 8px rgba(220, 53, 69, 0.3) !important;
}

.award-actions .btn.btn-danger i {
    width: 14px !important;
    height: 14px !important;
    display: flex !important;
    align-items: center !important;
    justify-content: center !important;
}




        .empty-state {
            grid-column: 1 / -1;
            text-align: center;
            padding: 3rem;
            background-color: rgba(0, 0, 0, 0.02);
            border-radius: 12px;
            border: 1px dashed var(--light-gray);
        }

        .empty-state i {
            font-size: 3rem;
            color: var(--gray);
            margin-bottom: 1.5rem;
            opacity: 0.5;
        }

        .empty-state h3 {
            color: var(--dark);
            margin-bottom: 0.5rem;
            font-size: 1.5rem;
        }

        .empty-state p {
            color: var(--gray);
            max-width: 500px;
            margin: 0 auto;
        }

        .form-container {
            background: var(--white);
            border-radius: 0.75rem;
            box-shadow: 0 4px 20px rgba(0,0,0,0.05);
            margin-bottom: 2rem;
            overflow: hidden;
        }

        .form-header {
            padding: 1.5rem;
            border-bottom: 1px solid var(--light-gray);
            position: relative; /* Needed to contain the absolute-positioned close button */
            padding-right: 3rem; /* Prevents text overlap with the close button */
        }

        .form-title {
            font-size: 1.25rem;
            font-weight: 600;
            color: var(--primary);
            margin: 0;
        }

        .form-body {
            padding: 1.5rem;
        }

        .form-group {
            margin-bottom: 1.25rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: var(--dark);
        }

        .form-control {
            width: 100%;
            padding: 0.75rem 1rem;
            border: 1px solid var(--light-gray);
            border-radius: 0.5rem;
            font-family: inherit;
            font-size: 1rem;
            transition: border-color 0.3s ease;
        }

        .form-control:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(177, 0, 35, 0.1);
        }

        textarea.form-control {
            min-height: 150px;
            resize: vertical;
        }

        .form-footer {
            padding: 1.5rem;
            border-top: 1px solid var(--light-gray);
            display: flex;
            justify-content: flex-end;
            gap: 1rem;
        }

        /* Buttons */
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 0.75rem 1.5rem;
            border-radius: 0.5rem;
            font-weight: 500;
            text-decoration: none;
            cursor: pointer;
            transition: all 0.3s ease;
            border: none;
            font-size: 1rem;
            gap: 0.5rem;
        }

        .btn-primary {
            background-color: var(--primary);
            color: var(--white);
        }

        .btn-primary:hover {
            background-color: var(--primary-dark);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(177, 0, 35, 0.2);
        }

        .btn-secondary {
            background-color: var(--gray);
            color: var(--white);
        }


        .btn-light-gray {
        background-color: var(--light-gray-btn) !important;
        color: white !important;
        }

        .btn-light-gray:hover {
        background-color: var(--light-gray-btn-dark);
        color: white; /* Text color on hover */
        }

        .btn-light-gray {
        color: #eeeeee; /* Slightly gray when clicked */
        }
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
            z-index: 1050;
        }

        .modal.show {
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .modal-content {
            background-color: var(--white);
            border-radius: 0.75rem;
            width: 100%;
            max-width: 500px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
        }

        @media (max-width: 768px) {
            .navbar {
                padding: 0 1rem;
            }
            
            .nav-links {
                gap: 0.75rem;
            }
            
            .awards-grid {
                grid-template-columns: 1fr;
            }
        }


        /* Close Button Styles */
.close-btn {
    position: absolute;
    top: 1.5rem;       /* Distance from top of form-header */
    right: 1rem;     /* Distance from right of form-header */
    width: 2rem;
    height: 2rem;
    border: none;
    background: none;
    cursor: pointer;
    color: var(--gray);
    font-size: 1.25rem;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
    transition: all 0.3s ease;
}

.close-btn:hover {
    color: var(--primary);
    background-color: rgba(177, 0, 35, 0.1); /* Light red hover background */
}


    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar">
      <div class="nav-box">
        <div class="nav-links">
            <a href="student.html"><span class="label">Home</span></a>
            <a href="bulletin.php"><span class="label">Bulletin</span></a>
            <a href="events.php"><span class="label">Events</span></a>
            <a href="awards.php"><span class="label">Awards</span></a>
            <a href="index.html"><span class="label">Log Out</span></a>
        </div>
      </div>
    </nav>

    <div class="container">
        <div class="page-header">
            <h1 class="page-title">
                <i class="fas fa-trophy"></i>
                SGGS Awards Gallery
            </h1>
            <div class="header-actions">
                <?php if ($show_admin_controls): ?>
                    <button id="newAwardBtn" class="btn btn-primary">
                        <i class="fas fa-plus"></i> New Award
                    </button>
                    <form action="awards.php" method="GET" style="display: inline;">
                        <input type="hidden" name="logout" value="1">
                        <button type="submit" class="btn btn-light-gray">
                            <i class="fas fa-sign-out-alt"></i> Logout
                        </button>
                    </form>
                <?php elseif ($show_login_button): ?>
                    <button id="adminLoginBtn" class="btn btn-primary">
                        <i class="fas fa-lock"></i> Admin Login
                    </button>
                <?php endif; ?>
            </div>
        </div>

        <?php if (!empty($success)): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i> <?= htmlspecialchars($success) ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($error)): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <?php if ($login_error): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($login_error) ?>
            </div>
        <?php endif; ?>

        <!-- New Award Form (hidden by default) -->
        <?php if (isset($_SESSION['admin_logged_in'])): ?>
            <div id="newAwardForm" class="form-container" style="display: none;">
                <div class="form-header">
                    <h2 class="form-title">
                        <i class="fas fa-trophy"></i>
                        Add New Award
                    </h2>
                </div>
                <form method="POST" action="">
                    <div class="form-body">
                        <div class="form-group">
                            <label for="student_name">Student Name</label>
                            <input type="text" id="student_name" name="student_name" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label for="award_name">Award Name</label>
                            <input type="text" id="award_name" name="award_name" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label for="achievement">Achievement Description</label>
                            <textarea id="achievement" name="achievement" class="form-control" required></textarea>
                        </div>
                        <div class="form-group">
                            <label for="award_date">Date Awarded</label>
                            <input type="date" id="award_date" name="award_date" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label for="category">Category</label>
                            <select id="category" name="category" class="form-control" required>
                                <option value="">Select a category</option>
                                <option value="Academic">Academic</option>
                                <option value="Sports">Sports</option>
                                <option value="Arts">Arts</option>
                                <option value="Leadership">Leadership</option>
                                <option value="Community Service">Community Service</option>
                                <option value="Special Recognition">Special Recognition</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-footer">
                        <button type="button" id="cancelAwardBtn" class="btn btn-secondary">
                            <i class="fas fa-times"></i> Cancel
                        </button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-trophy"></i> Add Award
                        </button>
                    </div>
                </form>
            </div>
        <?php endif; ?>

        <!-- Awards Grid -->
        <div class="awards-grid">
            <?php if (empty($awards)): ?>
                <div class="empty-state">
                    <i class="fas fa-trophy fa-3x"></i>
                    <h3>No Awards Found</h3>
                    <p>There are currently no awards to display.</p>
                </div>
            <?php else: ?>
                <?php foreach ($awards as $award): ?>
                    <div class="award-card <?= $award['category'] === 'Special Recognition' ? 'special' : '' ?>">
                        <div class="award-header">
                            <h2 class="award-title"><?= htmlspecialchars($award['award_name']) ?></h2>
                            <?php if ($award['category'] === 'Special Recognition'): ?>
                                <span class="special-badge">
                                    <i class="fas fa-star"></i> Special
                                </span>
                            <?php endif; ?>
                            <div class="award-date">
                                <i class="far fa-calendar-alt"></i>
                                <?= date('F j, Y', strtotime($award['award_date'])) ?>
                            </div>
                        </div>
                        <div class="award-body">
                            <div class="award-student">
                                <i class="fas fa-user-graduate"></i> <?= htmlspecialchars($award['student_name']) ?>
                            </div>
                            <div class="award-achievement">
                                <?= nl2br(htmlspecialchars($award['achievement'])) ?>
                            </div>
                            <span class="award-category"><?= htmlspecialchars($award['category']) ?></span>
                        </div>
                        <?php if (isset($_SESSION['admin_logged_in'])): ?>
                            <div class="award-footer">
                                <div class="award-actions">
            <form method="POST" action="delete_award.php" onsubmit="return confirm('Are you sure you want to delete this award?');">
                <input type="hidden" name="id" value="<?= htmlspecialchars($award['id']) ?>">
                <button type="submit" class="btn btn-danger btn-sm">
                    <i class="fas fa-trash-alt"></i> Delete
                </button>
            </form>
        </div>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- Login Modal -->
    <div id="loginModal" class="modal">
        <div class="modal-content">
            <div class="form-header">
                <h2 class="form-title">
                    <i class="fas fa-lock"></i>
                    Admin Login
                </h2>
                <button type="button" class="close-btn" id="closeLoginModal" aria-label="Close">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <form method="POST">
                <div class="form-body">
                    <?php if ($login_error): ?>
                        <div class="alert alert-error">
                            <i class="fas fa-exclamation-circle"></i> <?= $login_error ?>
                        </div>
                    <?php endif; ?>
                    <div class="form-group">
                        <label for="loginUsername">Username</label>
                        <input type="text" id="loginUsername" name="username" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label for="loginPassword">Password</label>
                        <input type="password" id="loginPassword" name="password" class="form-control" required>
                    </div>
                </div>
                <div class="form-footer">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-sign-in-alt"></i> Login
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
// Toggle new award form
const newAwardBtn = document.getElementById('newAwardBtn');
const cancelAwardBtn = document.getElementById('cancelAwardBtn');
const newAwardForm = document.getElementById('newAwardForm');

if (newAwardBtn && newAwardForm) {
    newAwardBtn.addEventListener('click', () => {
        newAwardForm.style.display = 'block';
        newAwardBtn.style.display = 'none';
    });
    
    cancelAwardBtn.addEventListener('click', () => {
        newAwardForm.style.display = 'none';
        newAwardBtn.style.display = 'inline-flex';
    });
}

// Login modal functionality
const adminLoginBtn = document.getElementById('adminLoginBtn');
const loginModal = document.getElementById('loginModal');
const closeLoginModal = document.getElementById('closeLoginModal');

if (adminLoginBtn && loginModal) {
    adminLoginBtn.addEventListener('click', () => {
        loginModal.classList.add('show');
    });
}

if (closeLoginModal) {
    closeLoginModal.addEventListener('click', () => {
        loginModal.classList.remove('show');
    });
}

if (loginModal) {
    loginModal.addEventListener('click', (e) => {
        if (e.target === loginModal) {
            loginModal.classList.remove('show');
        }
    });
}

// Auto-hide success message
const successMessage = document.querySelector('.alert-success');
if (successMessage) {
    setTimeout(() => {
        successMessage.style.transition = 'opacity 0.5s ease';
        successMessage.style.opacity = '0';
        setTimeout(() => successMessage.remove(), 500);
    }, 5000);
}

document.addEventListener('DOMContentLoaded', function() {
    // Get reference buttons
    const bulletinBtn = document.querySelector('.announcement-actions .btn-danger');
    const awardBtns = document.querySelectorAll('.award-actions .btn-danger');
    
    if(bulletinBtn && awardBtns.length > 0) {
        // Copy exact pixel measurements
        awardBtns.forEach(btn => {
            btn.style.cssText = `
                padding: 6.5px 13px !important;
                font-size: 13px !important;
                line-height: 1.5 !important;
                min-height: 32px !important;
                min-width: 68px !important;
                border-radius: 4px !important;
            `;
            
            // Copy icon sizing
            const icon = btn.querySelector('i');
            if(icon) {
                icon.style.cssText = `
                    font-size: 12px !important;
                    margin-right: 4px !important;
                    width: 12px !important;
                    height: 12px !important;
                `;
            }
        });
    }
});
</script>
</body>
</html>