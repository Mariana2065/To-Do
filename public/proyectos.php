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

// AÑADIR COLABORADOR
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['add_project_collaborator'])) {
    $project_id = $_POST['project_id'];
    $user_id = $_POST['user_id'];
    $role = $_POST['role'];

    $stmt = $pdo->prepare("INSERT INTO project_users (project_id, user_id, role) VALUES (?, ?, ?)");
    $stmt->execute([$project_id, $user_id, $role]);

    header("Location: proyectos.php");
    exit;
}

// ==========================
// 5. QUITAR COLABORADOR
// ==========================
if (isset($_GET['remove_user'])) {
    $project_id = $_GET['project_id'];
    $user_id = $_GET['remove_user'];

    $stmt = $pdo->prepare("DELETE FROM project_users WHERE project_id = ? AND user_id = ?");
    $stmt->execute([$project_id, $user_id]);

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

// ==========================
// 7. LISTAR USUARIOS DISPONIBLES
// ==========================
$usuarios = $pdo->query("SELECT id, name FROM users")->fetchAll(PDO::FETCH_ASSOC);

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

                        <!-- Colaboradores -->
                        <h4>👥 Colaboradores</h4>
                        <?php
                            $stmt = $pdo->prepare("
                                SELECT u.id, u.name, pu.role 
                                FROM project_users pu
                                JOIN users u ON pu.user_id = u.id
                                WHERE pu.project_id = ?
                            ");
                            $stmt->execute([$p['id']]);
                            $colaboradores = $stmt->fetchAll(PDO::FETCH_ASSOC);
                        ?>
                        <ul>
                            <?php foreach ($colaboradores as $c): ?>
                                <li>
                                    <?= htmlspecialchars($c['name']) ?> (<?= $c['role'] ?>)
                                    <a href="proyectos.php?project_id=<?= $p['id'] ?>&remove_user=<?= $c['id'] ?>" 
                                    onclick="return confirm('¿Quitar colaborador?')">❌</a>
                                </li>
                            <?php endforeach; ?>
                            <?php if (!$colaboradores): ?>
                                <li>No hay colaboradores.</li>
                            <?php endif; ?>
                        </ul>

                        <!-- Añadir colaborador -->
                        <form method="POST" style="margin-top:10px;">
                            <input type="hidden" name="project_id" value="<?= $p['id'] ?>">
                            <select name="user_id">
                                <?php foreach ($usuarios as $u): ?>
                                    <option value="<?= $u['id'] ?>"><?= htmlspecialchars($u['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <select name="role">
                                <option value="viewer">Solo lectura</option>
                                <option value="editor">Editor</option>
                            </select>
                            <button type="submit" name="add_project_collaborator">➕ Añadir</button>
                        </form>
                        

                    </div>
                    <div class="project-actions">
                        <button class="btn-crud-proyectos" onclick="toggleStar(this)"><img src="../assets/css/img/iconsEstrella.png" alt="" class="crud-proyectos"></button>
                        <button class="btn-crud-proyectos" onclick="editProject(<?= $p['id'] ?>, '<?= htmlspecialchars($p['name'], ENT_QUOTES) ?>', '<?= htmlspecialchars($p['description'], ENT_QUOTES) ?>')">
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
     <!-- Botón agregar proyecto -->
        <div class="add-project-btn" onclick="openCreateModal()">
            <div class="add-project-icon"><img src="../assets/css/img/iconsmas.png" alt=""></div>
            <span>Agregar proyecto</span>
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