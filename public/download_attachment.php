<?php
require_once __DIR__ . '/../init.php';
require_login();

if (!isset($_GET['id'])) {
    die("ID de adjunto no especificado.");
}

$id = $_GET['id'];

// Buscar adjunto en la BD
$stmt = $pdo->prepare("SELECT * FROM attachments WHERE id = ?");
$stmt->execute([$id]);
$adj = $stmt->fetch();

if (!$adj) {
    die("Adjunto no encontrado en la base de datos.");
}

$uploadDir = __DIR__ . '/../uploads/';
$rutaArchivo = $uploadDir . $adj['filepath'];

// Verificar si el archivo existe físicamente
if (!file_exists($rutaArchivo)) {
    die("El archivo no existe en el servidor.");
}

// Descargar
header('Content-Description: File Transfer');
header('Content-Type: application/octet-stream');
header('Content-Disposition: attachment; filename="' . basename($adj['filename']) . '"');
header('Expires: 0');
header('Cache-Control: must-revalidate');
header('Pragma: public');
header('Content-Length: ' . filesize($rutaArchivo));
readfile($rutaArchivo);
exit;
