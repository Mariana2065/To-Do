<?php
require_once __DIR__ . '/../init.php';
require_login();

// LISTA DE PROYECTOS PARA VINCULAR
$stmt = $pdo->prepare("SELECT id, name FROM projects WHERE creator_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$proyectos = $stmt->fetchAll(PDO::FETCH_ASSOC);

// CREAR
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['crear'])) {
    $titulo = trim($_POST['titulo']);
    $descripcion = trim($_POST['descripcion']);
    $proyecto_id = $_POST['proyecto_id'] ?: null;
    $prioridad = $_POST['prioridad'];
    $estado = $_POST['estado'];
    $fecha_venc = $_POST['due_date'] ?: null;

    if (!empty($titulo)) {
        $stmt = $pdo->prepare("INSERT INTO tasks (title, description, creator_id, project_id, priority, status, due_date) 
                               VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$titulo, $descripcion, $_SESSION['user_id'], $proyecto_id, $prioridad, $estado, $fecha_venc]);
    }
    header("Location: tareas.php");
    exit;
}

// EDITAR
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['editar'])) {
    $id = $_POST['id'];
    $titulo = trim($_POST['titulo']);
    $descripcion = trim($_POST['descripcion']);
    $proyecto_id = $_POST['proyecto_id'] ?: null;
    $prioridad = $_POST['prioridad'];
    $estado = $_POST['estado'];
    $fecha_venc = $_POST['due_date'] ?: null;

    $stmt = $pdo->prepare("UPDATE tasks SET title=?, description=?, project_id=?, priority=?, status=?, due_date=? 
                           WHERE id=? AND creator_id=?");
    $stmt->execute([$titulo, $descripcion, $proyecto_id, $prioridad, $estado, $fecha_venc, $id, $_SESSION['user_id']]);
    header("Location: tareas.php");
    exit;
}

// ELIMINAR
if (isset($_GET['eliminar'])) {
    $id = $_GET['eliminar'];
    $stmt = $pdo->prepare("DELETE FROM tasks WHERE id = ? AND creator_id = ?");
    $stmt->execute([$id, $_SESSION['user_id']]);
    header("Location: tareas.php");
    exit;
}

// LISTAR
$stmt = $pdo->prepare("SELECT t.*, p.name AS proyecto 
                       FROM tasks t 
                       LEFT JOIN projects p ON t.project_id = p.id 
                       WHERE t.creator_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$tareas = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Tareas - <?= APP_NAME ?></title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<div class="container-registro">
    <!-- Sidebar -->
    <div class="left-section">
        <div class="boton-registro-login-left">
            <button class="btn-registro" onclick="location.href='dashboard.php'">⬅ Dashboard</button>
            <button class="btn-login" onclick="location.href='logout.php'">Cerrar Sesión</button>
        </div>
    </div>

    <!-- Contenido -->
    <div class="right-section">
        <h2 class="logo-text">✅ Mis Tareas</h2>

        <!-- Crear Tarea -->
        <form method="POST" class="form-container">
            <div class="form-group">
                <input type="text" name="titulo" class="form-input" placeholder="Título de la tarea" required>
            </div>
            <div class="form-group">
                <textarea name="descripcion" class="form-input" placeholder="Descripción"></textarea>
            </div>
            <div class="form-group">
                <label>Proyecto:</label>
                <select name="proyecto_id" class="form-input">
                    <option value="">(Sin proyecto)</option>
                    <?php foreach ($proyectos as $p): ?>
                        <option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Prioridad:</label>
                <select name="prioridad" class="form-input">
                    <option value="low">Baja</option>
                    <option value="medium" selected>Media</option>
                    <option value="high">Alta</option>
                    <option value="urgent">Urgente</option>
                </select>
            </div>
            <div class="form-group">
                <label>Estado:</label>
                <select name="estado" class="form-input">
                    <option value="todo">Por hacer</option>
                    <option value="in_progress">En progreso</option>
                    <option value="done">Hecha</option>
                    <option value="archived">Archivada</option>
                </select>
            </div>
            <div class="form-group">
                <label>Fecha de vencimiento:</label>
                <input type="date" name="due_date" class="form-input">
            </div>
            <button type="submit" name="crear" class="register-btn">Crear Tarea</button>
        </form>

        <!-- Lista de Tareas -->
        <h3 class="logo-text" style="margin-top:20px;">📋 Lista de Tareas</h3>
        <ul style="list-style:none; padding:0;">
            <?php foreach ($tareas as $t): ?>
                <li style="margin:10px 0; padding:10px; border-bottom:1px solid #ccc;">
                    <strong><?= htmlspecialchars($t['title']) ?></strong> 
                    (<?= $t['status'] ?>, <?= $t['priority'] ?>)
                    <?php if ($t['proyecto']): ?> [Proyecto: <?= htmlspecialchars($t['proyecto']) ?>]<?php endif; ?>
                    <br>
                    <a href="tareas.php?editar=<?= $t['id'] ?>" class="btn-login">Editar</a>
                    <a href="tareas.php?eliminar=<?= $t['id'] ?>" class="btn-registro" onclick="return confirm('¿Eliminar tarea?')">Eliminar</a>
                </li>
            <?php endforeach; ?>
        </ul>

        <!-- Editar Tarea -->
        <?php if (isset($_GET['editar'])): 
            $id = $_GET['editar'];
            $stmt = $pdo->prepare("SELECT * FROM tasks WHERE id = ? AND creator_id = ?");
            $stmt->execute([$id, $_SESSION['user_id']]);
            $edit = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($edit):
        ?>
        <h3 class="logo-text" style="margin-top:20px;">✏ Editar Tarea</h3>
        <form method="POST" class="form-container">
            <input type="hidden" name="id" value="<?= $edit['id'] ?>">
            <div class="form-group">
                <input type="text" name="titulo" class="form-input" value="<?= htmlspecialchars($edit['title']) ?>" required>
            </div>
            <div class="form-group">
                <textarea name="descripcion" class="form-input"><?= htmlspecialchars($edit['description']) ?></textarea>
            </div>
            <div class="form-group">
                <label>Proyecto:</label>
                <select name="proyecto_id" class="form-input">
                    <option value="">(Sin proyecto)</option>
                    <?php foreach ($proyectos as $p): ?>
                        <option value="<?= $p['id'] ?>" <?= $p['id'] == $edit['project_id'] ? "selected" : "" ?>>
                            <?= htmlspecialchars($p['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Prioridad:</label>
                <select name="prioridad" class="form-input">
                    <option value="low" <?= $edit['priority']=="low"?"selected":"" ?>>Baja</option>
                    <option value="medium" <?= $edit['priority']=="medium"?"selected":"" ?>>Media</option>
                    <option value="high" <?= $edit['priority']=="high"?"selected":"" ?>>Alta</option>
                    <option value="urgent" <?= $edit['priority']=="urgent"?"selected":"" ?>>Urgente</option>
                </select>
            </div>
            <div class="form-group">
                <label>Estado:</label>
                <select name="estado" class="form-input">
                    <option value="todo" <?= $edit['status']=="todo"?"selected":"" ?>>Por hacer</option>
                    <option value="in_progress" <?= $edit['status']=="in_progress"?"selected":"" ?>>En progreso</option>
                    <option value="done" <?= $edit['status']=="done"?"selected":"" ?>>Hecha</option>
                    <option value="archived" <?= $edit['status']=="archived"?"selected":"" ?>>Archivada</option>
                </select>
            </div>
            <div class="form-group">
                <label>Fecha de vencimiento:</label>
                <input type="date" name="due_date" class="form-input" value="<?= $edit['due_date'] ?>">
            </div>
            <button type="submit" name="editar" class="login-btn">Guardar Cambios</button>
        </form>
        <?php endif; endif; ?>
    </div>
</div>
</body>
</html>
