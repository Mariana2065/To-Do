const NUM_ESTRELLAS = 50; 
const seccion = document.getElementById('espacio');

function crearEstrella() {
    const div = document.createElement('div');
    div.className = 'estrella';

    div.innerHTML = `
      <svg viewBox="0 0 100 100" xmlns="http://www.w3.org/2000/svg">
        <path d="M50 0 L60 40 L100 50 L60 60 L50 100 L40 60 L0 50 L40 40 Z"/>
      </svg>
    `;

    div.style.left = Math.random() * 100 + '%';

    const size = 8 + Math.random() * 20;
    div.style.width = size + 'px';
    div.style.height = size + 'px';

    const dur = 6 + Math.random() * 10;
    div.style.animationDuration = dur + 's, ' + (dur/3) + 's';

    div.style.animationDelay = (-Math.random() * dur) + 's, 0s';

    seccion.appendChild(div);
}

for (let i = 0; i < NUM_ESTRELLAS; i++) {
    crearEstrella();
}
//funcionamiunto del modal 
document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('formRecuperarContraseña');
    const mensajeModal = new bootstrap.Modal(document.getElementById('mensajeModal'));
    const modalMensaje = document.getElementById('modalMensaje');

    form.addEventListener('submit', async (e) => {
        e.preventDefault(); 

        const formData = new FormData(form);

        try {
            const response = await fetch('enviar_enlace.php', {
                method: 'POST',
                body: formData
            });

            if (!response.ok) {
                throw new Error('Network response was not ok');
            }

            const result = await response.json(); 
            modalMensaje.textContent = result.message; 
            mensajeModal.show();

        } catch (error) {
            console.error('Error:', error);
            modalMensaje.textContent = "Ocurrió un error al procesar tu solicitud.";
            mensajeModal.show();
        }
    });
});
//fin del funcionamiento del modal

//Modal proyectos
 // La función original openCreateModal()
function openCreateModal() {
    document.getElementById('modal-crear-proyecto').classList.add('show');
}

// Nueva función para cerrar cualquier modal de proyecto
function closeProjectModal(modalId) {
    document.getElementById(modalId).classList.remove('show');
}

// La función original editProject()
function editProject(id, nombre, descripcion) {
    document.getElementById('id-editar-proyecto').value = id;
    document.getElementById('nombre-editar-proyecto').value = nombre;
    document.getElementById('descripcion-editar-proyecto').value = descripcion;
    document.getElementById('modal-editar-proyecto').classList.add('show');
}

// Las siguientes funciones no necesitaban cambios, pero las incluyo por contexto
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

// Cerrar modal al hacer clic fuera (con la nueva clase)
window.onclick = function(event) {
    if (event.target.classList.contains('modal-proyecto')) {
        event.target.classList.remove('show');
    }
}
//fin modal proyectos