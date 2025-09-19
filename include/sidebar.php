<?php
if (!isset($_SESSION)) session_start();
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
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Dashboard - <?= APP_NAME ?></title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>

<body>
    <!-- Sidebar -->
    <div class="sidebar">

        <div class="sidebar-content">
        <div class="user">

            <!-- Foto de perfil -->
            <div style="margin-bottom:15px;">
                <?php if (!empty($usuarioSidebar['avatar'])): ?>
                    <a href="perfil.php"><img src="../<?= htmlspecialchars($usuarioSidebar['avatar']) ?>" 
                        alt="Avatar" 
                        style="width:80px;height:80px;border-radius:50%;object-fit:cover;"></a>
                <?php else: ?>
                    <a href="perfil.php"><img src="../assets/img/default-avatar.png" 
                        alt="Avatar" 
                        style="width:80px;height:80px;border-radius:50%;object-fit:cover;"></a>
                <?php endif; ?>
                <p class="user-name"><?= htmlspecialchars($usuarioSidebar['name']) ?></p>
            </div>
        </div>

        <div class="">
            <!-- Buscador -->
            <div class="sidebar-search">
                <div class="boton-registro-login-left">
                    <form method="GET" class="form-container" style="background:rgba(255,255,255,0.1); padding:10px; border-radius:10px;">
                        <input type="text" name="q" placeholder="Buscar..." class="form-input" value="<?= htmlspecialchars($_GET['q'] ?? '') ?>">

                        <!-- Select principal -->
                        
                        <select id="main-filter" class="form-input">
                            <option value="">Filtro...</option>
                            <option value="proyecto">Proyecto</option>
                            <option value="responsable">Responsable</option>
                            <option value="etiqueta">Etiqueta</option>
                            <option value="estado">Estado</option>
                            <option value="prioridad">Prioridad</option>
                            <option value="fecha">Fecha vencimiento</option>
                        </select>

                        <!-- Subfiltros ocultos -->
                        <div id="filter-proyecto" class="sub-filter" style="display:none;">
                            <label>Proyecto:</label>
                            <select name="proyecto_id" class="form-input">
                                <option value="">Todos los proyectos</option>
                                <?php foreach ($proyectos as $p): ?>
                                    <option value="<?= $p['id'] ?>" <?= ($_GET['proyecto_id'] ?? '') == $p['id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($p['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div id="filter-responsable" class="sub-filter" style="display:none;">
                            <label>Responsable:</label>
                            <select name="assignee_id" class="form-input">
                                <option value="">Todos los usuarios</option>
                                <?php foreach ($usuarios as $u): ?>
                                    <option value="<?= $u['id'] ?>" <?= ($_GET['assignee_id'] ?? '') == $u['id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($u['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div id="filter-etiqueta" class="sub-filter" style="display:none;">
                            <label>Etiqueta:</label>
                            <select name="tag_id" class="form-input">
                                <option value="">Todas las etiquetas</option>
                                <?php foreach ($etiquetas as $tag): ?>
                                    <option value="<?= $tag['id'] ?>" <?= ($_GET['tag_id'] ?? '') == $tag['id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($tag['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div id="filter-estado" class="sub-filter" style="display:none;">
                            <label>Estado:</label>
                            <select name="estado" class="form-input">
                                <option value="">Todos los estados</option>
                                <option value="todo" <?= ($_GET['estado'] ?? '') == "todo" ? "selected" : "" ?>>Por hacer</option>
                                <option value="in_progress" <?= ($_GET['estado'] ?? '') == "in_progress" ? "selected" : "" ?>>En progreso</option>
                                <option value="done" <?= ($_GET['estado'] ?? '') == "done" ? "selected" : "" ?>>Hecha</option>
                                <option value="archived" <?= ($_GET['estado'] ?? '') == "archived" ? "selected" : "" ?>>Archivada</option>
                            </select>
                        </div>

                        <div id="filter-prioridad" class="sub-filter" style="display:none;">
                            <label>Prioridad:</label>
                            <select name="prioridad" class="form-input">
                                <option value="">Todas las prioridades</option>
                                <option value="low" <?= ($_GET['prioridad'] ?? '') == "low" ? "selected" : "" ?>>Baja</option>
                                <option value="medium" <?= ($_GET['prioridad'] ?? '') == "medium" ? "selected" : "" ?>>Media</option>
                                <option value="high" <?= ($_GET['prioridad'] ?? '') == "high" ? "selected" : "" ?>>Alta</option>
                                <option value="urgent" <?= ($_GET['prioridad'] ?? '') == "urgent" ? "selected" : "" ?>>Urgente</option>
                            </select>
                        </div>

                        <div id="filter-fecha" class="sub-filter" style="display:none;">
                            <label>Fecha vencimiento:</label>
                            <input type="date" name="fecha_desde" class="form-input" value="<?= htmlspecialchars($_GET['fecha_desde'] ?? '') ?>">
                            <input type="date" name="fecha_hasta" class="form-input" value="<?= htmlspecialchars($_GET['fecha_hasta'] ?? '') ?>">
                        </div>

                        <button type="submit" class="logout-btn" style="margin-top:10px;">Filtrar</button>
                        <a href="dashboard.php" class="logout-btn" style="margin-top:10px;">Limpiar</a>
                    </form>

                </div>
            </div>

        </div>

        <nav>
            <a href="proyectos.php" class="nav-item">
                <img src="../assets/css/img/iconsproyecto.png" alt="" class="icons-sidebar"> Proyectos
            </a>
            <a href="tareas.php" class="nav-item">
               <img src="../assets/css/img/iconsTareas.png" alt="" class="icons-sidebar"> Tareas
            </a>
            <a href="tags.php" class="nav-item">
                <img src="../assets/css/img/iconstags.png" alt="" class="icons-sidebar">Etiquetas
            </a>
        </nav>

        <a href="logout.php" class="logout-btn"> Cerrar sesión</a>
    </div>
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
