<?php
define('ROL_REQUERIDO', 3);
require_once '../../seguridad.php';
$nombreUsuario   = htmlspecialchars($_SESSION['nombre']);
$idUsuario       = (int)$_SESSION['usuario_id'];
$inicialUsuario  = mb_strtoupper(mb_substr($_SESSION['nombre'], 0, 1));
$idTicketInicial = (int)($_GET['ticket'] ?? 0);
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Mesa de Servicio — Agente | ServiceCore</title>
<link rel="icon" type="image/png" href="../../img/LogoNav.png">
<script src="https://cdn.tailwindcss.com"></script>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" rel="stylesheet"/>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@3.x/tabler-icons.min.css">
<link rel="stylesheet" href="../../css/chat_agente.css">
</head>
<body class="min-h-screen bg-[#f5f7ff]">
    <header class="fixed top-0 left-64 right-0 h-16 bg-white shadow flex items-center justify-between px-8 z-50">
        <div class="flex items-center gap-4">
            <span class="material-symbols-outlined text-[#5750ad]">menu</span>
            <h1 class="text-xl font-bold text-[#1e1858]">Panel de Agente</h1>
        </div>
        <div class="relative flex items-center gap-4">
            <span class="material-symbols-outlined cursor-pointer">notifications</span>
            <div class="text-right">
                <p class="font-bold"><?= $nombreUsuario ?></p>
                <p class="text-sm text-gray-500">Agente</p>
            </div>
            <div id="botonUsuario" class="w-10 h-10 rounded-full cursor-pointer border-2 border-[#5750ad] bg-[#5750ad] flex items-center justify-center text-white font-bold">
                <?= $inicialUsuario ?>
            </div>
            <div id="menuUsuario" class="hidden absolute right-0 top-14 w-52 bg-white rounded-xl shadow-lg border border-gray-200 overflow-hidden z-50">
                <a href="#" class="flex items-center gap-3 px-4 py-3 hover:bg-gray-100 transition">
                    <span class="material-symbols-outlined text-gray-600">settings</span>Configuración
                </a>
                <a href="perfil.php" class="flex items-center gap-3 px-4 py-3 hover:bg-gray-100 transition">
                    <span class="material-symbols-outlined text-gray-600">person</span>Perfil
                </a>
                <a href="../../logout.php" class="flex items-center gap-3 px-4 py-3 hover:bg-red-50 text-red-600 transition">
                    <span class="material-symbols-outlined">logout</span>Cerrar Sesión
                </a>
            </div>
        </div>
    </header>

    <aside class="fixed left-0 top-0 w-64 h-screen bg-[#1e1858] text-white p-6 flex flex-col">
        <div class="flex flex-col items-center mb-8">
            <img src="../../img/logoSC.png" alt="Logo" class="w-20 h-20 object-contain mb-4">
            <h6 class="text-lg font-bold text-center leading-6">ServiceCore<br>Corporation</h6>
        </div>
        <nav class="flex flex-col flex-1 gap-2">
            <a href="dashboard_agente.php" class="menu-item">
                <span class="material-symbols-outlined">dashboard</span>Inicio
            </a>
            <a href="chat_agente.php" class="menu-item activo">
                <span class="material-symbols-outlined">confirmation_number</span>Tickets
            </a>
            <a href="#" class="menu-item">
                <span class="material-symbols-outlined">history</span>Historial
            </a>
            <a href="#" class="menu-item">
                <span class="material-symbols-outlined">insights</span>Estadísticas
            </a>
        </nav>
        <a href="../../logout.php" class="mt-auto flex items-center justify-center gap-3 w-full py-3 rounded-xl border-2 border-red-500 text-red-400 font-semibold transition-all duration-300 hover:bg-red-500 hover:text-white hover:shadow-lg">
            <span class="material-symbols-outlined">logout</span>
            Cerrar Sesión
        </a>
    </aside>

    <main class="contenido ml-64 pt-24 px-8 pb-10">
        <section class="mb-6">
            <h2 class="text-4xl font-bold text-[#1e1858]">Mesa de Servicio</h2>
            <p class="text-gray-500 mt-2">Atiende y da seguimiento a los tickets asignados.</p>
        </section>

        <section class="grid grid-cols-12 gap-6">
            <div class="col-span-12 xl:col-span-4">
                <div class="tarjeta p-0 overflow-hidden">
                    <div class="p-5 border-b border-gray-200">
                        <h3 class="text-lg font-semibold text-[#1e1858]">Tickets asignados</h3>
                        <div class="mt-3 flex items-center gap-2 rounded-xl border border-gray-200 bg-gray-50 px-3 py-2">
                            <span class="material-symbols-outlined text-gray-400">search</span>
                            <input id="sbSearch" type="text" placeholder="Buscar ticket..." class="w-full bg-transparent outline-none text-sm" autocomplete="off">
                        </div>
                    </div>
                    <div id="sbList" class="max-h-[560px] overflow-y-auto p-3"></div>
                </div>
            </div>

            <div class="col-span-12 xl:col-span-8">
                <div class="tarjeta p-0 overflow-hidden">
                    <div class="flex flex-col gap-4 border-b border-gray-200 p-5 lg:flex-row lg:items-center lg:justify-between">
                        <div class="flex items-center gap-3">
                            <div class="rounded-full bg-purple-50 px-3 py-1 text-sm font-bold text-[#5750ad]" id="tk-id">TK-0001</div>
                            <div>
                                <h3 class="font-semibold text-[#1e1858]" id="tk-title">Selecciona un ticket</h3>
                                <div class="mt-1 flex flex-wrap items-center gap-2">
                                    <span class="badge badge-cat" id="tk-cat">General</span>
                                    <span class="badge" id="tk-pri">—</span>
                                    <span class="badge badge-abierto" id="tk-est">Abierto</span>
                                </div>
                            </div>
                        </div>
                        <div class="flex flex-wrap gap-2">
                            <button class="atool success" onclick="markResolved()"><i class="ti ti-circle-check" aria-hidden="true"></i>Marcar resuelto</button>
                            <button class="atool" onclick="openReassign()"><i class="ti ti-transfer" aria-hidden="true"></i>Reasignar</button>
                            <button class="atool" onclick="openNote()"><i class="ti ti-note" aria-hidden="true"></i>Nota interna</button>
                            <button class="atool danger" onclick="closeTicket()"><i class="ti ti-circle-x" aria-hidden="true"></i>Cerrar ticket</button>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 xl:grid-cols-[0.9fr_1.1fr]">
                        <div class="border-b border-gray-200 p-5 xl:border-b-0 xl:border-r">
                            <div class="flex items-center justify-between mb-4">
                                <h4 class="font-semibold text-[#1e1858]">Detalle del cliente</h4>
                                <div class="flex items-center gap-2 text-sm text-gray-500">
                                    <span class="dot-online" id="dc-client-dot"></span>
                                    <span id="dc-client-status">En línea</span>
                                </div>
                            </div>
                            <div class="space-y-4">
                                <div class="rounded-2xl bg-gray-50 p-4">
                                    <div class="flex items-center gap-3">
                                        <div class="av av-sm" id="dc-client-av" style="background:#dcfce7;color:#166534;">CL</div>
                                        <div>
                                            <div class="font-semibold text-[#1e1858]" id="dc-client-name">Sin cliente</div>
                                            <div class="text-sm text-gray-500" id="dc-client-email">—</div>
                                        </div>
                                    </div>
                                </div>
                                <div class="rounded-2xl border border-gray-200 p-4 space-y-3">
                                    <div class="flex items-center justify-between text-sm">
                                        <span class="text-gray-500">Estado</span>
                                        <span class="badge badge-abierto" id="dc-est">Abierto</span>
                                    </div>
                                    <div class="flex items-center justify-between text-sm">
                                        <span class="text-gray-500">Prioridad</span>
                                        <span class="badge" id="dc-pri">—</span>
                                    </div>
                                    <div class="flex items-center justify-between text-sm">
                                        <span class="text-gray-500">Categoría</span>
                                        <span class="badge badge-cat" id="dc-cat">General</span>
                                    </div>
                                </div>
                                <div class="rounded-2xl border border-gray-200 p-4">
                                    <div class="flex items-center justify-between text-sm mb-2">
                                        <span class="text-gray-500">Creado</span>
                                        <span class="font-medium text-[#1e1858]" id="dc-opened">—</span>
                                    </div>
                                    <div class="flex items-center justify-between text-sm mb-2">
                                        <span class="text-gray-500">Vence</span>
                                        <span class="font-medium text-red-500" id="dc-due">—</span>
                                    </div>
                                    <div class="flex items-center justify-between text-sm">
                                        <span class="text-gray-500">Mensajes</span>
                                        <span class="font-semibold text-[#1e1858]" id="dc-count">0</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="flex flex-col p-5">
                            <div class="msgs" id="msgs"></div>
                            <div class="input-bar">
                                <div class="input-tools">
                                    <div class="itool" title="Adjuntar archivo"><i class="ti ti-paperclip" aria-hidden="true"></i></div>
                                    <div class="itool" title="Agregar imagen"><i class="ti ti-photo" aria-hidden="true"></i></div>
                                </div>
                                <input class="ibox" type="text" id="msgInput" placeholder="Escribe una respuesta al cliente..." autocomplete="off">
                                <button class="send-btn" onclick="sendMsg()" aria-label="Enviar mensaje"><i class="ti ti-send" aria-hidden="true"></i></button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <footer class="text-center text-gray-500 text-sm border-t mt-10 p-4 bg-white rounded-xl">
        <p>© 2026 ServiceCore Corporation</p>
    </footer>

    <div class="backdrop" id="modalNote">
        <div class="modal">
            <div class="modal-title">Nota interna</div>
            <div class="modal-desc">Esta nota solo es visible para el equipo de agentes.</div>
            <div class="frow">
                <label class="flabel">Nota</label>
                <textarea class="ftextarea" id="noteText" placeholder="Escribe aquí tu nota interna..."></textarea>
            </div>
            <div class="modal-foot">
                <button class="btn-cancel" onclick="closeNote()">Cancelar</button>
                <button class="btn-primary" onclick="saveNote()"><i class="ti ti-note" aria-hidden="true"></i>Guardar nota</button>
            </div>
        </div>
    </div>

    <div class="backdrop" id="modalReassign">
        <div class="modal">
            <div class="modal-title">Reasignar ticket</div>
            <div class="modal-desc">Selecciona el agente al que deseas transferir este ticket.</div>
            <div class="frow">
                <label class="flabel">Agente</label>
                <select class="finput" id="reassignAgent">
                    <option value="">Seleccionar agente...</option>
                </select>
            </div>
            <div class="frow">
                <label class="flabel">Motivo (opcional)</label>
                <textarea class="ftextarea" id="reassignReason" placeholder="Ej: Especialista en el área requerida..." style="min-height:60px"></textarea>
            </div>
            <div class="modal-foot">
                <button class="btn-cancel" onclick="closeReassign()">Cancelar</button>
                <button class="btn-primary" onclick="confirmReassign()"><i class="ti ti-transfer" aria-hidden="true"></i>Reasignar</button>
            </div>
        </div>
    </div>

    <div class="toasts" id="toasts"></div>

    <script src="../../js/api.js"></script>
    <script src="../../js/dashboard_admin.js"></script>
    <script>
const idUsuarioActual = <?= $idUsuario ?>;
const idTicketInicial = <?= $idTicketInicial ?>;

let TICKETS  = [];   // se llena desde /api/tickets/agente/:id
let AGENTES  = [];   // se llena desde /api/usuarios/rol/4
let activeId = null; // id_ticket actualmente seleccionado

const $ = id => document.getElementById(id);

function claseDesdeNombre(nombre, mapa) {
    const clave = (nombre || '').toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g, '');
    return mapa[clave] || '';
}

const EST_CLASS = { abierto: 'badge-abierto', pendiente: 'badge-abierto', 'en proceso': 'badge-proceso', cerrado: 'badge-cerrado', cancelado: 'badge-cerrado' };
const PRI_CLASS = { alta: 'badge-alta', media: 'badge-media', baja: 'badge-baja' };
const CHIP_CAT  = { soporte: 'chip-soporte', infraestructura: 'chip-infra', seguridad: 'chip-seguridad', facturacion: 'chip-facturacion', accesos: 'chip-accesos' };
const COLORES_AVATAR = ['#dcfce7,#166534', '#dbeafe,#1e40af', '#fef9c3,#854d0e', '#f3e8ff,#6b21a8', '#ffedd5,#9a3412'];

async function cargarTicketsAgente() {
    try {
        const datos = await peticion(`/api/tickets/agente/${idUsuarioActual}`);
        TICKETS = Array.isArray(datos) ? datos : [];
        renderSidebar();

        if (TICKETS.length === 0) {
            mostrarChatVacio('No tienes tickets asignados por el momento.');
            return;
        }

        const ticketUrl = TICKETS.find(t => parseInt(t.id_ticket) === idTicketInicial);
        selectTicket(ticketUrl ? ticketUrl.id_ticket : TICKETS[0].id_ticket);
    } catch (error) {
        console.error('Error cargando tickets:', error);
        mostrarChatVacio('No se pudieron cargar los tickets.');
    }
}

function mostrarChatVacio(texto) {
    $('msgs').innerHTML = `<div class="estado-vacio"><i class="ti ti-inbox" aria-hidden="true"></i><span>${texto}</span></div>`;
    $('sbList').innerHTML = `<div class="sb-empty">Sin tickets asignados</div>`;
}

function renderSidebar(filtro = '') {
    const lista = $('sbList');
    const filtrados = filtro
        ? TICKETS.filter(t =>
            String(t.id_ticket).includes(filtro) ||
            (t.titulo || '').toLowerCase().includes(filtro.toLowerCase()) ||
            (t.categoria || '').toLowerCase().includes(filtro.toLowerCase()))
        : TICKETS;

    if (filtrados.length === 0) {
        lista.innerHTML = `<div style="padding:20px 10px;text-align:center;font-size:12px;color:#6366f1;">Sin resultados</div>`;
        return;
    }

    lista.innerHTML = filtrados.map(t => {
        const clasePrio  = claseDesdeNombre(t.prioridad, PRI_CLASS) || 'badge-media';
        const colorPunto = t.estado === 'Cerrado' ? '#6b7280' : (t.estado === 'En proceso' ? '#f59e0b' : '#22c55e');
        return `
        <div class="sb-item${parseInt(t.id_ticket) === activeId ? ' active' : ''}" data-id="${t.id_ticket}" onclick="selectTicket(${t.id_ticket})">
            <div class="sb-item-top">
                <span class="sb-id">TK-${String(t.id_ticket).padStart(4, '0')}</span>
                <span class="sb-dot" style="background:${colorPunto}"></span>
            </div>
            <div class="sb-title">${t.titulo}</div>
            <div class="sb-chips">
                <span class="chip ${CHIP_CAT[(t.categoria || '').toLowerCase()] || 'chip-soporte'}">${t.categoria || 'General'}</span>
                <span class="chip ${clasePrio.replace('badge-', 'chip-')}">${t.prioridad || '—'}</span>
            </div>
        </div>`;
    }).join('');
}

async function selectTicket(idTicket) {
    activeId = parseInt(idTicket);
    const t = TICKETS.find(x => parseInt(x.id_ticket) === activeId);
    if (!t) return;

    renderSidebar($('sbSearch').value);
    renderTopBar(t);
    renderDetail(t);

    await cargarHistorialChat(activeId);
    iniciarPollingChatAgente(activeId);
}

function renderTopBar(t) {
    $('tk-id').textContent    = `TK-${String(t.id_ticket).padStart(4, '0')}`;
    $('tk-title').textContent = t.titulo;
    $('tk-cat').textContent   = t.categoria || 'General';
    setBadge('tk-pri', t.prioridad || '—', claseDesdeNombre(t.prioridad, PRI_CLASS));
    setBadge('tk-est', t.estado || '—', claseDesdeNombre(t.estado, EST_CLASS));
}

function renderDetail(t) {
    $('dc-client-name').textContent  = t.cliente || 'Sin cliente';
    $('dc-client-email').textContent = t.correo_cliente || '';
    $('dc-client-av').textContent    = t.cliente ? t.cliente.split(' ').map(w => w[0]).join('').substring(0, 2).toUpperCase() : '?';
    $('dc-client-dot').className     = 'dot-online';
    $('dc-client-status').textContent = 'Cliente';

    $('dc-opened').textContent = new Date(t.fecha_creacion).toLocaleDateString('es-GT', { day: '2-digit', month: 'short', hour: '2-digit', minute: '2-digit' });
    $('dc-due').textContent    = t.estado === 'Cerrado' ? 'Resuelto' : '—';
    $('dc-count').textContent  = document.querySelectorAll('#msgs .msg-row').length;

    setBadge('dc-est', t.estado || '—', claseDesdeNombre(t.estado, EST_CLASS));
    setBadge('dc-pri', t.prioridad || '—', claseDesdeNombre(t.prioridad, PRI_CLASS));
    $('dc-cat').textContent = t.categoria || 'General';
}

function setBadge(id, texto, clase) {
    const el = $(id);
    el.textContent = texto;
    el.className = 'badge ' + (clase || 'badge-abierto');
}

let ultimoIdMensaje = 0;

async function cargarHistorialChat(idTicket) {
    try {
        const historial = await peticion(`/api/mensajes/ticket/${idTicket}`);
        ultimoIdMensaje = historial.length > 0 ? historial[historial.length - 1].id_mensaje : 0;
        pintarMensajes(historial);
        await peticion(`/api/mensajes/ticket/${idTicket}/leidos`, 'PATCH');
        $('dc-count').textContent = historial.length;
    } catch (error) {
        $('msgs').innerHTML = `<div class="estado-vacio"><i class="ti ti-message-circle" aria-hidden="true"></i><span>No se pudo cargar la conversación.</span></div>`;
    }
}

function pintarMensajes(lista) {
    const contenedor = $('msgs');
    if (lista.length === 0) {
        contenedor.innerHTML = `<div class="msg-sys">Aún no hay mensajes en este ticket</div>`;
        return;
    }
    contenedor.innerHTML = lista.map(m => construirFilaMensaje(m)).join('');
    contenedor.scrollTop = contenedor.scrollHeight;
}

function agregarMensajesAlChat(lista) {
    if (lista.length === 0) return;
    const contenedor = $('msgs');
    if (contenedor.children.length === 1 && contenedor.querySelector('.msg-sys')) contenedor.innerHTML = '';
    contenedor.insertAdjacentHTML('beforeend', lista.map(m => construirFilaMensaje(m)).join(''));
    contenedor.scrollTop = contenedor.scrollHeight;
    ultimoIdMensaje = lista[lista.length - 1].id_mensaje;
    $('dc-count').textContent = document.querySelectorAll('#msgs .msg-row').length;
}

function construirFilaMensaje(m) {
    const esMio = parseInt(m.id_usuario) === idUsuarioActual;
    const hora  = new Date(m.fecha_envio).toLocaleTimeString('es', { hour: '2-digit', minute: '2-digit' });
    const texto = escHtml(m.contenido);

    if (esMio) {
        return `<div class="msg-row right">
            <div class="msg-meta right">
                <span class="msg-sender-name">${escHtml(m.remitente || 'Tú')}</span>
                <span class="msg-role-badge msg-role-agent">agente</span>
            </div>
            <div class="bubble from-me">${texto}</div>
            <div class="msg-time right">${hora}</div>
        </div>`;
    }

    const ini  = (m.remitente || '?').split(' ').map(w => w[0]).join('').substring(0, 2).toUpperCase();
    const colores = COLORES_AVATAR[1].split(',');
    return `<div class="msg-row">
        <div class="msg-meta">
            <div class="av av-sm" style="background:${colores[0]};color:${colores[1]};">${ini}</div>
            <span class="msg-sender-name">${escHtml(m.remitente || 'Cliente')}</span>
            <span class="msg-role-badge msg-role-client">cliente</span>
        </div>
        <div class="bubble from-client-recv">${texto}</div>
        <div class="msg-time">${hora}</div>
    </div>`;
}

async function sendMsg() {
    const inp = $('msgInput');
    const texto = inp.value.trim();
    if (!texto || !activeId) return;

    inp.value = '';
    try {
        const mensajeCreado = await peticion('/api/mensajes', 'POST', { id_ticket: activeId, contenido: texto });
        agregarMensajesAlChat([mensajeCreado]);
    } catch (error) {
        toast(error.message || 'No se pudo enviar el mensaje', 'err');
        inp.value = texto;
    }
}

let intervaloPollingAgente = null;

function iniciarPollingChatAgente(idTicket) {
    detenerPollingChatAgente();
    intervaloPollingAgente = setInterval(async () => {
        if (activeId !== idTicket) return;
        try {
            const nuevos = await peticion(`/api/mensajes/nuevos/${idTicket}/${ultimoIdMensaje}`);
            if (nuevos.length > 0) {
                agregarMensajesAlChat(nuevos);
                await peticion(`/api/mensajes/ticket/${idTicket}/leidos`, 'PATCH');
            }
        } catch (error) {
            console.error('Error en polling:', error);
        }
    }, 4000);
}

function detenerPollingChatAgente() {
    if (intervaloPollingAgente) { clearInterval(intervaloPollingAgente); intervaloPollingAgente = null; }
}

async function markResolved() {
    if (!activeId) return;
    try {
        await peticion(`/api/tickets/${activeId}/cerrar`, 'PATCH');
        await registrarHistorialAccion('Ticket marcado como resuelto');
        await refrescarTicketActivo();
        toast('Ticket marcado como resuelto.', 'ok');
    } catch (error) {
        toast(error.message || 'No se pudo resolver el ticket', 'err');
    }
}

async function closeTicket() {
    if (!activeId) return;
    if (!confirm('¿Seguro que deseas cerrar este ticket?')) return;
    try {
        await peticion(`/api/tickets/${activeId}/cerrar`, 'PATCH');
        await registrarHistorialAccion('Ticket cerrado por el agente');
        await refrescarTicketActivo();
        toast('Ticket cerrado.', 'info');
    } catch (error) {
        toast(error.message || 'No se pudo cerrar el ticket', 'err');
    }
}

async function refrescarTicketActivo() {
    const datos = await peticion(`/api/tickets/agente/${idUsuarioActual}`);
    TICKETS = Array.isArray(datos) ? datos : [];
    const t = TICKETS.find(x => parseInt(x.id_ticket) === activeId);
    if (t) { renderTopBar(t); renderDetail(t); }
    renderSidebar($('sbSearch').value);
}

async function registrarHistorialAccion(accion) {
    try {
        await peticion('/api/historial', 'POST', {
            id_ticket: activeId,
            accion: accion,
            campo_modificado: 'estado',
            valor_anterior: '',
            valor_nuevo: 'Cerrado'
        });
    } catch (error) {
        console.error('No se pudo registrar el historial:', error);
    }
}

function openNote() { $('modalNote').classList.add('open'); $('noteText').focus(); }
function closeNote() { $('modalNote').classList.remove('open'); $('noteText').value = ''; }

async function saveNote() {
    const txt = $('noteText').value.trim();
    if (!txt) { toast('Escribe una nota primero.', 'err'); return; }
    try {
        await peticion('/api/historial', 'POST', {
            id_ticket: activeId,
            accion: 'Nota interna',
            campo_modificado: 'nota',
            valor_anterior: '',
            valor_nuevo: txt
        });
        closeNote();
        toast('Nota interna guardada.', 'ok');
    } catch (error) {
        toast(error.message || 'No se pudo guardar la nota', 'err');
    }
}

async function cargarAgentesDisponibles() {
    try {
        AGENTES = await peticion('/api/usuarios/rol/4');
        $('reassignAgent').innerHTML = '<option value="">Seleccionar agente...</option>' +
            AGENTES.filter(a => parseInt(a.id_usuario) !== idUsuarioActual)
                   .map(a => `<option value="${a.id_usuario}">${a.nombre}</option>`).join('');
    } catch (error) {
        console.error('Error cargando agentes:', error);
    }
}

function openReassign() { $('modalReassign').classList.add('open'); }
function closeReassign() { $('modalReassign').classList.remove('open'); }

async function confirmReassign() {
    const idAgente = $('reassignAgent').value;
    if (!idAgente) { toast('Selecciona un agente.', 'err'); return; }

    try {
        await peticion(`/api/tickets/${activeId}/asignar`, 'PATCH', { id_agente: parseInt(idAgente) });
        const nombreAgente = AGENTES.find(a => parseInt(a.id_usuario) === parseInt(idAgente))?.nombre || 'otro agente';
        await registrarHistorialAccion(`Ticket reasignado a ${nombreAgente}`);
        closeReassign();
        toast(`Ticket reasignado a ${nombreAgente}.`, 'ok');
        await cargarTicketsAgente();
    } catch (error) {
        toast(error.message || 'No se pudo reasignar el ticket', 'err');
    }
}

function escHtml(s) {
    return String(s).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
}

function toast(msg, type = 'info') {
    const ico = type === 'ok' ? 'ti-circle-check' : type === 'err' ? 'ti-alert-circle' : 'ti-info-circle';
    const el = document.createElement('div');
    el.className = `toast ${type}`;
    el.innerHTML = `<i class="ti ${ico}" aria-hidden="true"></i><span>${msg}</span>`;
    $('toasts').appendChild(el);
    setTimeout(() => el.remove(), 3500);
}

$('msgInput').addEventListener('keydown', e => { if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); sendMsg(); } });
$('sbSearch').addEventListener('input', e => renderSidebar(e.target.value));
document.addEventListener('keydown', e => { if (e.key === 'Escape') { closeNote(); closeReassign(); } });
$('modalNote').addEventListener('click', e => { if (e.target === $('modalNote')) closeNote(); });
$('modalReassign').addEventListener('click', e => { if (e.target === $('modalReassign')) closeReassign(); });

const botonUsuario = document.getElementById('botonUsuario');
const menuUsuario = document.getElementById('menuUsuario');
if (botonUsuario && menuUsuario) {
    botonUsuario.addEventListener('click', () => menuUsuario.classList.toggle('hidden'));
    document.addEventListener('click', (e) => {
        if (!botonUsuario.contains(e.target) && !menuUsuario.contains(e.target)) {
            menuUsuario.classList.add('hidden');
        }
    });
}

cargarAgentesDisponibles();
cargarTicketsAgente();
</script>
</body>
</html>
