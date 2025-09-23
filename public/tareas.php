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

    if ($fecha_venc && $fecha_venc < date('Y-m-d')) {
        die("⚠ La fecha de vencimiento no puede ser anterior a hoy.");
    }

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
    // Validar permiso para editar
    $stmt = $pdo->prepare("SELECT creator_id FROM tasks WHERE id = ?");
    $stmt->execute([$id]);
    $task = $stmt->fetch(PDO::FETCH_ASSOC);

    $stmt = $pdo->prepare("SELECT role FROM task_users WHERE task_id = ? AND user_id = ?");
    $stmt->execute([$id, $_SESSION['user_id']]);
    $user_role = $stmt->fetchColumn();

    if (!$task || ($task['creator_id'] != $_SESSION['user_id'] && $user_role !== 'editor')) {
        // No permiso
        die("No tienes permiso para editar esta tarea.");
    }

    $titulo = trim($_POST['titulo']);
    $descripcion = trim($_POST['descripcion']);
    $proyecto_id = $_POST['proyecto_id'] ?: null;
    $assignee_id = $_POST['assignee_id'] ?: null;
    $prioridad = $_POST['prioridad'];
    $estado = $_POST['estado'];
    $fecha_venc = $_POST['due_date'] ?: null;

    $stmt = $pdo->prepare("UPDATE tasks 
            SET title=?, description=?, project_id=?, assignee_id=?, priority=?, status=?, due_date=? 
            WHERE id=?");
    $stmt->execute([$titulo, $descripcion, $proyecto_id, $assignee_id, $prioridad, $estado, $fecha_venc, $id]);

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
    // Solo el creador puede eliminar
    $stmt = $pdo->prepare("SELECT creator_id FROM tasks WHERE id = ?");
    $stmt->execute([$id]);
    $task = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$task || $task['creator_id'] != $_SESSION['user_id']) {
        die("No tienes permiso para eliminar esta tarea.");
    }

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
           (SELECT COUNT(*) FROM subtasks s WHERE s.task_id = t.id AND s.status = 'done') AS subtareas_completadas,
           -- Obtener rol del usuario para esta tarea:
           (SELECT role FROM task_users WHERE task_id = t.id AND user_id = ?) AS user_role
    FROM tasks t
    LEFT JOIN projects p ON t.project_id = p.id
    LEFT JOIN users u ON t.assignee_id = u.id
    WHERE t.creator_id = ? 
       OR t.assignee_id = ? 
       OR t.id IN (SELECT task_id FROM task_users WHERE user_id = ?)
");
$stmt->execute([$_SESSION['user_id'], $_SESSION['user_id'], $_SESSION['user_id'], $_SESSION['user_id']]);
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
    <div class="right-section" id="tareas-container">
    <h2 class="titulo-interfaz-derecha">Mis Tareas</h2>
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
                <input type="date" name="due_date" class="form-input-tareas" min="<?= date('Y-m-d') ?>">
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

                
                    <!-- <?php if ($t['total_subtareas'] > 0): ?>
                        <?php 
                            $color = ($t['subtareas_completadas'] == $t['total_subtareas']) ? "green" : "red";
                        ?>
                        <span style="color:<?= $color ?>; font-size:14px;">
                            (<?= $t['subtareas_completadas'] ?>/<?= $t['total_subtareas'] ?> subtareas)
                        </span>
                    <?php endif; ?> -->

                    <!-- Acciones -->
                    <a href="view_task.php?id=<?= $t['id'] ?>"><img src="../assets/css/img/ver-detalles.gif" alt="Ver detalles"></a>

                    <?php
                        $user_role = $t['user_role']; // rol colaborador
                        $is_creator = $t['creator_id'] == $_SESSION['user_id'];
                    ?>

                    <?php if ($is_creator || $user_role === 'editor'): ?>
                        <a href="tareas.php?editar=<?= $t['id'] ?>"><img src="../assets/css/img/iconEditar.png" alt="Editar" class="crud-proyectos"></a>
                    <?php endif; ?> 

                    <?php if ($is_creator): ?>
                        <a href="tareas.php?eliminar=<?= $t['id'] ?>" onclick="return confirm('¿Eliminar tarea?')"><img src="../assets/css/img/iconsEliminar.png" alt="Eliminar" class="crud-proyectos"></a>
                    <?php endif; ?>  

                 </li>
            <?php endforeach; ?>
        </ul>
        </div>

       <!-- Formulario de edición -->
        <?php 
        if (isset($_GET['editar'])) {
            $id = $_GET['editar'];

            // Obtener la tarea
            $stmt = $pdo->prepare("SELECT * FROM tasks WHERE id = ?");
            $stmt->execute([$id]);
            $edit = $stmt->fetch(PDO::FETCH_ASSOC);

            // Validar permisos (creador o editor)
            $stmt = $pdo->prepare("SELECT role FROM task_users WHERE task_id = ? AND user_id = ?");
            $stmt->execute([$id, $_SESSION['user_id']]);
            $user_role = $stmt->fetchColumn();

            if ($edit && ($edit['creator_id'] == $_SESSION['user_id'] || $user_role === 'editor')) {
                // Obtener etiquetas
                $stmt = $pdo->prepare("SELECT tag_id FROM task_tags WHERE task_id = ?");
                $stmt->execute([$id]);
                $tags_asignadas = $stmt->fetchAll(PDO::FETCH_COLUMN);
        ?>
<!-- Modal Overlay -->
<div class="modal-overlay-editar-tarea activo" id="modalEditarTarea">
    <div class="contenedor-modal-editar-tarea">
        <!-- Modal Header -->
        <div class="header-modal-editar-tarea">
            <h3 class="titulo-modal-editar-tarea">✏️ Editar Tarea</h3>
            <button class="btn-cerrar-modal-editar-tarea" onclick="cerrarModalEditarTarea()">&times;</button>
        </div>

        <!-- Modal Body -->
        <div class="body-modal-editar-tarea">
            <form method="POST" class="formulario-editar-tarea">
                <input type="hidden" name="id" value="<?= $edit['id'] ?>">
                
                <!-- Título -->
                <div class="grupo-input-editar-tarea">
                    <label class="label-editar-tarea" for="titulo">Título de la tarea</label>
                    <input type="text" name="titulo" id="titulo" class="input-editar-tarea" value="<?= htmlspecialchars($edit['title'] ?? '') ?>" required>
                </div>

                <!-- Descripción -->
                <div class="grupo-input-editar-tarea">
                    <label class="label-editar-tarea" for="descripcion">Descripción</label>
                    <textarea name="descripcion" id="descripcion" class="textarea-editar-tarea"><?= htmlspecialchars($edit['description'] ?? '') ?></textarea>
                </div>

                <!-- Row con Proyecto y Asignar a -->
                <div class="fila-inputs-editar-tarea">
                    <div class="grupo-input-editar-tarea">
                        <label class="label-editar-tarea">Proyecto</label>
                        <select name="proyecto_id" class="select-editar-tarea">
                            <option value="">(Sin proyecto)</option>
                            <?php foreach ($proyectos as $p): ?>
                                <option value="<?= $p['id'] ?>" <?= $p['id'] == $edit['project_id'] ? "selected" : "" ?>>
                                    <?= htmlspecialchars($p['name'] ?? '') ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <!-- Asignado a -->
                    <div class="grupo-input-editar-tarea">
                        <label class="label-editar-tarea">Asignar a</label>
                        <select name="assignee_id" class="select-editar-tarea">
                            <option value="">(Sin asignar)</option>
                            <?php foreach ($usuarios as $u): ?>
                                <option value="<?= $u['id'] ?>" <?= ($edit['assignee_id'] == $u['id']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($u['name'] ?? '') ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <!-- Etiquetas -->
                <div class="grupo-input-editar-tarea">
                    <label class="label-editar-tarea">Etiquetas</label>
                    <div class="grupo-checkboxes-editar-tarea">
                        <?php foreach ($etiquetas as $tag): ?>
                            <div class="item-checkbox-editar-tarea">
                                <input type="checkbox" name="tags[]" value="<?= $tag['id'] ?>" class="checkbox-editar-tarea" 
                                       <?= in_array($tag['id'], $tags_asignadas ?? []) ? 'checked' : '' ?>>
                                <label class="label-checkbox-editar-tarea"><?= htmlspecialchars($tag['name'] ?? '') ?></label>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Row con Prioridad y Estado -->
                <div class="fila-inputs-editar-tarea">
                    <div class="grupo-input-editar-tarea">
                        <label class="label-editar-tarea">Prioridad</label>
                        <select name="prioridad" class="select-editar-tarea">
                            <option value="low" <?= ($edit['priority']=="low"?"selected":"") ?>>🟢 Baja</option>
                            <option value="medium" <?= ($edit['priority']=="medium"?"selected":"") ?>>🟡 Media</option>
                            <option value="high" <?= ($edit['priority']=="high"?"selected":"") ?>>🟠 Alta</option>
                            <option value="urgent" <?= ($edit['priority']=="urgent"?"selected":"") ?>>🔴 Urgente</option>
                        </select>
                    </div>

                    <div class="grupo-input-editar-tarea">
                        <label class="label-editar-tarea">Estado</label>
                        <select name="estado" class="select-editar-tarea">
                            <option value="todo" <?= ($edit['status']=="todo"?"selected":"") ?>>📋 Por hacer</option>
                            <option value="in_progress" <?= ($edit['status']=="in_progress"?"selected":"") ?>>⚡ En progreso</option>
                            <option value="done" <?= ($edit['status']=="done"?"selected":"") ?>>✅ Hecha</option>
                        </select>
                    </div>
                </div>

                <!-- Fecha de vencimiento -->
                <div class="grupo-input-editar-tarea">
                    <label class="label-editar-tarea">Fecha de vencimiento</label>
                    <input type="date" name="due_date" class="input-editar-tarea" value="<?= htmlspecialchars($edit['due_date'] ?? '') ?>">
                </div>

                <!-- Buttons -->
                <div class="grupo-botones-editar-tarea">
                    <button type="button" class="btn-cancelar-editar-tarea" onclick="cerrarModalEditarTarea()">Cancelar</button>
                    <button type="submit" name="editar" class="btn-guardar-editar-tarea">💾 Guardar Cambios</button>
                </div>
            </form>
            <?php
                } else {
                    echo "<p style='color:red;'>No tienes permiso para editar esta tarea.</p>";
                }
            }
            ?>
        </div>
    </div>
</div>

<script>
function cerrarModalEditarTarea() {
    // Redirigir a la página sin el parámetro editar
    window.location.href = window.location.pathname;
}
</script>

</body>
</html>
