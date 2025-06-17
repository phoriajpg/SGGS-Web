<?php
// functions.php
require 'config1.php';

function getQuestions() {
    global $pdo;
    $stmt = $pdo->query("SELECT * FROM questions ORDER BY created_at DESC");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getAnswers($questionId) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM answers WHERE question_id = ? ORDER BY created_at ASC");
    $stmt->execute([$questionId]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function addQuestion($author, $title, $content) {
    global $pdo;
    $stmt = $pdo->prepare("INSERT INTO questions (author_name, title, content) VALUES (?, ?, ?)");
    return $stmt->execute([$author, $title, $content]);
}

function addAnswer($questionId, $author, $content) {
    global $pdo;
    $stmt = $pdo->prepare("INSERT INTO answers (question_id, author_name, content) VALUES (?, ?, ?)");
    return $stmt->execute([$questionId, $author, $content]);
}

function incrementHelpfulCount($type, $id) {
    global $pdo;
    $table = ($type === 'question') ? 'questions' : 'answers';
    $stmt = $pdo->prepare("UPDATE $table SET helpful_count = helpful_count + 1 WHERE id = ?");
    return $stmt->execute([$id]);
}
?>