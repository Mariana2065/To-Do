<?php
require_once '../config.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['token'], $_POST['password'], $_POST['confirm_password'])) {
        $token = $_POST['token'];
        $password = $_POST['password'];
        $confirm_password = $_POST['confirm_password'];

        // Validand las contraseñas
        if ($password !== $confirm_password) {
            die("Las contraseñas no coinciden.");
        }
        // validando el token
        $stmt = $pdo->prepare("SELECT user_id FROM password_resets WHERE token = ? AND expires_at > NOW()");
        $stmt->execute([$token]);
        $reset_request = $stmt->fetch();

        if ($reset_request) {
            // Obtener el ID del usuario del token
            $user_id = $reset_request['user_id'];

            // Hashear y actualizar la contraseña del usuario
            $hashed_password = password_hash($password, PASSWORD_BCRYPT);
            $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
            $stmt->execute([$hashed_password, $user_id]);

            // Eliminar el token para evitar reuso
            $stmt = $pdo->prepare("DELETE FROM password_resets WHERE token = ?");
            $stmt->execute([$token]);

            echo "Tu contraseña ha sido actualizada con éxito. Ahora puedes iniciar sesión.";
        } else {
            echo "El enlace de recuperación no es válido o ha expirado.";
        }
    } else {
        echo "Faltan datos en el formulario.";
    }
} else {
    echo "Acceso denegado.";
}
?>