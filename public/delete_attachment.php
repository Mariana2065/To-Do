<?php
require_once __DIR__ . '/../init.php';
require_login();

$id = $_GET['id'] ?? null;
if (!$id) die("Adjunto no encontrado.");

// Buscar adjunto
$stmt = $pdo->prepare("SELECT * FROM attachments WHERE id = ?");
$stmt->execute([$id]);
$attachment = $stmt->fetch();

if (!$attachment) die("Adjunto no encontrado.");

// Verificar que la tarea pertenezca al usuario
$stmt = $pdo->prepare("SELECT * FROM tasks WHERE id = ? AND creator_id = ?");
$stmt->execute([$attachment['task_id'], $_SESSION['user_id']]);
$task = $stmt->fetch();

if (!$task) die("No autorizado.");

// Borrar archivo físico
if (file_exists(__DIR__ . '/../uploads/' . $attachment['filename'])) {
    unlink(__DIR__ . '/../uploads/' . $attachment['filename']);
}

// Eliminar registro en DB
$stmt = $pdo->prepare("DELETE FROM attachments WHERE id = ?");
$stmt->execute([$id]);

header("Location: view_task.php?id=" . $attachment['task_id']);
exit;
