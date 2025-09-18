<?php
// Asegúrate de que esta ruta sea correcta
require_once '../config.php';

$error = '';
$token_valido = false;

// 1. Lógica para procesar el envío del formulario (solicitud POST)
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Validar que las variables POST existan antes de usarlas
    if (isset($_POST['token'], $_POST['password'], $_POST['confirm_password'])) {
        $token = $_POST['token'];
        $new_password = $_POST['password'];
        $confirm_password = $_POST['confirm_password'];

        if ($new_password !== $confirm_password) {
            $error = "Las contraseñas no coinciden.";
        } else {
            // Validar el token nuevamente antes de actualizar la contraseña
            $stmt = $pdo->prepare("SELECT user_id FROM password_resets WHERE token = ? AND expires_at > NOW()");
            $stmt->execute([$token]);
            $reset_request = $stmt->fetch();

            if ($reset_request) {
                // Actualizar la contraseña
                $user_id = $reset_request['user_id'];
                $hashed_password = password_hash($new_password, PASSWORD_BCRYPT);

                $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
                $stmt->execute([$hashed_password, $user_id]);

                // Eliminar el token de la base de datos
                $stmt = $pdo->prepare("DELETE FROM password_resets WHERE token = ?");
                $stmt->execute([$token]);

                echo "Tu contraseña ha sido actualizada con éxito.";
                exit; // Detiene la ejecución
            } else {
                $error = "El enlace de recuperación no es válido o ha expirado.";
            }
        }
    } else {
        $error = "Faltan datos en el formulario.";
    }
} 
// 2. Lógica para procesar la URL con el token (solicitud GET)
else if (isset($_GET['token'])) {
    $token = $_GET['token'];

    // Validar el token y la fecha de expiración
    $stmt = $pdo->prepare("SELECT user_id FROM password_resets WHERE token = ? AND expires_at > NOW()");
    $stmt->execute([$token]);
    $reset_request = $stmt->fetch();

    if ($reset_request) {
        $token_valido = true;
    } else {
        $error = "El enlace de recuperación no es válido o ha expirado.";
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Restablecer Contraseña</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
     <div class="container-login">
        <div class="left-section-login">
            <div id="espacio" class="fondo-estrellas"></div>

            <div class="boton-registro-login-left">
                <button class="btn-login-login" onclick="location.href='login.php'">Iniciar Sesión</button>
            </div>
        </div>

        <div class="right-section">
            <div class="logo">
                <div class="logo-icon"><img src="../assets/css/img/Logo to-do.png" alt="Logo TO-DO"></div>
            </div>
    <h3 class="titulo-interfaz-derecha-reset">Restablecer Contraseña</h3>
    <?php if ($error): ?>
        <p style="color: red;"><?= $error ?></p>
    <?php endif; ?>

    <?php if ($token_valido): ?>
        <form action="reset_password.php" class="form-container" method="POST">
            <input type="hidden" class="form-reset" name="token" value="<?= htmlspecialchars($_GET['token']) ?>">
            <input type="password" id="password" class="form-reset" placeholder="Nueva Contraseña" required><br><br>
            <input type="password" id="confirm_password" class="form-reset" placeholder="Confirmar Contraseña" name="confirm_password" required><br><br>
            <button type="submit" id="btn-actualizar-contraseña">Actualizar Contraseña</button>
        </form>
    <?php else: ?>
        <p>No se encontró un enlace válido para restablecer la contraseña.</p>
    <?php endif; ?>
    <script src="../assets/script/script.js"></script>
</body>
</html>