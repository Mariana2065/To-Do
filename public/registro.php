<?php
require_once __DIR__ . '/../init.php';

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $nombre = trim($_POST['nombre']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    // Validar campos
    if (empty($nombre) || empty($email) || empty($password) || empty($confirm_password)) {
        $error = "Todos los campos son obligatorios.";
    } elseif($password !== $confirm_password){
        $error = "Las contraseñas no coinciden.";
    }
    else {
        // Verificar si el email ya existe
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $error = "El correo ya está registrado.";
        } else {
            // Insertar usuario
            $hashed = password_hash($password, PASSWORD_BCRYPT);
            $stmt = $pdo->prepare("INSERT INTO users (name, email, password) VALUES (?, ?, ?)");
            if ($stmt->execute([$nombre, $email, $hashed])) {
                $success = "Usuario registrado correctamente. Ahora puedes iniciar sesión.";
            } else {
                $error = "Hubo un error al registrar el usuario.";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TO DO - Registro <?= APP_NAME ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">

    <link rel="stylesheet" href="../assets/css/style.css">

</head>

<body>
    <div class="container-registro">

        <div class="left-section">
            <div id="espacio" class="fondo-estrellas"></div>
            <div class="boton-registro-login-left">
                <button class="btn-registro active" onclick="location.href='registro.php'">Registrar</button>
                <button class="btn-login" onclick="location.href='login.php'">Iniciar Sesión</button>
            </div>
        </div>

        <div class="right-section">
            <div class="logo">
                <div class="logo-icon"><img src="../assets/css/img/Logo to-do.png" alt=""></div>
            </div>

          <div class="w-75 mx-auto"> 
        <?php if (!empty($error)): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?= $error ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
            </div>
        <?php endif; ?>

        <?php if (!empty($success)): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?= $success ?>
            </div>
            <?php else: ?>

                <form method="POST" class="form-container" id="registerForm">
                    <div class="form-group">
                        <input type="text" name="nombre" class="form-input" placeholder="Nombre" required>
                    </div>

                    <div class="form-group">
                        <input type="email" name="email" class="form-input" placeholder="Email" required>
                    </div>

                    <div class="password-row">
                        <input type="password" name="password" class="form-input" placeholder="Contraseña" required>
                        <input type="password" name="confirm_password" class="form-input" placeholder="Verificar Contraseña" required>
                    </div>

                    <button type="submit" class="register-btn">Registrar</button>
                </form>
            <?php endif; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js" integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI" crossorigin="anonymous"></script>

    <script src="../assets/script/script.js"></script>
</body>

</html>