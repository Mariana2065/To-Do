<?php
if (!isset($_SESSION)) session_start();
require_once __DIR__ . '/../init.php';
require_login();

// Obtener datos del usuario actual 
$stmt = $pdo->prepare("SELECT name, email FROM users WHERE id = ?"); 
$stmt->execute([$_SESSION['user_id']]); 
$user = $stmt->fetch(PDO::FETCH_ASSOC); 


$stmt = $pdo->prepare("SELECT id, name, email, avatar FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$usuarioSidebar = $stmt->fetch(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Dashboard - <?= APP_NAME ?></title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>

<body>
    <!-- Sidebar -->
    <div class="sidebar">

        <div class="sidebar-content">
        <div class="user">

            <!-- Foto de perfil -->
            <div style="margin-bottom:15px;">
                <?php if (!empty($usuarioSidebar['avatar'])): ?>
                    <a href="perfil.php"><img src="../<?= htmlspecialchars($usuarioSidebar['avatar']) ?>" 
                        alt="Avatar" 
                        style="width:80px;height:80px;border-radius:50%;object-fit:cover;"></a>
                <?php else: ?>
                    <a href="perfil.php"><img src="../assets/img/default-avatar.png" 
                        alt="Avatar" 
                        style="width:80px;height:80px;border-radius:50%;object-fit:cover;"></a>
                <?php endif; ?>
                <p class="user-name"><?= htmlspecialchars($usuarioSidebar['name']) ?></p>
            </div>
        </div>

        <nav>
            <a href="buscar.php" class="nav-item">
                <img src="../assets/css/img/iconbuscar.png" alt="" class="icons-sidebar"> Buscar
            </a>

            <a href="proyectos.php" class="nav-item">
                <img src="../assets/css/img/iconsproyecto.png" alt="" class="icons-sidebar"> Proyectos
            </a>
            <a href="tareas.php" class="nav-item">
               <img src="../assets/css/img/iconsTareas.png" alt="" class="icons-sidebar"> Tareas
            </a>
            <a href="tags.php" class="nav-item">
                <img src="../assets/css/img/iconstags.png" alt="" class="icons-sidebar">Etiquetas
            </a>
        </nav>

        <a href="logout.php" class="logout-btn"> Cerrar sesión</a>
    </div>
    </div>

