<?php
define('ROL_REQUERIDO', 5);
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
<title>Mis Solicitudes | ServiceCore</title>
<link rel="icon" type="image/png" href="../../img/LogoNav.png">
<script src="https://cdn.tailwindcss.com"></script>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@3.x/tabler-icons.min.css">
<link rel="stylesheet" href="../../css/chat_cliente.css">
</head>
<body>
<div class="app">

  <!-- ENCABEZADO -->
    <header class="fixed top-0 left-64 right-0 h-16 bg-white shadow flex items-center justify-between px-8 z-50">
        <div class="flex items-center gap-4">
            <span class="material-symbols-outlined text-[#5750ad]">menu</span>
            <h1 class="text-xl font-bold text-[#1e1858]">Mesa de Ayuda</h1>
        </div>
        <div class="relative flex items-center gap-4">
            <span class="material-symbols-outlined cursor-pointer">notifications</span>
            <div class="text-right">
                <p class="font-bold"><?= $nombreUsuario ?></p>
                <p class="text-sm text-gray-500">Cliente</p>
            </div>
            <div id="botonUsuario" class="w-10 h-10 rounded-full cursor-pointer border-2 border-[#5750ad] bg-[#5750ad] flex items-center justify-center text-white font-bold">
                <?= mb_strtoupper(mb_substr($_SESSION['nombre'], 0, 1)) ?>
            </div>
            <div id="menuUsuario" class="hidden absolute right-0 top-14 w-52 bg-white rounded-xl shadow-lg border border-gray-200 overflow-hidden z-50">
                <a href="perfil.php" class="flex items-center gap-3 px-4 py-3 hover:bg-gray-100 transition">
                    <span class="material-symbols-outlined text-gray-600">person</span>Perfil
                </a>
                <a href="../../logout.php" class="flex items-center gap-3 px-4 py-3 hover:bg-red-50 text-red-600 transition">
                    <span class="material-symbols-outlined">logout</span>Cerrar Sesión
                </a>
            </div>
        </div>
    </header>

    <!-- MENÚ LATERAL -->
    <aside class="fixed left-0 top-0 w-64 h-full bg-[#1e1858] text-white p-6">
        <div class="flex flex-col items-center mb-8">
            <img src="img/logoSC.png" alt="Logo" class="w-20 h-20 object-contain mb-4">
            <h6 class="text-lg font-bold text-center leading-6">ServiceCore<br>Corporation</h6>
        </div>
        <nav class="space-y-2">
            <a href="dashboard_cliente.php" class="menu-item activo">
                <span class="material-symbols-outlined">confirmation_number</span>Mis Tickets
            </a>
            <a href="#" class="menu-item">
                <span class="material-symbols-outlined">history</span>Historial
            </a>
        </nav>
    </aside>

  <!-- ═══ MAIN ═══ -->
  <div class="main">

    <!-- Top bar -->
    <div class="topbar">
      <div>
        <div class="tb-ticket-id" id="tk-id">TK-0041</div>
        <div class="tb-ticket-title" id="tk-title">Pantalla negra al iniciar la aplicación</div>
      </div>
      <div class="tb-badges">
        <span class="badge badge-cat" id="tk-cat">Soporte técnico</span>
        <span class="badge" id="tk-pri">Alta</span>
        <span class="badge" id="tk-est">Abierto</span>
      </div>
    </div>

    <!-- Body -->
    <div class="body">

      <!-- Detail column -->
      <div class="detail-col">
        <div class="dc-block">
          <div class="dc-label">Mi agente</div>
          <div class="dc-user-row">
            <div class="av av-sm" id="dc-agent-av" style="background:#ede9fe;color:#3730a3;">JM</div>
            <div>
              <div class="dc-name" id="dc-agent-name">Juan Martínez</div>
              <div class="dc-sub" id="dc-agent-area">Soporte técnico</div>
            </div>
          </div>
          <div class="dc-online">
            <div class="dot-online" id="dc-agent-dot"></div>
            <span id="dc-agent-status">En línea</span>
          </div>
        </div>
        <div class="dc-block">
          <div class="dc-label">Mi solicitud</div>
          <div class="dc-stat"><span class="dc-stat-label">Abierto</span><span class="dc-stat-val" id="dc-opened">Hace 2h</span></div>
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
        <div class="dc-block" id="ratingBlock" style="display:none">
          <div class="dc-label">Calificar atención</div>
          <div style="font-size:11px;color:var(--text-2);margin-bottom:6px;">¿Cómo fue tu experiencia?</div>
          <div class="rating-row" id="ratingRow">
            <span class="star" data-r="1">&#9733;</span>
            <span class="star" data-r="2">&#9733;</span>
            <span class="star" data-r="3">&#9733;</span>
            <span class="star" data-r="4">&#9733;</span>
            <span class="star" data-r="5">&#9733;</span>
          </div>
        </div>
      </div>

      <!-- Chat -->
      <div class="chat-col">
        <div class="msgs" id="msgs"></div>
        <div id="inputWrap">
          <div class="input-bar">
            <div class="input-tools">
              <div class="itool" title="Adjuntar archivo"><i class="ti ti-paperclip" aria-hidden="true"></i></div>
              <div class="itool" title="Agregar imagen"><i class="ti ti-photo" aria-hidden="true"></i></div>
            </div>
            <input class="ibox" type="text" id="msgInput" placeholder="Escribe tu mensaje..." autocomplete="off">
            <button class="send-btn" id="sendBtn" onclick="sendMsg()" aria-label="Enviar mensaje"><i class="ti ti-send" aria-hidden="true"></i></button>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- ═══ MODAL NUEVO TICKET ═══ -->
<div class="backdrop" id="modalNew">
  <div class="modal">
    <div class="modal-header">
      <div class="modal-header-title"><i class="ti ti-plus" aria-hidden="true"></i>Nueva solicitud</div>
      <div class="modal-header-sub">Describe tu problema y lo atenderemos a la brevedad.</div>
    </div>
    <div class="frow">
      <label class="flabel">Asunto <span style="color:#dc2626">*</span></label>
      <input class="finput" id="nAsunto" type="text" placeholder="Ej: No puedo acceder al sistema" maxlength="120" autocomplete="off">
    </div>
    <div class="fgrid">
      <div>
        <label class="flabel">Categoría <span style="color:#dc2626">*</span></label>
        <select class="fselect" id="nCat">
          <option value="">Seleccionar...</option>
        </select>
      </div>
      <div>
        <label class="flabel">Prioridad</label>
        <select class="fselect" id="nPri">
          <option value="">Seleccionar...</option>
        </select>
      </div>
    </div>
    <div class="frow">
      <label class="flabel">Descripción <span style="color:#dc2626">*</span></label>
      <textarea class="ftextarea" id="nDesc" placeholder="Describe con detalle el problema que estás experimentando..."></textarea>
    </div>
    <div class="modal-foot">
      <button class="btn-cancel" onclick="closeNewTicket()">Cancelar</button>
      <button class="btn-primary" onclick="submitNewTicket()"><i class="ti ti-send" aria-hidden="true"></i>Enviar solicitud</button>
    </div>
  </div>
</div>

<div class="toasts" id="toasts"></div>

<script src="../../js/api.js"></script>
<script src="../../js/dashboard_admin.js"></script>
<script>
const idUsuarioActual = <?= $idUsuario ?>;
const idTicketInicial = <?= $idTicketInicial ?>;

let TICKETS    = [];   // se llena desde /api/tickets/cliente/:id
let CATEGORIAS = [];   // se llena desde /api/categorias
let PRIORIDADES = [];  // se llena desde /api/prioridades
let activeId   = null; // id_ticket actualmente seleccionado

const $ = id => document.getElementById(id);

function claseDesdeNombre(nombre, mapa) {
    const clave = (nombre || '').toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g, '');
    return mapa[clave] || '';
}

const EST_CLASS = { abierto: 'badge-abierto', pendiente: 'badge-abierto', 'en proceso': 'badge-proceso', cerrado: 'badge-cerrado', cancelado: 'badge-cerrado' };
const PRI_CLASS = { alta: 'badge-alta', media: 'badge-media', baja: 'badge-baja' };
const CHIP_CAT  = { soporte: 'chip-soporte', infraestructura: 'chip-infra', seguridad: 'chip-seguridad', facturacion: 'chip-facturacion', accesos: 'chip-accesos' };

async function cargarTicketsCliente() {
    try {
        const datos = await peticion(`/api/tickets/cliente/${idUsuarioActual}`);
        TICKETS = Array.isArray(datos) ? datos : [];
        renderSidebar();

        if (TICKETS.length === 0) {
            mostrarChatVacio('Aún no tienes solicitudes. Crea una nueva para empezar.');
            return;
        }

        const ticketUrl = TICKETS.find(t => parseInt(t.id_ticket) === idTicketInicial);
        selectTicket(ticketUrl ? ticketUrl.id_ticket : TICKETS[0].id_ticket);
    } catch (error) {
        console.error('Error cargando tickets:', error);
        mostrarChatVacio('No se pudieron cargar tus solicitudes.');
    }
}

function mostrarChatVacio(texto) {
    $('msgs').innerHTML = `<div class="estado-vacio"><i class="ti ti-inbox" aria-hidden="true"></i><span>${texto}</span></div>`;
    $('sbList').innerHTML = `<div class="sb-empty">Sin solicitudes registradas</div>`;
    $('inputWrap').innerHTML = '';
}

function renderSidebar() {
    $('sbList').innerHTML = TICKETS.map(t => {
        const claseEstado = claseDesdeNombre(t.estado, EST_CLASS) || 'badge-abierto';
        const clasePrio   = claseDesdeNombre(t.prioridad, PRI_CLASS) || 'badge-media';
        const colorPunto  = t.estado === 'Cerrado' ? '#6b7280' : '#22c55e';
        return `
        <div class="sb-item${parseInt(t.id_ticket) === activeId ? ' active' : ''}" onclick="selectTicket(${t.id_ticket})">
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

    renderSidebar();

    $('tk-id').textContent    = `TK-${String(t.id_ticket).padStart(4, '0')}`;
    $('tk-title').textContent = t.titulo;
    $('tk-cat').textContent   = t.categoria || 'General';
    setBadge('tk-pri', t.prioridad || '—', claseDesdeNombre(t.prioridad, PRI_CLASS));
    setBadge('tk-est', t.estado || '—', claseDesdeNombre(t.estado, EST_CLASS));

    $('dc-agent-name').textContent  = t.agente || 'Sin asignar';
    $('dc-agent-av').textContent    = t.agente ? t.agente.split(' ').map(w => w[0]).join('').substring(0, 2).toUpperCase() : '?';
    $('dc-agent-area').textContent  = t.categoria || 'General';
    $('dc-agent-dot').className     = t.agente ? 'dot-online' : 'dot-offline';
    $('dc-agent-status').textContent = t.agente ? 'Asignado' : 'Sin asignar aún';

    $('dc-opened').textContent = new Date(t.fecha_creacion).toLocaleDateString('es-GT', { day: '2-digit', month: 'short', hour: '2-digit', minute: '2-digit' });
    setBadge('dc-est', t.estado || '—', claseDesdeNombre(t.estado, EST_CLASS));
    setBadge('dc-pri', t.prioridad || '—', claseDesdeNombre(t.prioridad, PRI_CLASS));
    $('dc-cat').textContent = t.categoria || 'General';

    const cerrado = t.estado === 'Cerrado';
    $('ratingBlock').style.display = cerrado ? '' : 'none';

    const inp = $('inputWrap');
    if (cerrado) {
        inp.innerHTML = `<div class="closed-banner"><strong>Este ticket está cerrado.</strong> Si tienes un problema nuevo, abre una nueva solicitud.</div>`;
    } else {
        inp.innerHTML = `<div class="input-bar">
            <div class="input-tools">
                <div class="itool" title="Adjuntar archivo"><i class="ti ti-paperclip" aria-hidden="true"></i></div>
                <div class="itool" title="Agregar imagen"><i class="ti ti-photo" aria-hidden="true"></i></div>
            </div>
            <input class="ibox" type="text" id="msgInput" placeholder="Escribe tu mensaje..." autocomplete="off">
            <button class="send-btn" id="sendBtn" onclick="sendMsg()" aria-label="Enviar mensaje"><i class="ti ti-send" aria-hidden="true"></i></button>
        </div>`;
        $('msgInput').addEventListener('keydown', e => { if (e.key === 'Enter') sendMsg(); });
    }

    await cargarHistorialChat(activeId);
    iniciarPollingChatCliente(activeId);
    $('dc-count').textContent = document.querySelectorAll('#msgs .msg-row').length;
}

async function cargarHistorialChat(idTicket) {
    try {
        const historial = await peticion(`/api/mensajes/ticket/${idTicket}`);
        ultimoIdMensaje = historial.length > 0 ? historial[historial.length - 1].id_mensaje : 0;
        pintarMensajes(historial);
        await peticion(`/api/mensajes/ticket/${idTicket}/leidos`, 'PATCH');
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
    const sistemaVacio = contenedor.querySelector('.msg-sys');
    if (sistemaVacio && contenedor.children.length === 1) contenedor.innerHTML = '';
    contenedor.insertAdjacentHTML('beforeend', lista.map(m => construirFilaMensaje(m)).join(''));
    contenedor.scrollTop = contenedor.scrollHeight;
    ultimoIdMensaje = lista[lista.length - 1].id_mensaje;
}

function construirFilaMensaje(m) {
    const esMio = parseInt(m.id_usuario) === idUsuarioActual;
    const hora  = new Date(m.fecha_envio).toLocaleTimeString('es', { hour: '2-digit', minute: '2-digit' });
    const texto = escHtml(m.contenido);

    if (esMio) {
        return `<div class="msg-row right">
            <div class="msg-meta right">
                <span class="msg-sender-name">Tú</span>
                <span class="msg-role-badge msg-role-client">tú</span>
            </div>
            <div class="bubble from-me">${texto}</div>
            <div class="msg-time right">${hora}</div>
        </div>`;
    }

    const ini = (m.remitente || '?').split(' ').map(w => w[0]).join('').substring(0, 2).toUpperCase();
    return `<div class="msg-row">
        <div class="msg-meta">
            <div class="av av-sm" style="background:#ede9fe;color:#3730a3;font-size:10px;">${ini}</div>
            <span class="msg-sender-name">${m.remitente || 'Agente'}</span>
            <span class="msg-role-badge msg-role-agent">agente</span>
        </div>
        <div class="bubble from-agent">${texto}</div>
        <div class="msg-time">${hora}</div>
    </div>`;
}

function escHtml(s) {
    return String(s).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
}

let intervaloPollingCliente = null;
let ultimoIdMensaje = 0;

function iniciarPollingChatCliente(idTicket) {
    detenerPollingChatCliente();
    intervaloPollingCliente = setInterval(async () => {
        if (activeId !== idTicket) return;
        try {
            const nuevos = await peticion(`/api/mensajes/nuevos/${idTicket}/${ultimoIdMensaje}`);
            if (nuevos.length > 0) {
                agregarMensajesAlChat(nuevos);
                await peticion(`/api/mensajes/ticket/${idTicket}/leidos`, 'PATCH');
                $('dc-count').textContent = document.querySelectorAll('#msgs .msg-row').length;
            }
        } catch (error) {
            console.error('Error en polling:', error);
        }
    }, 4000);
}

function detenerPollingChatCliente() {
    if (intervaloPollingCliente) { clearInterval(intervaloPollingCliente); intervaloPollingCliente = null; }
}

function setBadge(id, texto, clase) {
    const el = $(id);
    el.textContent = texto;
    el.className = 'badge ' + (clase || 'badge-abierto');
}

async function sendMsg() {
    const inp = $('msgInput');
    if (!inp) return;
    const texto = inp.value.trim();
    if (!texto || !activeId) return;

    inp.value = '';
    try {
        const mensajeCreado = await peticion('/api/mensajes', 'POST', { id_ticket: activeId, contenido: texto });
        agregarMensajesAlChat([mensajeCreado]);
        $('dc-count').textContent = document.querySelectorAll('#msgs .msg-row').length;
    } catch (error) {
        toast(error.message || 'No se pudo enviar el mensaje', 'err');
        inp.value = texto;
    }
}

document.addEventListener('click', e => {
    const star = e.target.closest('.star');
    if (!star) return;
    const val = parseInt(star.dataset.r);
    document.querySelectorAll('.star').forEach((s, i) => s.classList.toggle('on', i < val));
    toast(`Gracias por tu calificación de ${val} estrella${val > 1 ? 's' : ''}.`, 'ok');
});

async function cargarSelectoresNuevoTicket() {
    try {
        const [cats, prios] = await Promise.all([peticion('/api/categorias'), peticion('/api/prioridades')]);
        CATEGORIAS  = cats;
        PRIORIDADES = prios;

        $('nCat').innerHTML  = '<option value="">Seleccionar...</option>' + cats.map(c => `<option value="${c.id_categoria}">${c.nombre}</option>`).join('');
        $('nPri').innerHTML  = '<option value="">Seleccionar...</option>' + prios.map(p => `<option value="${p.id_prioridad}">${p.nombre}</option>`).join('');
    } catch (error) {
        console.error('Error cargando categorías/prioridades:', error);
    }
}

function openNewTicket() { $('modalNew').classList.add('open'); setTimeout(() => $('nAsunto').focus(), 50); }
function closeNewTicket() { $('modalNew').classList.remove('open'); }

async function submitNewTicket() {
    const asunto      = $('nAsunto').value.trim();
    const idCategoria = $('nCat').value;
    const idPrioridad = $('nPri').value;
    const desc        = $('nDesc').value.trim();

    if (!asunto)      { toast('El asunto es requerido.', 'err'); $('nAsunto').focus(); return; }
    if (!idCategoria) { toast('Selecciona una categoría.', 'err'); return; }
    if (!desc)        { toast('La descripción es requerida.', 'err'); $('nDesc').focus(); return; }
    if (!idPrioridad) { toast('Selecciona una prioridad.', 'err'); return; }

    try {
        const nuevoTicket = await peticion('/api/tickets', 'POST', {
            titulo: asunto,
            descripcion: desc,
            id_usuario_cliente: idUsuarioActual,
            id_usuario_agente: 0,
            id_categoria: parseInt(idCategoria),
            id_prioridad: parseInt(idPrioridad),
            id_estado: 1
        });

        closeNewTicket();
        $('nAsunto').value = ''; $('nCat').value = ''; $('nDesc').value = ''; $('nPri').value = '';
        toast(`Solicitud TK-${String(nuevoTicket.id_ticket).padStart(4, '0')} creada. Te responderemos pronto.`, 'ok');

        await cargarTicketsCliente();
        selectTicket(nuevoTicket.id_ticket);
    } catch (error) {
        toast(error.message || 'No se pudo crear la solicitud', 'err');
    }
}

function toast(msg, type = 'info') {
    const ico = type === 'ok' ? 'ti-circle-check' : type === 'err' ? 'ti-alert-circle' : 'ti-info-circle';
    const el = document.createElement('div');
    el.className = `toast ${type}`;
    el.innerHTML = `<i class="ti ${ico}" aria-hidden="true"></i><span>${msg}</span>`;
    $('toasts').appendChild(el);
    setTimeout(() => el.remove(), 3500);
}

document.addEventListener('keydown', e => { if (e.key === 'Escape') closeNewTicket(); });
$('modalNew').addEventListener('click', e => { if (e.target === $('modalNew')) closeNewTicket(); });

cargarSelectoresNuevoTicket();
cargarTicketsCliente();
</script>
</body>
</html>
