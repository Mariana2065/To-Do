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
    <link rel="stylesheet" href="../assets/css/proyectos.css">
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar">
        <div class="logo">
            <img src="../assets/css/img/Logo to-do.png" alt="Logo TO-DO">
            <div class="logo-text">TO DO</div>
        </div>
        
        <div class="user">
            <div class="user-icon">👤</div>
            <div class="user-name"><?= htmlspecialchars($user['name']) ?></div>
        </div>
        
        <div class="search">
            <input type="text" placeholder="Buscar">
        </div>
        
        <nav>
            <a href="dashboard.php">
                <span>🏠</span> Dashboard
            </a>
            <a href="proyectos.php" class="active">
                <span>📁</span> Proyectos
            </a>
            <a href="tareas.php">
                <span>✅</span> Tareas
            </a>
        </nav>
        
        <div style="margin-top: auto;">
            <a href="logout.php" class="btn btn-danger" style="width: 100%; text-align: center; text-decoration: none;">
                🚪 Cerrar Sesión
            </a>
        </div>
    </div>

    <!-- Main content -->
    <div class="main">
        <div class="page-header">
            <h2><span>📁</span> Proyectos</h2>
        </div>

        <!-- Lista de proyectos -->
        <div class="projects-list">
            <?php foreach ($proyectos as $p): ?>
                <div class="project-item">
                    <div class="project-content">
                        <div class="project-icon">📁</div>
                        <div class="project-info">
                            <h3><?= htmlspecialchars($p['name']) ?></h3>
                            <?php if (!empty($p['description'])): ?>
                                <p><?= htmlspecialchars($p['description']) ?></p>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="project-actions">
                        <button class="project-star" onclick="toggleStar(this)">☆</button>
                        <button class="btn btn-secondary" onclick="editProject(<?= $p['id'] ?>, '<?= htmlspecialchars($p['name'], ENT_QUOTES) ?>', '<?= htmlspecialchars($p['description'], ENT_QUOTES) ?>')">
                            ✏️
                        </button>
                        <button class="btn btn-danger" onclick="deleteProject(<?= $p['id'] ?>)">
                            🗑️
                        </button>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Botón agregar proyecto -->
        <div class="add-project-btn" onclick="openCreateModal()">
            <div class="add-project-icon">+</div>
            <span>Agregar proyecto</span>
        </div>
    </div>

    <!-- Modal para crear proyecto -->
    <div id="createModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Crear Nuevo Proyecto</h3>
                <button class="close-btn" onclick="closeModal('createModal')">&times;</button>
            </div>
            <form method="POST" id="createForm">
                <div class="form-group">
                    <input type="text" name="nombre" class="form-input" placeholder="Nombre del proyecto" required>
                </div>
                <div class="form-group">
                    <textarea name="descripcion" class="form-input" placeholder="Descripción (opcional)"></textarea>
                </div>
                <div class="form-actions">
                    <button type="button" class="btn btn-secondary" onclick="closeModal('createModal')">Cancelar</button>
                    <button type="submit" name="crear" class="btn btn-primary">Crear Proyecto</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal para editar proyecto -->
    <div id="editModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Editar Proyecto</h3>
                <button class="close-btn" onclick="closeModal('editModal')">&times;</button>
            </div>
            <form method="POST" id="editForm">
                <input type="hidden" name="id" id="editId">
                <div class="form-group">
                    <input type="text" name="nombre" id="editNombre" class="form-input" required>
                </div>
                <div class="form-group">
                    <textarea name="descripcion" id="editDescripcion" class="form-input" placeholder="Descripción (opcional)"></textarea>
                </div>
                <div class="form-actions">
                    <button type="button" class="btn btn-secondary" onclick="closeModal('editModal')">Cancelar</button>
                    <button type="submit" name="editar" class="btn btn-primary">Guardar Cambios</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openCreateModal() {
            document.getElementById('createModal').classList.add('show');
        }

        function closeModal(modalId) {
            document.getElementById(modalId).classList.remove('show');
        }

        function editProject(id, nombre, descripcion) {
            document.getElementById('editId').value = id;
            document.getElementById('editNombre').value = nombre;
            document.getElementById('editDescripcion').value = descripcion;
            document.getElementById('editModal').classList.add('show');
        }

        function deleteProject(id) {
            if (confirm('¿Estás seguro de que deseas eliminar este proyecto?')) {
                window.location.href = 'proyectos.php?eliminar=' + id;
            }
        }

        function toggleStar(element) {
            if (element.classList.contains('active')) {
                element.classList.remove('active');
                element.textContent = '☆';
            } else {
                element.classList.add('active');
                element.textContent = '★';
            }
        }

        // Cerrar modal al hacer click fuera
        window.onclick = function(event) {
            if (event.target.classList.contains('modal')) {
                event.target.classList.remove('show');
            }
        }
    </script>
</body>
</html>