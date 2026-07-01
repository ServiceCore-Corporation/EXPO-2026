<?php
define('ROL_REQUERIDO', 2);
require_once '../../seguridad.php';
$nombreUsuario = htmlspecialchars($_SESSION['nombre']);
$correoUsuario = htmlspecialchars($_SESSION['correo']);
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<title>Categorías — ServiceCore Corporation</title>
<link rel="icon" type="image/png" href="../../img/LogoNav.png">
<script src="https://cdn.tailwindcss.com"></script>
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" rel="stylesheet"/>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@3.x/tabler-icons.min.css" />
<link rel="stylesheet" href="../../css/crear_categorias.css">
</head>
<body>

    <!-- ENCABEZADO -->
    <header class="fixed top-0 left-64 right-0 h-16 bg-white shadow flex items-center justify-between px-8 z-50">
        <div class="flex items-center gap-4">
            <span class="material-symbols-outlined text-[#5750ad]">menu</span>
            <h1 class="text-xl font-bold text-[#1e1858]">Panel de Empresa</h1>
        </div>
        <div class="relative flex items-center gap-4">
            <span class="material-symbols-outlined cursor-pointer">notifications</span>
            <div class="text-right">
                <p class="font-bold"><?= $nombreUsuario ?></p>
                <p class="text-sm text-gray-500">Admin Empresa</p>
            </div>
            <div id="botonUsuario" class="w-10 h-10 rounded-full cursor-pointer border-2 border-[#5750ad] bg-[#5750ad] flex items-center justify-center text-white font-bold">
                <?= mb_strtoupper(mb_substr($_SESSION['nombre'], 0, 1)) ?>
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

    <!-- MENÚ LATERAL -->
    <aside class="fixed left-0 top-0 w-64 h-screen bg-[#1e1858] text-white p-6 flex flex-col">
        <div class="flex flex-col items-center mb-8">
            <img src="../../img/logoSC.png" alt="Logo" class="w-20 h-20 object-contain mb-4">
            <h6 class="text-lg font-bold text-center leading-6">ServiceCore<br>Corporation</h6>
        </div>
        <nav class="flex flex-col flex-1 gap-2">
            <a href="dashboard_admin_emp.php" class="menu-item">
                <span class="material-symbols-outlined">dashboard</span>Inicio
            </a>
            <a href="gestion_usuarios.php" class="menu-item">
                <span class="material-symbols-outlined">group</span>Gestion de Usuarios
            </a>
            <a href="crear_categorias.php" class="menu-item activo">
                <span class="material-symbols-outlined">category</span>Gestion de Categorías
            </a>
            <a href="asignar_categoria.php" class="menu-item">
                <span class="material-symbols-outlined">sell</span>Asignar Categoría
            </a>
            <a href="#" class="menu-item">
                <span class="material-symbols-outlined">history</span>Historial
            </a>
            
          <div class="flex-grow"></div>
          <!-- Cerrar sesión -->
          <a href="../../logout.php"class="mt-auto flex items-center justify-center gap-3 w-full py-3 rounded-xl border-2 border-red-500 text-red-400 font-semibold transition-all duration-300 hover:bg-red-500 hover:text-white hover:shadow-lg">
              <span class="material-symbols-outlined">logout</span>
              Cerrar Sesión
          </a>
            
        </nav>
    </aside>

  <!-- ═══ MAIN ═══ -->
  <main class="contenido ml-64 pt-24 px-8 pb-10">

    <!-- Page heading -->
    <section class="mb-6">
      <h2 class="text-4xl font-bold text-[#1e1858]">Gestión de Categorías</h2>
      <p class="text-gray-500 mt-2">
        Visualiza y administra las categorías de tu empresa que se asignan a los tickets.
        Las categorías son exclusivas de cada organización.
      </p>
    </section>

    <!-- Stats -->
    <div class="stats animar">
      <div class="stat sel" data-f="">
        <div class="stat-icon" style="background:#ede9fe"><i class="ti ti-tags" style="color:#5750ad"></i></div>
        <div class="stat-num" id="sTotal">6</div>
        <div class="stat-label">Total de categorías</div>
        <div class="stat-sub" id="sSub">5 activas · 1 inactiva</div>
      </div>
      <div class="stat" data-f="1">
        <div class="stat-icon" style="background:#dcfce7"><i class="ti ti-circle-check" style="color:#16a34a"></i></div>
        <div class="stat-num" id="sActivas">5</div>
        <div class="stat-label">Activas</div>
        <div class="stat-sub">Ver solo activas</div>
      </div>
      <div class="stat" data-f="0">
        <div class="stat-icon" style="background:#f3f4f6"><i class="ti ti-circle-x" style="color:#6b7280"></i></div>
        <div class="stat-num" id="sInactivas">1</div>
        <div class="stat-label">Inactivas</div>
        <div class="stat-sub">Ver solo inactivas</div>
      </div>
      <div class="stat" data-f="">
        <div class="stat-icon" style="background:#fef9c3"><i class="ti ti-ticket" style="color:#ca8a04"></i></div>
        <div class="stat-num">34</div>
        <div class="stat-label">Tickets asignados</div>
        <div class="stat-sub">Total en todas las categorías</div>
      </div>
    </div>

    <!-- Toolbar -->
    <div class="toolbar animar">
      <div class="tl-left">
        <select class="btn-filter" id="filterEstado">
          <option value="">Todos los estados</option>
          <option value="1">Solo activas</option>
          <option value="0">Solo inactivas</option>
        </select>
        <button class="btn-clear" id="btnClear"><i class="ti ti-refresh"></i> Limpiar filtros</button>
      </div>
      <button class="btn-primary" id="btnNew">
        <i class="ti ti-plus"></i> Nueva Categoría
      </button>
    </div>

    <!-- Cards grid -->
    <div class="grid-cards animar" id="grid"></div>

  </main>
  <footer class="text-center text-gray-500 text-sm border-t mt-10 p-4 bg-white rounded-xl">
    <p>© 2026 ServiceCore Corporation</p>
  </footer>

<!-- ═══ MODAL CREAR / EDITAR ═══ -->
<div class="backdrop" id="mForm">
  <div class="modal">
    <div class="modal-title" id="mTitle">Nueva Categoría</div>
    <div class="modal-desc" id="mDesc">Completa los campos para agregar una nueva categoría a tu empresa.</div>

    <div class="frow">
      <label class="flabel">Nombre de la categoría <span style="color:#dc2626">*</span></label>
      <input class="finput" id="fNombre" type="text" placeholder="Ej: Soporte Técnico" maxlength="100" autocomplete="off" />
    </div>
    <div class="frow">
      <label class="flabel">Descripción</label>
      <textarea class="ftextarea" id="fDesc" placeholder="Describe brevemente para qué se usa esta categoría..." maxlength="500"></textarea>
    </div>
    <div class="fgrid" style="margin-bottom:16px">
      <div>
        <label class="flabel">Empresa</label>
        <select class="fselect" id="fEmpresa">
          <option>ServiceCore Corporation</option>
          <option>Cliente Corp</option>
          <option>Banca Central</option>
        </select>
      </div>
      <div>
        <label class="flabel">Estado</label>
        <select class="fselect" id="fEstado">
          <option value="1">Activo</option>
          <option value="0">Inactivo</option>
        </select>
      </div>
    </div>
    <div class="frow">
      <label class="flabel">Color identificador</label>
      <div class="swatches" id="swatches"></div>
    </div>
    <div class="frow">
      <label class="flabel">Ícono</label>
      <div class="icon-grid" id="iconGrid"></div>
    </div>
    <div class="modal-foot">
      <button class="btn-cancel" id="mCancelForm">Cancelar</button>
      <button class="btn-primary" id="mSave">Guardar categoría</button>
    </div>
  </div>
</div>

<!-- ═══ MODAL ELIMINAR ═══ -->
<div class="backdrop" id="mDelete">
  <div class="modal">
    <div class="modal-title" style="color:var(--danger-text)">
      <i class="ti ti-alert-triangle" style="margin-right:6px"></i>Eliminar categoría
    </div>
    <div class="modal-desc">
      ¿Estás seguro de que deseas eliminar la categoría <strong id="dName"></strong>?
      Esta acción no se puede deshacer.<br>
      <span style="font-size:12px;color:var(--text-3)">Si tiene tickets asignados no podrá eliminarse. Desactívala en su lugar.</span>
    </div>
    <div class="modal-foot">
      <button class="btn-cancel" id="mCancelDel">Cancelar</button>
      <button class="btn-delete-confirm" id="mConfirmDel"><i class="ti ti-trash"></i> Sí, eliminar</button>
    </div>
  </div>
</div>

<!-- Loader -->
<div class="loader" id="loader"><div class="spin"></div></div>

<!-- Toasts -->
<div class="toasts" id="toasts"></div>

<script>
const COLORS = ['#5750ad','#0891b2','#16a34a','#d97706','#dc2626','#9333ea','#0f766e','#be185d'];
const COLOR_BG = {
  '#5750ad':'#ede9fe','#0891b2':'#cffafe','#16a34a':'#dcfce7','#d97706':'#fef9c3',
  '#dc2626':'#fee2e2','#9333ea':'#f3e8ff','#0f766e':'#ccfbf1','#be185d':'#fce7f3',
};
const ICONS = [
  'ti-headset','ti-server','ti-shield','ti-cloud','ti-tool','ti-lock',
  'ti-device-laptop','ti-database','ti-receipt','ti-users','ti-credit-card',
  'ti-file-text','ti-settings','ti-chart-bar','ti-mail','ti-alert-triangle',
];

let db = [
  {id:1,nombre:'Soporte Técnico',  desc:'Problemas técnicos con dispositivos y software.',  empresa:'ServiceCore Corporation',activo:1,color:'#5750ad',icono:'ti-headset',    tickets:12,fecha:'14 feb 2024'},
  {id:2,nombre:'Infraestructura',  desc:'Servidores, redes y servicios de nube.',            empresa:'ServiceCore Corporation',activo:1,color:'#0891b2',icono:'ti-server',      tickets:8, fecha:'19 may 2023'},
  {id:3,nombre:'Seguridad',        desc:'Incidentes de seguridad y accesos no autorizados.', empresa:'ServiceCore Corporation',activo:1,color:'#dc2626',icono:'ti-shield',      tickets:5, fecha:'05 mar 2024'},
  {id:4,nombre:'Accesos',          desc:'Gestión de permisos y cuentas de usuario.',         empresa:'ServiceCore Corporation',activo:0,color:'#9333ea',icono:'ti-lock',        tickets:0, fecha:'01 abr 2024'},
  {id:5,nombre:'Facturación',      desc:'Dudas y problemas relacionados con pagos.',         empresa:'ServiceCore Corporation',activo:1,color:'#d97706',icono:'ti-receipt',     tickets:4, fecha:'10 may 2024'},
  {id:6,nombre:'Software',         desc:'Errores y actualizaciones de aplicaciones.',        empresa:'ServiceCore Corporation',activo:1,color:'#0f766e',icono:'ti-device-laptop',tickets:5, fecha:'18 jun 2024'},
];
let nextId = 7;
let editId = null, delId = null;
let selColor = '#5750ad', selIcon = 'ti-headset';
let filterActivo = '';

const $  = id => document.getElementById(id);
const esc = s => String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');

function toast(msg, type='info') {
  const ico = type==='ok'?'ti-circle-check':type==='err'?'ti-alert-circle':'ti-info-circle';
  const el  = document.createElement('div');
  el.className = `toast ${type}`;
  el.innerHTML = `<i class="ti ${ico}"></i><span>${msg}</span>`;
  $('toasts').appendChild(el);
  setTimeout(() => el.remove(), 3500);
}

function updateStats() {
  const activas   = db.filter(c => c.activo === 1).length;
  const inactivas = db.filter(c => c.activo === 0).length;
  $('sTotal').textContent     = db.length;
  $('sActivas').textContent   = activas;
  $('sInactivas').textContent = inactivas;
  $('sSub').textContent       = `${activas} activas · ${inactivas} inactivas`;
}

function render() {
  const filtered = filterActivo === ''
    ? db
    : db.filter(c => String(c.activo) === filterActivo);

  const grid = $('grid');
  if (!filtered.length) {
    grid.innerHTML = `
      <div class="empty">
        <i class="ti ti-tags"></i>
        <strong>Sin categorías</strong>
        <p>No hay categorías que coincidan con el filtro.</p>
      </div>`;
    return;
  }

  grid.innerHTML = filtered.map(c => `
    <div class="card">
      <div class="card-head">
        <div class="card-icon-row">
          <div class="cat-icon" style="background:${COLOR_BG[c.color]??'#ede9fe'}">
            <i class="ti ${c.icono}" style="color:${c.color}"></i>
          </div>
          <div>
            <div class="cat-name">${esc(c.nombre)}</div>
            <div class="cat-company"><i class="ti ti-building"></i>${esc(c.empresa)}</div>
          </div>
        </div>
        <span class="badge ${c.activo ? 'badge-on' : 'badge-off'}">${c.activo ? 'ACTIVO' : 'INACTIVO'}</span>
      </div>
      <div class="cat-desc">${esc(c.desc || 'Sin descripción.')}</div>
      <div class="card-meta">
        <div class="meta-item"><i class="ti ti-calendar"></i>Desde ${c.fecha}</div>
        <div class="meta-item"><i class="ti ti-ticket"></i>${c.tickets} tickets</div>
      </div>
      <div class="card-actions">
        <button class="act" title="Editar"   data-a="edit"   data-id="${c.id}"><i class="ti ti-pencil"></i></button>
        <button class="act ${c.activo ? 'on' : ''}" title="${c.activo ? 'Desactivar' : 'Activar'}" data-a="toggle" data-id="${c.id}">
          <i class="ti ti-toggle-${c.activo ? 'right' : 'left'}"></i>
        </button>
        <button class="act del" title="Eliminar" data-a="delete" data-id="${c.id}"><i class="ti ti-trash"></i></button>
      </div>
    </div>`).join('');

  updateStats();
}

function openForm(cat = null) {
  editId   = cat?.id ?? null;
  selColor = cat?.color ?? '#5750ad';
  selIcon  = cat?.icono ?? 'ti-headset';
  $('mTitle').textContent = cat ? 'Editar Categoría' : 'Nueva Categoría';
  $('mDesc').textContent  = cat
    ? 'Actualiza la información de esta categoría.'
    : 'Completa los campos para agregar una nueva categoría a tu empresa.';
  $('fNombre').value = cat?.nombre ?? '';
  $('fDesc').value   = cat?.desc   ?? '';
  $('fEstado').value = cat ? String(cat.activo) : '1';
  renderSwatches(); renderIcons();
  $('mForm').classList.add('open');
  setTimeout(() => $('fNombre').focus(), 50);
}
function closeForm() { $('mForm').classList.remove('open'); }

function renderSwatches() {
  $('swatches').innerHTML = COLORS.map(h =>
    `<div class="swatch${h === selColor ? ' sel' : ''}" style="background:${h}" data-c="${h}" title="${h}"></div>`
  ).join('');
}
function renderIcons() {
  $('iconGrid').innerHTML = ICONS.map(ic =>
    `<button class="icon-btn${ic === selIcon ? ' sel' : ''}" data-i="${ic}" type="button" title="${ic}">
      <i class="ti ${ic}"></i></button>`
  ).join('');
}

function saveForm() {
  const nombre = $('fNombre').value.trim();
  if (!nombre) { toast('El nombre es requerido.', 'err'); $('fNombre').focus(); return; }
  const duplicate = db.find(c => c.nombre.toLowerCase() === nombre.toLowerCase() && c.id !== editId);
  if (duplicate) { toast('Ya existe una categoría con ese nombre.', 'err'); return; }

  if (editId) {
    const c = db.find(x => x.id === editId);
    if (c) {
      c.nombre  = nombre;
      c.desc    = $('fDesc').value.trim();
      c.empresa = $('fEmpresa').value;
      c.activo  = parseInt($('fEstado').value);
      c.color   = selColor;
      c.icono   = selIcon;
    }
    toast('Categoría actualizada.', 'ok');
  } else {
    db.push({
      id: nextId++, nombre, desc: $('fDesc').value.trim(),
      empresa: $('fEmpresa').value, activo: parseInt($('fEstado').value),
      color: selColor, icono: selIcon, tickets: 0,
      fecha: new Date().toLocaleDateString('es', {day:'2-digit', month:'short', year:'numeric'})
    });
    toast('Categoría creada correctamente.', 'ok');
  }
  closeForm(); render();
}

function openDelete(id) {
  const c = db.find(x => x.id === id);
  if (!c) return;
  delId = id;
  $('dName').textContent = c.nombre;
  $('mDelete').classList.add('open');
}
function closeDelete() { $('mDelete').classList.remove('open'); delId = null; }
function confirmDelete() {
  const c = db.find(x => x.id === delId);
  if (c?.tickets > 0) {
    toast(`No se puede eliminar: tiene ${c.tickets} ticket(s) asignado(s). Desactívala en su lugar.`, 'err');
    closeDelete(); return;
  }
  db = db.filter(x => x.id !== delId);
  toast('Categoría eliminada.', 'ok');
  closeDelete(); render();
}

$('btnNew').addEventListener('click', () => openForm());

$('filterEstado').addEventListener('change', e => {
  filterActivo = e.target.value; render();
});

$('btnClear').addEventListener('click', () => {
  filterActivo = ''; $('filterEstado').value = '';
  document.querySelectorAll('[data-f]').forEach(s => s.classList.remove('sel'));
  document.querySelector('[data-f=""]').classList.add('sel');
  render();
});

document.querySelectorAll('[data-f]').forEach(s => {
  s.addEventListener('click', () => {
    document.querySelectorAll('[data-f]').forEach(x => x.classList.remove('sel'));
    s.classList.add('sel');
    filterActivo = s.dataset.f;
    $('filterEstado').value = filterActivo;
    render();
  });
});

$('grid').addEventListener('click', e => {
  const btn = e.target.closest('[data-a]'); if (!btn) return;
  const id  = parseInt(btn.dataset.id);
  const a   = btn.dataset.a;
  if (a === 'edit')   { openForm(db.find(x => x.id === id)); }
  if (a === 'toggle') { const c = db.find(x => x.id === id); if (c) { c.activo = c.activo ? 0 : 1; toast(`Categoría ${c.activo ? 'activada' : 'desactivada'}.`, 'ok'); render(); } }
  if (a === 'delete') { openDelete(id); }
});

$('mSave').addEventListener('click', saveForm);
$('mCancelForm').addEventListener('click', closeForm);
$('mForm').addEventListener('click', e => { if (e.target === $('mForm')) closeForm(); });

$('swatches').addEventListener('click', e => {
  const s = e.target.closest('[data-c]'); if (!s) return;
  selColor = s.dataset.c; renderSwatches();
});
$('iconGrid').addEventListener('click', e => {
  const b = e.target.closest('[data-i]'); if (!b) return;
  selIcon = b.dataset.i; renderIcons();
});
$('fNombre').addEventListener('keydown', e => { if (e.key === 'Enter') saveForm(); });

$('mConfirmDel').addEventListener('click', confirmDelete);
$('mCancelDel').addEventListener('click', closeDelete);
$('mDelete').addEventListener('click', e => { if (e.target === $('mDelete')) closeDelete(); });

document.addEventListener('keydown', e => { if (e.key === 'Escape') { closeForm(); closeDelete(); } });

const botonUsuario = document.getElementById('botonUsuario');
const menuUsuario  = document.getElementById('menuUsuario');
botonUsuario.addEventListener('click', () => menuUsuario.classList.toggle('hidden'));
document.addEventListener('click', e => {
  if (!botonUsuario.contains(e.target) && !menuUsuario.contains(e.target))
    menuUsuario.classList.add('hidden');
});

window.addEventListener('load', () => {
  document.querySelectorAll('.animar').forEach((el, i) => {
    setTimeout(() => {
      el.style.opacity = '1';
      el.style.transform = 'translateY(0)';
    }, i * 150);
  });
});

render();
</script>
</body>
</html>