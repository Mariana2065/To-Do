<?php

require_once '../config.php';

$error = '';
$success = '';
$token_valido = false;

// Handle POST request (form submission)
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Sanitize and validate input
    if (isset($_POST['token'], $_POST['password'], $_POST['confirm_password'])) {
        $token = $_POST['token'];
        $new_password = $_POST['password'];
        $confirm_password = $_POST['confirm_password'];

        if ($new_password !== $confirm_password) {
            $error = "Las contraseñas no coinciden.";
        } else {
            // Check for a valid, non-expired token
            $stmt = $pdo->prepare("SELECT user_id FROM password_resets WHERE token = ? AND expires_at > NOW()");
            $stmt->execute([$token]);
            $reset_request = $stmt->fetch();

            if ($reset_request) {
                // Update password and delete the token
                $user_id = $reset_request['user_id'];
                $hashed_password = password_hash($new_password, PASSWORD_BCRYPT);
                $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
                $stmt->execute([$hashed_password, $user_id]);
                $stmt = $pdo->prepare("DELETE FROM password_resets WHERE token = ?");
                $stmt->execute([$token]);
                $success = "Tu contraseña ha sido actualizada con éxito.";
            } else {
                $error = "El enlace de recuperación no es válido o ha expirado.";
            }
        }
    } else {
        $error = "Faltan datos en el formulario.";
    }
} 
// Handle GET request (initial page load from email link)
else if (isset($_GET['token'])) {
    $token = $_GET['token'];
    // Check for a valid token
    $stmt = $pdo->prepare("SELECT user_id FROM password_resets WHERE token = ? AND expires_at > NOW()");
    $stmt->execute([$token]);
    if ($stmt->fetch()) {
        $token_valido = true;
    } else {
        $error = "El enlace de recuperación no es válido o ha expirado.";
    }
} else {
    $error = "No se encontró un enlace válido para restablecer la contraseña.";
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Restablecer Contraseña</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
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
            
            <?php if ($success): ?>
                <div class="alert alert-success mt-3"><?= $success ?></div>
                <script>
                    // Redirect to the login page after 3 seconds
                    setTimeout(() => {
                        window.location.href = 'login.php';
                    }, 3000);
                </script>
            <?php elseif ($error): ?>
                <div class="alert alert-danger mt-3"><?= $error ?></div>
            <?php endif; ?>

            <?php if ($token_valido && !$success && !$error): ?>
                <form action="reset_password.php" class="form-container" method="POST" id="reset-form">
                    <input type="hidden" name="token" value="<?= htmlspecialchars($_GET['token']) ?>">
                    <input type="password" id="password" class="form-reset" placeholder="Nueva Contraseña" name="password" required>
                    <input type="password" id="confirm_password" class="form-reset" placeholder="Confirmar Contraseña" name="confirm_password" required>
                    <button type="submit" id="btn-actualizar-contraseña">Actualizar Contraseña</button>
                </form>
            <?php endif; ?>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>