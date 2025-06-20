<?php
session_start();
require_once 'db_connect1.php';

// Security headers
header("X-Frame-Options: DENY");
header("X-Content-Type-Options: nosniff");
header("X-XSS-Protection: 1; mode=block");

// Display messages
$success = '';
$error = '';
if (isset($_GET['success'])) {
    $success = match($_GET['success']) {
        '1' => 'Announcement added successfully!',
        'Announcement+deleted+successfully' => 'Announcement deleted successfully!',
        default => 'Operation completed successfully!'
    };
}
if (isset($_GET['error'])) {
    $error = match($_GET['error']) {
        '1' => 'Error adding announcement. Please try again.',
        'Failed+to+delete+announcement' => 'Error deleting announcement. Please try again.',
        default => 'An error occurred. Please try again.'
    };
}

// Handle logout
if (isset($_GET['logout'])) {
    session_unset();
    session_destroy();
    header("Location: bulletin.php");
    exit();
}

// Fetch active announcements
try {
    $stmt = $pdo->query("SELECT * FROM announcements WHERE is_active = TRUE ORDER BY is_urgent DESC, date_posted DESC");
    $announcements = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    $announcements = [];
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
        header("Location: bulletin.php");
        exit();
    } else {
        $login_error = "Invalid credentials";
    }
}

// Add this variable to control what shows in the header
$show_admin_controls = isset($_SESSION['admin_logged_in']);
$show_login_button = !isset($_SESSION['admin_logged_in']);

// Handle new announcement submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['title']) && isset($_SESSION['admin_logged_in'])) {
    $title = htmlspecialchars(trim($_POST['title']));
    $content = htmlspecialchars(trim($_POST['content']));
    $author = htmlspecialchars(trim($_POST['author']));
    $isUrgent = isset($_POST['is_urgent']) ? 1 : 0;
    
    try {
        $stmt = $pdo->prepare("INSERT INTO announcements (title, content, author, is_urgent) VALUES (?, ?, ?, ?)");
        $stmt->execute([$title, $content, $author, $isUrgent]);
        header("Location: bulletin.php?success=1");
        exit();
    } catch (PDOException $e) {
        error_log("Announcement creation error: " . $e->getMessage());
        $error = "Error creating announcement";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="College Bulletin Board">
    <title>SGGS Bulletin Board</title>
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
        
        /* Main Container */
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem;
        }
        
        /* Header */
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
        
        /* Announcement Grid Styles */
        .announcement-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
            gap: 2rem;
            margin-top: 1.5rem;
        }

        .announcement-card {
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

        .announcement-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.12);
            border-color: rgba(177, 0, 35, 0.2);
        }

        .announcement-card.urgent {
            border-left: 4px solid var(--warning);
        }

        .announcement-header {
            padding: 1.5rem;
            border-bottom: 1px solid var(--light-gray);
            position: relative;
            background-color: rgba(177, 0, 35, 0.03);
        }

        .announcement-title {
            font-size: 1.3rem;
            font-weight: 700;
            color: var(--primary-dark);
            margin: 0 0 0.5rem 0;
            line-height: 1.4;
            padding-right: 2.5rem;
        }

        .urgent-badge {
            position: absolute;
            top: 1.5rem;
            right: 1.5rem;
            background-color: var(--warning);
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

        .announcement-date {
            font-size: 0.85rem;
            color: var(--gray);
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .announcement-date i {
            color: var(--primary-light);
        }

        .announcement-body {
            padding: 1.5rem;
            flex-grow: 1;
        }

        .announcement-content {
            color: var(--dark);
            line-height: 1.7;
            margin-bottom: 1.5rem;
            font-size: 0.95rem;
        }

        .announcement-content p {
            margin-bottom: 1rem;
        }

        .announcement-content p:last-child {
            margin-bottom: 0;
        }

        .announcement-footer {
            padding: 1.25rem 1.5rem;
            border-top: 1px solid var(--light-gray);
            display: flex;
            justify-content: space-between;
            align-items: center;
            background-color: rgba(0, 0, 0, 0.02);
        }

        .announcement-author {
            font-style: italic;
            color: var(--gray);
            font-size: 0.85rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .announcement-author i {
            color: var(--primary);
        }

        .announcement-actions .btn.btn-danger:hover {
            background-color: #c82333 !important;
            transform: translateY(-1px) !important;
            box-shadow: 0 2px 8px rgba(220, 53, 69, 0.3) !important;
        }

        .announcement-actions .btn.btn-danger i {
            width: 14px !important;
            height: 14px !important;
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
        }

        /* Empty State */
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

        /* Forms */
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
            position: relative;
            padding-right: 3rem;
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
            color: white;
        }

        /* Modal */
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

        /* Close Button Styles */
        .close-btn {
            position: absolute;
            top: 1.5rem;
            right: 1rem;
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
            background-color: rgba(177, 0, 35, 0.1);
        }

        /* Alert Messages */
        .alert {
            padding: 15px 20px;
            margin-bottom: 20px;
            border-radius: 4px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: relative;
            animation: fadeIn 0.3s ease-in-out;
        }

        .alert-success {
            background-color: rgba(40, 167, 69, 0.15);
            color: #155724;
            border-left: 4px solid var(--success);
        }

        .alert-error {
            background-color: rgba(220, 53, 69, 0.15);
            color: #721c24;
            border-left: 4px solid var(--danger);
        }

        .close-alert {
            background: none;
            border: none;
            color: inherit;
            cursor: pointer;
            padding: 0;
            margin-left: 15px;
            opacity: 0.7;
            transition: opacity 0.2s;
        }

        .close-alert:hover {
            opacity: 1;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        @keyframes fadeOut {
            from { opacity: 1; }
            to { opacity: 0; }
        }

        /* Responsive Adjustments */
        @media (max-width: 768px) {
            .announcement-grid {
                grid-template-columns: 1fr;
                gap: 1.5rem;
            }
            
            .announcement-card {
                border-radius: 10px;
            }
            
            .announcement-header {
                padding: 1.25rem;
            }
            
            .announcement-title {
                font-size: 1.2rem;
            }
            
            .urgent-badge {
                top: 1.25rem;
                right: 1.25rem;
            }
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
                <i class="fas fa-bullhorn"></i>
                SGGS Bulletin Board
            </h1>
            <div class="header-actions">
                <?php if ($show_admin_controls): ?>
                    <button id="newPostBtn" class="btn btn-primary">
                        <i class="fas fa-plus"></i> New Announcement
                    </button>
                    <form action="bulletin.php" method="GET" style="display: inline;">
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
                <div>
                    <i class="fas fa-check-circle"></i> 
                    <?= htmlspecialchars($success) ?>
                </div>
                <button type="button" class="close-alert" aria-label="Close">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        <?php endif; ?>

        <?php if (!empty($error)): ?>
            <div class="alert alert-error">
                <div>
                    <i class="fas fa-exclamation-circle"></i> 
                    <?= htmlspecialchars($error) ?>
                </div>
                <button type="button" class="close-alert" aria-label="Close">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        <?php endif; ?>

        <!-- New Announcement Form (hidden by default) -->
        <?php if (isset($_SESSION['admin_logged_in'])): ?>
            <div id="newPostForm" class="form-container" style="display: none;">
                <div class="form-header">
                    <h2 class="form-title">
                        <i class="fas fa-edit"></i>
                        Create New Announcement
                    </h2>
                </div>
                <form method="POST">
                    <div class="form-body">
                        <div class="form-group">
                            <label for="title">Title</label>
                            <input type="text" id="title" name="title" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label for="content">Content</label>
                            <textarea id="content" name="content" class="form-control" required></textarea>
                        </div>
                        <div class="form-group">
                            <label for="author">Author</label>
                            <input type="text" id="author" name="author" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label>
                                <input type="checkbox" id="is_urgent" name="is_urgent">
                                Mark as Urgent
                            </label>
                        </div>
                    </div>
                    <div class="form-footer">
                        <button type="button" id="cancelPostBtn" class="btn btn-secondary">
                            <i class="fas fa-times"></i> Cancel
                        </button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-paper-plane"></i> Post Announcement
                        </button>
                    </div>
                </form>
            </div>
        <?php endif; ?>

        <!-- Announcements Grid -->
        <div class="announcement-grid">
            <?php if (empty($announcements)): ?>
                <div class="empty-state">
                    <i class="fas fa-bullhorn fa-3x"></i>
                    <h3>No Announcements Found</h3>
                    <p>There are currently no announcements to display.</p>
                </div>
            <?php else: ?>
                <?php foreach ($announcements as $announcement): ?>
                    <div class="announcement-card <?= $announcement['is_urgent'] ? 'urgent' : '' ?>">
                        <div class="announcement-header">
                            <h2 class="announcement-title"><?= htmlspecialchars($announcement['title']) ?></h2>
                            <?php if ($announcement['is_urgent']): ?>
                                <span class="urgent-badge">
                                    <i class="fas fa-exclamation"></i> Urgent
                                </span>
                            <?php endif; ?>
                            <div class="announcement-date">
                                <i class="far fa-calendar-alt"></i>
                                <?= date('F j, Y \a\t g:i a', strtotime($announcement['date_posted'])) ?>
                            </div>
                        </div>
                        <div class="announcement-body">
                            <div class="announcement-content">
                                <?= nl2br(htmlspecialchars($announcement['content'])) ?>
                            </div>
                        </div>
                        <div class="announcement-footer">
                            <div class="announcement-author">
                                <i class="fas fa-user-edit"></i> <?= htmlspecialchars($announcement['author']) ?>
                            </div>
                            <?php if (isset($_SESSION['admin_logged_in'])): ?>
                                <div class="announcement-actions">
                                    <form method="POST" action="delete_announcement.php" 
                                        onsubmit="return confirm('Permanently delete this announcement?');">
                                        <input type="hidden" name="id" value="<?= $announcement['id'] ?>">
                                        <button type="submit" class="btn btn-danger btn-sm">
                                            <i class="fas fa-trash-alt"></i> Delete
                                        </button>
                                    </form>
                                </div>
                            <?php endif; ?>
                        </div>
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
        // Toggle new announcement form
        const newPostBtn = document.getElementById('newPostBtn');
        const cancelPostBtn = document.getElementById('cancelPostBtn');
        const newPostForm = document.getElementById('newPostForm');
        
        if (newPostBtn && newPostForm) {
            newPostBtn.addEventListener('click', () => {
                newPostForm.style.display = 'block';
                newPostBtn.style.display = 'none';
            });
            
            cancelPostBtn.addEventListener('click', () => {
                newPostForm.style.display = 'none';
                newPostBtn.style.display = 'inline-flex';
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

        // Auto-hide alerts after 5 seconds or when clicked
        document.querySelectorAll('.alert').forEach(alert => {
            // Close on click
            const closeBtn = alert.querySelector('.close-alert');
            if (closeBtn) {
                closeBtn.addEventListener('click', () => {
                    alert.style.animation = 'fadeOut 0.3s ease-out';
                    setTimeout(() => alert.remove(), 300);
                });
            }
            
            // Auto-hide after delay
            setTimeout(() => {
                alert.style.transition = 'opacity 0.5s ease';
                alert.style.opacity = '0';
                setTimeout(() => alert.remove(), 500);
            }, 5000);
        });
    </script>
</body>
</html>