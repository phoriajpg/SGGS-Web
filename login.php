<?php
session_start();

// Simple file-based password storage
$passwordFile = 'admin_password.txt';

// Redirect if already logged in
if (isset($_SESSION['authenticated'])) {
    header('Location: admin_events.php');
    exit;
}

// Check if form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $inputPassword = $_POST['password'] ?? '';
    
    // Check if password file exists, if not create with default password
    if (!file_exists($passwordFile)) {
        $defaultPassword = password_hash('admin123', PASSWORD_DEFAULT);
        file_put_contents($passwordFile, $defaultPassword);
    }
    
    $storedHash = file_get_contents($passwordFile);
    
    if (password_verify($inputPassword, $storedHash)) {
        $_SESSION['authenticated'] = true;
        header('Location: admin_events.php');
        exit;
    } else {
        $error = "Invalid password";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - SGGS Bulletin</title>
    <link href="https://fonts.googleapis.com/css2?family=Gabarito:wght@400..900&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: "Gabarito", sans-serif;
            background-color: #f5f5f5;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
        }
        .login-container {
            background-color: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            width: 300px;
        }
        .login-container h2 {
            color: #B10023;
            text-align: center;
            margin-bottom: 20px;
        }
        .form-group {
            margin-bottom: 15px;
        }
        .form-group label {
            display: block;
            margin-bottom: 5px;
        }
        .form-group input {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        button {
            background-color: #B10023;
            color: white;
            border: none;
            padding: 10px 15px;
            border-radius: 4px;
            width: 100%;
            cursor: pointer;
        }
        .error {
            color: red;
            margin-bottom: 15px;
            text-align: center;
        }
        .back-link {
            display: block;
            text-align: center;
            margin-top: 15px;
            color: #B10023;
            text-decoration: none;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <h2>Admin Login</h2>
        <?php if (isset($error)): ?>
            <div class="error"><?= htmlspecialchars($error); ?></div>
        <?php endif; ?>
        <form method="POST">
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required>
            </div>
            <button type="submit">Login</button>
            <a href="events.php" class="back-link">Back to Events</a>
        </form>
    </div>
</body>
</html>