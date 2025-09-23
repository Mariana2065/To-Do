<?php
require_once __DIR__ . '/../init.php';
require_login();

$id = $_GET['id'] ?? null;

if (!$id) {
    die("ID de archivo no especificado.");
}

// Obtener info del adjunto
$stmt = $pdo->prepare("
    SELECT a.*, 
           t.project_id, 
           t.creator_id,
           t.assignee_id,
           COALESCE(tu.role, pu.role, 'owner') AS user_role
    FROM attachments a
    JOIN tasks t ON t.id = a.task_id
    LEFT JOIN task_users tu ON tu.task_id = t.id AND tu.user_id = ?
    LEFT JOIN project_users pu ON pu.project_id = t.project_id AND pu.user_id = ?
    WHERE a.id = ?
");
$stmt->execute([$_SESSION['user_id'], $_SESSION['user_id'], $id]);
$adjunto = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$adjunto) {
    die("Adjunto no encontrado o sin permisos.");
}

if ($adjunto['user_role'] === 'viewer') {
    die("No tienes permiso para eliminar archivos.");
}

// Eliminar archivo físico
if (file_exists($adjunto['filepath'])) {
    unlink($adjunto['filepath']);
}

// Eliminar registro de la base de datos
$stmt = $pdo->prepare("DELETE FROM attachments WHERE id = ?");
$stmt->execute([$id]);

header("Location: view_task.php?id=" . $adjunto['task_id']);
exit;
