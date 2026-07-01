<?php
define('ROL_REQUERIDO', 4);
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
<title>Mesa de Servicio — Supervisor | ServiceCore</title>
<link rel="icon" type="image/png" href="../../img/LogoNav.png">
<script src="https://cdn.tailwindcss.com"></script>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@3.x/tabler-icons.min.css">
<link rel="stylesheet" href="../../css/chat_aprovador.css">
</head>
<body>
<div class="app">

  <!-- ═══ SIDEBAR ═══ -->
  <aside class="sidebar">
    <div class="sb-head">
      <div class="sb-logo">
        <div class="sb-logo-icon"><i class="ti ti-ticket" aria-hidden="true"></i></div>
        <div>
          <div class="sb-logo-name">ServiceCore</div>
          <div class="sb-logo-sub">Supervisión</div>
        </div>
      </div>
      <div class="sb-search">
        <i class="ti ti-search" aria-hidden="true"></i>
        <input type="text" id="sbSearch" placeholder="Buscar ticket..." autocomplete="off">
      </div>
    </div>

    <div class="sb-section">Todos los tickets</div>
    <div class="sb-list" id="sbList">
      <!-- Renderizado por JS -->
    </div>

    <div class="sb-footer">
      <div class="sb-user">
        <div class="av av-sm" style="background:#5750ad;color:#e0e7ff;"><?= $inicialUsuario ?></div>
        <div>
          <div class="sb-uname"><?= $nombreUsuario ?></div>
          <div class="sb-urole">Supervisor</div>
        </div>
        <a href="logout.php" class="sb-logout" title="Cerrar sesión"><i class="ti ti-logout" aria-hidden="true"></i></a>
      </div>
    </div>
  </aside>

  <!-- ═══ MAIN ═══ -->
  <div class="main">

    <!-- Top bar -->
    <div class="topbar">
      <div class="tb-info">
        <div class="tb-ticket-id" id="tk-id">TK-0041</div>
        <div class="tb-ticket-title" id="tk-title">Pantalla negra al iniciar la aplicación</div>
      </div>
      <div class="tb-badges">
        <span class="badge badge-cat" id="tk-cat">Soporte técnico</span>
        <span class="badge" id="tk-pri">Alta</span>
        <span class="badge" id="tk-est">Abierto</span>
      </div>
      <div class="tb-btn" title="Más opciones"><i class="ti ti-dots-vertical" aria-hidden="true"></i></div>
    </div>

    <!-- Agent toolbar -->
    <div class="agent-toolbar">
      <button class="atool success" onclick="markResolved()"><i class="ti ti-circle-check" aria-hidden="true"></i>Marcar resuelto</button>
      <button class="atool" onclick="openReassign()"><i class="ti ti-transfer" aria-hidden="true"></i>Reasignar</button>
      <button class="atool" onclick="openNote()"><i class="ti ti-note" aria-hidden="true"></i>Nota interna</button>
      <button class="atool danger" onclick="closeTicket()"><i class="ti ti-circle-x" aria-hidden="true"></i>Cerrar ticket</button>
    </div>

    <!-- Body -->
    <div class="body">

      <!-- Detail column -->
      <div class="detail-col">
        <div class="dc-block">
          <div class="dc-label">Cliente</div>
          <div class="dc-user-row">
            <div class="av av-sm" id="dc-client-av" style="background:#dcfce7;color:#166534;">CL</div>
            <div>
              <div class="dc-name" id="dc-client-name">Carlos López</div>
              <div class="dc-email" id="dc-client-email">carlos@empresa.com</div>
            </div>
          </div>
          <div class="dc-online">
            <div class="dot-online" id="dc-client-dot"></div>
            <span id="dc-client-status">En línea</span>
          </div>
        </div>
        <div class="dc-block">
          <div class="dc-label">Agente asignado</div>
          <div class="dc-name" id="dc-agent-name">Sin asignar</div>
        </div>
        <div class="dc-block">
          <div class="dc-label">Ticket</div>
          <div class="dc-stat"><span class="dc-stat-label">Abierto</span><span class="dc-stat-val" id="dc-opened">Hace 2h</span></div>
          <div class="dc-stat"><span class="dc-stat-label">Vence</span><span class="dc-stat-val danger" id="dc-due">En 4h</span></div>
          <div class="dc-stat"><span class="dc-stat-label">Mensajes</span><span class="dc-stat-val" id="dc-count">3</span></div>
        </div>
        <div class="dc-block">
          <div class="dc-label">Estado</div>
          <span class="badge" id="dc-est">Abierto</span>
        </div>
        <div class="dc-block">
          <div class="dc-label">Prioridad</div>
          <span class="badge" id="dc-pri">Alta</span>
        </div>
        <div class="dc-block">
          <div class="dc-label">Categoría</div>
          <span class="badge badge-cat" id="dc-cat">Soporte técnico</span>
        </div>
      </div>

      <!-- Chat -->
      <div class="chat-col">
        <div class="msgs" id="msgs">
          <!-- Renderizado por JS -->
        </div>
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

<!-- ═══ MODAL NOTA INTERNA ═══ -->
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

<!-- ═══ MODAL REASIGNAR ═══ -->
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

<script src="js/api.js"></script>
<script>
const idUsuarioActual = <?= $idUsuario ?>;
const idTicketInicial = <?= $idTicketInicial ?>;

let TICKETS  = [];   // se llena desde /api/tickets (vista completa de supervisor)
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
        const datos = await peticion('/api/tickets');
        TICKETS = Array.isArray(datos) ? datos : [];
        renderSidebar();

        if (TICKETS.length === 0) {
            mostrarChatVacio('No hay tickets registrados en el sistema.');
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
    $('sbList').innerHTML = `<div class="sb-empty">Sin tickets registrados</div>`;
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
    $('dc-agent-name').textContent   = t.agente || 'Sin asignar';

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
    const datos = await peticion('/api/tickets');
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

cargarAgentesDisponibles();
cargarTicketsAgente();
</script>
</body>
</html>
