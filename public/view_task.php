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
$stmt = $pdo->prepare("
    SELECT t.*, p.name AS proyecto, u.name AS asignado,
           COALESCE(tu.role, pu.role, 'owner') AS user_role
    FROM tasks t
    LEFT JOIN projects p ON t.project_id = p.id
    LEFT JOIN users u ON t.assignee_id = u.id
    LEFT JOIN task_users tu ON tu.task_id = t.id AND tu.user_id = ?
    LEFT JOIN project_users pu ON pu.project_id = t.project_id AND pu.user_id = ?
    WHERE t.id = ?
      AND (
          t.creator_id = ?
          OR t.assignee_id = ?
          OR tu.user_id IS NOT NULL
          OR pu.user_id IS NOT NULL
      )
");
$stmt->execute([$_SESSION['user_id'], $_SESSION['user_id'], $id, $_SESSION['user_id'], $_SESSION['user_id']]);
$task = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$task) {
    die("Tarea no encontrada o no tienes permisos para verla.");
}

// Guardamos el rol del usuario en la tarea/proyecto
$userRole = $task['user_role'];

// ==========================
// 2. SUBTAREAS
// ==========================
// Crear subtarea (solo owner o editor)
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['crear_subtarea']) && $userRole !== 'viewer') {
    $titulo = trim($_POST['subtitulo']);
    if (!empty($titulo)) {
        $stmt = $pdo->prepare("INSERT INTO subtasks (task_id, title, status) VALUES (?, ?, 'todo')");
        $stmt->execute([$id, $titulo]);
    }
    header("Location: view_task.php?id=" . $id);
    exit;
}

// Cambiar estado de subtarea (solo owner o editor)
if (isset($_GET['toggle_subtarea']) && $userRole !== 'viewer') {
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

// Eliminar subtarea (solo owner o editor)
if (isset($_GET['eliminar_subtarea']) && $userRole !== 'viewer') {
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
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['comentar']) && $userRole !== 'viewer') {
    $contenido = trim($_POST['contenido']);
    if (!empty($contenido)) {
        $stmt = $pdo->prepare("INSERT INTO comments (task_id, user_id, content, created_at) VALUES (?, ?, ?, NOW())");
        $stmt->execute([$id, $_SESSION['user_id'], $contenido]);
    }
    header("Location: view_task.php?id=" . $id);
    exit;
}
$stmt = $pdo->prepare("
    SELECT c.id, c.content, u.name, c.created_at, c.user_id
    FROM comments c
    JOIN users u ON c.user_id = u.id
    WHERE c.task_id = ?
    ORDER BY c.created_at DESC
");

$stmt->execute([$id]);
$comentarios = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ==========================
// 4. ARCHIVOS ADJUNTOS
// ==========================
$upload_dir = __DIR__ . '/../uploads/';
if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0777, true);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['subir_adjunto']) && $userRole !== 'viewer') {
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

$stmt = $pdo->prepare("SELECT * FROM attachments WHERE task_id = ?");
$stmt->execute([$id]);
$adjuntos = $stmt->fetchAll(PDO::FETCH_ASSOC);

// =============================
// Colaboradores
// =============================

// Agregar colaborador
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['agregar_colaborador']) && $userRole === 'owner') {
    $user_id = $_POST['user_id'];
    $role = $_POST['role'] ?? 'colaborador';

    if ($user_id) {
        $stmt = $pdo->prepare("INSERT IGNORE INTO task_users (task_id, user_id, role) VALUES (?, ?, ?)");
        $stmt->execute([$id, $user_id, $role]);
    }
    header("Location: view_task.php?id=" . $id);
    exit;
}

$stmt = $pdo->prepare("
    SELECT u.id, u.name, tu.role
    FROM task_users tu
    JOIN users u ON u.id = tu.user_id
    WHERE tu.task_id = ?
");
$stmt->execute([$id]);
$colaboradores = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Eliminar colaborador
if (isset($_GET['remove_colab']) && $userRole === 'owner') {
    $colab_id = $_GET['remove_colab'];
    $stmt = $pdo->prepare("DELETE FROM task_users WHERE task_id=? AND user_id=?");
    $stmt->execute([$id, $colab_id]);
    header("Location: view_task.php?id=" . $id);
    exit;
}

// Listar colaboradores
$stmt = $pdo->prepare("SELECT u.id, u.name, tu.role FROM task_users tu JOIN users u ON tu.user_id=u.id WHERE tu.task_id=?");
$stmt->execute([$id]);
$colaboradores = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Listar usuarios para invitar
$usuarios = $pdo->query("SELECT id, name FROM users ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);



// Traducciones para prioridad
$prioridades = [
    'low' => 'Baja',
    'medium' => 'Media',
    'high' => 'Alta',
    'urgent' => 'Urgente'
];

// Traducciones para estado
$estados = [
    'todo' => 'Por hacer',
    'in_progress' => 'En progreso',
    'done' => 'Completada'
];
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Tarea - <?= htmlspecialchars($task['title']) ?></title>
    <link rel="stylesheet" href="../assets/css/style.css">
    </head>

<body class="task-view-body">
    <?php include_once '../include/sidebar.php'; ?>

    <div class="right-section">
        <h1 class="titulo-ver-tareas">Tarea:<br> <?= htmlspecialchars($task['title']) ?></h1>

        <div class="task-grid-container">

            <div class="task-section">
                <h3 class="detalles-tareas-titulos">Detalles de la Tarea</h3>
                <p><strong class="descripcion-tarea">Descripción:</strong> <?= nl2br(htmlspecialchars($task['description'] ?? '')) ?></p>
                <br>
                <p><strong class="descripcion-tarea">Proyecto:</strong> <?= htmlspecialchars($task['proyecto'] ?? '(Ninguno)') ?></p>
                <br>
                <p><strong class="descripcion-tarea">Asignado a:</strong> <?= $task['asignado'] ? htmlspecialchars($task['asignado']) : 'No asignado' ?></p>
                <br>
                <p><strong class="descripcion-tarea">Etiquetas:</strong> <?= htmlspecialchars($task['etiquetas'] ?? '(Sin etiquetas)') ?></p>
                <br>
                <p><strong class="descripcion-tarea">Prioridad:</strong> <?= $prioridades[$task['priority']] ?? '(No definida)' ?></p>                
                <br>
                <p><strong class="descripcion-tarea">Estado:</strong> <?= $estados[$task['status']] ?? '(No definida)' ?></p>
                <br>
                <p><strong class="descripcion-tarea">Fecha límite:</strong> <?= $task['due_date'] ?: '(No definida)' ?></p>
                <br>
                <p><strong class="descripcion-tarea">Creada el:</strong> <?= $task['created_at'] ?></p>
                <br>
                <?php if ($userRole === 'owner' || $userRole === 'editor'): ?>
                    <a href="tareas.php?editar=<?= $task['id'] ?>">✏ Editar</a>
                <?php else: ?>
                    <p><em>🔒 Modo solo lectura (viewer)</em></p>
                <?php endif; ?>
            </div>

            <div class="task-section">
                <h3 class="detalles-tareas-titulos">Subtareas</h3>

                <?php if ($userRole !== 'viewer'): ?>
                    <form method="POST" style="margin-bottom:15px;">
                        <input type="hidden" name="task_id" value="<?= $id ?>" class="input-subtareas">
                        <input type="text" name="subtitulo" placeholder="Nueva subtarea" required class="input-subtareas">
                        <button type="submit" name="crear_subtarea" class="crear-subtarea-icon"><img src="../assets/css/img/iconsmas.png" alt=""></button>
                    </form>
                <?php endif; ?>

                <ul style="list-style:none; padding:0;">
                    <?php if (!empty($subtareas)): ?>
                        <?php foreach ($subtareas as $s): ?>
                            <li style="margin:10px 0; padding:10px; border-bottom:1px solid #ccc;">
                                <!-- Checkbox para cambiar estado de subtarea -->
                                <input type="checkbox" class="checkbox-subtarea"
                                    onclick="location.href='view_task.php?id=<?= $task['id'] ?>&toggle_subtarea=<?= $s['id'] ?>'"
                                    <?= $s['status'] === 'done' ? 'checked' : '' ?>
                                    <?= $userRole === 'viewer' ? 'disabled' : '' ?>>

                                <?= htmlspecialchars($s['title']) ?>

                                <?php if ($s['status'] === 'done'): ?>
                                    <span style="color:green;">✔</span>
                                <?php endif; ?>
                                    
                                <?php if ($userRole !== 'viewer'): ?>
                                    <a href="view_task.php?id=<?= $task['id'] ?>&eliminar_subtarea=<?= $s['id'] ?>"
                                       onclick="return confirm('¿Eliminar esta subtarea?')"><img src="../assets/css/img/iconsEliminar.png" alt="" class="icons-eliminar-subtarea"></a>
                                <?php endif; ?>
                            </li>
                        <?php endforeach; ?>
                        <?php else: ?>
                            <li>No hay subtareas.</li>
                        <?php endif; ?>
                </ul>
            </div>
            
            <div class="task-section">
                <h3 class="detalles-tareas-titulos"> Comentarios</h3>
                <ul style="list-style:none; padding:0;">
                    <?php if (!empty($comentarios)): ?>
                        <?php foreach ($comentarios as $c): ?>
                            <li>
                                <strong class="descripcion-tarea"><?= htmlspecialchars($c['name']) ?>:</strong> 
                                <?= htmlspecialchars($c['content']) ?> 
                                <em>(<?= $c['created_at'] ?>)</em>

                                <?php if ($userRole === 'owner' || $c['user_id'] == $_SESSION['user_id']): ?>
                                    <a href="delete_comment.php?id=<?= $c['id'] ?>" 
                                    onclick="return confirm('¿Eliminar comentario?')">
                                        <img src="../assets/css/img/iconsEliminar.png" alt="Eliminar" class="icons-eliminar-subtarea">
                                    </a>
                                <?php endif; ?>
                            </li>
                        <?php endforeach; ?>
                    <?php else: ?>
                         <li>No hay comentarios.</li>
                    <?php endif; ?>
                </ul>
                <?php if ($userRole !== 'viewer'): ?>
                    <form method="POST">
                        <input type="text" name="contenido" class="form-input" placeholder="Escribe un comentario" required>
                        <button type="submit" name="comentar" class="login-btn">Comentar</button>
                    </form>
                <?php else: ?>
                    <p><em>🔒 No tienes permisos para comentar.</em></p>
                <?php endif; ?>    
            </div>

            <div class="task-section">
                <h3 class="detalles-tareas-titulos"> Archivos Adjuntos</h3>
                <ul style="list-style:none; padding:0;">
                <?php if ($adjuntos) {
                    foreach ($adjuntos as $adj): ?>
                        <li>
                            <a href="download_attachment.php?id=<?= $adj['id'] ?>">
                                <?= htmlspecialchars($adj['filename']) ?>
                            </a>

                            <?php if ($userRole !== 'viewer'): ?>
                                <a href="delete_attachment.php?id=<?= $adj['id'] ?>" 
                                onclick="return confirm('¿Eliminar adjunto?')">❌</a>
                            <?php endif; ?>
                        
                        </li>
                    <?php endforeach;
                } else {
                    echo "<p>No hay adjuntos.</p>";
                    echo "<br>";
                } ?>
                </ul>

                <?php if ($userRole !== 'viewer'): ?>
                    <form method="POST" enctype="multipart/form-data" action="upload_attachment.php">
                        <input type="hidden" name="task_id" value="<?= $task['id'] ?>">
                        <input type="file" name="archivo" required>
                        <button type="submit">Subir Archivo</button>
                    </form>
                <?php else: ?>
                    <p><em>🔒 No tienes permisos para subir archivos.</em></p>
                <?php endif; ?>
            </div>

                <!-- Colaboradores -->
                <h3 class="logo-text" style="margin-top:20px;">👥 Colaboradores</h3>
                <ul>
                    <?php foreach ($colaboradores as $col): ?>
                        <li><?= htmlspecialchars($col['name']) ?> (<?= htmlspecialchars($col['role']) ?>)</li>
                    <?php endforeach; ?>
                </ul>

                <!-- Formulario para invitar colaborador -->
                <?php if ($userRole === 'owner'): ?>
                    <form method="POST" action="share_task.php">
                        <input type="hidden" name="task_id" value="<?= $task['id'] ?>">
                        <label>Usuario:</label>
                        <select name="user_id">
                            <?php
                            $users = $pdo->query("SELECT id, name FROM users")->fetchAll(PDO::FETCH_ASSOC);
                            foreach ($users as $u):
                                if ($u['id'] == $_SESSION['user_id']) continue;
                            ?>
                                <option value="<?= $u['id'] ?>"><?= htmlspecialchars($u['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <label>Rol:</label>
                        <select name="role">
                            <option value="editor">Editor</option>
                            <option value="viewer">Viewer</option>
                        </select>
                        <button type="submit">➕ Agregar Colaborador</button>
                    </form>
                <?php endif; ?>



        </div>
    </div>
</body>
</html>