let chatTicketActivo   = null;
let chatIdUsuarioActual = null;
let chatUltimoMensajeId = 0;
let chatIntervaloPolling = null;

async function obtenerMensajesTicket(idTicket) {
    return await peticion(`/api/mensajes/ticket/${idTicket}`);
}

async function obtenerMensajesNuevos(idTicket, idUltimoMensaje) {
    return await peticion(`/api/mensajes/nuevos/${idTicket}/${idUltimoMensaje}`);
}

async function enviarMensajeChat(idTicket, contenido) {
    return await peticion('/api/mensajes', 'POST', { id_ticket: idTicket, contenido });
}

async function marcarMensajesLeidos(idTicket) {
    return await peticion(`/api/mensajes/ticket/${idTicket}/leidos`, 'PATCH');
}

async function eliminarMensajeChat(idMensaje) {
    return await peticion(`/api/mensajes/${idMensaje}`, 'DELETE');
}

function construirBurbujaMensaje(mensaje, idUsuarioActual) {
    const esPropio = parseInt(mensaje.id_usuario) === parseInt(idUsuarioActual);
    const hora     = new Date(mensaje.fecha_envio).toLocaleTimeString('es-GT', { hour: '2-digit', minute: '2-digit' });

    const claseFila    = esPropio ? 'fila-mensaje propio' : 'fila-mensaje ajeno';
    const claseBurbuja = esPropio ? 'burbuja-mensaje propio' : 'burbuja-mensaje ajeno';

    return `
        <div class="${claseFila}" data-id-mensaje="${mensaje.id_mensaje}">
            <div class="${claseBurbuja}">
                ${!esPropio ? `<p class="remitente-mensaje">${mensaje.remitente || 'Usuario'}</p>` : ''}
                <p class="contenido-mensaje">${escaparHtmlChat(mensaje.contenido)}</p>
                <span class="hora-mensaje">${hora}</span>
            </div>
        </div>
    `;
}

function escaparHtmlChat(texto) {
    const div = document.createElement('div');
    div.textContent = texto;
    return div.innerHTML;
}

function renderizarChat(listaMensajes, idContenedor, idUsuarioActual) {
    const contenedor = document.getElementById(idContenedor);
    if (!contenedor) return;

    if (listaMensajes.length === 0) {
        contenedor.innerHTML = '<p class="chat-vacio">Aún no hay mensajes. Escribe el primero.</p>';
        return;
    }

    contenedor.innerHTML = listaMensajes.map(m => construirBurbujaMensaje(m, idUsuarioActual)).join('');
    contenedor.scrollTop = contenedor.scrollHeight;

    chatUltimoMensajeId = listaMensajes[listaMensajes.length - 1].id_mensaje;
}

function agregarMensajesNuevos(listaMensajes, idContenedor, idUsuarioActual) {
    if (listaMensajes.length === 0) return;

    const contenedor = document.getElementById(idContenedor);
    if (!contenedor) return;

    const vacio = contenedor.querySelector('.chat-vacio');
    if (vacio) vacio.remove();

    const htmlNuevo = listaMensajes.map(m => construirBurbujaMensaje(m, idUsuarioActual)).join('');
    contenedor.insertAdjacentHTML('beforeend', htmlNuevo);
    contenedor.scrollTop = contenedor.scrollHeight;

    chatUltimoMensajeId = listaMensajes[listaMensajes.length - 1].id_mensaje;
}

async function abrirChatTicket(idTicket, idUsuarioActual, idContenedor = 'chat-mensajes', tituloChat = '') {
    chatTicketActivo    = idTicket;
    chatIdUsuarioActual = idUsuarioActual;
    chatUltimoMensajeId = 0;

    detenerPollingChat();

    const contenedor = document.getElementById(idContenedor);
    if (contenedor) contenedor.innerHTML = '<p class="chat-vacio">Cargando conversación...</p>';

    const elTitulo = document.getElementById('chat-titulo-ticket');
    if (elTitulo && tituloChat) elTitulo.textContent = tituloChat;

    try {
        const historial = await obtenerMensajesTicket(idTicket);
        renderizarChat(historial, idContenedor, idUsuarioActual);
        await marcarMensajesLeidos(idTicket);
        iniciarPollingChat(idContenedor);
    } catch (error) {
        if (contenedor) contenedor.innerHTML = '<p class="chat-vacio">No se pudo cargar la conversación.</p>';
        console.error('Error abriendo el chat:', error);
    }
}

function iniciarPollingChat(idContenedor = 'chat-mensajes') {
    chatIntervaloPolling = setInterval(async () => {
        if (!chatTicketActivo) return;
        try {
            const nuevos = await obtenerMensajesNuevos(chatTicketActivo, chatUltimoMensajeId);
            if (nuevos.length > 0) {
                agregarMensajesNuevos(nuevos, idContenedor, chatIdUsuarioActual);
                await marcarMensajesLeidos(chatTicketActivo);
            }
        } catch (error) {
            console.error('Error en el polling del chat:', error);
        }
    }, 4000);
}

function detenerPollingChat() {
    if (chatIntervaloPolling) {
        clearInterval(chatIntervaloPolling);
        chatIntervaloPolling = null;
    }
}

async function enviarMensajeDesdeFormulario(idInput = 'chat-input', idContenedor = 'chat-mensajes') {
    const input     = document.getElementById(idInput);
    const contenido = input.value.trim();

    if (!contenido) return;
    if (!chatTicketActivo) {
        console.error('No hay un ticket activo para enviar el mensaje.');
        return;
    }

    input.value = '';

    try {
        const mensajeCreado = await enviarMensajeChat(chatTicketActivo, contenido);
        agregarMensajesNuevos([mensajeCreado], idContenedor, chatIdUsuarioActual);
    } catch (error) {
        alert(error.message || 'No se pudo enviar el mensaje');
        input.value = contenido;
    }
}

function configurarFormularioChat(idFormulario = 'chat-formulario', idInput = 'chat-input', idContenedor = 'chat-mensajes') {
    const formulario = document.getElementById(idFormulario);
    if (!formulario) return;

    formulario.addEventListener('submit', (evento) => {
        evento.preventDefault();
        enviarMensajeDesdeFormulario(idInput, idContenedor);
    });
}

function cerrarChatTicket() {
    detenerPollingChat();
    chatTicketActivo    = null;
    chatUltimoMensajeId = 0;
}
