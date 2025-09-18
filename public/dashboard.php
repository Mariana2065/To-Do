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
<body class="dashboard-body">     
    <!-- Sidebar -->
    <div class="sidebar">
        <div class="logo">
            <!-- Reemplaza la ruta de la imagen con tu logo -->
                         <div class="logo-text">TO DO</div>

            <img src="../assets/css/img/Logo to-do.png" alt="Logo" />
        </div>
        
        <div class="user">
            <div class="user-icon">👤</div>
            <div class="user-name"><?= htmlspecialchars($user['name']) ?></div>
        </div>
        
        <div class="search">
            <input type="text" placeholder="Buscar">
        </div>
        
        <nav>
            <a href="proyectos.php" class="nav-item">
                <span>🏠</span> Proyectos
            </a>
            <a href="tareas.php" class="nav-item">
                <span>✅</span> Tareas
            </a>
            <a href="perfil.php" class="nav-item">
                <span>👤</span> Mi perfil
            </a>
        </nav>
        
        <a href="logout.php" class="logout-btn">🚪 Cerrar sesión</a>
    </div>

    <!-- Main content -->
    <div class="main">
        <div class="welcome-header">
            <h1>¡Bienvenido, <?= htmlspecialchars($user['name']) ?>!</h1>
        </div>

        <div class="dashboard-grid">
            <div class="dashboard-card projects">
                <div class="card-header">
                    <div class="card-icon projects">📂</div>
                    <div class="card-title">Mis Proyectos</div>
                </div>
                <div class="card-description">
                    Gestiona y organiza todos tus proyectos de manera eficiente. Crea nuevos proyectos y mantén el seguimiento de su progreso.
                </div>
                <a href="proyectos.php" class="card-action">Ver Proyectos</a>
            </div>

            <div class="dashboard-card tasks">
                <div class="card-header">
                    <div class="card-icon tasks">✅</div>
                    <div class="card-title">Mis Tareas</div>
                </div>
                <div class="card-description">
                    Administra tus tareas diarias, marca las completadas y prioriza las más importantes para mantener tu productividad.
                </div>
                <a href="tareas.php" class="card-action">Ver Tareas</a>
            </div>

            <div class="dashboard-card profile">
                <div class="card-header">
                    <div class="card-icon profile">👤</div>
                    <div class="card-title">Mi Perfil</div>
                </div>
                <div class="card-description">
                    Actualiza tu información personal, cambia tu contraseña y personaliza tu experiencia en la aplicación.
                </div>
                <a href="perfil.php" class="card-action">Ver Perfil</a>
            </div>
        </div>
    </div>
</body> 
</html>