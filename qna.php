<?php
require 'functions.php';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['submit_question'])) {
        addQuestion($_POST['author'], $_POST['title'], $_POST['content']);
        header("Location: ".$_SERVER['PHP_SELF']);
        exit();
    } elseif (isset($_POST['submit_answer'])) {
        addAnswer($_POST['question_id'], $_POST['author'], $_POST['content']);
        header("Location: ".$_SERVER['PHP_SELF']);
        exit();
    } elseif (isset($_POST['helpful'])) {
        incrementHelpfulCount($_POST['type'], $_POST['id']);
        header("Location: ".$_SERVER['PHP_SELF']);
        exit();
    }
}

$questions = getQuestions();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>SGGS Q&A Forum</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <link href="https://fonts.googleapis.com/css2?family=Gabarito:wght@400..900&display=swap" rel="stylesheet">
  <style>
    /* ========== Base Styles ========== */
    :root {
      --primary: #B10023;
      --primary-dark: #830000;
      --accent: #f1c40f;
      --text-dark: #2c3e50;
      --text-light: #f5f5f5;
    }
    
    html {
      scroll-behavior: smooth;
    }
    
    body {
      font-family: "Gabarito", sans-serif;
      margin: 0;
      padding: 0;
      background-color: #f5f5f5;
      color: var(--text-dark);
      line-height: 1.6;
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

    .navbar a {
      padding: 8px 16px;
      color: var(--primary);
      text-decoration: none;
      font-size: 16px;
      border-radius: 10px;
      white-space: nowrap;
      transition: background-color 0.3s ease, color 0.3s ease;
      display: inline-block;
    }

    .navbar a:hover {
      background-color: var(--primary);
      color: white;
    }

    .label {
      font-weight: 700;
      font-size: 35px;
      display: inline;
    }

    /* ========== Q&A Container ========== */
    .qa-container {
      max-width: 800px;
      margin: 120px auto 40px;
      padding: 20px;
      background-color: white;
      border-radius: 10px;
      box-shadow: 0 4px 15px rgba(0,0,0,0.1);
    }

    .qa-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 30px;
      padding-bottom: 15px;
      border-bottom: 2px solid #f0f0f0;
    }

    .qa-title {
      font-size: 2rem;
      color: var(--primary);
      margin: 0;
    }

    /* ========== Question Form ========== */
    .question-form {
      margin-bottom: 40px;
      padding: 20px;
      background-color: #f9f9f9;
      border-radius: 8px;
    }

    .form-title {
      margin-top: 0;
      color: var(--primary);
    }

    .form-group {
      margin-bottom: 15px;
    }

    .form-input, .form-textarea {
      width: 100%;
      padding: 10px;
      border: 1px solid #ddd;
      border-radius: 4px;
      font-family: "Gabarito", sans-serif;
    }

    .form-textarea {
      min-height: 100px;
      resize: vertical;
    }

    .submit-btn {
      background-color: var(--primary);
      color: white;
      border: none;
      padding: 10px 20px;
      border-radius: 4px;
      cursor: pointer;
      font-family: "Gabarito", sans-serif;
      transition: background-color 0.3s;
    }

    .submit-btn:hover {
      background-color: var(--primary-dark);
    }

    /* ========== Comments Section ========== */
    .comments-section {
      margin-top: 30px;
    }

    .comment {
      padding: 20px;
      margin-bottom: 20px;
      background-color: white;
      border-radius: 8px;
      box-shadow: 0 2px 5px rgba(0,0,0,0.05);
      border-left: 3px solid var(--primary);
    }

    .comment-header {
      display: flex;
      justify-content: space-between;
      margin-bottom: 10px;
    }

    .comment-author {
      font-weight: 700;
      color: var(--primary);
    }

    .comment-date {
      color: #777;
      font-size: 0.9rem;
    }

    .comment-content {
      margin-bottom: 15px;
    }

    .comment-actions {
      display: flex;
      gap: 15px;
    }

    .action-btn {
      background: none;
      border: none;
      color: #777;
      cursor: pointer;
      font-family: "Gabarito", sans-serif;
      display: flex;
      align-items: center;
      gap: 5px;
    }

    .action-btn:hover {
      color: var(--primary);
    }

    .replies {
      margin-left: 30px;
      margin-top: 15px;
      padding-left: 15px;
      border-left: 2px solid #eee;
    }

    /* ========== Responsive ========== */
    @media (max-width: 768px) {
      .qa-container {
        margin: 100px 15px 40px;
      }
      
      .nav-links {
        gap: 10px;
      }
      
      .label {
        font-size: 28px;
      }
      
      .replies {
        margin-left: 15px;
      }
    }
  </style>
</head>
<body>
  <!-- Navigation -->
  <nav class="navbar">
    <div class="nav-box">
      <div class="nav-links">
        <a href="parent.html"><span class="label">Home</span></a>
            <a href="qna.php"><span class="label">Q&A</span></a>
            <a href="faq.html"><span class="label">FAQ</span></a>
            <a href="academics.php"><span class="label">Academics</span></a>
            <a href="index.html"><span class="label">Log Out</span></a>
      </div>
    </div>
  </nav>

  <div class="qa-container">
    <div class="qa-header">
      <h1 class="qa-title">School Q&A Forum</h1>
    </div>

    <!-- Question Form -->
    <div class="question-form">
      <h3 class="form-title">Ask a Question</h3>
      <form method="POST" action="">
        <input type="hidden" name="submit_question" value="1">
        <div class="form-group">
          <input type="text" name="author" class="form-input" placeholder="Your Name" required>
        </div>
        <div class="form-group">
          <input type="text" name="title" class="form-input" placeholder="Question Title" required>
        </div>
        <div class="form-group">
          <textarea name="content" class="form-textarea" placeholder="Your question..." required></textarea>
        </div>
        <button type="submit" class="submit-btn">Post Question</button>
      </form>
    </div>

    <!-- Comments Section -->
    <div class="comments-section">
      <h2>Recent Questions</h2>
      
      <?php foreach ($questions as $question): ?>
      <div class="comment">
        <div class="comment-header">
          <span class="comment-author"><?= htmlspecialchars($question['author_name']) ?></span>
          <span class="comment-date"><?= date('F j, Y', strtotime($question['created_at'])) ?></span>
        </div>
        <h3><?= htmlspecialchars($question['title']) ?></h3>
        <p class="comment-content">
          <?= nl2br(htmlspecialchars($question['content'])) ?>
        </p>
        <div class="comment-actions">
          <form method="POST" action="" style="display: inline;">
            <input type="hidden" name="helpful" value="1">
            <input type="hidden" name="type" value="question">
            <input type="hidden" name="id" value="<?= $question['id'] ?>">
            <button type="submit" class="action-btn"><i class="far fa-thumbs-up"></i> Helpful (<?= $question['helpful_count'] ?>)</button>
          </form>
          <button class="action-btn reply-btn" data-question-id="<?= $question['id'] ?>"><i class="far fa-comment"></i> Reply</button>
        </div>
        
        <!-- Replies -->
        <div class="replies" id="replies-<?= $question['id'] ?>">
          <?php
          $answers = getAnswers($question['id']);
          foreach ($answers as $answer):
          ?>
          <div class="comment">
            <div class="comment-header">
              <span class="comment-author"><?= htmlspecialchars($answer['author_name']) ?></span>
              <span class="comment-date"><?= date('F j, Y', strtotime($answer['created_at'])) ?></span>
            </div>
            <p class="comment-content">
              <?= nl2br(htmlspecialchars($answer['content'])) ?>
            </p>
            <div class="comment-actions">
              <form method="POST" action="" style="display: inline;">
                <input type="hidden" name="helpful" value="1">
                <input type="hidden" name="type" value="answer">
                <input type="hidden" name="id" value="<?= $answer['id'] ?>">
                <button type="submit" class="action-btn"><i class="far fa-thumbs-up"></i> Helpful (<?= $answer['helpful_count'] ?>)</button>
              </form>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>

  <script>
    // JavaScript for reply forms
    document.querySelectorAll('.reply-btn').forEach(btn => {
      btn.addEventListener('click', function() {
        const questionId = this.getAttribute('data-question-id');
        const repliesContainer = document.getElementById(`replies-${questionId}`);
        
        const replyForm = document.createElement('div');
        replyForm.className = 'question-form';
        replyForm.innerHTML = `
          <h3>Post a Reply</h3>
          <form method="POST" action="">
            <input type="hidden" name="submit_answer" value="1">
            <input type="hidden" name="question_id" value="${questionId}">
            <div class="form-group">
              <input type="text" name="author" class="form-input" placeholder="Your Name" required>
            </div>
            <div class="form-group">
              <textarea name="content" class="form-textarea" placeholder="Your reply..." required></textarea>
            </div>
            <button type="submit" class="submit-btn">Post Reply</button>
          </form>
        `;
        
        repliesContainer.appendChild(replyForm);
        replyForm.scrollIntoView({ behavior: 'smooth' });
      });
    });
  </script>
</body>
</html>