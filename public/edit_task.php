<?php
require_once __DIR__ . '/../init.php';
require_login();

$id = $_GET['id'] ?? null;
if (!$id) redirect('tareas.php');

$stmt = $pdo->prepare("SELECT * FROM tasks WHERE id = ?");
$stmt->execute([$id]);
$task = $stmt->fetch();

if (!$task) die("Tarea no encontrada");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title']);
    $desc = trim($_POST['description']);

    $stmt = $pdo->prepare("UPDATE tasks SET title=?, description=?, updated_at=NOW() WHERE id=?");
    $stmt->execute([$title, $desc, $id]);

    redirect('tareas.php');
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Editar Tarea</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<div class="container-registro">
    <!-- Sidebar -->
    <div class="left-section">
        <div class="boton-registro-login-left">
            <button class="btn-registro" onclick="location.href='tareas.php'">⬅ Volver</button>
            <button class="btn-login" onclick="location.href='logout.php'">Cerrar Sesión</button>
        </div>
    </div>

    <!-- Contenido principal -->
    <div class="right-section">
        <div class="logo">
            <div class="logo-icon">
                <img src="../assets/css/img/Logo to-do.png" alt="Logo TO-DO">
            </div>
        </div>

        <h2 class="logo-text">✏ Editar Tarea</h2>

        <form method="post" class="form-container">
            <div class="form-group">
                <input type="text" name="title" class="form-input" value="<?= e($task['title']) ?>" required>
            </div>
            <div class="form-group">
                <textarea name="description" class="form-input" placeholder="Descripción"><?= e($task['description']) ?></textarea>
            </div>
            <button type="submit" class="login-btn">Actualizar</button>
        </form>
    </div>
</div>
</body>
</html>
