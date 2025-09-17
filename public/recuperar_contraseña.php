<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recuperación contraseña</title>
</head>
<body>
    <h3>Recuperación de contraseña</h3>
    <form action="enviar_enlace.php" method="POST">
        <label for="email">Correo electrónico:</label>
        <input type="email" id="email" name="email" required>
        <button type="submit">Enviar</button>
    </form>
</body>
</html>