<?php
require_once __DIR__ . '/../init.php';
require_login();

// ==========================
// 1. OBTENER PROYECTOS DEL USUARIO
// ==========================
$stmt = $pdo->prepare("SELECT id, name FROM projects WHERE creator_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$proyectos = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ==========================
// 2. OBTENER ETIQUETAS DISPONIBLES
// ==========================
$stmt = $pdo->prepare("SELECT id, name FROM tags WHERE creator_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$etiquetas = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ==========================
// 3. OBTENER USUARIOS PARA ASIGNAR
// ==========================
$stmt = $pdo->query("SELECT id, name FROM users");
$usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ==========================
// 3. CREAR UNA NUEVA TAREA
// ==========================
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['crear'])) {
    $titulo = trim($_POST['titulo']);
    $descripcion = trim($_POST['descripcion']);
    $proyecto_id = $_POST['proyecto_id'] ?: null;
    $assignee_id = $_POST['assignee_id'] ?: null;
    $prioridad = $_POST['prioridad'];
    $estado = $_POST['estado'];
    $fecha_venc = $_POST['due_date'] ?: null;

    if (!empty($titulo)) {
        $stmt = $pdo->prepare("INSERT INTO tasks (title, description, creator_id, project_id, assignee_id, priority, status, due_date) 
                               VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$titulo, $descripcion, $_SESSION['user_id'], $proyecto_id, $assignee_id, $prioridad, $estado, $fecha_venc]);
        $task_id = $pdo->lastInsertId();

        // Asociar etiquetas
        if (!empty($_POST['tags'])) {
            foreach ($_POST['tags'] as $tag_id) {
                $pdo->prepare("INSERT INTO task_tags (task_id, tag_id) VALUES (?, ?)")->execute([$task_id, $tag_id]);
            }
        }
    }
    header("Location: tareas.php");
    exit;
}

// ==========================
// 4. EDITAR UNA TAREA
// ==========================
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['editar'])) {
    $id = $_POST['id'];
    $titulo = trim($_POST['titulo']);
    $descripcion = trim($_POST['descripcion']);
    $proyecto_id = $_POST['proyecto_id'] ?: null;
    $assignee_id = $_POST['assignee_id'] ?: null;
    $prioridad = $_POST['prioridad'];
    $estado = $_POST['estado'];
    $fecha_venc = $_POST['due_date'] ?: null;

    $stmt = $pdo->prepare("UPDATE tasks 
                           SET title=?, description=?, project_id=?, assignee_id=?, priority=?, status=?, due_date=? 
                           WHERE id=? AND creator_id=?");
    $stmt->execute([$titulo, $descripcion, $proyecto_id, $assignee_id, $prioridad, $estado, $fecha_venc, $id, $_SESSION['user_id']]);

    // Actualizar etiquetas
    $pdo->prepare("DELETE FROM task_tags WHERE task_id=?")->execute([$id]);
    if (!empty($_POST['tags'])) {
        foreach ($_POST['tags'] as $tag_id) {
            $pdo->prepare("INSERT INTO task_tags (task_id, tag_id) VALUES (?, ?)")->execute([$id, $tag_id]);
        }
    }

    header("Location: tareas.php");
    exit;
}

// ==========================
// 5. ELIMINAR TAREA
// ==========================
if (isset($_GET['eliminar'])) {
    $id = $_GET['eliminar'];
    $stmt = $pdo->prepare("DELETE FROM tasks WHERE id = ? AND creator_id = ?");
    $stmt->execute([$id, $_SESSION['user_id']]);
    header("Location: tareas.php");
    exit;
}

// ==========================
// 6. LISTAR TAREAS
// ==========================
$stmt = $pdo->prepare("
    SELECT t.*, p.name AS proyecto, u.name AS asignado,
           (SELECT GROUP_CONCAT(tags.name SEPARATOR ', ')
            FROM task_tags 
            JOIN tags ON tags.id = task_tags.tag_id
            WHERE task_tags.task_id = t.id) AS etiquetas,
           (SELECT COUNT(*) FROM subtasks s WHERE s.task_id = t.id) AS total_subtareas,
           (SELECT COUNT(*) FROM subtasks s WHERE s.task_id = t.id AND s.status = 'done') AS subtareas_completadas
    FROM tasks t
    LEFT JOIN projects p ON t.project_id = p.id
    LEFT JOIN users u ON t.assignee_id = u.id
    WHERE t.creator_id = ?

");
$stmt->execute([$_SESSION['user_id']]);
$tareas = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Tareas - <?= APP_NAME ?></title>
    <!-- <link rel="stylesheet" href="../assets/css/style.css"> -->
</head>
<body>

    <?php include_once '../include/sidebar.php'; ?>


    <!-- Contenido -->
    <h2 class="titulo-interfaz-derecha">Mis Tareas</h2>
    <div class="right-section" id="tareas-container">

        <!-- Crear tarea -->

         <div class="creartareas">
        <form method="POST" class="form-container-tareas">
            <div class="form-group-tareas">
                <input type="text" name="titulo" class="form-input-tareas" placeholder="Título de la tarea" required>
            </div>
            <div class="form-group-tareas">
                <textarea name="descripcion" class="form-input-tareas" placeholder="Descripción"></textarea>
            </div>

            <!-- proyecto -->
            <div class="form-group-tareas">
                <label>Proyecto:</label>
                <br>
                <select name="proyecto_id" class="form-input-tareas">
                    <option value="">(Sin proyecto)</option>
                    <?php foreach ($proyectos as $p): ?>
                        <option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['name'] ?? '') ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Asignar usuario -->
            <div class="form-group-tareas">
                <label>Asignar a:</label>
                <br>
                <select name="assignee_id" class="form-input-tareas">
                    <option value="">(Sin asignar)</option>
                    <?php foreach ($usuarios as $u): ?>
                        <option value="<?= $u['id'] ?>"><?= htmlspecialchars($u['name'] ?? '') ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <!-- Etiquetas -->
            <div class="form-group-tareas">
                <label>Etiquetas:</label><br>
                <?php foreach ($etiquetas as $tag): ?>
                    <label>
                        <input type="checkbox" name="tags[]" value="<?= $tag['id'] ?>"> 
                        <?= htmlspecialchars($tag['name'] ?? '') ?>
                    </label>
                    <br>
                <?php endforeach; ?>
            </div>

            <!-- Prioridad -->
            <div class="form-group-tareas">
                <label>Prioridad:</label>
                <br>
                <select name="prioridad" class="form-input-tareas">
                    <option value="low">Baja</option>
                    <option value="medium" selected>Media</option>
                    <option value="high">Alta</option>
                    <option value="urgent">Urgente</option>
                </select>
            </div>

            <!-- Estado -->
            <div class="form-group-tareas">
                <label>Estado:</label>
                <br>
                <select name="estado" class="form-input-tareas">
                    <option value="todo">Por hacer</option>
                    <option value="in_progress">En progreso</option>
                    <option value="done">Hecha</option>
                </select>
            </div>

            <!-- Fecha de vencimiento -->
            <div class="form-group-tareas">
                <label>Fecha de vencimiento:</label>
                <br>
                <input type="date" name="due_date" class="form-input-tareas">
            </div>

            <button type="submit" name="crear" class="btn-crear-tarea">Crear Tarea</button>
        </form>
        </div>
        <span class="linea-separacion"></span>

        <!-- Lista de tareas -->
         <div class="listatare">
        <h3 class="titulo-derecha-tareas" style="margin-top:20px;">Lista de Tareas</h3>
        <ul style="list-style:none; padding:0;">
            <?php foreach ($tareas as $t): ?>
                <li class="lista-tareas-derecha">
                    <strong><?= htmlspecialchars($t['title'] ?? '') ?></strong>

                
                    <?php if ($t['total_subtareas'] > 0): ?>
                        <?php 
                            $color = ($t['subtareas_completadas'] == $t['total_subtareas']) ? "green" : "red";
                        ?>
                        <span style="color:<?= $color ?>; font-size:14px;">
                            (<?= $t['subtareas_completadas'] ?>/<?= $t['total_subtareas'] ?> subtareas)
                        </span>
                    <?php endif; ?>

                    <!-- Acciones -->
                    <a href="view_task.php?id=<?= $t['id'] ?>" ><img src="../assets/css/img/ver-detalles.gif" alt=""></a>
                    <a href="tareas.php?editar=<?= $t['id'] ?>" ><img src="../assets/css/img/iconEditar.png" alt="" class="crud-proyectos"></a>
                    <a href="tareas.php?eliminar=<?= $t['id'] ?>"  onclick="return confirm('¿Eliminar tarea?')"><img src="../assets/css/img/iconsEliminar.png" alt="" class="crud-proyectos"></a>
                </li>
            <?php endforeach; ?>
        </ul>
        </div>

        <!-- Formulario de edición -->
        <?php if (isset($_GET['editar'])): 
            $id = $_GET['editar'];
            $stmt = $pdo->prepare("SELECT * FROM tasks WHERE id = ? AND creator_id = ?");
            $stmt->execute([$id, $_SESSION['user_id']]);
            $edit = $stmt->fetch(PDO::FETCH_ASSOC);

            $stmt = $pdo->prepare("SELECT tag_id FROM task_tags WHERE task_id = ?");
            $stmt->execute([$id]);
            $tags_asignadas = $stmt->fetchAll(PDO::FETCH_COLUMN);

            if ($edit):
        ?>
        <h3 class="logo-text" style="margin-top:20px;">✏ Editar Tarea</h3>
        <form method="POST" class="form-container-tareas">
            <input type="hidden" name="id" value="<?= $edit['id'] ?>">
            <div class="form-group">
                <input type="text" name="titulo" class="form-input-tareas" value="<?= htmlspecialchars($edit['title'] ?? '') ?>" required>
            </div>
            <div class="form-group">
                <textarea name="descripcion" class="form-input-tareas"><?= htmlspecialchars($edit['description'] ?? '') ?></textarea>
            </div>

            <!-- Editar proyecto -->
            <label>Proyecto:</label>
            <br>
            <select name="proyecto_id" class="form-input">
                <option value="">(Sin proyecto)</option>
                <?php foreach ($proyectos as $p): ?>
                    <option value="<?= $p['id'] ?>" <?= $p['id'] == $edit['project_id'] ? "selected" : "" ?>>
                        <?= htmlspecialchars($p['name'] ?? '') ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <!-- Asignar usuario -->
            <div class="form-group">
                <label>Asignar a:</label>
                <br>
                <select name="assignee_id" class="form-input-tareas">
                    <option value="">(Sin asignar)</option>
                    <?php foreach ($usuarios as $u): ?>
                        <option value="<?= $u['id'] ?>" <?= ($edit['assignee_id'] == $u['id']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($u['name'] ?? '') ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Editar etiquetas -->
            <div class="form-group">
                <label>Etiquetas:</label>
                <br>
                <?php foreach ($etiquetas as $tag): ?>
                    <label>
                        <input type="checkbox" name="tags[]" value="<?= $tag['id'] ?>" 
                               <?= in_array($tag['id'], $tags_asignadas ?? []) ? 'checked' : '' ?>>
                        <?= htmlspecialchars($tag['name'] ?? '') ?>
                    </label>
                <?php endforeach; ?>
            </div>

            <!-- Editar prioridad -->
            <label>Prioridad:</label>
            <br>
            <select name="prioridad" class="form-input-tareas">
                <option value="low" <?= ($edit['priority']=="low"?"selected":"") ?>>Baja</option>
                <option value="medium" <?= ($edit['priority']=="medium"?"selected":"") ?>>Media</option>
                <option value="high" <?= ($edit['priority']=="high"?"selected":"") ?>>Alta</option>
                <option value="urgent" <?= ($edit['priority']=="urgent"?"selected":"") ?>>Urgente</option>
            </select>

            <!-- Editar estado -->
            <label>Estado:</label>
            <br>
            <select name="estado" class="form-input-tareas">
                <option value="todo" <?= ($edit['status']=="todo"?"selected":"") ?>>Por hacer</option>
                <option value="in_progress" <?= ($edit['status']=="in_progress"?"selected":"") ?>>En progreso</option>
                <option value="done" <?= ($edit['status']=="done"?"selected":"") ?>>Hecha</option>
                <option value="archived" <?= ($edit['status']=="archived"?"selected":"") ?>>Archivada</option>
            </select>


            <!-- Editar fecha de vencimiento -->
            <label>Fecha de vencimiento:</label>
            <br>
            <input type="date" name="due_date" class="form-input-tareas" value="<?= htmlspecialchars($edit['due_date'] ?? '') ?>">

            <button type="submit" name="editar" class="login-btn">Guardar Cambios</button>
        </form>
        <?php endif; endif; ?>
    </div>
</div>
</body>
</html>
