<?php
session_start();
require_once 'db_connect.php';

// Security headers
header("X-Frame-Options: DENY");
header("X-Content-Type-Options: nosniff");
header("X-XSS-Protection: 1; mode=block");

// Display messages - PUT THIS RIGHT HERE
$success = '';
$error = '';
if (isset($_GET['success'])) {
    $success = match($_GET['success']) {
        '1' => 'Award added successfully!',
        'Award+deleted+successfully' => 'Award deleted successfully!',
        default => 'Operation completed successfully!'
    };
}
if (isset($_GET['error'])) {
    $error = match($_GET['error']) {
        '1' => 'Error adding award. Please try again.',
        'Failed+to+delete+award' => 'Error deleting award. Please try again.',
        default => 'An error occurred. Please try again.'
    };
}

// Handle logout
if (isset($_GET['logout'])) {
    session_unset();
    session_destroy();
    header("Location: events.php");     
    exit();
}

// Fetch upcoming events
try {
    $currentDate = date('Y-m-d H:i:s');
    $stmt = $pdo->prepare("SELECT * FROM events WHERE event_date >= ? ORDER BY is_featured DESC, event_date ASC");
    $stmt->execute([$currentDate]);
    $events = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    $events = [];
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
        header("Location: events.php");
        exit();
    } else {
        $login_error = "Invalid credentials";
    }
}

// Add this variable to control what shows in the header
$show_admin_controls = isset($_SESSION['admin_logged_in']);
$show_login_button = !isset($_SESSION['admin_logged_in']);

// Handle new event submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['title']) && isset($_SESSION['admin_logged_in'])) {
    $title = htmlspecialchars(trim($_POST['title']));
    $description = htmlspecialchars(trim($_POST['description']));
    $location = htmlspecialchars(trim($_POST['location']));
    $event_date = $_POST['event_date'];
    $is_featured = isset($_POST['is_featured']) ? 1 : 0;
    
    try {
        $stmt = $pdo->prepare("INSERT INTO events (title, description, location, event_date, is_featured) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$title, $description, $location, $event_date, $is_featured]);
        header("Location: events.php?success=1");
        exit();
    } catch (PDOException $e) {
        error_log("Event creation error: " . $e->getMessage());
        $error = "Error creating event";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="School Events Calendar">
    <title>SGGS Events Calendar</title>
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
        
        /* Events Grid Styles */
        .events-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
            gap: 2rem;
            margin-top: 1.5rem;
        }

        .event-card {
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

        .event-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.12);
            border-color: rgba(177, 0, 35, 0.2);
        }

        .event-card.featured {
            border-left: 4px solid var(--accent);
        }

        .event-header {
            padding: 1.5rem;
            border-bottom: 1px solid var(--light-gray);
            position: relative;
            background-color: rgba(177, 0, 35, 0.03);
        }

        .event-title {
            font-size: 1.3rem;
            font-weight: 700;
            color: var(--primary-dark);
            margin: 0 0 0.5rem 0;
            line-height: 1.4;
            padding-right: 2.5rem;
        }

        .featured-badge {
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

        .event-date {
            font-size: 0.85rem;
            color: var(--gray);
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .event-date i {
            color: var(--primary-light);
        }

        .event-body {
            padding: 1.5rem;
            flex-grow: 1;
        }

        .event-description {
            color: var(--dark);
            line-height: 1.7;
            margin-bottom: 1.5rem;
            font-size: 0.95rem;
        }

        .event-description p {
            margin-bottom: 1rem;
        }

        .event-description p:last-child {
            margin-bottom: 0;
        }

        .event-footer {
            padding: 1.25rem 1.5rem;
            border-top: 1px solid var(--light-gray);
            display: flex;
            justify-content: space-between;
            align-items: center;
            background-color: rgba(0, 0, 0, 0.02);
        }

        .event-location {
            font-style: italic;
            color: var(--gray);
            font-size: 0.85rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .event-location i {
            color: var(--primary);
        }

        .event-actions .btn.btn-danger:hover {
    background-color: #c82333 !important;
    transform: translateY(-1px) !important;
    box-shadow: 0 2px 8px rgba(220, 53, 69, 0.3) !important;
}

.event-actions .btn.btn-danger i {
    width: 14px !important;
    height: 14px !important;
    display: flex !important;
    align-items: center !important;
    justify-content: center !important;
}

        /* Countdown styles */
        .countdown {
            display: flex;
            gap: 0.5rem;
            align-items: center;
            font-size: 0.9rem;
        }

        .countdown-item {
            background-color: var(--primary);
            color: white;
            padding: 0.25rem 0.5rem;
            border-radius: 4px;
            font-weight: 600;
            min-width: 2.5rem;
            text-align: center;
        }

        .countdown-label {
            color: var(--gray);
            font-size: 0.7rem;
            text-transform: uppercase;
            margin-top: 0.2rem;
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

        /* Admin badge */
        .admin-badge {
            position: fixed;
            bottom: 20px;
            right: 20px;
            background-color: var(--primary);
            color: var(--white);
            padding: 0.75rem 1.5rem;
            border-radius: 0.5rem;
            font-weight: 500;
            cursor: pointer;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            z-index: 100;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            display: none;
        }

        .admin-badge:hover {
            background-color: var(--primary-dark);
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

        /* Responsive */
        @media (max-width: 768px) {
            .navbar {
                padding: 0 1rem;
            }
            
            .nav-links {
                gap: 0.75rem;
            }
            
            .events-grid {
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

/* Alert Messages - Improved Version */
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
                <i class="fas fa-calendar-alt"></i>
                SGGS Events Calendar
            </h1>
            <div class="header-actions">
                <?php if ($show_admin_controls): ?>
                    <button id="newEventBtn" class="btn btn-primary">
                        <i class="fas fa-plus"></i> New Event
                    </button>
                    <form action="events.php" method="GET" style="display: inline;">
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

        <!-- New Event Form (hidden by default) -->
        <?php if (isset($_SESSION['admin_logged_in'])): ?>
            <div id="newEventForm" class="form-container" style="display: none;">
                <div class="form-header">
                    <h2 class="form-title">
                        <i class="fas fa-calendar-plus"></i>
                        Add New Event
                    </h2>
                </div>
                <form method="POST">
                    <div class="form-body">
                        <div class="form-group">
                            <label for="title">Event Title</label>
                            <input type="text" id="title" name="title" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label for="description">Description</label>
                            <textarea id="description" name="description" class="form-control" required></textarea>
                        </div>
                        <div class="form-group">
                            <label for="location">Location</label>
                            <input type="text" id="location" name="location" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label for="event_date">Date & Time</label>
                            <input type="datetime-local" id="event_date" name="event_date" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label>
                                <input type="checkbox" id="is_featured" name="is_featured">
                                Mark as Featured
                            </label>
                        </div>
                    </div>
                    <div class="form-footer">
                        <button type="button" id="cancelEventBtn" class="btn btn-secondary">
                            <i class="fas fa-times"></i> Cancel
                        </button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-calendar-check"></i> Add Event
                        </button>
                    </div>
                </form>
            </div>
        <?php endif; ?>

        <!-- Events Grid -->
        <div class="events-grid">
            <?php if (empty($events)): ?>
                <div class="empty-state">
                    <i class="fas fa-calendar-times fa-3x"></i>
                    <h3>No Upcoming Events</h3>
                    <p>There are currently no scheduled events. Check back later!</p>
                </div>
            <?php else: ?>
                <?php foreach ($events as $event): ?>
                    <div class="event-card <?= $event['is_featured'] ? 'featured' : '' ?>">
                        <div class="event-header">
                            <h2 class="event-title"><?= htmlspecialchars($event['title']) ?></h2>
                            <?php if ($event['is_featured']): ?>
                                <span class="featured-badge">
                                    <i class="fas fa-star"></i> Featured
                                </span>
                            <?php endif; ?>
                            <div class="event-date">
                                <i class="far fa-calendar-alt"></i>
                                <?= date('F j, Y \a\t g:i a', strtotime($event['event_date'])) ?>
                            </div>
                        </div>
                        <div class="event-body">
                            <div class="event-description">
                                <?= nl2br(htmlspecialchars($event['description'])) ?>
                            </div>
                            <div class="countdown" data-event-date="<?= $event['event_date'] ?>">
                                <div>
                                    <div class="countdown-item" id="days-<?= $event['id'] ?>">00</div>
                                    <div class="countdown-label">Days</div>
                                </div>
                                <div>
                                    <div class="countdown-item" id="hours-<?= $event['id'] ?>">00</div>
                                    <div class="countdown-label">Hours</div>
                                </div>
                                <div>
                                    <div class="countdown-item" id="minutes-<?= $event['id'] ?>">00</div>
                                    <div class="countdown-label">Minutes</div>
                                </div>
                                <div>
                                    <div class="countdown-item" id="seconds-<?= $event['id'] ?>">00</div>
                                    <div class="countdown-label">Seconds</div>
                                </div>
                            </div>
                        </div>
                        <div class="event-footer">
                            <div class="event-location">
                                <i class="fas fa-map-marker-alt"></i> <?= htmlspecialchars($event['location']) ?>
                            </div>
                            <?php if (isset($_SESSION['admin_logged_in'])): ?>
                                <div class="event-actions">
                                    <form method="POST" action="delete_event.php" 
                                        onsubmit="return confirm('Permanently delete this event?');">
                                        <input type="hidden" name="id" value="<?= $event['id'] ?>">
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
        // Toggle new event form
        const newEventBtn = document.getElementById('newEventBtn');
        const cancelEventBtn = document.getElementById('cancelEventBtn');
        const newEventForm = document.getElementById('newEventForm');
        
        if (newEventBtn && newEventForm) {
            newEventBtn.addEventListener('click', () => {
                newEventForm.style.display = 'block';
                newEventBtn.style.display = 'none';
            });
            
            cancelEventBtn.addEventListener('click', () => {
                newEventForm.style.display = 'none';
                newEventBtn.style.display = 'inline-flex';
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
        
        // Update countdown every second
function updateCountdowns() {
    const countdowns = document.querySelectorAll('.countdown');
    
    countdowns.forEach(countdown => {
        const eventDate = new Date(countdown.dataset.eventDate).getTime();
        const now = new Date().getTime();
        const distance = eventDate - now;
        
        // If event passed, show "Event Started"
        if (distance < 0) {
            countdown.innerHTML = '<div class="countdown-ended">Event Started</div>';
            return;
        }
        
        // Calculate days, hours, minutes, seconds
        const days = Math.floor(distance / (1000 * 60 * 60 * 24));
        const hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
        const minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
        const seconds = Math.floor((distance % (1000 * 60)) / 1000);
        
        // Update the countdown UI (works for both admin and non-admin)
        countdown.innerHTML = `
            <div>
                <div class="countdown-item">${days.toString().padStart(2, '0')}</div>
                <div class="countdown-label">Days</div>
            </div>
            <div>
                <div class="countdown-item">${hours.toString().padStart(2, '0')}</div>
                <div class="countdown-label">Hours</div>
            </div>
            <div>
                <div class="countdown-item">${minutes.toString().padStart(2, '0')}</div>
                <div class="countdown-label">Minutes</div>
            </div>
            <div>
                <div class="countdown-item">${seconds.toString().padStart(2, '0')}</div>
                <div class="countdown-label">Seconds</div>
            </div>
        `;
    });
}

// Start the timer
setInterval(updateCountdowns, 1000);
updateCountdowns(); // Initial call
        
        // Auto-hide success message
        const successMessage = document.querySelector('.alert-success');
        if (successMessage) {
            setTimeout(() => {
                successMessage.style.transition = 'opacity 0.5s ease';
                successMessage.style.opacity = '0';
                setTimeout(() => successMessage.remove(), 500);
            }, 5000);
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