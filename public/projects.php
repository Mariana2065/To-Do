<?php
require_once "../init.php";
require_login();

$stmt = $pdo->prepare("SELECT * FROM projects WHERE creator_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$projects = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Proyectos</title>
</head>
<body>
    <h1>Mis Proyectos</h1>
    <a href="create_project.php">➕ Crear Proyecto</a>
    <ul>
        <?php foreach ($projects as $p): ?>
            <li>
                <?= htmlspecialchars($p['name']) ?>
                <a href="edit_project.php?id=<?= $p['id'] ?>">✏️</a>
                <a href="delete_project.php?id=<?= $p['id'] ?>">🗑️</a>
            </li>
        <?php endforeach; ?>
    </ul>
    <a href="dashboard.php">⬅️ Volver</a>
</body>
</html>
