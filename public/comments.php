<?php
require_once __DIR__ . '/../init.php';
require_login();

$task_id = $_GET['task_id'] ?? null;
if (!$task_id) redirect('tareas.php');

// Verificar tarea
$stmt = $pdo->prepare("SELECT * FROM tasks WHERE id = ? AND (creator_id=? OR assignee_id=?)");
$stmt->execute([$task_id, $_SESSION['user_id'], $_SESSION['user_id']]);
$task = $stmt->fetch();
if (!$task) die("No tienes acceso a esta tarea.");

// Crear comentario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $comentario = trim($_POST['comentario']);
    if (!empty($comentario)) {
        $stmt = $pdo->prepare("INSERT INTO comments (task_id, user_id, content, created_at) VALUES (?, ?, ?, NOW())");
        $stmt->execute([$task_id, $_SESSION['user_id'], $comentario]);
    }
    header("Location: comments.php?task_id=$task_id");
    exit;
}

// Listar comentarios
$stmt = $pdo->prepare("SELECT c.*, u.name 
                       FROM comments c 
                       JOIN users u ON c.user_id = u.id 
                       WHERE c.task_id = ? 
                       ORDER BY c.created_at DESC");
$stmt->execute([$task_id]);
$comentarios = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Comentarios - <?= APP_NAME ?></title>
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
        <h2 class="logo-text">💬 Comentarios en: <?= htmlspecialchars($task['title']) ?></h2>

        <!-- Crear comentario -->
        <form method="POST" class="form-container">
            <div class="form-group">
                <textarea name="comentario" class="form-input" placeholder="Escribe un comentario..." required></textarea>
            </div>
            <button type="submit" class="register-btn">Comentar</button>
        </form>

        <!-- Lista de comentarios -->
        <h3 class="logo-text" style="margin-top:20px;">📋 Comentarios</h3>
        <ul style="list-style:none; padding:0;">
            <?php if ($comentarios): ?>
                <?php foreach ($comentarios as $c): ?>
                    <li style="margin:10px 0; padding:10px; border-bottom:1px solid #ccc;">
                        <strong><?= htmlspecialchars($c['name']) ?>:</strong><br>
                        <?= htmlspecialchars($c['content']) ?><br>
                        <small><?= $c['created_at'] ?></small>
                    </li>
                <?php endforeach; ?>
            <?php else: ?>
                <p>No hay comentarios aún.</p>
            <?php endif; ?>
        </ul>
    </div>
</div>
</body>
</html>
