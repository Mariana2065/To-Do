<?php
// config.php
define('DB_HOST', 'localhost');
define('DB_NAME', 'todo_mvp');
define('DB_USER', 'root');
define('DB_PASS', '');

// Opciones generales
define('APP_NAME', 'To-Do Avanzado');
define('BASE_URL', 'http://localhost/To-Do/public/');

// === Código para crear la conexión PDO ===
try {
    $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME;
    $pdo = new PDO($dsn, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Error de conexión a la base de datos: " . $e->getMessage());
}
