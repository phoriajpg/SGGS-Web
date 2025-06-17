<?php
session_start();
require_once 'db_connect.php';

// Fetch all upcoming events
try {
    $stmt = $pdo->query("SELECT * FROM events ORDER BY event_date DESC");
    $events = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Error fetching events: " . $e->getMessage());
}

// Handle login if form submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['password'])) {
    $passwordFile = 'admin_password.txt';
    $inputPassword = $_POST['password'];
    
    // Check if password file exists, if not create with default password
    if (!file_exists($passwordFile)) {
        $defaultPassword = password_hash('admin123', PASSWORD_DEFAULT);
        file_put_contents($passwordFile, $defaultPassword);
    }
    
    $storedHash = file_get_contents($passwordFile);
    
    if (password_verify($inputPassword, $storedHash)) {
        $_SESSION['authenticated'] = true;
        // Refresh to show admin controls
        header("Location: events.php");
        exit;
    } else {
        $loginError = "Invalid password";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://fonts.googleapis.com/css2?family=Gabarito:wght@400..900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <title>SGGS Events</title>
    <style>
        :root {
            --primary: #B10023;
            --primary-dark: #830000;
            --accent: #f1c40f;
            --text-dark: #2c3e50;
            --text-light: #f5f5f5;
        }
        
        body {
            font-family: "Gabarito", sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f5f5f5;
            color: var(--text-dark);
            line-height: 1.6;
        }
        
        /* Navigation Styles */
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
        
        .nav-box {
            background-color: white;
            padding: 10px 20px;
            height: 60px;
            display: inline-flex;
            align-items: center;
            border-top-left-radius: 12px;
            border-bottom-left-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
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
            border-radius: 10px;
            white-space: nowrap;
            transition: all 0.3s ease;
        }
        
        .navbar a:hover {
            background-color: #B10023;
            color: white;
            transform: translateY(-2px);
        }
        
        .label {
            font-weight: 700;
            font-size: 35px;
            display: inline;
        }
        
        /* Events Container */
        .events-container {
            max-width: 1200px;
            margin: 60px auto 40px;
            padding: 20px;
        }
        
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            flex-wrap: wrap;
            gap: 20px;
        }
        
        .page-title {
            color: var(--primary);
            font-size: 2.5rem;
            margin: 0;
            flex-grow: 1;
        }
        
        /* Event Cards */
        .event-card {
            background-color: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            margin-bottom: 30px;
            display: flex;
            transition: all 0.3s ease;
        }
        
        .event-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.15);
        }
        
        .event-date {
            background-color: var(--primary);
            color: white;
            padding: 25px;
            min-width: 100px;
            text-align: center;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }
        
        .event-day {
            font-size: 2.5rem;
            font-weight: bold;
            line-height: 1;
        }
        
        .event-month {
            font-size: 1.2rem;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        .event-details {
            padding: 25px;
            flex-grow: 1;
        }
        
        .event-details h2 {
            margin: 0 0 10px 0;
            color: var(--primary);
            font-size: 1.8rem;
        }
        
        .event-meta {
            display: flex;
            gap: 20px;
            margin-bottom: 15px;
            flex-wrap: wrap;
        }
        
        .event-meta p {
            margin: 0;
            display: flex;
            align-items: center;
            gap: 8px;
            color: #555;
        }
        
        .event-description {
            margin: 15px 0;
            line-height: 1.7;
        }
        
        .event-image {
            max-width: 100%;
            height: auto;
            border-radius: 8px;
            margin-top: 15px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        
        .event-button {
            background-color: var(--primary);
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 8px;
            cursor: pointer;
            font-size: 16px;
            margin-top: 15px;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        
        .event-button:hover {
            background-color: var(--primary-dark);
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.2);
        }
        
        /* Admin Button Styles */
        .admin-button {
            background-color: var(--primary);
            color: white;
            padding: 12px 24px;
            border-radius: 8px;
            text-decoration: none;
            font-size: 16px;
            font-weight: 600;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            box-shadow: 0 4px 8px rgba(177, 0, 35, 0.2);
        }
        
        .admin-button:hover {
            background-color: var(--primary-dark);
            transform: translateY(-2px);
            box-shadow: 0 6px 12px rgba(177, 0, 35, 0.3);
        }
        
        /* Login Modal */
        .login-modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
            z-index: 1001;
            justify-content: center;
            align-items: center;
        }
        
        .login-modal.active {
            display: flex;
        }
        
        .login-box {
            background-color: white;
            padding: 30px;
            border-radius: 12px;
            width: 100%;
            max-width: 400px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            transform: translateY(-20px);
            opacity: 0;
            transition: all 0.3s ease;
        }
        
        .login-modal.active .login-box {
            transform: translateY(0);
            opacity: 1;
        }
        
        .login-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .login-title {
            color: var(--primary);
            margin: 0;
            font-size: 1.8rem;
        }
        
        .close-button {
            background: none;
            border: none;
            font-size: 24px;
            cursor: pointer;
            color: #777;
        }
        
        .login-form .form-group {
            margin-bottom: 20px;
        }
        
        .login-form label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #444;
        }
        
        .login-form input {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 16px;
            transition: border-color 0.3s ease;
        }
        
        .login-form input:focus {
            border-color: var(--primary);
            outline: none;
        }
        
        .login-error {
            color: #d9534f;
            margin-bottom: 15px;
            text-align: center;
        }
        
        .submit-button {
            width: 100%;
            padding: 12px;
            background-color: var(--primary);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .submit-button:hover {
            background-color: var(--primary-dark);
        }
        
        /* No Events Message */
        .no-events {
            text-align: center;
            padding: 40px;
            background-color: white;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        
        .no-events p {
            font-size: 1.2rem;
            color: #666;
            margin-bottom: 20px;
        }
        
        /* Responsive Styles */
        @media (max-width: 768px) {
            .event-card {
                flex-direction: column;
            }
            
            .event-date {
                flex-direction: row;
                justify-content: space-around;
                align-items: center;
                padding: 15px;
            }
            
            .event-day, .event-month {
                font-size: 1.5rem;
            }
            
            .label {
                font-size: 28px;
            }
            
            .page-header {
                flex-direction: column;
                align-items: flex-start;
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
                <a href="awards.html"><span class="label">Awards</span></a>
                <a href="index.html"><span class="label">Log Out</span></a>
                </div>
        </div>
    </nav>


<!DOCTYPE html>
<html lang="en">
<head>
    <!-- Your existing head content -->
    <style>
        /* Add these new styles */
        .admin-only {
            display: none;
        }
        
        <?php if (isset($_SESSION['authenticated'])): ?>
            .admin-only {
                display: block;
                background-color: #fff8e1;
                padding: 10px;
                border-left: 4px solid #ffc107;
                margin: 10px 0;
                border-radius: 4px;
            }
            
            .event-actions {
                display: flex;
                gap: 10px;
                margin-top: 15px;
            }
            
            .edit-btn, .delete-btn {
                padding: 5px 10px;
                border-radius: 4px;
                font-size: 14px;
                cursor: pointer;
            }
            
            .edit-btn {
                background-color: #2196F3;
                color: white;
                border: none;
            }
            
            .delete-btn {
                background-color: #f44336;
                color: white;
                border: none;
            }

            .admin-controls {
            display: flex;
            gap: 15px;
            align-items: center;
            }

        .admin-button {
        background-color: var(--primary);
        color: white;
        padding: 12px 24px;
        border-radius: 8px;
        text-decoration: none;
        font-size: 16px;
        font-weight: 600;
        transition: all 0.3s ease;
        display: inline-flex;
        align-items: center;
        gap: 10px;
        box-shadow: 0 4px 8px rgba(177, 0, 35, 0.2);
        border: none;
        cursor: pointer;
        font-family: "Gabarito", sans-serif;
    }

    .admin-button:hover {
        background-color: var(--primary-dark);
        transform: translateY(-2px);
        box-shadow: 0 6px 12px rgba(177, 0, 35, 0.3);
    }
        
        .logout-button {
        background-color: #2c3e50;
        color: white;
        padding: 12px 24px;
        border-radius: 8px;
        text-decoration: none;
        font-size: 16px;
        font-weight: 600;
        transition: all 0.3s ease;
        display: inline-flex;
        align-items: center;
        gap: 10px;
        box-shadow: 0 4px 8px rgba(44, 62, 80, 0.2);
        border: none;
        cursor: pointer;
        font-family: "Gabarito", sans-serif;
    }
        
        .logout-button:hover {
        background-color: #1a252f;
        transform: translateY(-2px);
        box-shadow: 0 6px 12px rgba(44, 62, 80, 0.3);
    }
    
    /* Add a subtle animation on click */
    .admin-button:active, .logout-button:active {
        transform: translateY(1px);
        box-shadow: 0 2px 4px rgba(0,0,0,0.2);
    }
    
    /* Make the icons slightly larger */
    .admin-button i, .logout-button i {
        font-size: 18px;
    }
        <?php endif; ?>
    </style>
</head>
<body>
    <!-- Your existing navigation -->

    <div class="events-container">
        <div class="events-container">
        <div class="page-header">
            <h1 class="page-title">
                <?php if (isset($_SESSION['authenticated'])): ?>
                    Manage Events
                <?php else: ?>
                    Upcoming Events
                <?php endif; ?>
            </h1>
            
            <?php if (isset($_SESSION['authenticated'])): ?>
                <div class="admin-controls">
                    <a href="admin_events.php" class="admin-button">
                        <i class="fas fa-plus"></i> New Event
                    </a>
                    <form action="logout.php" method="POST" style="display: inline;">
                        <button type="submit" class="logout-button">
                            <i class="fas fa-sign-out-alt"></i> Log Out
                        </button>
                    </form>
                </div>
            <?php else: ?>
                <button id="openLogin" class="admin-button">
                    <i class="fas fa-lock"></i> Admin Login
                </button>
            <?php endif; ?>
        </div>
        
        <?php if (isset($_SESSION['authenticated'])): ?>
            <div class="admin-message">
                <p>You are viewing this page as an administrator. You can edit or delete events below.</p>
            </div>
        <?php endif; ?>
        
        <?php if (empty($events)): ?>
            <div class="no-events">
                <p>
                    <?php if (isset($_SESSION['authenticated'])): ?>
                        No events found.
                    <?php else: ?>
                        No upcoming events scheduled. Check back later!
                    <?php endif; ?>
                </p>
            </div>
        <?php else: ?>
            <?php foreach ($events as $event): 
                $eventDate = new DateTime($event['event_date']);
                $startTime = new DateTime($event['start_time']);
                $endTime = new DateTime($event['end_time']);
            ?>
                <div class="event-card">
                    <div class="event-date">
                        <span class="event-day"><?= $eventDate->format('d') ?></span>
                        <span class="event-month"><?= strtoupper($eventDate->format('M')) ?></span>
                    </div>
                    <div class="event-details">
                        <h2><?= htmlspecialchars($event['title']) ?></h2>
                        
                        <?php if (isset($_SESSION['authenticated'])): ?>
                            <div class="admin-only">
                                <p><strong>Admin View:</strong> This event is <?= $event['is_active'] ? 'visible' : 'hidden' ?> to the public</p>
                            </div>
                        <?php endif; ?>
                        
                        <div class="event-meta">
                            <p><i class="far fa-clock"></i> <?= $startTime->format('g:i A') ?> - <?= $endTime->format('g:i A') ?></p>
                            <p><i class="fas fa-map-marker-alt"></i> <?= htmlspecialchars($event['location']) ?></p>
                        </div>
                        <p class="event-description"><?= htmlspecialchars($event['description']) ?></p>
                        
                        <?php if ($event['image_path']): ?>
                            <img src="<?= $event['image_path'] ?>" class="event-image" alt="Event Image">
                        <?php endif; ?>
                        
                        <?php if (isset($_SESSION['authenticated'])): ?>
                            <div class="event-actions">
                                <a href="admin_events.php?edit=<?= $event['id'] ?>" class="edit-btn">
                                    <i class="fas fa-edit"></i> Edit
                                </a>
                                <form method="POST" action="delete_event.php" style="display:inline;">
                                    <input type="hidden" name="event_id" value="<?= $event['id'] ?>">
                                    <button type="submit" class="delete-btn" onclick="return confirm('Are you sure you want to delete this event?')">
                                        <i class="fas fa-trash"></i> Delete
                                    </button>
                                </form>
                            </div>
                        <?php else: ?>
                            <button class="event-button">
                                <i class="fas fa-info-circle"></i> Learn More
                            </button>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <!-- Login Modal -->
    <div id="loginModal" class="login-modal">
        <div class="login-box">
            <div class="login-header">
                <h3 class="login-title">Admin Login</h3>
                <button class="close-button" id="closeLogin">&times;</button>
            </div>
            
            <?php if (isset($loginError)): ?>
                <div class="login-error"><?= $loginError ?></div>
            <?php endif; ?>
            
            <form class="login-form" method="POST">
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" required>
                </div>
                <button type="submit" class="submit-button">
                    <i class="fas fa-sign-in-alt"></i> Login
                </button>
            </form>
        </div>
    </div>

    <script>
        // Login modal functionality
        const openLogin = document.getElementById('openLogin');
        const closeLogin = document.getElementById('closeLogin');
        const loginModal = document.getElementById('loginModal');
        
        if (openLogin) {
            openLogin.addEventListener('click', () => {
                loginModal.classList.add('active');
            });
        }
        
        if (closeLogin) {
            closeLogin.addEventListener('click', () => {
                loginModal.classList.remove('active');
            });
        }
        
        // Close modal when clicking outside
        loginModal.addEventListener('click', (e) => {
            if (e.target === loginModal) {
                loginModal.classList.remove('active');
            }
        });
    </script>
</body>
</html>