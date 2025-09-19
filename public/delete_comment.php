<?php
require_once __DIR__ . '/../init.php';
require_login();

$id = $_GET['id'] ?? null;
if (!$id) die("Comentario no encontrado.");

// Buscar comentario
$stmt = $pdo->prepare("SELECT * FROM comments WHERE id = ?");
$stmt->execute([$id]);
$comment = $stmt->fetch();

if (!$comment) die("Comentario no encontrado.");

// Verificar que el comentario sea del usuario o que sea dueño de la tarea
$stmt = $pdo->prepare("SELECT * FROM tasks WHERE id = ?");
$stmt->execute([$comment['task_id']]);
$task = $stmt->fetch();

if (!$task) die("No autorizado.");

if ($comment['user_id'] != $_SESSION['user_id'] && $task['creator_id'] != $_SESSION['user_id']) {
    die("No autorizado para eliminar este comentario.");
}

// Eliminar comentario
$stmt = $pdo->prepare("DELETE FROM comments WHERE id = ?");
$stmt->execute([$id]);

header("Location: view_task.php?id=" . $comment['task_id']);
exit;
