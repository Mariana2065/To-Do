<?php
require_once __DIR__ . '/../init.php';
require_login();

// Validar que los datos existan
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['task_id'], $_POST['user_id'], $_POST['role'])) {
    $task_id = intval($_POST['task_id']);
    $user_id = intval($_POST['user_id']);
    $role = $_POST['role'];

    // Validar rol
    if (!in_array($role, ['editor', 'viewer'])) {
        die("Rol inválido.");
    }

    // Verificar que el usuario actual sea dueño de la tarea
    $stmt = $pdo->prepare("SELECT creator_id FROM tasks WHERE id = ?");
    $stmt->execute([$task_id]);
    $task = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$task || $task['creator_id'] != $_SESSION['user_id']) {
        die("No tienes permisos para compartir esta tarea.");
    }

    // Evitar duplicados: si ya existe colaboración, actualizamos el rol
    $stmt = $pdo->prepare("SELECT * FROM task_users WHERE task_id = ? AND user_id = ?");
    $stmt->execute([$task_id, $user_id]);
    $exists = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($exists) {
        $stmt = $pdo->prepare("UPDATE task_users SET role = ? WHERE task_id = ? AND user_id = ?");
        $stmt->execute([$role, $task_id, $user_id]);
    } else {
        $stmt = $pdo->prepare("INSERT INTO task_users (task_id, user_id, role) VALUES (?, ?, ?)");
        $stmt->execute([$task_id, $user_id, $role]);
    }

    // Redirigir de nuevo a la vista de la tarea
    header("Location: view_task.php?id=" . $task_id);
    exit;
} else {
    die("Solicitud inválida.");
}
