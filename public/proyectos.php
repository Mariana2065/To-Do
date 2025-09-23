<?php
require_once __DIR__ . '/../init.php';
require_login();

// CREAR
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['crear'])) {
    $nombre = trim($_POST['nombre']);
    $descripcion = trim($_POST['descripcion']);
    if (!empty($nombre)) {
        $stmt = $pdo->prepare("INSERT INTO projects (name, description, creator_id) VALUES (?, ?, ?)");
        $stmt->execute([$nombre, $descripcion, $_SESSION['user_id']]);
    }
    header("Location: proyectos.php");
    exit;
}

// EDITAR
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['editar'])) {
    $id = $_POST['id'];
    $nombre = trim($_POST['nombre']);
    $descripcion = trim($_POST['descripcion']);
    $stmt = $pdo->prepare("UPDATE projects SET name = ?, description = ? WHERE id = ? AND creator_id = ?");
    $stmt->execute([$nombre, $descripcion, $id, $_SESSION['user_id']]);
    header("Location: proyectos.php");
    exit;
}

// ELIMINAR
if (isset($_GET['eliminar'])) {
    $id = $_GET['eliminar'];
    $stmt = $pdo->prepare("DELETE FROM projects WHERE id = ? AND creator_id = ?");
    $stmt->execute([$id, $_SESSION['user_id']]);
    header("Location: proyectos.php");
    exit;
}

// LISTAR
$stmt = $pdo->prepare("SELECT * FROM projects WHERE creator_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$proyectos = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Obtener datos del usuario
$stmt = $pdo->prepare("SELECT name FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Proyectos - <?= APP_NAME ?></title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>

  <?php include_once '../include/sidebar.php'; ?>
    <!-- Main content -->
    <div class="main-proyectos">
        <div class="page-header">
            <h2 class="titulos-main"><img src="../assets/css/img/iconscarpeta.png" alt="" class="icons-sidebar"> Proyectos</h2>
        </div>

        <!-- Lista de proyectos -->
        <div class="projects-list">
            <?php foreach ($proyectos as $p): ?>
                <div class="project-item">
                    <div class="project-content">
                        <div class="project-icon"><img src="../assets/css/img/destello.png" alt="" class="icons-lista-proyectos">
                         <h3><?= htmlspecialchars($p['name']) ?></h3>
                    </div>
                        <div class="project-info">
                            <?php if (!empty($p['description'])): ?>
                                <p class="descripcion-proyecto"><?= htmlspecialchars($p['description']) ?></p>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="project-actions">
                        <button class="btn-crud-proyectos" onclick="toggleStar(this)"><img src="../assets/css/img/iconsEstrella.png" alt="" class="crud-proyectos"></button>
                        <button class="btn-crud-proyectos" onclick="editProject(<?= $p['id'] ?>, '<?= htmlspecialchars($p['name'], ENT_QUOTES) ?>', '<?= htmlspecialchars($p['description'] ?? '', ENT_QUOTES) ?>')">
                            <img src="../assets/css/img/iconEditar.png" alt="" class="crud-proyectos">
                        </button>
                        <button class="btn-crud-proyectos" onclick="deleteProject(<?= $p['id'] ?>)">
                            <img src="../assets/css/img/iconsEliminar.png" alt="" class="crud-proyectos">
                        </button>
                    </div>
                </div>
            <?php endforeach; ?>
                                    <span class="project-star-line"></span>

        </div>

       
<!-- Botón agregar proyecto -->
<div class="add-project-btn" onclick="openCreateModal()">
    <div class="add-project-icon">
        <img src="../assets/css/img/iconsmas.png" alt="">
    </div>
    <span>Agregar proyecto</span>
</div>

<!-- Modal para crear proyecto -->
<div id="modalCrearProyecto" class="modal-crear-proyecto">
    <div class="contenido-modal-proyecto">
        <div class="header-modal-proyecto">
            <h3 class="titulo-modal-proyecto"> Crear Nuevo Proyecto</h3>
            <button class="btn-cerrar-modal-proyecto" onclick="cerrarModalCrearProyecto()">&times;</button>
        </div>
        <form method="POST" class="formulario-crear-proyecto">
            <div class="grupo-input-proyecto">
                <input type="text" name="nombre" class="input-proyecto" placeholder="Nombre del proyecto" required>
            </div>
            <div class="grupo-input-proyecto">
                <textarea name="descripcion" class="textarea-proyecto" placeholder="Descripción (opcional)"></textarea>
            </div>
            <div class="acciones-formulario-proyecto">
                <button type="button" class="btn-proyecto btn-secundario-proyecto" onclick="cerrarModalCrearProyecto()">Cancelar</button>
                <button type="submit" name="crear" class="btn-proyecto btn-primario-proyecto">Crear Proyecto</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal para editar proyecto -->
<div id="modalEditarProyecto" class="modal-editar-proyecto">
    <div class="contenido-modal-proyecto">
        <div class="header-modal-proyecto">
            <h3 class="titulo-modal-proyecto">Editar Proyecto</h3>
            <button class="btn-cerrar-modal-proyecto" onclick="cerrarModalEditarProyecto()">&times;</button>
        </div>
        <form method="POST" class="formulario-editar-proyecto">
            <input type="hidden" name="id" id="editarIdProyecto">
            <div class="grupo-input-proyecto">
                <input type="text" name="nombre" id="editarNombreProyecto" class="input-proyecto" required>
            </div>
            <div class="grupo-input-proyecto">
                <textarea name="descripcion" id="editarDescripcionProyecto" class="textarea-proyecto" placeholder="Descripción (opcional)"></textarea>
            </div>
            <div class="acciones-formulario-proyecto">
                <button type="button" class="btn-proyecto btn-secundario-proyecto" onclick="cerrarModalEditarProyecto()">Cancelar</button>
                <button type="submit" name="editar" class="btn-proyecto btn-primario-proyecto"> Guardar Cambios</button>
            </div>
        </form>
    </div>
</div>
    <script>
    function openCreateModal() {
        document.getElementById('modalCrearProyecto').classList.add('show');
    }

    function cerrarModalCrearProyecto() {
        document.getElementById('modalCrearProyecto').classList.remove('show');
    }

    function cerrarModalEditarProyecto() {
        document.getElementById('modalEditarProyecto').classList.remove('show');
    }

    function editProject(id, nombre, descripcion) {
        document.getElementById('editarIdProyecto').value = id;
        document.getElementById('editarNombreProyecto').value = nombre;
        document.getElementById('editarDescripcionProyecto').value = descripcion;
        document.getElementById('modalEditarProyecto').classList.add('show');
    }

    function deleteProject(id) {
        if (confirm('🗑️ ¿Estás seguro de que deseas eliminar este proyecto?')) {
            window.location.href = 'proyectos.php?eliminar=' + id;
        }
    }

    function toggleStar(element) {
        element.classList.toggle('active');
    }

    // Cerrar modales al hacer click fuera
    window.onclick = function(event) {
        if (event.target.classList.contains('modal-crear-proyecto')) {
            cerrarModalCrearProyecto();
        }
        if (event.target.classList.contains('modal-editar-proyecto')) {
            cerrarModalEditarProyecto();
        }
    }

    // Cerrar modales con tecla Escape
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            cerrarModalCrearProyecto();
            cerrarModalEditarProyecto();
        }
    });
</script>
</body>
</html>