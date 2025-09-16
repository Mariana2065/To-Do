<?php
require_once __DIR__ . '/../init.php';
require_login();

$id = $_GET['id'] ?? null;
if (!$id) redirect('projects.php');

// Obtener proyecto
$stmt = $pdo->prepare("SELECT * FROM projects WHERE id = ?");
$stmt->execute([$id]);
$project = $stmt->fetch();

if (!$project) {
    die("Proyecto no encontrado");
}

// Obtener tareas del proyecto
$stmt = $pdo->prepare("SELECT * FROM tasks WHERE project_id = ?");
$stmt->execute([$id]);
$tasks = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title><?= e($project['name']) ?></title>
</head>
<body>
    <h1><?= e($project['name']) ?></h1>
    <p><?= e($project['description']) ?></p>

    <h2>Tareas</h2>
    <ul>
        <?php foreach ($tasks as $t): ?>
            <li><?= e($t['title']) ?> (<?= e($t['status']) ?>)</li>
        <?php endforeach; ?>
    </ul>
</body>
</html>
