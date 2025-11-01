<?php
session_start();

// Check for admin session - using the same variables your login sets
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'admin') {
    header('Location: login.php');
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <link href="https://fonts.googleapis.com/css2?family=Gabarito:wght@400..900&display=swap" rel="stylesheet" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
  <title>Admin Dashboard - SGGSWeb</title>
  <style>
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }

    body {
      font-family: "Gabarito", sans-serif;
      background: linear-gradient(135deg, #B10023 0%, #830000 100%);
      min-height: 100vh;
      color: #333;
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

    .label {
      font-weight: 700;
      font-size: 35px;
      display: inline;
    }

    /* Main Content */
    .main-content {
      padding-top: 120px;
      min-height: 100vh;
      display: flex;
      flex-direction: column;
      align-items: center;
    }

    .dashboard-container {
      background: white;
      border-radius: 20px;
      box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
      padding: 40px;
      max-width: 1200px;
      width: 95%;
      margin-bottom: 40px;
    }

    .welcome-header {
      text-align: center;
      margin-bottom: 40px;
      padding-bottom: 20px;
      border-bottom: 3px solid #B10023;
    }

    .welcome-header h1 {
      color: #B10023;
      font-size: 2.5rem;
      margin-bottom: 10px;
    }

    .welcome-header p {
      color: #666;
      font-size: 1.2rem;
    }

/* Admin Cards Grid - 2x2 Layout */
.admin-cards {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 25px;
  margin-bottom: 40px;
}

.admin-card {
  background: #f8f9fa;
  border-radius: 15px;
  padding: 30px;
  text-align: center;
  transition: all 0.3s ease;
  border: 2px solid transparent;
  text-decoration: none;
  color: inherit;
  position: relative;
  overflow: hidden;
  min-height: 250px;
  display: flex;
  flex-direction: column;
  justify-content: center;
  align-items: center;
}

.admin-card:hover {
  transform: translateY(-5px);
  box-shadow: 0 10px 30px rgba(177, 0, 35, 0.2);
  border-color: #B10023;
  background: white;
}

.admin-card::before {
  content: '';
  position: absolute;
  top: 0;
  left: -100%;
  width: 100%;
  height: 100%;
  background: linear-gradient(90deg, transparent, rgba(177, 0, 35, 0.1), transparent);
  transition: left 0.5s;
}

.admin-card:hover::before {
  left: 100%;
}

.card-icon {
  font-size: 3rem;
  color: #B10023;
  margin-bottom: 20px;
  transition: transform 0.3s ease;
}

.admin-card:hover .card-icon {
  transform: scale(1.1);
}

.card-title {
  font-size: 1.4rem;
  font-weight: 600;
  color: #333;
  margin-bottom: 10px;
}

.card-description {
  color: #666;
  font-size: 1rem;
  line-height: 1.5;
}

    /* Quick Stats */
    .stats-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
      gap: 20px;
      margin-top: 40px;
    }

    .stat-card {
      background: linear-gradient(135deg, #B10023, #830000);
      color: white;
      padding: 25px;
      border-radius: 12px;
      text-align: center;
      transition: transform 0.3s ease;
    }

    .stat-card:hover {
      transform: translateY(-3px);
      box-shadow: 0 8px 25px rgba(177, 0, 35, 0.3);
    }

    .stat-number {
      font-size: 2.5rem;
      font-weight: bold;
      margin-bottom: 5px;
    }

    .stat-label {
      font-size: 1rem;
      opacity: 0.9;
    }

    /* Recent Activity */
    .recent-activity {
      background: #f8f9fa;
      border-radius: 15px;
      padding: 30px;
      margin-top: 40px;
    }

    .recent-activity h3 {
      color: #B10023;
      margin-bottom: 20px;
      font-size: 1.5rem;
    }

    .activity-list {
      list-style: none;
    }

    .activity-item {
      padding: 15px 0;
      border-bottom: 1px solid #e9ecef;
      display: flex;
      align-items: center;
      gap: 15px;
    }

    .activity-item:last-child {
      border-bottom: none;
    }

    .activity-icon {
      color: #B10023;
      font-size: 1.2rem;
    }

    .activity-text {
      color: #555;
    }

    /* Responsive Design */
    @media (max-width: 768px) {
      .dashboard-container {
        padding: 25px;
        margin: 20px;
      }

      .admin-cards {
        grid-template-columns: 1fr;
      }

      .welcome-header h1 {
        font-size: 2rem;
      }

      .nav-links {
        gap: 10px;
      }

      .navbar a {
        padding: 6px 12px;
        font-size: 14px;
      }
    }

    @media (max-width: 480px) {
      .dashboard-container {
        padding: 20px;
      }

      .admin-card {
        padding: 20px;
      }

      .card-icon {
        font-size: 2.5rem;
      }
    }

    /* Session Info (for debugging) */
    .session-info {
      background: #e9ecef;
      padding: 15px;
      border-radius: 8px;
      margin-top: 20px;
      font-size: 0.9rem;
      color: #666;
    }

    /* Quick Actions */
    .quick-actions {
      display: flex;
      gap: 15px;
      justify-content: center;
      margin-top: 30px;
      flex-wrap: wrap;
    }

    .quick-action-btn {
      background: #28a745;
      color: white;
      padding: 12px 24px;
      border: none;
      border-radius: 8px;
      font-family: 'Gabarito', sans-serif;
      font-size: 1rem;
      font-weight: 600;
      cursor: pointer;
      transition: all 0.3s ease;
      text-decoration: none;
      display: inline-flex;
      align-items: center;
      gap: 8px;
    }

    .quick-action-btn:hover {
      background: #218838;
      transform: translateY(-2px);
      box-shadow: 0 5px 15px rgba(40, 167, 69, 0.3);
    }

    .quick-action-btn.secondary {
      background: #6c757d;
    }

    .quick-action-btn.secondary:hover {
      background: #545b62;
    }
  </style>
</head>
<body>
  <div id="home" class="container">

    <!-- Main Content -->
    <div class="main-content">
      <div class="dashboard-container">
        <!-- Navbar -->
        <nav class="navbar">
          <div class="nav-box">
            <div class="nav-links">
              <a href="admin_dashboard.php"><span class="label">Dashboard</span></a>
              <a href="bulletin.php"><span class="label">Bulletin</span></a>
              <a href="events.php"><span class="label">Events</span></a>
              <a href="awards.php"><span class="label">Awards</span></a>
              <a href="qna.php"><span class="label">Q&A</span></a>
              <a href="logout.php"><span class="label">Log Out</span></a>
            </div>
          </div>
        </nav>

        <!-- Welcome Header -->
        <div class="welcome-header">
          <h1>Admin Dashboard</h1>
          <p>Welcome back, <?php echo htmlspecialchars($_SESSION['full_name'] ?? 'Administrator'); ?>!</p>
          <p style="color: #28a745; font-weight: 600; margin-top: 10px;">
            <i class="fas fa-shield-alt"></i> Administrator Mode - Full System Access
          </p>
        </div>

        <!-- Admin Cards Grid -->
        <div class="admin-cards">
          <a href="bulletin.php" class="admin-card">
            <div class="card-icon">
              <i class="fas fa-bullhorn"></i>
            </div>
            <div class="card-title">Manage Bulletin</div>
            <div class="card-description">
              Create, edit, and delete announcements and important notices for the community
            </div>
          </a>

          <a href="events.php" class="admin-card">
            <div class="card-icon">
              <i class="fas fa-calendar-alt"></i>
            </div>
            <div class="card-title">Manage Events</div>
            <div class="card-description">
              Organize and manage campus events, workshops, and activities
            </div>
          </a>

          <a href="awards.php" class="admin-card">
            <div class="card-icon">
              <i class="fas fa-trophy"></i>
            </div>
            <div class="card-title">Awards & Achievements</div>
            <div class="card-description">
              Recognize and manage student and faculty achievements
            </div>
          </a>

          <!-- NEW: Q&A Management Card -->
          <a href="qna.php" class="admin-card">
            <div class="card-icon">
              <i class="fas fa-comments"></i>
            </div>
            <div class="card-title">Q&A Forum Management</div>
            <div class="card-description">
              Moderate questions, manage categories, and provide official answers to community queries
            </div>
          </a>
        </div>

        <!-- Session Info (Remove in production) -->
        <div class="session-info">
          <strong>Session Debug:</strong><br>
          Username: <?php echo $_SESSION['username'] ?? 'Not set'; ?><br>
          User Type: <?php echo $_SESSION['user_type'] ?? 'Not set'; ?><br>
          Full Name: <?php echo $_SESSION['full_name'] ?? 'Not set'; ?>
        </div>
      </div>
    </div>
  </div>

  <script>
    // Enhanced animations
    document.addEventListener('DOMContentLoaded', function() {
      const cards = document.querySelectorAll('.admin-card');
      const stats = document.querySelectorAll('.stat-card');
      
      // Animate admin cards
      cards.forEach((card, index) => {
        card.style.animationDelay = (index * 0.1) + 's';
        card.style.animation = 'fadeInUp 0.6s ease-out forwards';
      });
      
      // Animate stat cards
      stats.forEach((stat, index) => {
        stat.style.animationDelay = (index * 0.05 + 0.6) + 's';
        stat.style.animation = 'fadeInUp 0.5s ease-out forwards';
      });

      // Add click tracking for analytics (optional)
      cards.forEach(card => {
        card.addEventListener('click', function() {
          const cardTitle = this.querySelector('.card-title').textContent;
          console.log(`Admin clicked: ${cardTitle}`);
        });
      });
    });

    // Add CSS animations
    const style = document.createElement('style');
    style.textContent = `
      @keyframes fadeInUp {
        from {
          opacity: 0;
          transform: translateY(20px);
        }
        to {
          opacity: 1;
          transform: translateY(0);
        }
      }
      
      .admin-card, .stat-card {
        opacity: 0;
      }
    `;
    document.head.appendChild(style);
  </script>
</body>
</html>