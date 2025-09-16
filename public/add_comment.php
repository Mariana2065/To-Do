<?php
require_once __DIR__ . '/../init.php';
require_login();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $task_id = $_POST['task_id'];
    $text = trim($_POST['text']);

    $stmt = $pdo->prepare("INSERT INTO comments (task_id, user_id, text) VALUES (?, ?, ?)");
    $stmt->execute([$task_id, $_SESSION['user_id'], $text]);

    redirect("view_task.php?id=$task_id");
}
