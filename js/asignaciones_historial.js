async function obtenerAsignaciones() {
    return await peticion('/api/asignaciones');
}

async function crearAsignacion(datosAsignacion) {
    return await peticion('/api/asignaciones', 'POST', datosAsignacion);
}

async function actualizarAsignacion(idAsignacion, datosAsignacion) {
    return await peticion(`/api/asignaciones/${idAsignacion}`, 'PUT', datosAsignacion);
}

async function eliminarAsignacion(idAsignacion) {
    return await peticion(`/api/asignaciones/${idAsignacion}`, 'DELETE');
}

// Obtiene asignaciones de un ticket específico
async function obtenerAsignacionesPorTicket(idTicket) {
    return await peticion(`/api/asignaciones/ticket/${idTicket}`);
}

// Obtiene asignaciones de un agente
async function obtenerAsignacionesPorAgente(idAgente) {
    return await peticion(`/api/asignaciones/agente/${idAgente}`);
}

// Obtiene asignaciones de un supervisor
async function obtenerAsignacionesPorSupervisor(idSupervisor) {
    return await peticion(`/api/asignaciones/supervisor/${idSupervisor}`);
}

function renderizarTablaAsignaciones(lista, idTbody) {
    const cuerpo = document.getElementById(idTbody);
    if (!cuerpo) return;

    if (lista.length === 0) {
        cuerpo.innerHTML = '<tr><td colspan="5" class="sin-datos">Sin asignaciones</td></tr>';
        return;
    }

    cuerpo.innerHTML = lista.map(asig => `
        <tr>
            <td>${asig.ticket || '—'}</td>
            <td>${asig.cliente || '—'}</td>
            <td>${asig.agente || '—'}</td>
            <td>${asig.supervisor || '—'}</td>
            <td class="acciones">
                <button class="peligro" onclick="confirmarEliminarAsignacion(${asig.id_asignar_ticket})">Eliminar</button>
            </td>
        </tr>
    `).join('');
}

async function cargarTablaAsignaciones(idTbody = 'cuerpo-asignaciones') {
    try {
        const lista = await obtenerAsignaciones();
        renderizarTablaAsignaciones(lista, idTbody);
    } catch (error) {
        console.error('Error cargando asignaciones:', error);
    }
}

async function confirmarEliminarAsignacion(idAsignacion) {
    if (!confirm('¿Eliminar esta asignación?')) return;
    try {
        await eliminarAsignacion(idAsignacion);
        mostrarMensaje('msg-asignaciones', 'Asignación eliminada.');
        cargarTablaAsignaciones();
    } catch (error) {
        mostrarMensaje('msg-asignaciones', error.message, true);
    }
}

const formularioAsignacion = document.getElementById('formulario-asignacion');
if (formularioAsignacion) {
    formularioAsignacion.addEventListener('submit', async (e) => {
        e.preventDefault();

        const idAsignacion    = document.getElementById('asignacion-id').value;
        const datosAsignacion = {
            id_ticket:    parseInt(document.getElementById('asignacion-ticket').value),
            id_cliente:   parseInt(document.getElementById('asignacion-cliente').value),
            id_agente:    parseInt(document.getElementById('asignacion-agente').value),
            id_supervisor: parseInt(document.getElementById('asignacion-supervisor').value)
        };

        try {
            if (idAsignacion) {
                await actualizarAsignacion(idAsignacion, datosAsignacion);
                mostrarMensaje('msg-asignaciones', 'Asignación actualizada.');
            } else {
                await crearAsignacion(datosAsignacion);
                mostrarMensaje('msg-asignaciones', 'Asignación creada.');
            }
            limpiarFormulario('formulario-asignacion');
            document.getElementById('asignacion-id').value = '';
            cargarTablaAsignaciones();
        } catch (error) {
            mostrarMensaje('msg-asignaciones', error.message, true);
        }
    });
}

// ── HISTORIAL ─────────────────────────────────────────────────

async function obtenerHistorial() {
    return await peticion('/api/historial');
}

// Obtiene el historial de un ticket
async function obtenerHistorialPorTicket(idTicket) {
    return await peticion(`/api/historial/ticket/${idTicket}`);
}

// Obtiene el historial de un usuario
async function obtenerHistorialPorUsuario(idUsuario) {
    return await peticion(`/api/historial/usuario/${idUsuario}`);
}

// Registra una acción en el historial
async function registrarHistorial(datosHistorial) {
    return await peticion('/api/historial', 'POST', datosHistorial);
}

function renderizarTablaHistorial(lista, idTbody) {
    const cuerpo = document.getElementById(idTbody);
    if (!cuerpo) return;

    if (lista.length === 0) {
        cuerpo.innerHTML = '<tr><td colspan="5" class="sin-datos">Sin historial</td></tr>';
        return;
    }

    cuerpo.innerHTML = lista.map(h => `
        <tr>
            <td>${h.ticket || '—'}</td>
            <td>${h.usuario || '—'}</td>
            <td>${h.accion}</td>
            <td>${h.campo_modificado || '—'}</td>
            <td>${new Date(h.fecha).toLocaleString('es-GT')}</td>
        </tr>
    `).join('');
}

async function cargarTablaHistorial(idTbody = 'cuerpo-historial') {
    try {
        const lista = await obtenerHistorial();
        renderizarTablaHistorial(lista, idTbody);
    } catch (error) {
        console.error('Error cargando historial:', error);
    }
}
