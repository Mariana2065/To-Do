<?php
require_once __DIR__ . '/../init.php';
require_login();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title']);
    $desc = trim($_POST['description']);
    $project_id = $_POST['project_id'] ?? null;

    $stmt = $pdo->prepare("INSERT INTO tasks (title, description, creator_id, project_id) VALUES (?, ?, ?, ?)");
    $stmt->execute([$title, $desc, $_SESSION['user_id'], $project_id ?: null]);

    redirect('view_task.php');
}
