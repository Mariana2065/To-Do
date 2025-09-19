<?php
require_once __DIR__ . '/../init.php';
require_login();

$id = $_GET['id'] ?? null;
if (!$id) redirect('tareas.php');

// Obtener la tarea
$stmt = $pdo->prepare("SELECT * FROM tasks WHERE id = ? AND creator_id = ?");
$stmt->execute([$id, $_SESSION['user_id']]);
$task = $stmt->fetch();

if (!$task) die("Tarea no encontrada.");

// Guardar cambios
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $titulo = trim($_POST['titulo']);
    $descripcion = trim($_POST['descripcion']);
    $prioridad = $_POST['prioridad'];
    $estado = $_POST['estado'];
    $fecha_venc = $_POST['due_date'] ?: null;

    $stmt = $pdo->prepare("UPDATE tasks 
                           SET title=?, description=?, priority=?, status=?, due_date=?, updated_at=NOW() 
                           WHERE id=? AND creator_id=?");
    $stmt->execute([$titulo, $descripcion, $prioridad, $estado, $fecha_venc, $id, $_SESSION['user_id']]);

    header("Location: tareas.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Editar Tarea - <?= APP_NAME ?></title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<div class="container-registro">
    <!-- Sidebar -->
    <div class="left-section">
        <div class="boton-registro-login-left">
            <button class="btn-registro" onclick="location.href='tareas.php'">⬅ Volver a Tareas</button>
            <button class="btn-login" onclick="location.href='logout.php'">Cerrar Sesión</button>
        </div>
    </div>

    <!-- Contenido -->
    <div class="right-section">
        <h2 class="logo-text">✏ Editar Tarea</h2>

        <form method="POST" class="form-container">
            <div class="form-group">
                <input type="text" name="titulo" class="form-input" value="<?= htmlspecialchars($task['title']) ?>" required>
            </div>
            <div class="form-group">
                <textarea name="descripcion" class="form-input"><?= htmlspecialchars($task['description']) ?></textarea>
            </div>

            <div class="form-group">
                <label>Prioridad:</label>
                <select name="prioridad" class="form-input">
                    <option value="low" <?= $task['priority']=="low"?"selected":"" ?>>Baja</option>
                    <option value="medium" <?= $task['priority']=="medium"?"selected":"" ?>>Media</option>
                    <option value="high" <?= $task['priority']=="high"?"selected":"" ?>>Alta</option>
                    <option value="urgent" <?= $task['priority']=="urgent"?"selected":"" ?>>Urgente</option>
                </select>
            </div>

            <div class="form-group">
                <label>Estado:</label>
                <select name="estado" class="form-input">
                    <option value="todo" <?= $task['status']=="todo"?"selected":"" ?>>Por hacer</option>
                    <option value="in_progress" <?= $task['status']=="in_progress"?"selected":"" ?>>En progreso</option>
                    <option value="done" <?= $task['status']=="done"?"selected":"" ?>>Hecha</option>
                    <option value="archived" <?= $task['status']=="archived"?"selected":"" ?>>Archivada</option>
                </select>
            </div>

            <div class="form-group">
                <label>Fecha de vencimiento:</label>
                <input type="date" name="due_date" value="<?= $task['due_date'] ?>" class="form-input">
            </div>

            <button type="submit" class="login-btn">Guardar Cambios</button>
        </form>
    </div>
</div>
</body>
</html>
