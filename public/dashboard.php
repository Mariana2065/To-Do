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
    <link rel="stylesheet" href="styles.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f7fa;
            display: flex;
            height: 100vh;
        }

        /* Sidebar */
        .sidebar {
            width: 220px;
            background: #f6f6f0;
            padding: 20px;
            display: flex;
            flex-direction: column;
            border-right: 1px solid #ddd;
        }

        .sidebar .logo {
            text-align: center;
            margin-bottom: 20px;
        }

        .sidebar .logo img {
            width: 80px;
            height: 80px;
            object-fit: cover;
        }

        .sidebar .logo-text {
            font-size: 18px;
            font-weight: 700;
            color: #2c3e50;
            letter-spacing: 1px;
            margin-top: 10px;
        }

        .sidebar .user {
            text-align: center;
            margin-bottom: 20px;
            padding: 15px;
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        .sidebar .user .user-icon {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: linear-gradient(135deg, #a8c8ec 0%, #7fb3d3 50%, #91b8db 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 10px;
            color: white;
            font-weight: bold;
        }

        .sidebar .user .user-name {
            font-weight: 600;
            color: #2c3e50;
            font-size: 14px;
        }

        .sidebar .search input {
            width: 100%;
            padding: 12px;
            border-radius: 20px;
            border: 1px solid #ddd;
            margin-bottom: 20px;
            font-size: 14px;
            outline: none;
            transition: all 0.3s ease;
        }

        .sidebar .search input:focus {
            border-color: #7fb3d3;
            box-shadow: 0 0 0 3px rgba(127, 179, 211, 0.1);
        }

        .sidebar nav a {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 15px;
            border-radius: 8px;
            text-decoration: none;
            color: #333;
            margin-bottom: 8px;
            transition: all 0.3s ease;
            font-size: 14px;
        }

        .sidebar nav a:hover {
            background: linear-gradient(135deg, #a8c8ec 0%, #7fb3d3 50%);
            color: white;
            transform: translateX(5px);
        }

        .sidebar nav a.active {
            background: linear-gradient(135deg, #7fb3d3, #a8c8ec);
            color: white;
        }

        /* Main content */
        .main {
            flex: 1;
            background: linear-gradient(135deg, #c8d9ec 0%, #e3c7e8 50%, #f7d6e6 100%);
            padding: 30px;
            overflow-y: auto;
        }

        .welcome-header {
            background: white;
            padding: 30px;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            margin-bottom: 30px;
            text-align: center;
        }

        .welcome-header h1 {
            color: #2c3e50;
            font-size: 28px;
            margin-bottom: 10px;
        }

        .welcome-header p {
            color: #7f8c8d;
            font-size: 16px;
        }

        .dashboard-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-top: 30px;
        }

        .dashboard-card {
            background: white;
            padding: 25px;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
            border-left: 4px solid transparent;
        }

        .dashboard-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
        }

        .dashboard-card.projects {
            border-left-color: #7fb3d3;
        }

        .dashboard-card.tasks {
            border-left-color: #a8c8ec;
        }

        .dashboard-card.profile {
            border-left-color: #F0C6D8;
        }

        .card-header {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 15px;
        }

        .card-icon {
            width: 50px;
            height: 50px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            color: white;
        }

        .card-icon.projects { background: linear-gradient(135deg, #7fb3d3, #a8c8ec); }
        .card-icon.tasks { background: linear-gradient(135deg, #a8c8ec, #91b8db); }
        .card-icon.profile { background: linear-gradient(135deg, #F0C6D8, #e3c7e8); }

        .card-title {
            font-size: 20px;
            font-weight: 600;
            color: #2c3e50;
        }

        .card-description {
            color: #7f8c8d;
            font-size: 14px;
            line-height: 1.6;
            margin-bottom: 20px;
        }

        .card-action {
            background: linear-gradient(135deg, #7fb3d3, #a8c8ec);
            color: white;
            border: none;
            padding: 12px 25px;
            border-radius: 25px;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-block;
            font-size: 14px;
            font-weight: 500;
        }

        .card-action:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(127, 179, 211, 0.4);
        }

        .logout-btn {
            background: #e74c3c;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 20px;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            font-size: 14px;
            margin-top: auto;
            display: block;
            text-align: center;
        }

        .logout-btn:hover {
            background: #c0392b;
            transform: translateY(-2px);
        }

        @media (max-width: 768px) {
            body {
                flex-direction: column;
            }
            
            .sidebar {
                width: 100%;
                padding: 15px;
            }
            
            .main {
                padding: 20px;
            }
            
            .dashboard-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head> 
<body>     
    <!-- Sidebar -->
    <div class="sidebar">
        <div class="logo">
            <!-- Reemplaza la ruta de la imagen con tu logo -->
            <img src="path/to/your/logo.png" alt="Logo" />
            <div class="logo-text">TO DO</div>
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
            <h1>¡Bienvenido, <?= htmlspecialchars($user['name']) ?>! 🎉</h1>
            <p>Tu correo registrado es: <?= htmlspecialchars($user['email']) ?></p>
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