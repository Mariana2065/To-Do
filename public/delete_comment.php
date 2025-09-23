<?php
require_once __DIR__ . '/../init.php';
require_login();

$commentId = $_GET['id'] ?? null;

if (!$commentId) {
    die("Comentario no especificado.");
}

// Obtener el comentario y tarea asociada
$stmt = $pdo->prepare("
    SELECT c.*, t.project_id, t.creator_id, t.assignee_id,
           COALESCE(tu.role, pu.role, 'owner') AS user_role
    FROM comments c
    JOIN tasks t ON c.task_id = t.id
    LEFT JOIN task_users tu ON tu.task_id = t.id AND tu.user_id = ?
    LEFT JOIN project_users pu ON pu.project_id = t.project_id AND pu.user_id = ?
    WHERE c.id = ?
");
$stmt->execute([$_SESSION['user_id'], $_SESSION['user_id'], $commentId]);
$comment = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$comment) {
    die("Comentario no encontrado.");
}

if ($comment['user_id'] != $_SESSION['user_id'] && $comment['user_role'] !== 'owner') {
    die("No tienes permisos para eliminar este comentario.");
}

// Eliminar
$stmt = $pdo->prepare("DELETE FROM comments WHERE id = ?");
$stmt->execute([$commentId]);

header("Location: view_task.php?id=" . $comment['task_id']);
exit;
