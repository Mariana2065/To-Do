<?php
// save_project.php
require_once __DIR__ . '/../init.php';
require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: projects.php');
    exit;
}

$name = trim($_POST['name'] ?? '');
$description = trim($_POST['description'] ?? '');

if (!$name) {
    flash_set('error', 'El nombre del proyecto es obligatorio.');
    header('Location: projects.php');
    exit;
}

$creator_id = (int)$_SESSION['user_id']; // usar siempre la sesión

try {
    $stmt = $pdo->prepare("INSERT INTO projects (name, description, creator_id) VALUES (?, ?, ?)");
    $stmt->execute([$name, $description, $creator_id]);
    flash_set('success', 'Proyecto creado correctamente.');
} catch (PDOException $e) {
    flash_set('error', 'Error al crear proyecto: ' . $e->getMessage());
}
header('Location: projects.php');
exit;
