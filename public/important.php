<?php
require_once __DIR__ . '/../init.php';
if (!is_logged()) header('Location: login.php');

$tasks = $pdo->query("SELECT * FROM tasks WHERE priority='urgent' ORDER BY created_at DESC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>TO DO - Importante</title>
  <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<div class="page page-pink">
  <aside class="sidebar">
    <div class="logo"><img src="../assets/css/img/Logo to-do.png" width="60"></div>
    <div class="user">👤 <?= htmlspecialchars(current_user()['name']); ?></div>
    <div class="search"><input type="text" placeholder="Buscar"></div>
    <nav>
      <a href="projects.php">📂 Proyectos</a>
      <a href="important.php">⭐ Importante</a>
    </nav>
  </aside>

  <main class="main">
    <h2>Importante</h2>
    <?php foreach($tasks as $t): ?>
      <div class="list">
        <span><?= htmlspecialchars($t['title']) ?></span>
      </div>
    <?php endforeach; ?>
  </main>
</div>
</body>
</html>
