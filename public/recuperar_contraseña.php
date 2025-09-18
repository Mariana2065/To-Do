<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TO DO - Registro</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <div class="container-login">
   <div class="left-section">
            <div id="espacio" class="fondo-estrellas"></div>
            <div class="boton-registro-login-left">
                <button class="btn-login" onclick="location.href='login.php'">Iniciar Sesión</button>
            </div>
        </div>

        <div class="right-section">
            <div class="logo">
                <div class="logo-icon-right-section"><img src="../assets/css/img/Logo to-do.png" alt=""></div>
            </div>

            <h3 class="titulo-interfaz-derecha">Recuperación de contraseña</h3>
            <form action="enviar_enlace.php" method="POST" id="formRecuperarContraseña" class="form-container">
                <input type="email" id="email_recuperacion" name="email" required placeholder="Ingresa tu correo electrónico">
                <button type="submit" class="enviarEnlace">Enviar Enlace</button>
            </form>
        </div>
    </div>
     <div class="modal fade" id="mensajeModal" tabindex="-1" aria-labelledby="mensajeModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="mensajeModalLabel">To-Do</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p id="modalMensaje"></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

          <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js" integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI" crossorigin="anonymous"></script>
<script src="../assets/script/script.js"></script>
</body>
</html>