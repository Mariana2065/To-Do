<?php
require_once __DIR__ . '/../init.php';
if(!is_logged()) header('Location: login.php');

$fname = $_GET['f'] ?? '';
$fname = basename($fname); // evita traversal
$cfg = require __DIR__ . '/../config.php';
$path = $cfg['upload_dir'] . '/' . $fname;

if(!is_file($path)) { http_response_code(404); echo "Archivo no encontrado"; exit; }

$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mime = finfo_file($finfo, $path) ?: 'application/octet-stream';
finfo_close($finfo);

header('Content-Type: ' . $mime);
header('Content-Disposition: attachment; filename="'.basename($path).'"');
header('Content-Length: ' . filesize($path));
readfile($path);
exit;
