// Obtiene todos los archivos
async function obtenerArchivos() {
    return await peticion('/api/archivos');
}

// Obtiene archivos de un ticket
async function obtenerArchivosPorTicket(idTicket) {
    return await peticion(`/api/archivos/ticket/${idTicket}`);
}

// Descarga un archivo — abre en nueva pestaña
function descargarArchivo(idArchivo) {
    window.open(`/api/archivos/download/${idArchivo}`, '_blank');
}

// Elimina un archivo
async function eliminarArchivo(idArchivo) {
    return await peticion(`/api/archivos/${idArchivo}`, 'DELETE');
}

// Sube un archivo usando FormData (multipart)
async function subirArchivo(idTicket, archivoInput) {
    const formularioSubida = new FormData();
    formularioSubida.append('id_ticket', idTicket);
    formularioSubida.append('archivo', archivoInput.files[0]);

    const respuesta = await fetch('/api/archivos', {
        method: 'POST',
        body:   formularioSubida
        // No poner Content-Type — el navegador lo agrega con el boundary
    });

    const datos = await respuesta.json();
    if (!respuesta.ok) throw new Error(datos.error || 'Error subiendo archivo');
    return datos;
}

function renderizarTablaArchivos(lista, idTbody) {
    const cuerpo = document.getElementById(idTbody);
    if (!cuerpo) return;

    if (lista.length === 0) {
        cuerpo.innerHTML = '<tr><td colspan="4" class="sin-datos">Sin archivos</td></tr>';
        return;
    }

    cuerpo.innerHTML = lista.map(archivo => `
        <tr>
            <td>${archivo.id_archivo}</td>
            <td>${archivo.nombre}</td>
            <td>${new Date(archivo.fecha_subida).toLocaleDateString('es-GT')}</td>
            <td class="acciones">
                <button onclick="descargarArchivo(${archivo.id_archivo})">Descargar</button>
                <button class="peligro" onclick="confirmarEliminarArchivo(${archivo.id_archivo})">Eliminar</button>
            </td>
        </tr>
    `).join('');
}

async function cargarTablaArchivos(idTbody = 'cuerpo-archivos') {
    try {
        const lista = await obtenerArchivos();
        renderizarTablaArchivos(lista, idTbody);
    } catch (error) {
        console.error('Error cargando archivos:', error);
    }
}

async function confirmarEliminarArchivo(idArchivo) {
    if (!confirm('¿Eliminar este archivo?')) return;
    try {
        await eliminarArchivo(idArchivo);
        mostrarMensaje('msg-archivos', 'Archivo eliminado.');
        cargarTablaArchivos();
    } catch (error) {
        mostrarMensaje('msg-archivos', error.message, true);
    }
}

// Maneja el formulario de subida de archivo
const formularioArchivo = document.getElementById('formulario-archivo');
if (formularioArchivo) {
    formularioArchivo.addEventListener('submit', async (e) => {
        e.preventDefault();

        const idTicket     = document.getElementById('archivo-ticket').value;
        const inputArchivo = document.getElementById('archivo-file');

        if (!idTicket || !inputArchivo.files.length) {
            mostrarMensaje('msg-archivos', 'Selecciona un ticket y un archivo.', true);
            return;
        }

        try {
            await subirArchivo(idTicket, inputArchivo);
            mostrarMensaje('msg-archivos', 'Archivo subido correctamente.');
            limpiarFormulario('formulario-archivo');
            cargarTablaArchivos();
        } catch (error) {
            mostrarMensaje('msg-archivos', error.message, true);
        }
    });
}
