<?php
require_once __DIR__ . '/../init.php';
require_login();

$task_id = $_POST['task_id'] ?? null;
if (!$task_id) {
    die("Tarea no especificada.");
}

// Obtener rol del usuario para la tarea
$stmt = $pdo->prepare("
    SELECT COALESCE(tu.role, pu.role, 'owner') AS user_role
    FROM tasks t
    LEFT JOIN task_users tu ON tu.task_id = t.id AND tu.user_id = ?
    LEFT JOIN project_users pu ON pu.project_id = t.project_id AND pu.user_id = ?
    WHERE t.id = ?
");
$stmt->execute([$_SESSION['user_id'], $_SESSION['user_id'], $task_id]);
$task = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$task) {
    die("No tienes acceso a esta tarea.");
}

$userRole = $task['user_role'];

// Solo permitir subir si no es viewer
if ($userRole === 'viewer') {
    die("No tienes permisos para subir archivos.");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['archivo'])) {

    $uploadDir = __DIR__ . '/../uploads/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    $nombreOriginal = basename($_FILES['archivo']['name']);
    $nombreSeguro = time() . '_' . preg_replace('/[^A-Za-z0-9\.\-_]/', '_', $nombreOriginal);
    $rutaDestino = $uploadDir . $nombreSeguro;

    if (move_uploaded_file($_FILES['archivo']['tmp_name'], $rutaDestino)) {
        $rutaRelativa = '../uploads/' . $nombreSeguro;

        $stmt = $pdo->prepare("INSERT INTO attachments (task_id, user_id, filename, filepath, uploaded_at) VALUES (?, ?, ?, ?, NOW())");
        $stmt->execute([
            $task_id,
            $_SESSION['user_id'],
            $nombreOriginal,
            $rutaRelativa
        ]);
    }

    header("Location: view_task.php?id=" . $task_id);
    exit;
} else {
    echo "No se recibió archivo.";
}
