<?php
require_once __DIR__ . '/../init.php';
require_login();

// CREAR
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['crear'])) {
    $nombre = trim($_POST['nombre']);
    if (!empty($nombre)) {
        $stmt = $pdo->prepare("INSERT INTO tags (name, creator_id) VALUES (?, ?)");
        $stmt->execute([$nombre, $_SESSION['user_id']]);
    }
    header("Location: tags.php");
    exit;
}

// EDITAR
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['editar'])) {
    $id = $_POST['id'];
    $nombre = trim($_POST['nombre']);
    $stmt = $pdo->prepare("UPDATE tags SET name = ? WHERE id = ? AND creator_id = ?");
    $stmt->execute([$nombre, $id, $_SESSION['user_id']]);
    header("Location: tags.php");
    exit;
}

// ELIMINAR
if (isset($_GET['eliminar'])) {
    $id = $_GET['eliminar'];
    $stmt = $pdo->prepare("DELETE FROM tags WHERE id = ? AND creator_id = ?");
    $stmt->execute([$id, $_SESSION['user_id']]);
    header("Location: tags.php");
    exit;
}

// LISTAR
$stmt = $pdo->prepare("SELECT * FROM tags WHERE creator_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$tags = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Etiquetas - <?= APP_NAME ?></title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
        <?php include_once '../include/sidebar.php'; ?>

        
<body class="body-ver-tags">
 


    <!-- Contenido -->
    <div class="right-section">
        <h2 class="titulos-tags">Mis Etiquetas</h2>

        <!-- Crear etiqueta -->
        <form method="POST" class="form-container-tags">
            <div class="form-group-tags">
                <input type="text" name="nombre" class="form-input-tags" placeholder="Nombre de la etiqueta" required>
            </div>
            <button type="submit" name="crear" class="crear-tag">Crear Etiqueta</button>
        </form>

        <h3 class="logo-text" style="margin-top:20px;">Lista de Etiquetas</h3>
        <ul style="list-style:none; padding:0;">
            <?php foreach ($tags as $tag): ?>
                <li style="margin:10px 0; padding:10px; border-bottom:1px solid #ccc;">
                    <strong><?= htmlspecialchars($tag['name']) ?></strong>
                    <a href="tags.php?editar=<?= $tag['id'] ?>" class="btn-login">Editar</a>
                    <a href="tags.php?eliminar=<?= $tag['id'] ?>" class="btn-registro" onclick="return confirm('¿Eliminar etiqueta?')">Eliminar</a>
                </li>
            <?php endforeach; ?>
        </ul>

        <!-- Editar etiqueta -->
        <?php if (isset($_GET['editar'])): 
            $id = $_GET['editar'];
            $stmt = $pdo->prepare("SELECT * FROM tags WHERE id = ? AND creator_id = ?");
            $stmt->execute([$id, $_SESSION['user_id']]);
            $edit = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($edit):
        ?>
        <h3 class="logo-text" style="margin-top:20px;">✏ Editar Etiqueta</h3>
        <form method="POST" class="form-container-tags">
            <input type="hidden" name="id" value="<?= $edit['id'] ?>">
            <div class="form-group">
                <input type="text" name="nombre" class="form-input-tags" value="<?= htmlspecialchars($edit['name']) ?>" required>
            </div>
            <button type="submit" name="editar" class="login-btn">Guardar Cambios</button>
        </form>
        <?php endif; endif; ?>
    </div>
</div>
</body>
</html>
