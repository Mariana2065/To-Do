<?php
require_once __DIR__ . '/../init.php';
if(!is_logged()) header('Location: login.php');

if($_SERVER['REQUEST_METHOD'] === 'POST') {
  $name = trim($_POST['name'] ?? '');
  $color = trim($_POST['color'] ?? '#888');
  if($name) {
    $pdo->prepare("INSERT INTO labels (name, color, user_id) VALUES (?,?,?)")
        ->execute([$name,$color,current_user()['id']]);
    $pdo->prepare("INSERT INTO audit_log (user_id,action,target) VALUES (?,?,?)")
        ->execute([ current_user()['id'], 'create_label', $name ]);
  }
}
header('Location: dashboard.php');
