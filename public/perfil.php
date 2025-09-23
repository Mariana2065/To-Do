<?php
require_once __DIR__ . '/../init.php';
require_login();

// ==========================
// OBTENER DATOS DEL USUARIO
// ==========================
$stmt = $pdo->prepare("SELECT id, name, email, avatar FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$usuario = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$usuario) {
    die("Usuario no encontrado.");
}

// ==========================
// ACTUALIZAR PERFIL
// ==========================
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['actualizar'])) {
    $nombre = trim($_POST['name']);

    // Subida de avatar
    $avatar = $usuario['avatar']; // mantener actual si no se cambia
    if (!empty($_FILES['avatar']['name'])) {
        $uploadDir = __DIR__ . '/../uploads/avatars/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        $fileName = uniqid() . "_" . basename($_FILES['avatar']['name']);
        $targetFile = $uploadDir . $fileName;

        if (move_uploaded_file($_FILES['avatar']['tmp_name'], $targetFile)) {
            $avatar = 'uploads/avatars/' . $fileName;
        }
    }

    if (!empty($nombre)) {
        $stmt = $pdo->prepare("UPDATE users SET name=?, avatar=? WHERE id=?");
        $stmt->execute([$nombre, $avatar, $_SESSION['user_id']]);

        $_SESSION['success'] = "Perfil actualizado correctamente ✅";
        header("Location: perfil.php");
        exit;
    } else {
        $_SESSION['error'] = "El nombre es obligatorio ❌";
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Perfil - <?= APP_NAME ?></title>
     <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<div class="container-registro">

    <!-- Sidebar -->

    <?php include_once '../include/sidebar.php'; ?>

    <!-- Contenido -->
    <div class="right-section">
        <h2 class="logo-text-perfil">Mi Perfil</h2>

        <?php if (!empty($_SESSION['success'])): ?>
            <p style="color:green;"><?= $_SESSION['success']; unset($_SESSION['success']); ?></p>
        <?php endif; ?>

        <?php if (!empty($_SESSION['error'])): ?>
            <p style="color:red;"><?= $_SESSION['error']; unset($_SESSION['error']); ?></p>
        <?php endif; ?>

        <!-- Foto de perfil -->
        <div style="margin-bottom:15px;">
            <?php if (!empty($usuario['avatar'])): ?>
                <img src="../<?= htmlspecialchars($usuario['avatar']) ?>" alt="Avatar" style="width:120px;height:120px;border-radius:50%;object-fit:cover;">
            <?php else: ?>
                <img src="../assets/css/img/iconperfil.png" alt="Avatar" style="width:120px;height:120px;border-radius:50%;object-fit:cover;">
            <?php endif; ?>
        </div>

        <!-- Formulario de actualización -->
        <form method="POST" class="form-container" enctype="multipart/form-data">
            <div class="form-group-project">
                <label class="label-perfil">Nombre:</label>
                <input type="text" name="name" class="form-input" value="<?= htmlspecialchars($usuario['name']) ?>" required>
            </div>

            <div class="form-group-project">
                <label class="label-perfil">Email:</label><br>
                <strong class="perfil-email"><?= htmlspecialchars($usuario['email']) ?></strong>
            </div>

            <div class="form-group-project-foto">
                <label class="label-perfil" id="btn-selec-file" for="file-upload">Subir Foto</label>
                <input type="file" name="avatar" accept="image/*" class="form-input" id="file-upload">
            </div>
                <div class="btn-acualizar-avatar">
            <button type="submit" name="actualizar" class="btn-actualizar">Actualizar Perfil</button>

                </div>
        </form>
    </div>
</div>
</body>
</html>
