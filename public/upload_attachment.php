<?php
require_once __DIR__ . '/../init.php';
require_login();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['archivo'])) {
    $task_id = $_POST['task_id'];

    // Verificar si existe la carpeta uploads
    $uploadDir = __DIR__ . '/../uploads/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    // Nombre seguro del archivo
    $nombreOriginal = basename($_FILES['archivo']['name']);
    $nombreSeguro = time() . '_' . preg_replace('/[^A-Za-z0-9\.\-_]/', '_', $nombreOriginal);
    $rutaDestino = $uploadDir . $nombreSeguro;

    if (move_uploaded_file($_FILES['archivo']['tmp_name'], $rutaDestino)) {
        // Guardar en BD
        $stmt = $pdo->prepare("INSERT INTO attachments (task_id, filename, filepath, uploaded_at) VALUES (?, ?, ?, NOW())");
        $stmt->execute([$task_id, $nombreOriginal, $nombreSeguro]);
    }

    header("Location: view_task.php?id=" . $task_id);
    exit;
} else {
    echo "No se recibió archivo.";
}
