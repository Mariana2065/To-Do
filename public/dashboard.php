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
    <?php include_once '../include/sidebar.php'; ?>

    <!-- Main content -->
    <div class="main">
        <div class="welcome-header">
            <h1>¡Bienvenido a tu To-Do <?= htmlspecialchars($user['name']) ?>!</h1>
            <img src="../assets/css/img/logo-blanco.png" alt="">
        </div>
    </div>


    
</body>
</html>