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
//Script alertas confirmacion y error
document.addEventListener('DOMContentLoaded', () => {
    let form = document.getElementById('reset-form');
    let alertmensaje = document.getElementById('alert-mensaje');

    if (form) {
        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            let formData = new FormData(form);
            try {
                let response = await fetch('reset_password.php', {
                    method: 'POST',
                    body: formData
                });

                let result = await response.json();
                alertmensaje.textContent = result.message;
                alertmensaje.style.display = 'block';

                if (result.success) {
                    alertmensaje.classList.remove('alert-danger');
                    alertmensaje.classList.add('alert-success');
                    form.reset(); // Opcional: limpiar el formulario
                    // Opcional: redireccionar al login después de un tiempo
                    // setTimeout(() => {
                    //     window.location.href = 'login.php';
                    // }, 3000); 
                } else {
                    alertmensaje.classList.remove('alert-success');
                    alertmensaje.classList.add('alert-danger');
                }
            } catch (error) {
                alertmensaje.textContent = "Ocurrió un error en la solicitud.";
                alertmensaje.style.display = 'block';
                alertmensaje.classList.add('alert-danger');
                console.error(error);
            }
        });
    }
});