<?php
require_once __DIR__ . '/../init.php';
header('Content-Type: application/json; charset=utf-8');

$r = $_GET['r'] ?? '';
if($r === 'tasks') {
  echo json_encode($pdo->query("SELECT * FROM tasks ORDER BY created_at DESC")->fetchAll());
  exit;
}
if($r === 'projects') {
  echo json_encode($pdo->query("SELECT * FROM projects ORDER BY created_at DESC")->fetchAll());
  exit;
}
if($r === 'labels') {
  echo json_encode($pdo->query("SELECT * FROM labels ORDER BY name")->fetchAll());
  exit;
}

http_response_code(404);
echo json_encode(['error'=>'endpoint no encontrado']);
