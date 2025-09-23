<?php
require_once __DIR__ . '/../init.php';
require_login();

// ==========================
// 1. OBTENER PROYECTOS, USUARIOS Y ETIQUETAS
// ==========================
$stmt = $pdo->prepare("SELECT id, name FROM projects WHERE creator_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$proyectos = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt = $pdo->query("SELECT id, name FROM users");
$usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt = $pdo->prepare("SELECT id, name FROM tags WHERE creator_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$etiquetas = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ==========================
// 2. APLICAR FILTROS
// ==========================
$where = ["t.creator_id = ?"];
$params = [$_SESSION['user_id']];

if (!empty($_GET['q'])) {
    $where[] = "(t.title LIKE ? OR t.description LIKE ?)";
    $params[] = "%" . $_GET['q'] . "%";
    $params[] = "%" . $_GET['q'] . "%";
}
if (!empty($_GET['proyecto_id'])) {
    $where[] = "t.project_id = ?";
    $params[] = $_GET['proyecto_id'];
}
if (!empty($_GET['assignee_id'])) {
    $where[] = "t.assignee_id = ?";
    $params[] = $_GET['assignee_id'];
}

if (!empty($_GET['estado'])) {
    $where[] = "t.status = ?";
    $params[] = $_GET['estado'];
}
if (!empty($_GET['prioridad'])) {
    $where[] = "t.priority = ?";
    $params[] = $_GET['prioridad'];
}
if (!empty($_GET['fecha_desde']) && !empty($_GET['fecha_hasta'])) {
    $where[] = "t.due_date BETWEEN ? AND ?";
    $params[] = $_GET['fecha_desde'];
    $params[] = $_GET['fecha_hasta'];
}
if (!empty($_GET['tag_id'])) {
    $where[] = "EXISTS (SELECT 1 FROM task_tags tt WHERE tt.task_id = t.id AND tt.tag_id = ?)";
    $params[] = $_GET['tag_id'];
}


// ==========================
// 3. LISTAR TAREAS FILTRADAS
// ==========================
$sql = "
    SELECT t.*, p.name AS proyecto, u.name AS asignado,
           (SELECT GROUP_CONCAT(tags.name SEPARATOR ', ')
            FROM task_tags 
            JOIN tags ON tags.id = task_tags.tag_id
            WHERE task_tags.task_id = t.id) AS etiquetas
    FROM tasks t
    LEFT JOIN projects p ON t.project_id = p.id
    LEFT JOIN users u ON t.assignee_id = u.id
    WHERE " . implode(" AND ", $where) . "
    ORDER BY t.due_date ASC
";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$tareas = $stmt->fetchAll(PDO::FETCH_ASSOC);


// Obtener datos del usuario actual 
$stmt = $pdo->prepare("SELECT name, email FROM users WHERE id = ?"); 
$stmt->execute([$_SESSION['user_id']]); 
$user = $stmt->fetch(PDO::FETCH_ASSOC); 


$stmt = $pdo->prepare("SELECT id, name, email, avatar FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$usuarioSidebar = $stmt->fetch(PDO::FETCH_ASSOC);


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
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Buscar - <?= APP_NAME ?></title>
    <link rel="stylesheet" href="../assets/css/style.css">

</head>
<body>

    <?php include_once '../include/sidebar.php'; ?>
    <div class="main-buscador">
        <!-- Buscador -->
        <div class="projects-list">
            <div class="boton-registro-login-left">
                <form method="GET" class="form-container-buscador">
                    <input type="text" name="q" placeholder="Buscar..." id="input-buscador" class="form-input" id="input-buscador"  value=  "<?= htmlspecialchars($_GET['q'] ?? '') ?>">

                    <!-- Select principal -->
                    
                    <select id="main-filter" id="input-buscador" >
                        <option value="">Filtro...</option>
                        <option value="proyecto">Proyecto</option>
                        <option value="responsable">Responsable</option>
                        <option value="etiqueta">Etiqueta</option>
                        <option value="estado">Estado</option>
                        <option value="prioridad">Prioridad</option>
                        <option value="fecha">Fecha vencimiento</option>
                    </select>

                    <!-- Subfiltros ocultos -->
                    <div id="filter-proyecto" class="sub-filter" style="display:none; margin-top:15px;">
                        <label class="descripcion-tarea">Proyecto:</label>
                        <br>
                        <select name="proyecto_id" id="input-buscador"id="input-buscador"   class="form-input">
                            <option value="">Todos los proyectos</option>
                            <?php foreach ($proyectos as $p): ?>
                                <option value="<?= $p['id'] ?>" <?= ($_GET['proyecto_id'] ?? '') == $p['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($p['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div id="filter-responsable" class="sub-filter" style="display:none; margin-top:15px;">
                        <label class="descripcion-tarea">Responsable:</label>
                        <br>
                        <select name="assignee_id" id="input-buscador" id="input-buscador" class="form-input">
                            <option value="">Todos los usuarios</option>
                            <?php foreach ($usuarios as $u): ?>
                                <option value="<?= $u['id'] ?>" <?= ($_GET['assignee_id'] ?? '') == $u['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($u['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div id="filter-etiqueta" class="sub-filter" style="display:none; margin-top:15px;">
                        <label class="descripcion-tarea">Etiqueta:</label>
                        <br>
                        <select name="tag_id" id="input-buscador" id="input-buscador"  class="form-input">
                            <option value="">Todas las etiquetas</option>
                            <?php foreach ($etiquetas as $tag): ?>
                                <option value="<?= $tag['id'] ?>" <?= ($_GET['tag_id'] ?? '') == $tag['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($tag['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div id="filter-estado" class="sub-filter" style="display:none; margin-top:15px;">
                        <label class="descripcion-tarea">Estado:</label>
                        <br>
                        <select name="estado"id="input-buscador"  id="input-buscador"  class="form-input">
                            <option value="">Todos los estados</option>
                            <option value="todo" <?= ($_GET['estado'] ?? '') == "todo" ? "selected" : "" ?>>Por hacer</option>
                            <option value="in_progress" <?= ($_GET['estado'] ?? '') == "in_progress" ? "selected" : "" ?>>En progreso</option>
                            <option value="done" <?= ($_GET['estado'] ?? '') == "done" ? "selected" : "" ?>>Hecha</option>
                        </select>
                    </div>

                    <div id="filter-prioridad" class="sub-filter" style="display:none; margin-top:15px;">
                        <label class="descripcion-tarea">Prioridad:</label>
                        <br>
                        <select name="prioridad" id="input-buscador" id="input-buscador" class="form-input">
                            <option value="">Todas las prioridades</option>
                            <option value="low" <?= ($_GET['prioridad'] ?? '') == "low" ? "selected" : "" ?>>Baja</option>
                            <option value="medium" <?= ($_GET['prioridad'] ?? '') == "medium" ? "selected" : "" ?>>Media</option>
                            <option value="high" <?= ($_GET['prioridad'] ?? '') == "high" ? "selected" : "" ?>>Alta</option>
                            <option value="urgent" <?= ($_GET['prioridad'] ?? '') == "urgent" ? "selected" : "" ?>>Urgente</option>
                        </select>
                    </div>

                    <div id="filter-fecha" class="sub-filter" style="display:none; margin-top:15px;">
                        <label class="descripcion-tarea">Fecha vencimiento:</label>
                        <br>
                        <input type="date" name="fecha_desde" id="input-buscador" class="form-input" value="<?= htmlspecialchars($_GET['fecha_desde'] ?? '') ?>">
                        <input type="date" name="fecha_hasta" id="input-buscador" class="form-input" value="<?= htmlspecialchars($_GET['fecha_hasta'] ?? '') ?>">
                    </div>

                    <button type="submit" class="btn-filtarYLimpiar" style="margin-top:25px;">Filtrar</button>
                    <a href="buscar.php" class="btn-filtarYLimpiar" style="margin-top:15px; margin-bottom:25px;">Limpiar</a>
                </form>
            </div>
        </div>  
<div class="seccion-resultados-busqueda">
    <h3 class="titulo-resultados">Resultados de la búsqueda</h3>
    <ul class="lista-resultados-tareas">
        <?php foreach ($tareas as $t): ?>
          <li class="tarjeta-tarea-resultado">
    <div class="encabezado-tarea-resultado">
        <a href="view_task.php?id=<?= $t['id'] ?>" class="enlace-tarea-titulo">
            <?= htmlspecialchars($t['title'] ?? '') ?>
        </a>
       <div class="meta-tarea-resultado">
    <span class="estado-tarea-badge <?= str_replace(' ', '-', strtolower($estados[$t['status'] ?? ''])) ?>">
    <?= $estados[$t['status'] ?? ''] ?>
</span>
    <span class="prioridad-tarea-badge <?= strtolower($prioridades[$t['priority'] ?? '']) ?>">
        <?= $prioridades[$t['priority'] ?? ''] ?>
    </span>
    
    <?php if (!empty($t['proyecto'])): ?>
       <span class="proyecto-tarea-info">
    <img src="../assets/css/img/iconscarpeta.png" alt="Icono de proyecto" class="icono-info">
    <?= htmlspecialchars($t['proyecto']) ?>
</span>
    <?php endif; ?>
</div>
    </div>

    <?php if (!empty($t['asignado'])): ?>
        <div class="detalles-tarea-inferior">
            <span class="asignado-a-usuario">
                <img src="../assets/css/img/iconperfil.png" alt="Icono de perfil" class="icono-info">
                <?= htmlspecialchars($t['asignado']) ?>
            </span>
        </div>
    <?php endif; ?>

    <?php if (!empty($t['etiquetas'])): ?>
        <div class="etiquetas-tarea-listado">
            <img src="../assets/css/img/iconstags.png" alt="Icono de etiquetas" class="icono-info-pequeno">
            <small><?= htmlspecialchars($t['etiquetas']) ?></small>
        </div>
    <?php endif; ?>
</li>
        <?php endforeach; ?>
    </ul>
</div>

    <script>
        const mainFilter = document.getElementById('main-filter');
        const subFilters = document.querySelectorAll('.sub-filter');

        mainFilter.addEventListener('change', function () {
            // Oculta todos los subfiltros
            subFilters.forEach(div => div.style.display = 'none');

            // Muestra el seleccionado
            const selected = mainFilter.value;
            const target = document.getElementById('filter-' + selected);
            if (target) target.style.display = 'block';
        });

        // Mostrar automáticamente si ya hay un filtro seleccionado en GET
        window.addEventListener('DOMContentLoaded', () => {
            const preSelected = [
                { key: 'proyecto_id', value: 'proyecto' },
                { key: 'assignee_id', value: 'responsable' },
                { key: 'tag_id', value: 'etiqueta' },
                { key: 'estado', value: 'estado' },
                { key: 'prioridad', value: 'prioridad' },
                { key: 'fecha_desde', value: 'fecha' }
            ];

            for (const pair of preSelected) {
                if (new URLSearchParams(window.location.search).has(pair.key)) {
                    mainFilter.value = pair.value;
                    document.getElementById('filter-' + pair.value).style.display = 'block';
                    break;
                }
            }
        });
    </script>        
</body>
</html>