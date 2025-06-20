<?php
session_start();
require_once 'db_connect.php';

if (!isset($_SESSION['authenticated'])) {
    header('Location: login.php');  // Assuming this is your login page
    exit;
}

// Initialize variables
$success = '';
$error = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_event'])) {
        // Add new event
        $title = $_POST['title'];
        $description = $_POST['description'];
        $event_date = $_POST['event_date'];
        $location = $_POST['location'];
        $is_active = isset($_POST['is_active']) ? 1 : 0;
        
        try {
            $stmt = $pdo->prepare("INSERT INTO events (title, description, event_date, location, is_active) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$title, $description, $event_date, $location, $is_active]);
            $success = "Event added successfully!";
        } catch (PDOException $e) {
            $error = "Error adding event: " . $e->getMessage();
        }
    } elseif (isset($_POST['update_event'])) {
        // Update existing event
        $id = $_POST['event_id'];
        $title = $_POST['title'];
        $description = $_POST['description'];
        $event_date = $_POST['event_date'];
        $location = $_POST['location'];
        $is_active = isset($_POST['is_active']) ? 1 : 0;
        
        try {
            $stmt = $pdo->prepare("UPDATE events SET title = ?, description = ?, event_date = ?, location = ?, is_active = ? WHERE id = ?");
            $stmt->execute([$title, $description, $event_date, $location, $is_active, $id]);
            $success = "Event updated successfully!";
        } catch (PDOException $e) {
            $error = "Error updating event: " . $e->getMessage();
        }
    } elseif (isset($_POST['delete_event'])) {
        // Delete event
        $id = $_POST['event_id'];
        
        try {
            $stmt = $pdo->prepare("DELETE FROM events WHERE id = ?");
            $stmt->execute([$id]);
            $success = "Event deleted successfully!";
        } catch (PDOException $e) {
            $error = "Error deleting event: " . $e->getMessage();
        }
    }
}

// Fetch all events
$stmt = $pdo->query("SELECT * FROM events ORDER BY event_date DESC");
$events = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Events - Admin Panel</title>
    <link href="https://fonts.googleapis.com/css2?family=Gabarito:wght@400..900&family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        :root {
            --primary: #B10023;
            --primary-light: #e63946;
            --primary-dark: #830000;
            --accent: #f1c40f;
            --dark: #2c3e50;
            --light: #f8f9fa;
            --gray: #6c757d;
            --light-gray: #e9ecef;
            --white: #ffffff;
            --success: #28a745;
            --danger: #dc3545;
            --warning: #fd7e14;
            --info: #17a2b8;
        }
        
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }
        
        body {
            font-family: 'Gabarito', sans-serif;
            background-color: #f5f7fa;
            color: var(--dark);
            line-height: 1.6;
        }
        
        /* Navigation */
        .navbar {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            background: var(--white);
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            height: 70px;
            display: flex;
            align-items: center;
            padding: 0 2rem;
            z-index: 1000;
        }
        
        .nav-brand {
            font-size: 1.5rem;
            font-weight: 600;
            color: var(--primary);
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .nav-links {
            display: flex;
            margin-left: auto;
            gap: 1.5rem;
        }
        
        .nav-links a {
            color: var(--dark);
            text-decoration: none;
            font-weight: 500;
            padding: 0.5rem 1rem;
            border-radius: 0.5rem;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .nav-links a:hover, .nav-links a.active {
            background-color: var(--primary);
            color: var(--white);
        }
        
        .nav-links a i {
            font-size: 1.1rem;
        }
        
        /* Main Container */
        .admin-container {
            max-width: 1400px;
            margin: 90px auto 40px;
            padding: 0 20px;
        }
        
        /* Header */
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
        }
        
        /* Cards */
        .card {
            background: var(--white);
            border-radius: 0.75rem;
            box-shadow: 0 4px 20px rgba(0,0,0,0.05);
            margin-bottom: 2rem;
            overflow: hidden;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        
        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
        }
        
        .card-header {
            padding: 1.5rem;
            border-bottom: 1px solid var(--light-gray);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .card-title {
            font-size: 1.25rem;
            font-weight: 600;
            color: var(--primary);
            margin: 0;
        }
        
        .card-body {
            padding: 1.5rem;
        }
        
        /* Forms */
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
            min-height: 120px;
            resize: vertical;
        }
        
        .form-row {
            display: flex;
            gap: 1rem;
            margin-bottom: 1.25rem;
        }
        
        .form-row .form-group {
            flex: 1;
            margin-bottom: 0;
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
        
        .btn-secondary:hover {
            background-color: #5a6268;
        }
        
        .btn-danger {
            background-color: var(--danger);
            color: var(--white);
        }
        
        .btn-danger:hover {
            background-color: #c82333;
        }
        
        .btn-sm {
            padding: 0.5rem 1rem;
            font-size: 0.875rem;
        }
        
        /* Alerts */
        .alert {
            padding: 1rem;
            border-radius: 0.5rem;
            margin-bottom: 1.5rem;
            font-weight: 500;
        }
        
        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .alert-error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        /* Tables */
        .table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 1rem;
        }
        
        .table th, .table td {
            padding: 1rem;
            text-align: left;
            border-bottom: 1px solid var(--light-gray);
        }
        
        .table th {
            background-color: var(--primary);
            color: var(--white);
            font-weight: 500;
            text-transform: uppercase;
            font-size: 0.875rem;
            letter-spacing: 0.5px;
        }
        
        .table tr:hover {
            background-color: rgba(0,0,0,0.02);
        }
        
        /* Badges */
        .badge {
            display: inline-block;
            padding: 0.35rem 0.65rem;
            font-size: 0.75rem;
            font-weight: 600;
            border-radius: 50rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .badge-success {
            background-color: #d4edda;
            color: #155724;
        }
        
        .badge-danger {
            background-color: #f8d7da;
            color: #721c24;
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
            overflow-y: auto;
            padding: 2rem;
        }
        
        .modal.show {
            display: flex;
            justify-content: center;
            align-items: flex-start;
        }
        
        .modal-content {
            background-color: var(--white);
            border-radius: 0.75rem;
            width: 100%;
            max-width: 700px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            transform: translateY(-50px);
            opacity: 0;
            transition: all 0.3s ease;
        }
        
        .modal.show .modal-content {
            transform: translateY(0);
            opacity: 1;
        }
        
        .modal-header {
            padding: 1.5rem;
            border-bottom: 1px solid var(--light-gray);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .modal-title {
            font-size: 1.5rem;
            font-weight: 600;
            color: var(--primary);
            margin: 0;
        }
        
        .modal-body {
            padding: 1.5rem;
        }
        
        .modal-footer {
            padding: 1.5rem;
            border-top: 1px solid var(--light-gray);
            display: flex;
            justify-content: flex-end;
            gap: 1rem;
        }
        
        .close {
            background: none;
            border: none;
            font-size: 1.5rem;
            cursor: pointer;
            color: var(--gray);
        }
        
        /* Utilities */
        .text-muted {
            color: var(--gray);
        }
        
        .text-center {
            text-align: center;
        }
        
        .mb-3 {
            margin-bottom: 1rem;
        }
        
        .mb-4 {
            margin-bottom: 1.5rem;
        }
        
        .mt-3 {
            margin-top: 1rem;
        }
        
        .d-flex {
            display: flex;
        }
        
        .align-items-center {
            align-items: center;
        }
        
        .justify-content-between {
            justify-content: space-between;
        }
        
        .gap-2 {
            gap: 0.5rem;
        }
        
        .gap-3 {
            gap: 1rem;
        }
        
        /* Responsive */
        @media (max-width: 992px) {
            .form-row {
                flex-direction: column;
                gap: 1.25rem;
            }
        }
        
        @media (max-width: 768px) {
            .navbar {
                padding: 0 1rem;
            }
            
            .nav-links {
                gap: 0.75rem;
            }
            
            .table {
                display: block;
                overflow-x: auto;
                white-space: nowrap;
            }
            
            .action-buttons {
                flex-direction: column;
                gap: 0.5rem;
            }
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar">
        <a href="admin.php" class="nav-brand">
            <i class="fas fa-university"></i>
            Campus Admin
        </a>
        <div class="nav-links">
            <a href="admin_dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
            <a href="admin_events.php" class="active"><i class="fas fa-calendar-check"></i> Events</a>
            <a href="admin_announcements.php"><i class="fas fa-bullhorn"></i> Announcements</a>
            <a href="admin_users.php"><i class="fas fa-users"></i> Users</a>
            <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="admin-container">
        <div class="page-header">
            <h1 class="page-title">
                <i class="fas fa-calendar-check"></i>
                Manage Events
            </h1>
            <div class="d-flex gap-2">
                <a href="events.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Back
                </a>
            </div>
        </div>
        
        <?php if (isset($success)): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i> <?= $success ?>
            </div>
        <?php elseif (isset($error)): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i> <?= $error ?>
            </div>
        <?php endif; ?>
        
        <!-- Add Event Form -->
        <div class="card mb-4">
            <div class="card-header">
                <h2 class="card-title">
                    <i class="fas fa-plus-circle"></i>
                    Add New Event
                </h2>
            </div>
            <div class="card-body">
                <form method="POST">
                    <input type="hidden" name="add_event" value="1">
                    
                    <div class="form-group">
                        <label for="title">Event Title</label>
                        <input type="text" id="title" name="title" class="form-control" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="description">Description</label>
                        <textarea id="description" name="description" class="form-control" required></textarea>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="event_date">Event Date</label>
                            <input type="datetime-local" id="event_date" name="event_date" class="form-control" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="location">Location</label>
                            <input type="text" id="location" name="location" class="form-control" required>
                        </div>
                    </div>
                    
                    <div class="form-group checkbox-group">
                        <input type="checkbox" id="is_active" name="is_active" checked>
                        <label for="is_active">Active (Visible on website)</label>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Save Event
                    </button>
                </form>
            </div>
        </div>
        
        <!-- Events List -->
        <div class="card">
            <div class="card-header">
                <h2 class="card-title">
                    <i class="fas fa-list"></i>
                    All Events
                </h2>
            </div>
            <div class="card-body">
                <?php if (empty($events)): ?>
                    <div class="text-center text-muted py-4">
                        <i class="fas fa-calendar fa-3x mb-3"></i>
                        <h3>No Events Found</h3>
                        <p>Create your first event using the form above</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Title</th>
                                    <th>Description Preview</th>
                                    <th>Date & Time</th>
                                    <th>Location</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($events as $event): 
                                    $eventDate = new DateTime($event['event_date']);
                                    $descriptionPreview = strlen($event['description']) > 50 
                                        ? substr($event['description'], 0, 50) . '...' 
                                        : $event['description'];
                                ?>
                                    <tr>
                                        <td><?= htmlspecialchars($event['title']) ?></td>
                                        <td><?= htmlspecialchars($descriptionPreview) ?></td>
                                        <td><?= $eventDate->format('M j, Y g:i A') ?></td>
                                        <td><?= htmlspecialchars($event['location']) ?></td>
                                        <td>
                                            <span class="badge <?= $event['is_active'] ? 'badge-success' : 'badge-danger' ?>">
                                                <?= $event['is_active'] ? 'Active' : 'Inactive' ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="d-flex gap-2 action-buttons">
                                                <button onclick="openEditModal(
                                                    <?= $event['id'] ?>, 
                                                    '<?= htmlspecialchars($event['title'], ENT_QUOTES) ?>',
                                                    `<?= htmlspecialchars($event['description'], ENT_QUOTES) ?>`,
                                                    '<?= $event['event_date'] ?>',
                                                    '<?= htmlspecialchars($event['location'], ENT_QUOTES) ?>',
                                                    <?= $event['is_active'] ? 'true' : 'false' ?>
                                                )" class="btn btn-sm btn-secondary">
                                                    <i class="fas fa-edit"></i> Edit
                                                </button>
                                                <form method="POST" style="display:inline;">
                                                    <input type="hidden" name="delete_event" value="1">
                                                    <input type="hidden" name="event_id" value="<?= $event['id'] ?>">
                                                    <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to delete this event?')">
                                                        <i class="fas fa-trash"></i> Delete
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Edit Event Modal -->
    <div id="editModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title">
                    <i class="fas fa-edit"></i>
                    Edit Event
                </h3>
                <button type="button" class="close" onclick="closeEditModal()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body">
                <form method="POST" id="editForm">
                    <input type="hidden" name="update_event" value="1">
                    <input type="hidden" name="event_id" id="edit_event_id">
                    
                    <div class="form-group">
                        <label for="edit_title">Event Title</label>
                        <input type="text" id="edit_title" name="title" class="form-control" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="edit_description">Description</label>
                        <textarea id="edit_description" name="description" class="form-control" required></textarea>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="edit_event_date">Event Date</label>
                            <input type="datetime-local" id="edit_event_date" name="event_date" class="form-control" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="edit_location">Location</label>
                            <input type="text" id="edit_location" name="location" class="form-control" required>
                        </div>
                    </div>
                    
                    <div class="form-group checkbox-group">
                        <input type="checkbox" id="edit_is_active" name="is_active">
                        <label for="edit_is_active">Active (Visible on website)</label>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeEditModal()">
                    <i class="fas fa-times"></i> Cancel
                </button>
                <button type="submit" form="editForm" class="btn btn-primary">
                    <i class="fas fa-save"></i> Save Changes
                </button>
            </div>
        </div>
    </div>
    
    <script>
        // Edit modal functions
        function openEditModal(id, title, description, event_date, location, is_active) {
            document.getElementById('edit_event_id').value = id;
            document.getElementById('edit_title').value = title;
            document.getElementById('edit_description').value = description;
            
            // Format the datetime for the input field
            const eventDate = new Date(event_date);
            const formattedDate = eventDate.toISOString().slice(0, 16);
            document.getElementById('edit_event_date').value = formattedDate;
            
            document.getElementById('edit_location').value = location;
            document.getElementById('edit_is_active').checked = is_active;
            
            document.getElementById('editModal').classList.add('show');
            document.body.style.overflow = 'hidden';
        }
        
        function closeEditModal() {
            document.getElementById('editModal').classList.remove('show');
            document.body.style.overflow = 'auto';
        }
        
        // Close modal when clicking outside
        document.getElementById('editModal').addEventListener('click', function(event) {
            if (event.target === this) {
                closeEditModal();
            }
        });
        
        // Close modal with ESC key
        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape') {
                closeEditModal();
            }
        });
    </script>
</body>
</html>