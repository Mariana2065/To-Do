<?php
require_once __DIR__ . '/../init.php';

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    $stmt = $pdo->prepare("SELECT id, name, email, password FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        // Verificar hash o texto plano
        if ($password === $user['password'] || password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['name'];
            header("Location: dashboard.php");
            exit;
        } else {
            $error = "Contraseña incorrecta.";
        }
    } else {
        $error = "No existe un usuario con ese correo.";
    }
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TO DO - Iniciar Sesión</title>
    <link rel="stylesheet" href="../assets/css/style.css">

</head>

<body>
    <div class="container-login">
        <div class="left-section-login">
            <div id="espacio" class="fondo-estrellas"></div>

            <div class="boton-registro-login-left">
                <button class="btn-registro-login" onclick="location.href='registro.php'">Registrar</button>
                <button class="btn-login-login">Iniciar Sesión</button>
            </div>
        </div>

        <div class="right-section">
            <div class="logo">
                <div class="logo-icon"><img src="../assets/css/img/Logo to-do.png" alt="Logo TO-DO"></div>
            </div>

            <?php if (!empty($error)): ?>
                <p style="color:red; text-align:center;"><?= $error ?></p>
            <?php endif; ?>

            <form method="post" class="form-container" id="registerForm">
                <div class="form-group">
                    <input type="email" name="email" class="form-input login" placeholder="Email" required>
                </div>

                <div class="password-row">
                    <input type="password" name="password" class="form-input login" placeholder="Contraseña" required>
                </div>

                <button type="submit" class="login-btn">Iniciar Sesión</button>
            </form>
            <a href="recuperar_contraseña.php" id="link_recuperar_contraseña">Olvidaste tu contraseña?</a>

        </div>
    </div>

    <script src="../assets/script/script.js"></script>
</body>

</html>