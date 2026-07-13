// Obtiene todos los tickets
async function obtenerTickets() {
    return await peticion('/api/tickets');
}

// Obtiene un ticket por id
async function obtenerTicket(idTicket) {
    return await peticion(`/api/tickets/${idTicket}`);
}

// Crea un nuevo ticket
async function crearTicket(datosTicket) {
    return await peticion('/api/tickets', 'POST', datosTicket);
}

// Actualiza todos los campos de un ticket
async function actualizarTicket(idTicket, datosTicket) {
    return await peticion(`/api/tickets/${idTicket}`, 'PUT', datosTicket);
}

// Elimina un ticket
async function eliminarTicket(idTicket) {
    return await peticion(`/api/tickets/${idTicket}`, 'DELETE');
}

// Cambia el estado de un ticket
async function cambiarEstadoTicket(idTicket, idEstado) {
    return await peticion(`/api/tickets/${idTicket}/estado`, 'PATCH', { id_estado: idEstado });
}

// Cambia la prioridad de un ticket
async function cambiarPrioridadTicket(idTicket, idPrioridad) {
    return await peticion(`/api/tickets/${idTicket}/prioridad`, 'PATCH', { id_prioridad: idPrioridad });
}

// Asigna un agente a un ticket
async function asignarTicket(idTicket, idAgente) {
    return await peticion(`/api/tickets/${idTicket}/asignar`, 'PATCH', { id_agente: idAgente });
}

// Cierra un ticket
async function cerrarTicket(idTicket) {
    return await peticion(`/api/tickets/${idTicket}/cerrar`, 'PATCH');
}

// Obtiene tickets de un cliente específico
async function obtenerTicketsPorCliente(idCliente) {
    return await peticion(`/api/tickets/cliente/${idCliente}`);
}

// Obtiene tickets asignados a un agente
async function obtenerTicketsPorAgente(idAgente) {
    return await peticion(`/api/tickets/agente/${idAgente}`);
}

// Obtiene tickets por categoría
async function obtenerTicketsPorCategoria(idCategoria) {
    return await peticion(`/api/tickets/categoria/${idCategoria}`);
}

// Obtiene tickets por prioridad
async function obtenerTicketsPorPrioridad(idPrioridad) {
    return await peticion(`/api/tickets/prioridad/${idPrioridad}`);
}
// Obtiene tickets por estado
async function obtenerTicketsPorEstado(idEstado) {
    return await peticion(`/api/tickets/estado/${idEstado}`);
}

// ── Renderizado de tabla de tickets ──────────────────────────

// Pinta la lista de tickets en el tbody indicado
function renderizarTablaTickets(lista, idTbody) {
    const cuerpo = document.getElementById(idTbody);
    if (!cuerpo) return;

    if (lista.length === 0) {
        cuerpo.innerHTML = '<tr><td colspan="7" class="sin-datos">Sin tickets</td></tr>';
        return;
    }

    cuerpo.innerHTML = lista.map(ticket => `
        <tr>
            <td>#TK-${ticket.id_ticket}</td>
            <td>${ticket.titulo}</td>
            <td>${ticket.cliente || '—'}</td>
            <td>${ticket.categoria || '—'}</td>
            <td><span class="badge estado-${ticket.estado?.toLowerCase().replace(' ', '-')}">${ticket.estado || '—'}</span></td>
            <td><span class="badge prioridad-${ticket.prioridad?.toLowerCase()}">${ticket.prioridad || '—'}</span></td>
            <td class="acciones">
                <button onclick="editarTicket(${ticket.id_ticket})">Editar</button>
                <button class="peligro" onclick="confirmarEliminarTicket(${ticket.id_ticket})">Eliminar</button>
            </td>
        </tr>
    `).join('');
}

// Carga y pinta los tickets en la tabla
async function cargarTablaTickets(idTbody = 'cuerpo-tickets') {
    try {
        const lista = await obtenerTickets();
        renderizarTablaTickets(lista, idTbody);
    } catch (error) {
        console.error('Error cargando tickets:', error);
    }
}

// Carga el formulario con los datos de un ticket para editar
async function editarTicket(idTicket) {
    try {
        const ticket = await obtenerTicket(idTicket);
        document.getElementById('ticket-id').value          = ticket.id_ticket;
        document.getElementById('ticket-titulo').value      = ticket.titulo;
        document.getElementById('ticket-descripcion').value = ticket.descripcion;
        document.getElementById('ticket-categoria').value   = ticket.id_categoria;
        document.getElementById('ticket-prioridad').value   = ticket.id_prioridad;
        document.getElementById('ticket-estado').value      = ticket.id_estado;
        document.getElementById('titulo-formulario-ticket').textContent = 'Editar Ticket';
    } catch (error) {
        mostrarMensaje('msg-tickets', 'Error al cargar el ticket.', true);
    }
}

// Confirma y elimina un ticket
async function confirmarEliminarTicket(idTicket) {
    if (!confirm('¿Eliminar este ticket?')) return;
    try {
        await eliminarTicket(idTicket);
        mostrarMensaje('msg-tickets', 'Ticket eliminado.');
        cargarTablaTickets();
    } catch (error) {
        mostrarMensaje('msg-tickets', error.message, true);
    }
}

// Maneja el envío del formulario de ticket (crear o actualizar)
const formularioTicket = document.getElementById('formulario-ticket');
if (formularioTicket) {
    formularioTicket.addEventListener('submit', async (e) => {
        e.preventDefault();

        const idTicket   = document.getElementById('ticket-id').value;
        const datosTicket = {
            titulo:             document.getElementById('ticket-titulo').value.trim(),
            descripcion:        document.getElementById('ticket-descripcion').value.trim(),
            id_usuario_cliente: parseInt(document.getElementById('ticket-cliente')?.value || 0),
            id_categoria:       parseInt(document.getElementById('ticket-categoria').value),
            id_prioridad:       parseInt(document.getElementById('ticket-prioridad').value),
            id_estado:          parseInt(document.getElementById('ticket-estado').value)
        };

        try {
            if (idTicket) {
                await actualizarTicket(idTicket, datosTicket);
                mostrarMensaje('msg-tickets', 'Ticket actualizado.');
            } else {
                await crearTicket(datosTicket);
                mostrarMensaje('msg-tickets', 'Ticket creado.');
            }
            limpiarFormulario('formulario-ticket');
            document.getElementById('ticket-id').value = '';
            document.getElementById('titulo-formulario-ticket').textContent = 'Nuevo Ticket';
            cargarTablaTickets();
        } catch (error) {
            mostrarMensaje('msg-tickets', error.message, true);
        }
    });
}
