<?php
require_once __DIR__ . '/../init.php';
require_login();

// LISTAR TAREAS DEL USUARIO
$stmt = $pdo->prepare("SELECT * FROM tasks WHERE creator_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Tareas - <?= APP_NAME ?></title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<div class="container-registro">
    <!-- Sidebar -->
    <div class="left-section">
        <div class="boton-registro-login-left">
            <button class="btn-registro" onclick="location.href='projects.php'">⬅ Proyectos</button>
            <button class="btn-login" onclick="location.href='dashboard.php'">Dashboard</button>
            <button class="btn-login" onclick="location.href='logout.php'">Cerrar Sesión</button>
        </div>
    </div>

    <!-- Contenido -->
    <div class="right-section">
        <div class="logo">
            <div class="logo-icon">
                <img src="../assets/css/img/Logo to-do.png" alt="Logo TO-DO">
            </div>
        </div>

        <h2 class="logo-text">✅ Mis Tareas</h2>

        <!-- Lista de tareas -->
        <ul style="list-style:none; padding:0;">
            <?php if ($tasks): ?>
                <?php foreach ($tasks as $t): ?>
                    <li style="margin:10px 0; padding:10px; border-bottom:1px solid #ccc;">
                        <strong><?= e($t['title']) ?></strong> 
                        <small>(<?= e($t['status']) ?>)</small><br>
                        <a href="edit_task.php?id=<?= $t['id'] ?>" class="btn-login">Editar</a>
                        <a href="delete_task.php?id=<?= $t['id'] ?>" class="btn-registro" onclick="return confirm('¿Eliminar tarea?')">Eliminar</a>
                    </li>
                <?php endforeach; ?>
            <?php else: ?>
                <p>No tienes tareas aún.</p>
            <?php endif; ?>
        </ul>

        <!-- Nueva tarea -->
        <h3 class="logo-text" style="margin-top:20px;">➕ Nueva Tarea</h3>
        <form method="post" action="save_task.php" class="form-container">
            <div class="form-group">
                <input type="text" name="title" class="form-input" placeholder="Título" required>
            </div>
            <div class="form-group">
                <textarea name="description" class="form-input" placeholder="Descripción"></textarea>
            </div>
            <button type="submit" class="register-btn">Guardar</button>
        </form>
    </div>
</div>
</body>
</html>
