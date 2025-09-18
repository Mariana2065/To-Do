<?php
require_once __DIR__ . '/../init.php';
require_login(); // protege la página  

// Obtener datos del usuario actual 
$stmt = $pdo->prepare("SELECT name, email FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);
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
            <img src="../assets/css/img/iconperfil.png" alt="" class="user-avatar-sidebar" />
            <div class="user-name"><?= htmlspecialchars($user['name']) ?></div>
        </div>

        <div class="search">
            <input type="text" placeholder="Buscar">
        </div>

        <nav>
            <a href="proyectos.php" class="nav-item">
                <img src="../assets/css/img/iconsproyecto.png" alt="" class="icons-sidebar"> Proyectos
            </a>
            <a href="tareas.php" class="nav-item">
               <img src="../assets/css/img/iconsTareas.png" alt="" class="icons-sidebar"> Tareas
            </a>
            <a href="perfil.php" class="nav-item">
                <img src="../assets/css/img/iconstags.png" alt="" class="icons-sidebar">Etiquetas
            </a>
        </nav>

        <a href="logout.php" class="logout-btn"> Cerrar sesión</a>
    </div>
    </div>

    <!-- Main content -->
    <div class="main">
        <div class="welcome-header">
            <h1>¡Bienvenido a tu To-Do <?= htmlspecialchars($user['name']) ?>!</h1>
            <img src="../assets/css/img/logo-blanco.png" alt="">
        </div>

            </div>
        </div>
    </div>
</body>

</html>