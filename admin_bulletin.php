<?php
session_start();
require_once 'db_connect.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) {
    header("Location: login.php");
    exit;
}

// Initialize variables
$success = '';
$error = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_announcement'])) {
        // Add new announcement
        $title = $_POST['title'];
        $content = $_POST['content'];
        $is_active = isset($_POST['is_active']) ? 1 : 0;
        $is_important = isset($_POST['is_important']) ? 1 : 0;
        $category = $_POST['category'];
        $current_date = date('Y-m-d H:i:s');
        
        try {
            $stmt = $pdo->prepare("INSERT INTO announcements (title, content, is_active, is_important, category, created_at) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$title, $content, $is_active, $is_important, $category, $current_date]);
            $success = "Announcement added successfully!";
        } catch (PDOException $e) {
            $error = "Error adding announcement: " . $e->getMessage();
        }
    } elseif (isset($_POST['update_announcement'])) {
        // Update existing announcement
        $id = $_POST['announcement_id'];
        $title = $_POST['title'];
        $content = $_POST['content'];
        $is_active = isset($_POST['is_active']) ? 1 : 0;
        $is_important = isset($_POST['is_important']) ? 1 : 0;
        $category = $_POST['category'];
        
        try {
            $stmt = $pdo->prepare("UPDATE announcements SET title = ?, content = ?, is_active = ?, is_important = ?, category = ? WHERE id = ?");
            $stmt->execute([$title, $content, $is_active, $is_important, $category, $id]);
            $success = "Announcement updated successfully!";
        } catch (PDOException $e) {
            $error = "Error updating announcement: " . $e->getMessage();
        }
    } elseif (isset($_POST['delete_announcement'])) {
        // Delete announcement
        $id = $_POST['announcement_id'];
        
        try {
            $stmt = $pdo->prepare("DELETE FROM announcements WHERE id = ?");
            $stmt->execute([$id]);
            $success = "Announcement deleted successfully!";
        } catch (PDOException $e) {
            $error = "Error deleting announcement: " . $e->getMessage();
        }
    } elseif (isset($_POST['bulk_action'])) {
        // Handle bulk actions
        $action = $_POST['bulk_action'];
        $selected = $_POST['selected_announcements'] ?? [];
        
        if (!empty($selected)) {
            try {
                $placeholders = implode(',', array_fill(0, count($selected), '?'));
                
                if ($action === 'delete') {
                    $stmt = $pdo->prepare("DELETE FROM announcements WHERE id IN ($placeholders)");
                    $stmt->execute($selected);
                    $success = "Selected announcements deleted successfully!";
                } elseif ($action === 'activate') {
                    $stmt = $pdo->prepare("UPDATE announcements SET is_active = 1 WHERE id IN ($placeholders)");
                    $stmt->execute($selected);
                    $success = "Selected announcements activated successfully!";
                } elseif ($action === 'deactivate') {
                    $stmt = $pdo->prepare("UPDATE announcements SET is_active = 0 WHERE id IN ($placeholders)");
                    $stmt->execute($selected);
                    $success = "Selected announcements deactivated successfully!";
                } elseif ($action === 'mark_important') {
                    $stmt = $pdo->prepare("UPDATE announcements SET is_important = 1 WHERE id IN ($placeholders)");
                    $stmt->execute($selected);
                    $success = "Selected announcements marked as important!";
                }
            } catch (PDOException $e) {
                $error = "Error performing bulk action: " . $e->getMessage();
            }
        } else {
            $error = "No announcements selected for bulk action!";
        }
    }
}

// Fetch all announcements with optional filtering
$category_filter = $_GET['category'] ?? '';
$status_filter = $_GET['status'] ?? '';
$importance_filter = $_GET['importance'] ?? '';

$query = "SELECT * FROM announcements";
$params = [];
$conditions = [];

if ($category_filter) {
    $conditions[] = "category = ?";
    $params[] = $category_filter;
}

if ($status_filter === 'active') {
    $conditions[] = "is_active = 1";
} elseif ($status_filter === 'inactive') {
    $conditions[] = "is_active = 0";
}

if ($importance_filter === 'important') {
    $conditions[] = "is_important = 1";
} elseif ($importance_filter === 'normal') {
    $conditions[] = "is_important = 0";
}

if (!empty($conditions)) {
    $query .= " WHERE " . implode(" AND ", $conditions);
}

$query .= " ORDER BY is_important DESC, created_at DESC";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$announcements = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get distinct categories for filter dropdown
$categories = $pdo->query("SELECT DISTINCT category FROM announcements WHERE category IS NOT NULL AND category != '' ORDER BY category")->fetchAll(PDO::FETCH_COLUMN);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Announcements - Admin Panel</title>
    <link href="https://fonts.googleapis.com/css2?family=Gabarito:wght@400..900&family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        /* Previous CSS remains the same, just adding new styles for the enhancements */
        
        /* New styles for filter section */
        .filter-section {
            background-color: var(--white);
            border-radius: 0.75rem;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            box-shadow: 0 4px 20px rgba(0,0,0,0.05);
        }
        
        .filter-row {
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
            margin-bottom: 1rem;
        }
        
        .filter-group {
            flex: 1;
            min-width: 200px;
        }
        
        .filter-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: var(--dark);
        }
        
        .filter-select {
            width: 100%;
            padding: 0.75rem 1rem;
            border: 1px solid var(--light-gray);
            border-radius: 0.5rem;
            font-family: inherit;
            font-size: 1rem;
            background-color: var(--white);
            transition: border-color 0.3s ease;
        }
        
        .filter-select:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(177, 0, 35, 0.1);
        }
        
        .filter-actions {
            display: flex;
            gap: 1rem;
            align-items: flex-end;
        }
        
        /* Bulk actions */
        .bulk-actions {
            display: flex;
            gap: 1rem;
            margin-bottom: 1.5rem;
            align-items: center;
        }
        
        .bulk-select-all {
            margin-right: 1rem;
        }
        
        /* Category badges */
        .badge-category {
            background-color: var(--light-gray);
            color: var(--dark);
            padding: 0.35rem 0.65rem;
            border-radius: 0.5rem;
            font-size: 0.75rem;
            font-weight: 600;
        }
        
        .badge-important {
            background-color: var(--warning);
            color: var(--white);
        }
        
        /* Checkbox styling */
        .checkbox-group {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-bottom: 1rem;
        }
        
        .checkbox-group input[type="checkbox"] {
            width: auto;
            margin: 0;
        }
        
        /* Responsive adjustments */
        @media (max-width: 768px) {
            .filter-row {
                flex-direction: column;
                gap: 1rem;
            }
            
            .filter-group {
                min-width: 100%;
            }
            
            .filter-actions {
                width: 100%;
            }
            
            .bulk-actions {
                flex-wrap: wrap;
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
            <a href="admin_events.php"><i class="fas fa-calendar-check"></i> Events</a>
            <a href="admin_announcements.php" class="active"><i class="fas fa-bullhorn"></i> Announcements</a>
            <a href="admin_users.php"><i class="fas fa-users"></i> Users</a>
            <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="admin-container">
        <div class="page-header">
            <h1 class="page-title">
                <i class="fas fa-bullhorn"></i>
                Manage Announcements
            </h1>
            <div class="d-flex gap-2">
                <a href="announcements.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Back to Public View
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
        
        <!-- Filter Section -->
        <div class="filter-section">
            <form method="GET">
                <div class="filter-row">
                    <div class="filter-group">
                        <label for="category">Category</label>
                        <select id="category" name="category" class="filter-select">
                            <option value="">All Categories</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?= htmlspecialchars($cat) ?>" <?= $category_filter === $cat ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($cat) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="filter-group">
                        <label for="status">Status</label>
                        <select id="status" name="status" class="filter-select">
                            <option value="">All Statuses</option>
                            <option value="active" <?= $status_filter === 'active' ? 'selected' : '' ?>>Active</option>
                            <option value="inactive" <?= $status_filter === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                        </select>
                    </div>
                    
                    <div class="filter-group">
                        <label for="importance">Importance</label>
                        <select id="importance" name="importance" class="filter-select">
                            <option value="">All</option>
                            <option value="important" <?= $importance_filter === 'important' ? 'selected' : '' ?>>Important</option>
                            <option value="normal" <?= $importance_filter === 'normal' ? 'selected' : '' ?>>Normal</option>
                        </select>
                    </div>
                    
                    <div class="filter-actions">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-filter"></i> Filter
                        </button>
                        <a href="admin_announcements.php" class="btn btn-secondary">
                            <i class="fas fa-sync-alt"></i> Reset
                        </a>
                    </div>
                </div>
            </form>
        </div>
        
        <!-- Add Announcement Form -->
        <div class="card mb-4">
            <div class="card-header">
                <h2 class="card-title">
                    <i class="fas fa-plus-circle"></i>
                    Add New Announcement
                </h2>
            </div>
            <div class="card-body">
                <form method="POST">
                    <input type="hidden" name="add_announcement" value="1">
                    
                    <div class="form-row">
                        <div class="form-group" style="flex: 2;">
                            <label for="title">Announcement Title</label>
                            <input type="text" id="title" name="title" class="form-control" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="category">Category</label>
                            <input type="text" id="category" name="category" class="form-control" list="category-list">
                            <datalist id="category-list">
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?= htmlspecialchars($cat) ?>">
                                <?php endforeach; ?>
                            </datalist>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="content">Content</label>
                        <textarea id="content" name="content" class="form-control" required></textarea>
                    </div>
                    
                    <div class="form-row">
                        <div class="checkbox-group">
                            <input type="checkbox" id="is_active" name="is_active" checked>
                            <label for="is_active">Active (Visible on website)</label>
                        </div>
                        
                        <div class="checkbox-group">
                            <input type="checkbox" id="is_important" name="is_important">
                            <label for="is_important">Mark as Important</label>
                        </div>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Save Announcement
                    </button>
                </form>
            </div>
        </div>
        
        <!-- Announcements List -->
        <div class="card">
            <div class="card-header">
                <h2 class="card-title">
                    <i class="fas fa-list"></i>
                    All Announcements
                </h2>
            </div>
            <div class="card-body">
                <?php if (empty($announcements)): ?>
                    <div class="text-center text-muted py-4">
                        <i class="fas fa-bullhorn fa-3x mb-3"></i>
                        <h3>No Announcements Found</h3>
                        <p>Create your first announcement using the form above</p>
                    </div>
                <?php else: ?>
                    <form method="POST" id="bulkForm">
                        <div class="bulk-actions">
                            <div class="checkbox-group bulk-select-all">
                                <input type="checkbox" id="selectAll">
                                <label for="selectAll">Select All</label>
                            </div>
                            
                            <select name="bulk_action" class="filter-select" style="width: auto;">
                                <option value="">Bulk Actions</option>
                                <option value="activate">Activate</option>
                                <option value="deactivate">Deactivate</option>
                                <option value="mark_important">Mark as Important</option>
                                <option value="delete">Delete</option>
                            </select>
                            
                            <button type="submit" class="btn btn-sm btn-primary" onclick="return confirm('Are you sure you want to perform this action on selected announcements?')">
                                <i class="fas fa-check"></i> Apply
                            </button>
                            
                            <div class="text-muted" style="margin-left: auto;">
                                <?= count($announcements) ?> announcement(s) found
                            </div>
                        </div>
                        
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th width="40px"></th>
                                        <th>Title</th>
                                        <th>Category</th>
                                        <th>Content Preview</th>
                                        <th>Date Created</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($announcements as $announcement): 
                                        $createdDate = new DateTime($announcement['created_at']);
                                        $contentPreview = strlen($announcement['content']) > 50 
                                            ? substr($announcement['content'], 0, 50) . '...' 
                                            : $announcement['content'];
                                    ?>
                                        <tr class="<?= $announcement['is_important'] ? 'important-row' : '' ?>">
                                            <td>
                                                <input type="checkbox" name="selected_announcements[]" value="<?= $announcement['id'] ?>">
                                            </td>
                                            <td>
                                                <?= htmlspecialchars($announcement['title']) ?>
                                                <?php if ($announcement['is_important']): ?>
                                                    <span class="badge badge-important ml-2">Important</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if ($announcement['category']): ?>
                                                    <span class="badge-category"><?= htmlspecialchars($announcement['category']) ?></span>
                                                <?php endif; ?>
                                            </td>
                                            <td><?= htmlspecialchars($contentPreview) ?></td>
                                            <td><?= $createdDate->format('M j, Y g:i A') ?></td>
                                            <td>
                                                <span class="badge <?= $announcement['is_active'] ? 'badge-success' : 'badge-danger' ?>">
                                                    <?= $announcement['is_active'] ? 'Active' : 'Inactive' ?>
                                                </span>
                                            </td>
                                            <td>
                                                <div class="d-flex gap-2 action-buttons">
                                                    <button onclick="openEditModal(
                                                        <?= $announcement['id'] ?>, 
                                                        '<?= htmlspecialchars($announcement['title'], ENT_QUOTES) ?>',
                                                        `<?= htmlspecialchars($announcement['content'], ENT_QUOTES) ?>`,
                                                        <?= $announcement['is_active'] ? 'true' : 'false' ?>,
                                                        <?= $announcement['is_important'] ? 'true' : 'false' ?>,
                                                        '<?= htmlspecialchars($announcement['category'], ENT_QUOTES) ?>'
                                                    )" class="btn btn-sm btn-secondary">
                                                        <i class="fas fa-edit"></i> Edit
                                                    </button>
                                                    <form method="POST" style="display:inline;">
                                                        <input type="hidden" name="delete_announcement" value="1">
                                                        <input type="hidden" name="announcement_id" value="<?= $announcement['id'] ?>">
                                                        <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to delete this announcement?')">
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
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Edit Announcement Modal -->
    <div id="editModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title">
                    <i class="fas fa-edit"></i>
                    Edit Announcement
                </h3>
                <button type="button" class="close" onclick="closeEditModal()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body">
                <form method="POST" id="editForm">
                    <input type="hidden" name="update_announcement" value="1">
                    <input type="hidden" name="announcement_id" id="edit_announcement_id">
                    
                    <div class="form-row">
                        <div class="form-group" style="flex: 2;">
                            <label for="edit_title">Announcement Title</label>
                            <input type="text" id="edit_title" name="title" class="form-control" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="edit_category">Category</label>
                            <input type="text" id="edit_category" name="category" class="form-control" list="category-list">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="edit_content">Content</label>
                        <textarea id="edit_content" name="content" class="form-control" required></textarea>
                    </div>
                    
                    <div class="form-row">
                        <div class="checkbox-group">
                            <input type="checkbox" id="edit_is_active" name="is_active">
                            <label for="edit_is_active">Active (Visible on website)</label>
                        </div>
                        
                        <div class="checkbox-group">
                            <input type="checkbox" id="edit_is_important" name="is_important">
                            <label for="edit_is_important">Mark as Important</label>
                        </div>
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
        function openEditModal(id, title, content, is_active, is_important, category) {
            document.getElementById('edit_announcement_id').value = id;
            document.getElementById('edit_title').value = title;
            document.getElementById('edit_content').value = content;
            document.getElementById('edit_is_active').checked = is_active;
            document.getElementById('edit_is_important').checked = is_important;
            document.getElementById('edit_category').value = category || '';
            
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
        
        // Bulk select all functionality
        document.getElementById('selectAll').addEventListener('change', function() {
            const checkboxes = document.querySelectorAll('input[name="selected_announcements[]"]');
            checkboxes.forEach(checkbox => {
                checkbox.checked = this.checked;
            });
        });
        
        // Prevent form submission if no bulk action is selected
        document.getElementById('bulkForm').addEventListener('submit', function(e) {
            const bulkAction = document.querySelector('select[name="bulk_action"]');
            if (bulkAction.value === '') {
                e.preventDefault();
                alert('Please select a bulk action to perform.');
            }
        });
    </script>
</body>
</html>