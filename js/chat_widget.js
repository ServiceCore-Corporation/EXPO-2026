(function () {
    'use strict';

    const ID_USUARIO_ACTUAL = window.ID_USUARIO_ACTUAL;

    if (typeof peticion !== 'function') {
        console.error('[chat_widget.js] No se encontró la función peticion(). Asegúrate de cargar js/api.js antes que js/chat_widget.js.');
        return;
    }
    if (!ID_USUARIO_ACTUAL) {
        console.error('[chat_widget.js] window.ID_USUARIO_ACTUAL no está definido.');
        return;
    }

    // Estado interno
    let TICKETS = [];
    let activeId = null;
    let ultimoIdMensaje = 0;
    let intervaloPolling = null;
    let yaCargoTickets = false;

    const COLOR_ESTADO = {
        'Pendiente':  'bg-yellow-100 text-yellow-700',
        'Abierto':    'bg-yellow-100 text-yellow-700',
        'En proceso': 'bg-blue-100 text-blue-700',
        'Cerrado':    'bg-green-100 text-green-700',
        'Cancelado':  'bg-red-100 text-red-700'
    };
    const COLOR_PRIORIDAD = {
        'Alta':  'text-red-600',
        'Media': 'text-orange-500',
        'Baja':  'text-blue-500'
    };

    // Estilos 
    const ESTILOS = `
    .cw-fab {
        position: fixed; right: 24px; bottom: 24px; z-index: 9998;
        width: 58px; height: 58px; border-radius: 9999px; border: none; cursor: pointer;
        background: #5750ad; color: #fff; display: flex; align-items: center; justify-content: center;
        box-shadow: 0 10px 25px rgba(87,80,173,.45);
        transition: transform .15s ease, box-shadow .15s ease;
    }
    .cw-fab:hover { transform: translateY(-2px) scale(1.04); box-shadow: 0 14px 30px rgba(87,80,173,.55); }
    .cw-fab .material-symbols-outlined { font-size: 28px; }
    .cw-fab-badge {
        position: absolute; top: -4px; right: -4px; min-width: 20px; height: 20px; padding: 0 5px;
        border-radius: 9999px; background: #dc2626; color: #fff; font-size: 11px; font-weight: 700;
        display: flex; align-items: center; justify-content: center; border: 2px solid #fff;
    }
    .cw-overlay {
        position: fixed; inset: 0; background: rgba(15,15,30,.55); z-index: 9999;
        display: none; align-items: center; justify-content: center; padding: 16px;
    }
    .cw-overlay.cw-open { display: flex; }
    .cw-modal {
        background: #fff; width: 100%; max-width: 920px; height: 80vh; max-height: 640px;
        border-radius: 18px; overflow: hidden; display: flex; box-shadow: 0 25px 60px rgba(0,0,0,.35);
        animation: cw-pop .18s ease;
    }
    @keyframes cw-pop { from { transform: scale(.96); opacity: 0; } to { transform: scale(1); opacity: 1; } }
    .cw-sidebar { width: 280px; flex: none; background: #f8f8fc; border-right: 1px solid #ececf5; display: flex; flex-direction: column; }
    .cw-sidebar-head {
        display: flex; align-items: center; justify-content: space-between; padding: 16px;
        font-weight: 800; color: #1e1858; border-bottom: 1px solid #ececf5;
    }
    .cw-close-btn { background: none; border: none; cursor: pointer; color: #6b7280; display: flex; }
    .cw-close-btn:hover { color: #1e1858; }
    .cw-lista { flex: 1; overflow-y: auto; padding: 8px; }
    .cw-lista-vacio { padding: 24px 12px; text-align: center; color: #9ca3af; font-size: 13px; }
    .cw-item {
        padding: 10px 12px; border-radius: 12px; cursor: pointer; margin-bottom: 6px; border: 1px solid transparent;
    }
    .cw-item:hover { background: #eeedfb; }
    .cw-item.cw-activo { background: #5750ad; }
    .cw-item.cw-activo .cw-item-id,
    .cw-item.cw-activo .cw-item-title { color: #fff; }
    .cw-item-top { display: flex; align-items: center; justify-content: space-between; margin-bottom: 2px; }
    .cw-item-id { font-size: 11px; font-weight: 800; color: #5750ad; }
    .cw-item-title { font-size: 13px; font-weight: 600; color: #1e1858; line-height: 1.3; }
    .cw-item.cw-activo .cw-item-id { color: #e5e3fb; }
    .cw-chat { flex: 1; display: flex; flex-direction: column; min-width: 0; }
    .cw-chat-head { padding: 14px 18px; border-bottom: 1px solid #ececf5; }
    .cw-chat-title { font-weight: 800; color: #1e1858; font-size: 15px; }
    .cw-chat-sub { font-size: 12px; color: #9ca3af; margin-top: 2px; }
    .cw-mensajes { flex: 1; overflow-y: auto; padding: 16px; background: #fbfbfe; display: flex; flex-direction: column; gap: 10px; }
    .cw-vacio { margin: auto; text-align: center; color: #9ca3af; font-size: 13px; }
    .cw-fila { display: flex; flex-direction: column; max-width: 75%; }
    .cw-fila.cw-propio { align-self: flex-end; align-items: flex-end; }
    .cw-fila.cw-ajeno { align-self: flex-start; align-items: flex-start; }
    .cw-burbuja { padding: 9px 13px; border-radius: 14px; font-size: 13px; line-height: 1.45; word-break: break-word; }
    .cw-fila.cw-propio .cw-burbuja { background: #5750ad; color: #fff; border-bottom-right-radius: 4px; }
    .cw-fila.cw-ajeno .cw-burbuja { background: #eceefc; color: #1e1858; border-bottom-left-radius: 4px; }
    .cw-remitente { font-size: 11px; font-weight: 700; color: #6b7280; margin-bottom: 2px; }
    .cw-hora { font-size: 10px; color: #9ca3af; margin-top: 3px; }
    .cw-input-wrap { border-top: 1px solid #ececf5; padding: 12px; }
    .cw-input-bar { display: flex; align-items: center; gap: 8px; }
    .cw-input { flex: 1; border: 1px solid #e5e7eb; border-radius: 9999px; padding: 10px 16px; font-size: 13px; outline: none; }
    .cw-input:focus { border-color: #5750ad; }
    .cw-send-btn {
        width: 40px; height: 40px; border-radius: 9999px; border: none; background: #5750ad; color: #fff;
        display: flex; align-items: center; justify-content: center; cursor: pointer; flex: none;
    }
    .cw-send-btn:hover { opacity: .9; }
    .cw-closed-banner { font-size: 12px; color: #6b7280; background: #f3f4f6; padding: 10px 14px; border-radius: 10px; text-align: center; }
    @media (max-width: 640px) {
        .cw-modal { flex-direction: column; height: 90vh; max-height: none; }
        .cw-sidebar { width: 100%; max-height: 35%; border-right: none; border-bottom: 1px solid #ececf5; }
    }
    `;

    function inyectarEstilos() {
        if (document.getElementById('cw-styles')) return;
        const style = document.createElement('style');
        style.id = 'cw-styles';
        style.textContent = ESTILOS;
        document.head.appendChild(style);
    }

    // Construcción del DOM
    function construirDOM() {
        if (document.getElementById('cw-overlay')) return;

        const fab = document.createElement('button');
        fab.id = 'cw-fab';
        fab.className = 'cw-fab';
        fab.title = 'Mensajes';
        fab.innerHTML = `
            <span class="material-symbols-outlined">chat</span>
            <span id="cw-fab-badge" class="cw-fab-badge" style="display:none;">0</span>
        `;

        const overlay = document.createElement('div');
        overlay.id = 'cw-overlay';
        overlay.className = 'cw-overlay';
        overlay.innerHTML = `
            <div class="cw-modal">
                <aside class="cw-sidebar">
                    <div class="cw-sidebar-head">
                        <span>Mis conversaciones</span>
                        <button id="cw-close" class="cw-close-btn" aria-label="Cerrar">
                            <span class="material-symbols-outlined">close</span>
                        </button>
                    </div>
                    <div id="cw-lista" class="cw-lista"></div>
                </aside>
                <section class="cw-chat">
                    <div class="cw-chat-head">
                        <div id="cw-chat-title" class="cw-chat-title">Selecciona una conversación</div>
                        <div id="cw-chat-sub" class="cw-chat-sub"></div>
                    </div>
                    <div id="cw-mensajes" class="cw-mensajes">
                        <div class="cw-vacio">Elige un ticket para ver la conversación.</div>
                    </div>
                    <div id="cw-input-wrap" class="cw-input-wrap"></div>
                </section>
            </div>
        `;

        document.body.appendChild(fab);
        document.body.appendChild(overlay);

        fab.addEventListener('click', () => abrir());
        document.getElementById('cw-close').addEventListener('click', cerrar);
        overlay.addEventListener('click', (e) => { if (e.target === overlay) cerrar(); });
        document.addEventListener('keydown', (e) => { if (e.key === 'Escape') cerrar(); });
    }

    // Carga de tickets / sidebar
    async function cargarTickets() {
        try {
            const datos = await peticion(`/api/tickets/cliente/${ID_USUARIO_ACTUAL}`);
            TICKETS = Array.isArray(datos) ? datos : [];
            yaCargoTickets = true;
            renderLista();
            return TICKETS;
        } catch (error) {
            console.error('[chat_widget.js] Error cargando tickets:', error);
            document.getElementById('cw-lista').innerHTML = '<div class="cw-lista-vacio">No se pudieron cargar tus conversaciones.</div>';
            return [];
        }
    }

    function renderLista() {
        const cont = document.getElementById('cw-lista');
        if (TICKETS.length === 0) {
            cont.innerHTML = '<div class="cw-lista-vacio">Aún no tienes tickets registrados.</div>';
            return;
        }
        cont.innerHTML = TICKETS.map(t => `
            <div class="cw-item${parseInt(t.id_ticket) === activeId ? ' cw-activo' : ''}" data-id="${t.id_ticket}">
                <div class="cw-item-top">
                    <span class="cw-item-id">TK-${String(t.id_ticket).padStart(4, '0')}</span>
                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold ${COLOR_ESTADO[t.estado] || 'bg-gray-100 text-gray-700'}">${t.estado || '—'}</span>
                </div>
                <div class="cw-item-title">${escHtml(t.titulo || 'Sin título')}</div>
            </div>
        `).join('');

        cont.querySelectorAll('.cw-item').forEach(el => {
            el.addEventListener('click', () => seleccionarTicket(parseInt(el.dataset.id)));
        });
    }

    // Selección de ticket + carga de mensajes
    async function seleccionarTicket(idTicket) {
        activeId = idTicket;
        ultimoIdMensaje = 0;
        detenerPolling();
        renderLista();

        const t = TICKETS.find(x => parseInt(x.id_ticket) === idTicket);

        document.getElementById('cw-chat-title').textContent = t
            ? `TK-${String(t.id_ticket).padStart(4, '0')} — ${t.titulo}`
            : `Ticket #${idTicket}`;
        document.getElementById('cw-chat-sub').textContent = t
            ? `${t.categoria || 'General'} · ${t.agente || 'Sin asignar'}`
            : '';

        const cerrado = t && t.estado === 'Cerrado';
        const sinAgente = t && !t.id_usuario_agente;
        const inputWrap = document.getElementById('cw-input-wrap');
        if (cerrado) {
            inputWrap.innerHTML = `<div class="cw-closed-banner"><strong>Este ticket está cerrado.</strong> Si tienes un problema nuevo, abre una nueva solicitud.</div>`;
        } else if (sinAgente) {
            inputWrap.innerHTML = `<div class="cw-closed-banner"><strong>Agente no asignado.</strong> Un supervisor debe asignar un agente a este ticket antes de poder chatear.</div>`;
        } else {
            inputWrap.innerHTML = `
                <div class="cw-input-bar">
                    <input type="text" id="cw-input" class="cw-input" placeholder="Escribe tu mensaje..." autocomplete="off">
                    <button id="cw-send" class="cw-send-btn" aria-label="Enviar mensaje">
                        <span class="material-symbols-outlined">send</span>
                    </button>
                </div>
            `;
            const input = document.getElementById('cw-input');
            document.getElementById('cw-send').addEventListener('click', enviarMensaje);
            input.addEventListener('keydown', e => { if (e.key === 'Enter') enviarMensaje(); });
        }

        document.getElementById('cw-mensajes').innerHTML = '<div class="cw-vacio">Cargando conversación...</div>';

        try {
            const historial = await peticion(`/api/mensajes/ticket/${idTicket}`);
            ultimoIdMensaje = historial.length > 0 ? historial[historial.length - 1].id_mensaje : 0;
            pintarMensajes(historial);
            await peticion(`/api/mensajes/ticket/${idTicket}/leidos`, 'PATCH');
            iniciarPolling(idTicket);
        } catch (error) {
            console.error('[chat_widget.js] Error cargando historial:', error);
            document.getElementById('cw-mensajes').innerHTML = '<div class="cw-vacio">No se pudo cargar la conversación.</div>';
        }
    }

    function pintarMensajes(lista) {
        const cont = document.getElementById('cw-mensajes');
        if (lista.length === 0) {
            cont.innerHTML = '<div class="cw-vacio">Aún no hay mensajes en este ticket.</div>';
            return;
        }
        cont.innerHTML = lista.map(construirBurbuja).join('');
        cont.scrollTop = cont.scrollHeight;
    }

    function agregarMensajes(lista) {
        if (lista.length === 0) return;
        const cont = document.getElementById('cw-mensajes');
        const vacio = cont.querySelector('.cw-vacio');
        if (vacio) cont.innerHTML = '';
        cont.insertAdjacentHTML('beforeend', lista.map(construirBurbuja).join(''));
        cont.scrollTop = cont.scrollHeight;
        ultimoIdMensaje = lista[lista.length - 1].id_mensaje;
    }

    function construirBurbuja(m) {
        const esMio = parseInt(m.id_usuario) === ID_USUARIO_ACTUAL;
        const hora = new Date(m.fecha_envio).toLocaleTimeString('es-GT', { hour: '2-digit', minute: '2-digit' });
        const texto = escHtml(m.contenido);
        return `
            <div class="cw-fila ${esMio ? 'cw-propio' : 'cw-ajeno'}">
                ${!esMio ? `<span class="cw-remitente">${escHtml(m.remitente || 'Agente')}</span>` : ''}
                <div class="cw-burbuja">${texto}</div>
                <span class="cw-hora">${hora}</span>
            </div>
        `;
    }

    function escHtml(s) {
        return String(s).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
    }

    // Envío de mensajes
    async function enviarMensaje() {
        const input = document.getElementById('cw-input');
        if (!input || !activeId) return;
        const texto = input.value.trim();
        if (!texto) return;

        input.value = '';
        try {
            const mensajeCreado = await peticion('/api/mensajes', 'POST', { id_ticket: activeId, contenido: texto });
            if (!mensajeCreado) {
                // servidor respondió sin JSON pero la petición pudo haber funcionado
                // hacemos un fetch de los mensajes más recientes para sincronizar
                const recientes = await peticion(`/api/mensajes/nuevos/${activeId}/${ultimoIdMensaje}`);
                if (Array.isArray(recientes) && recientes.length) {
                    agregarMensajes(recientes);
                }
            } else {
                agregarMensajes([mensajeCreado]);
            }
        } catch (error) {
            console.error('[chat_widget.js] Error enviando mensaje:', error);
            input.value = texto;
            alert(error.message || 'No se pudo enviar el mensaje');
        }
    }

    // Polling de mensajes nuevos
    function iniciarPolling(idTicket) {
        detenerPolling();
        intervaloPolling = setInterval(async () => {
            if (activeId !== idTicket) return;
            try {
                const nuevos = await peticion(`/api/mensajes/nuevos/${idTicket}/${ultimoIdMensaje}`);
                if (nuevos.length > 0) {
                    agregarMensajes(nuevos);
                    await peticion(`/api/mensajes/ticket/${idTicket}/leidos`, 'PATCH');
                }
            } catch (error) {
                console.error('[chat_widget.js] Error en polling:', error);
            }
        }, 4000);
    }

    function detenerPolling() {
        if (intervaloPolling) { clearInterval(intervaloPolling); intervaloPolling = null; }
    }

    // Abrir / cerrar modal (API pública)
    async function abrir(idTicket) {
        inyectarEstilos();
        construirDOM();
        document.getElementById('cw-overlay').classList.add('cw-open');

        if (!yaCargoTickets) await cargarTickets();

        if (idTicket) {
            seleccionarTicket(parseInt(idTicket));
        } else if (!activeId && TICKETS.length > 0) {
            seleccionarTicket(TICKETS[0].id_ticket);
        }
    }

    function cerrar() {
        const overlay = document.getElementById('cw-overlay');
        if (overlay) overlay.classList.remove('cw-open');
        detenerPolling();
    }

    window.ChatWidget = { abrir, cerrar };

    // Construye el ícono flotante apenas carga la página (el modal se llena al abrirlo)
    document.addEventListener('DOMContentLoaded', () => {
        inyectarEstilos();
        construirDOM();
    });
})();
