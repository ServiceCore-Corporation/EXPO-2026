
(function () {
    'use strict';

    const ID_USUARIO_ACTUAL = window.ID_USUARIO_ACTUAL;

    if (typeof peticion !== 'function') {
        console.error('[chat_widget_aprovador.js] No se encontró la función peticion(). Asegúrate de cargar js/api.js antes que js/chat_widget_aprovador.js.');
        return;
    }
    if (!ID_USUARIO_ACTUAL) {
        console.error('[chat_widget_aprovador.js] window.ID_USUARIO_ACTUAL no está definido.');
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

    // Estilos (auto-inyectados, sin depender de un .css externo)
    const ESTILOS = `
    .cwa-fab {
        position: fixed; right: 24px; bottom: 24px; z-index: 9998;
        width: 58px; height: 58px; border-radius: 9999px; border: none; cursor: pointer;
        background: #5750ad; color: #fff; display: flex; align-items: center; justify-content: center;
        box-shadow: 0 10px 25px rgba(87,80,173,.45);
        transition: transform .15s ease, box-shadow .15s ease;
    }
    .cwa-fab:hover { transform: translateY(-2px) scale(1.04); box-shadow: 0 14px 30px rgba(87,80,173,.55); }
    .cwa-fab .material-symbols-outlined { font-size: 28px; }
    .cwa-fab-badge {
        position: absolute; top: -4px; right: -4px; min-width: 20px; height: 20px; padding: 0 5px;
        border-radius: 9999px; background: #dc2626; color: #fff; font-size: 11px; font-weight: 700;
        display: flex; align-items: center; justify-content: center; border: 2px solid #fff;
    }
    .cwa-overlay {
        position: fixed; inset: 0; background: rgba(15,15,30,.55); z-index: 9999;
        display: none; align-items: center; justify-content: center; padding: 16px;
    }
    .cwa-overlay.cwa-open { display: flex; }
    .cwa-modal {
        background: #fff; width: 100%; max-width: 960px; height: 80vh; max-height: 660px;
        border-radius: 18px; overflow: hidden; display: flex; box-shadow: 0 25px 60px rgba(0,0,0,.35);
        animation: cwa-pop .18s ease;
    }
    @keyframes cwa-pop { from { transform: scale(.96); opacity: 0; } to { transform: scale(1); opacity: 1; } }
    .cwa-sidebar { width: 290px; flex: none; background: #f8f8fc; border-right: 1px solid #ececf5; display: flex; flex-direction: column; }
    .cwa-sidebar-head {
        display: flex; align-items: center; justify-content: space-between; padding: 16px;
        font-weight: 800; color: #1e1858; border-bottom: 1px solid #ececf5;
    }
    .cwa-close-btn { background: none; border: none; cursor: pointer; color: #6b7280; display: flex; }
    .cwa-close-btn:hover { color: #1e1858; }
    .cwa-lista { flex: 1; overflow-y: auto; padding: 8px; }
    .cwa-lista-vacio { padding: 24px 12px; text-align: center; color: #9ca3af; font-size: 13px; }
    .cwa-item { padding: 10px 12px; border-radius: 12px; cursor: pointer; margin-bottom: 6px; border: 1px solid transparent; }
    .cwa-item:hover { background: #eeedfb; }
    .cwa-item.cwa-activo { background: #5750ad; }
    .cwa-item.cwa-activo .cwa-item-id,
    .cwa-item.cwa-activo .cwa-item-title,
    .cwa-item.cwa-activo .cwa-item-cliente { color: #fff; }
    .cwa-item-top { display: flex; align-items: center; justify-content: space-between; margin-bottom: 2px; }
    .cwa-item-id { font-size: 11px; font-weight: 800; color: #5750ad; }
    .cwa-item.cwa-activo .cwa-item-id { color: #e5e3fb; }
    .cwa-item-title { font-size: 13px; font-weight: 600; color: #1e1858; line-height: 1.3; }
    .cwa-item-cliente { font-size: 11px; color: #9ca3af; margin-top: 2px; }
    .cwa-item.cwa-activo .cwa-item-cliente { color: #e5e3fb; }
    .cwa-chat { flex: 1; display: flex; flex-direction: column; min-width: 0; }
    .cwa-chat-head { padding: 14px 18px; border-bottom: 1px solid #ececf5; display: flex; align-items: flex-start; justify-content: space-between; gap: 10px; flex-wrap: wrap; }
    .cwa-chat-title { font-weight: 800; color: #1e1858; font-size: 15px; }
    .cwa-chat-sub { font-size: 12px; color: #9ca3af; margin-top: 2px; }
    .cwa-acciones { display: flex; gap: 8px; flex-wrap: wrap; }
    .cwa-accion-btn {
        font-size: 11px; font-weight: 700; padding: 6px 12px; border-radius: 9999px; cursor: pointer;
        border: 1px solid #5750ad; background: #fff; color: #5750ad; white-space: nowrap;
    }
    .cwa-accion-btn:hover { background: #5750ad; color: #fff; }
    .cwa-mensajes { flex: 1; overflow-y: auto; padding: 16px; background: #fbfbfe; display: flex; flex-direction: column; gap: 10px; }
    .cwa-vacio { margin: auto; text-align: center; color: #9ca3af; font-size: 13px; }
    .cwa-fila { display: flex; flex-direction: column; max-width: 75%; }
    .cwa-fila.cwa-propio { align-self: flex-end; align-items: flex-end; }
    .cwa-fila.cwa-ajeno { align-self: flex-start; align-items: flex-start; }
    .cwa-burbuja { padding: 9px 13px; border-radius: 14px; font-size: 13px; line-height: 1.45; word-break: break-word; }
    .cwa-fila.cwa-propio .cwa-burbuja { background: #5750ad; color: #fff; border-bottom-right-radius: 4px; }
    .cwa-fila.cwa-ajeno .cwa-burbuja { background: #eceefc; color: #1e1858; border-bottom-left-radius: 4px; }
    .cwa-remitente { font-size: 11px; font-weight: 700; color: #6b7280; margin-bottom: 2px; }
    .cwa-hora { font-size: 10px; color: #9ca3af; margin-top: 3px; }
    .cwa-input-wrap { border-top: 1px solid #ececf5; padding: 12px; }
    .cwa-input-bar { display: flex; align-items: center; gap: 8px; }
    .cwa-input { flex: 1; border: 1px solid #e5e7eb; border-radius: 9999px; padding: 10px 16px; font-size: 13px; outline: none; }
    .cwa-input:focus { border-color: #5750ad; }
    .cwa-send-btn {
        width: 40px; height: 40px; border-radius: 9999px; border: none; background: #5750ad; color: #fff;
        display: flex; align-items: center; justify-content: center; cursor: pointer; flex: none;
    }
    .cwa-send-btn:hover { opacity: .9; }
    .cwa-closed-banner { font-size: 12px; color: #6b7280; background: #f3f4f6; padding: 10px 14px; border-radius: 10px; text-align: center; }
    @media (max-width: 640px) {
        .cwa-modal { flex-direction: column; height: 90vh; max-height: none; }
        .cwa-sidebar { width: 100%; max-height: 32%; border-right: none; border-bottom: 1px solid #ececf5; }
        .cwa-chat-head { flex-direction: column; }
    }
    `;

    function inyectarEstilos() {
        if (document.getElementById('cwa-styles')) return;
        const style = document.createElement('style');
        style.id = 'cwa-styles';
        style.textContent = ESTILOS;
        document.head.appendChild(style);
    }

    // Construcción del DOM
    function construirDOM() {
        if (document.getElementById('cwa-overlay')) return;

        const fab = document.createElement('button');
        fab.id = 'cwa-fab';
        fab.className = 'cwa-fab';
        fab.title = 'Mensajes';
        fab.innerHTML = `
            <span class="material-symbols-outlined">chat</span>
            <span id="cwa-fab-badge" class="cwa-fab-badge" style="display:none;">0</span>
        `;

        const overlay = document.createElement('div');
        overlay.id = 'cwa-overlay';
        overlay.className = 'cwa-overlay';
        overlay.innerHTML = `
            <div class="cwa-modal">
                <aside class="cwa-sidebar">
                    <div class="cwa-sidebar-head">
                        <span>Conversaciones</span>
                        <button id="cwa-close" class="cwa-close-btn" aria-label="Cerrar">
                            <span class="material-symbols-outlined">close</span>
                        </button>
                    </div>
                    <div id="cwa-lista" class="cwa-lista"></div>
                </aside>
                <section class="cwa-chat">
                    <div class="cwa-chat-head">
                        <div>
                            <div id="cwa-chat-title" class="cwa-chat-title">Selecciona una conversación</div>
                            <div id="cwa-chat-sub" class="cwa-chat-sub"></div>
                        </div>
                        <div id="cwa-acciones" class="cwa-acciones"></div>
                    </div>
                    <div id="cwa-mensajes" class="cwa-mensajes">
                        <div class="cwa-vacio">Elige un ticket para ver la conversación.</div>
                    </div>
                    <div id="cwa-input-wrap" class="cwa-input-wrap"></div>
                </section>
            </div>
        `;

        document.body.appendChild(fab);
        document.body.appendChild(overlay);

        fab.addEventListener('click', () => abrir());
        document.getElementById('cwa-close').addEventListener('click', cerrar);
        overlay.addEventListener('click', (e) => { if (e.target === overlay) cerrar(); });
        document.addEventListener('keydown', (e) => { if (e.key === 'Escape') cerrar(); });
    }

    // Carga de tickets / sidebar (misma fuente que la tabla del panel)
    async function cargarTickets() {
        try {
            const res = await fetch('/api/dashboard/tickets');
            const datos = await res.json();
            TICKETS = Array.isArray(datos.recientes) ? datos.recientes : [];
            yaCargoTickets = true;
            renderLista();
            return TICKETS;
        } catch (error) {
            console.error('[chat_widget_aprovador.js] Error cargando tickets:', error);
            document.getElementById('cwa-lista').innerHTML = '<div class="cwa-lista-vacio">No se pudieron cargar los tickets.</div>';
            return [];
        }
    }

    function renderLista() {
        const cont = document.getElementById('cwa-lista');
        if (TICKETS.length === 0) {
            cont.innerHTML = '<div class="cwa-lista-vacio">No hay tickets recientes.</div>';
            return;
        }
        cont.innerHTML = TICKETS.map(t => `
            <div class="cwa-item${parseInt(t.id_ticket) === activeId ? ' cwa-activo' : ''}" data-id="${t.id_ticket}">
                <div class="cwa-item-top">
                    <span class="cwa-item-id">TK-${String(t.id_ticket).padStart(4, '0')}</span>
                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold ${COLOR_ESTADO[t.estado] || 'bg-gray-100 text-gray-700'}">${t.estado || '—'}</span>
                </div>
                <div class="cwa-item-title">${escHtml(t.titulo || 'Sin título')}</div>
                <div class="cwa-item-cliente">${escHtml(t.cliente || '—')}</div>
            </div>
        `).join('');

        cont.querySelectorAll('.cwa-item').forEach(el => {
            el.addEventListener('click', () => seleccionarTicket(parseInt(el.dataset.id)));
        });
    }

    // Selección de ticket + carga de mensajes
    async function seleccionarTicket(idTicket) {
        activeId = idTicket;
        ultimoIdMensaje = 0;
        detenerPolling();
        renderLista();

        let t = TICKETS.find(x => parseInt(x.id_ticket) === idTicket);

        document.getElementById('cwa-chat-title').textContent = t
            ? `TK-${String(t.id_ticket).padStart(4, '0')} — ${t.titulo}`
            : `Ticket #${idTicket}`;
        document.getElementById('cwa-chat-sub').textContent = t
            ? `Cliente: ${t.cliente || '—'} · ${t.estado || ''}`
            : '';

        renderAcciones(t);
        renderInput(t);

        document.getElementById('cwa-mensajes').innerHTML = '<div class="cwa-vacio">Cargando conversación...</div>';

        try {
            const historial = await peticion(`/api/mensajes/ticket/${idTicket}`);
            ultimoIdMensaje = historial.length > 0 ? historial[historial.length - 1].id_mensaje : 0;
            pintarMensajes(historial);
            await peticion(`/api/mensajes/ticket/${idTicket}/leidos`, 'PATCH');
            iniciarPolling(idTicket);
        } catch (error) {
            console.error('[chat_widget_aprovador.js] Error cargando historial:', error);
            document.getElementById('cwa-mensajes').innerHTML = '<div class="cwa-vacio">No se pudo cargar la conversación.</div>';
        }
    }

    function renderAcciones(t) {
        const cont = document.getElementById('cwa-acciones');
        const cerrado = t && t.estado === 'Cerrado';
        if (cerrado) { cont.innerHTML = ''; return; }
        cont.innerHTML = `
            <button class="cwa-accion-btn" id="cwa-btn-resolver">Marcar resuelto</button>
            <button class="cwa-accion-btn" id="cwa-btn-cerrar">Cerrar ticket</button>
        `;
        document.getElementById('cwa-btn-resolver').addEventListener('click', () => actualizarEstadoTicket('resolver'));
        document.getElementById('cwa-btn-cerrar').addEventListener('click', () => actualizarEstadoTicket('cerrar'));
    }

    function renderInput(t) {
        const cerrado = t && t.estado === 'Cerrado';
        const sinAgente = t && !t.id_usuario_agente;
        const inputWrap = document.getElementById('cwa-input-wrap');
        if (cerrado) {
            inputWrap.innerHTML = `<div class="cwa-closed-banner"><strong>Este ticket está cerrado.</strong></div>`;
            return;
        }
        if (sinAgente) {
            inputWrap.innerHTML = `<div class="cwa-closed-banner"><strong>Agente no asignado.</strong> Asigna un agente desde "Asignación de Tickets" para habilitar el chat.</div>`;
            return;
        }
        inputWrap.innerHTML = `
            <div class="cwa-input-bar">
                <input type="text" id="cwa-input" class="cwa-input" placeholder="Escribe tu mensaje..." autocomplete="off">
                <button id="cwa-send" class="cwa-send-btn" aria-label="Enviar mensaje">
                    <span class="material-symbols-outlined">send</span>
                </button>
            </div>
        `;
        const input = document.getElementById('cwa-input');
        document.getElementById('cwa-send').addEventListener('click', enviarMensaje);
        input.addEventListener('keydown', e => { if (e.key === 'Enter') enviarMensaje(); });
    }

    function pintarMensajes(lista) {
        const cont = document.getElementById('cwa-mensajes');
        if (lista.length === 0) {
            cont.innerHTML = '<div class="cwa-vacio">Aún no hay mensajes en este ticket.</div>';
            return;
        }
        cont.innerHTML = lista.map(construirBurbuja).join('');
        cont.scrollTop = cont.scrollHeight;
    }

    function agregarMensajes(lista) {
        if (lista.length === 0) return;
        const cont = document.getElementById('cwa-mensajes');
        const vacio = cont.querySelector('.cwa-vacio');
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
            <div class="cwa-fila ${esMio ? 'cwa-propio' : 'cwa-ajeno'}">
                ${!esMio ? `<span class="cwa-remitente">${escHtml(m.remitente || 'Usuario')}</span>` : ''}
                <div class="cwa-burbuja">${texto}</div>
                <span class="cwa-hora">${hora}</span>
            </div>
        `;
    }

    function escHtml(s) {
        return String(s).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
    }

    // Envío de mensajes
    async function enviarMensaje() {
        const input = document.getElementById('cwa-input');
        if (!input || !activeId) return;
        const texto = input.value.trim();
        if (!texto) return;

        input.value = '';
        try {
            const mensajeCreado = await peticion('/api/mensajes', 'POST', { id_ticket: activeId, contenido: texto });
            if (!mensajeCreado) {
                const recientes = await peticion(`/api/mensajes/nuevos/${activeId}/${ultimoIdMensaje}`);
                if (Array.isArray(recientes) && recientes.length) {
                    agregarMensajes(recientes);
                }
            } else {
                agregarMensajes([mensajeCreado]);
            }
        } catch (error) {
            console.error('[chat_widget_aprovador.js] Error enviando mensaje:', error);
            input.value = texto;
            alert(error.message || 'No se pudo enviar el mensaje');
        }
    }

    // Acciones rápidas: marcar resuelto / cerrar ticket
    async function actualizarEstadoTicket(tipo) {
        if (!activeId) return;
        if (tipo === 'cerrar' && !confirm('¿Seguro que deseas cerrar este ticket?')) return;

        const accion = tipo === 'resolver' ? 'Ticket marcado como resuelto' : 'Ticket cerrado por el supervisor';
        try {
            await peticion(`/api/tickets/${activeId}/cerrar`, 'PATCH');
            await peticion('/api/historial', 'POST', {
                id_ticket: activeId,
                accion,
                campo_modificado: 'estado',
                valor_anterior: '',
                valor_nuevo: 'Cerrado'
            });

            const t = TICKETS.find(x => parseInt(x.id_ticket) === activeId);
            if (t) t.estado = 'Cerrado';
            document.getElementById('cwa-chat-sub').textContent = t ? `Cliente: ${t.cliente || '—'} · Cerrado` : 'Cerrado';
            renderAcciones(t);
            renderInput(t);
            renderLista();

            if (typeof cargarDatos === 'function') cargarDatos();
            alert(tipo === 'resolver' ? 'Ticket marcado como resuelto.' : 'Ticket cerrado.');
        } catch (error) {
            console.error('[chat_widget_aprovador.js] Error actualizando ticket:', error);
            alert(error.message || 'No se pudo actualizar el ticket');
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
                console.error('[chat_widget_aprovador.js] Error en polling:', error);
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
        document.getElementById('cwa-overlay').classList.add('cwa-open');

        if (!yaCargoTickets) await cargarTickets();

        if (idTicket) {
            seleccionarTicket(parseInt(idTicket));
        } else if (!activeId && TICKETS.length > 0) {
            seleccionarTicket(TICKETS[0].id_ticket);
        }
    }

    function cerrar() {
        const overlay = document.getElementById('cwa-overlay');
        if (overlay) overlay.classList.remove('cwa-open');
        detenerPolling();
    }

    window.ChatWidgetAprobador = { abrir, cerrar };

    // Construye el ícono flotante apenas carga la página (el modal se llena al abrirlo)
    document.addEventListener('DOMContentLoaded', () => {
        inyectarEstilos();
        construirDOM();
    });
})();
