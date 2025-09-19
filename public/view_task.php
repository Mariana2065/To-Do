<?php
require_once __DIR__ . '/../init.php';
require_login();

$id = $_GET['id'] ?? null;
if (!$id) {
    header("Location: tareas.php");
    exit;
}

// ==========================
// 1. OBTENER INFORMACIÓN DE LA TAREA
// ==========================
// Obtener datos de la tarea con proyecto, usuario asignado y etiquetas
$stmt = $pdo->prepare("
    SELECT t.*, 
           p.name AS proyecto, 
           u.name AS asignado,
           (SELECT GROUP_CONCAT(tags.name SEPARATOR ', ')
            FROM task_tags 
            JOIN tags ON tags.id = task_tags.tag_id
            WHERE task_tags.task_id = t.id) AS etiquetas
    FROM tasks t
    LEFT JOIN projects p ON t.project_id = p.id
    LEFT JOIN users u ON t.assignee_id = u.id
    WHERE t.id = ? AND t.creator_id = ?
");
$stmt->execute([$id, $_SESSION['user_id']]);
$task = $stmt->fetch(PDO::FETCH_ASSOC);




// ==========================
// 2. SUBTAREAS
// ==========================
// Crear nueva subtarea
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['crear_subtarea'])) {
    $titulo = trim($_POST['subtitulo']);
    if (!empty($titulo)) {
        $stmt = $pdo->prepare("INSERT INTO subtasks (task_id, title, status) VALUES (?, ?, 'todo')");
        $stmt->execute([$id, $titulo]);
    }
    header("Location: view_task.php?id=" . $id);
    exit;
}

// Toggle estado subtarea
if (isset($_GET['toggle_subtarea'])) {
    $sub_id = $_GET['toggle_subtarea'];
    $stmt = $pdo->prepare("SELECT status FROM subtasks WHERE id=? AND task_id=?");
    $stmt->execute([$sub_id, $id]);
    $sub = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($sub) {
        $nuevo = ($sub['status'] === 'done') ? 'todo' : 'done';
        $pdo->prepare("UPDATE subtasks SET status=? WHERE id=?")->execute([$nuevo, $sub_id]);
    }
    header("Location: view_task.php?id=" . $id);
    exit;
}

// Eliminar subtarea
if (isset($_GET['eliminar_subtarea'])) {
    $sub_id = $_GET['eliminar_subtarea'];
    $pdo->prepare("DELETE FROM subtasks WHERE id=? AND task_id=?")->execute([$sub_id, $id]);
    header("Location: view_task.php?id=" . $id);
    exit;
}

// Obtener subtareas
$stmt = $pdo->prepare("SELECT * FROM subtasks WHERE task_id=? ORDER BY id DESC");
$stmt->execute([$id]);
$subtareas = $stmt->fetchAll(PDO::FETCH_ASSOC);


// ==========================
// 3. COMENTARIOS
// ==========================
$stmt = $pdo->prepare("
    SELECT c.*, u.name 
    FROM comments c
    JOIN users u ON u.id = c.user_id
    WHERE c.task_id = ?
    ORDER BY c.created_at DESC
");
$stmt->execute([$id]);
$comentarios = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ==========================
// 4. ARCHIVOS ADJUNTOS
// ==========================
$stmt = $pdo->prepare("SELECT * FROM attachments WHERE task_id = ?");
$stmt->execute([$id]);
$adjuntos = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ==========================
// 5. GUARDAR COMENTARIO NUEVO
// ==========================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['comentar'])) {
    $contenido = trim($_POST['contenido']);
    if (!empty($contenido)) {
        $stmt = $pdo->prepare("INSERT INTO comments (task_id, user_id, content, created_at) VALUES (?, ?, ?, NOW())");
        $stmt->execute([$id, $_SESSION['user_id'], $contenido]);
    }
    header("Location: view_task.php?id=" . $id);
    exit;
}

// ==========================
// 6. SUBIR ADJUNTOS
// ==========================
$upload_dir = __DIR__ . '/../uploads/';
if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0777, true);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['subir_adjunto'])) {
    if (!empty($_FILES['attachment']['name'])) {
        $filename = basename($_FILES['attachment']['name']);
        $filepath = $upload_dir . time() . "_" . $filename;

        if (move_uploaded_file($_FILES['attachment']['tmp_name'], $filepath)) {
            $stmt = $pdo->prepare("INSERT INTO attachments (task_id, user_id, filename, filepath) VALUES (?, ?, ?, ?)");
            $stmt->execute([$id, $_SESSION['user_id'], $filename, $filepath]);
        }
    }
    header("Location: view_task.php?id=" . $id);
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($task['title']) ?> - <?= APP_NAME ?></title>
    <!-- <link rel="stylesheet" href="../assets/css/style.css"> -->
</head>
<body>
<div class="container-registro">
    <!-- Sidebar -->
    <div class="left-section">
        <div class="boton-registro-login-left">
            <button class="btn-registro" onclick="location.href='tareas.php'">⬅ Volver a Tareas</button>
            <button class="btn-login" onclick="location.href='logout.php'">Cerrar Sesión</button>
        </div>
    </div>

    <!-- Contenido principal -->
    <div class="right-section">
        <h2 class="logo-text">📌 <?= htmlspecialchars($task['title']) ?></h2>
        <p><?= nl2br(htmlspecialchars($task['description'] ?? '')) ?></p>

        <p><strong>Proyecto:</strong> <?= htmlspecialchars($task['proyecto'] ?? '(Ninguno)') ?></p>
        <p><strong>Asignado a:</strong> 
            <?= $task['asignado'] ? htmlspecialchars($task['asignado']) : 'No asignado' ?>
        </p>
        <p><strong>Etiquetas:</strong> <?= htmlspecialchars($task['etiquetas'] ?? '(Sin etiquetas)') ?></p>
        <p><strong>Prioridad:</strong> <?= htmlspecialchars($task['priority']) ?></p>
        <p><strong>Estado:</strong> <?= htmlspecialchars($task['status']) ?></p>
        <p><strong>Fecha límite:</strong> <?= $task['due_date'] ?: '(No definida)' ?></p>

        <!-- ================== Subtareas ================== -->
        <h3 class="logo-text" style="margin-top:20px;">📋 Lista de Subtareas</h3>

        <!-- Formulario para añadir nueva subtarea -->
        <form method="POST" style="margin-bottom:15px;">
            <input type="hidden" name="task_id" value="<?= $id ?>">
            <input type="text" name="subtitulo" placeholder="Nueva subtarea" required>
            <button type="submit" name="crear_subtarea">➕ Agregar</button>
        </form>

        <ul style="list-style:none; padding:0;">
            <?php if (!empty($subtareas)): ?>
                <?php foreach ($subtareas as $s): ?>
                    <li style="margin:10px 0; padding:10px; border-bottom:1px solid #ccc;">
                        <input type="checkbox" 
                            onclick="location.href='view_task.php?id=<?= $id ?>&toggle_subtarea=<?= $s['id'] ?>'"
                            <?= $s['status']=='done' ? 'checked' : '' ?>>
                        <?= htmlspecialchars($s['title']) ?>
                        <?php if ($s['status']=='done'): ?>
                            <span style="color:green;">✔</span>
                        <?php endif; ?>
                        <a href="view_task.php?id=<?= $id ?>&eliminar_subtarea=<?= $s['id'] ?>" 
                        onclick="return confirm('¿Eliminar subtarea?')">❌</a>
                    </li>
                <?php endforeach; ?>
            <?php else: ?>
                <li>No hay subtareas.</li>
            <?php endif; ?>
        </ul>


        <!-- ================== Comentarios ================== -->
        <h3 class="logo-text">💬 Comentarios</h3>
        <ul>
            <?php foreach ($comentarios as $c): ?>
                <li>
                    <strong><?= htmlspecialchars($c['name']) ?>:</strong> 
                    <?= htmlspecialchars($c['content']) ?> 
                    <em>(<?= $c['created_at'] ?>)</em>
                    <a href="delete_comment.php?id=<?= $c['id'] ?>" onclick="return confirm('¿Eliminar comentario?')">❌</a>
                </li>
            <?php endforeach; ?>
        </ul>
        <form method="POST">
            <input type="text" name="contenido" class="form-input" placeholder="Escribe un comentario" required>
            <button type="submit" name="comentar" class="login-btn">Comentar</button>
        </form>

        <!-- ================== Archivos ================== -->
        <h3>📂 Archivos Adjuntos</h3>
        <ul>
        <?php
            $stmt = $pdo->prepare("SELECT * FROM attachments WHERE task_id = ?");
            $stmt->execute([$task['id']]);
            $adjuntos = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if ($adjuntos) {
                foreach ($adjuntos as $adj): ?>
                    <li>
                        <a href="download_attachment.php?id=<?= $adj['id'] ?>">
                            <?= htmlspecialchars($adj['filename']) ?>
                        </a>
                        <a href="delete_attachment.php?id=<?= $adj['id'] ?>" 
                        onclick="return confirm('¿Eliminar adjunto?')">❌</a>
                    </li>
                <?php endforeach;
            } else {
                echo "<p>No hay adjuntos.</p>";
            }
        ?>
        </ul>

        <!-- Formulario para subir nuevo adjunto -->
        <form method="POST" enctype="multipart/form-data" action="upload_attachment.php">
            <input type="hidden" name="task_id" value="<?= $task['id'] ?>">
            <input type="file" name="archivo" required>
            <button type="submit">Subir Archivo</button>
        </form>

    </div>
</div>
</body>
</html>
