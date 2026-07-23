<?php
define('ROL_REQUERIDO', 5);
require_once '../../seguridad.php';
$nombreUsuario = htmlspecialchars($_SESSION['nombre']);
$idUsuario     = (int)$_SESSION['usuario_id'];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel Cliente | ServiceCore</title>
    <link rel="icon" type="image/png" href="../../img/LogoNav.png">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../../css/dashboard_cliente.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>

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
            <div id="botonUsuario" class="w-10 h-10 rounded-full cursor-pointer border-2 border-[#5750ad] bg-[#5750ad] flex items-center justify-center text-white font-bold overflow-hidden">
                <?php if (!empty($_SESSION['foto'])): ?>
                    <img src="../<?= htmlspecialchars($_SESSION['foto']) ?>" alt="Foto de perfil" class="w-full h-full object-cover">
                <?php else: ?>
                    <?= mb_strtoupper(mb_substr($_SESSION['nombre'], 0, 1)) ?>
                <?php endif; ?>
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
            <img src="../../img/logoSC.png" alt="Logo" class="w-20 h-20 object-contain mb-4">
            <h6 class="text-lg font-bold text-center leading-6">ServiceCore<br>Corporation</h6>
        </div>
        <nav class="space-y-2">
            <a href="dashboard_cliente.php" class="menu-item activo">
                <span class="material-symbols-outlined">confirmation_number</span>Mis Tickets
            </a>
            <a href="perfil.php" class="menu-item">
                <span class="material-symbols-outlined">person</span>Perfil
            </a>
            <a href="../historial.php" class="menu-item">
                <span class="material-symbols-outlined">history</span>Historial
            </a>
        </nav>
        <a href="../../logout.php" class="mt-8 flex items-center justify-center gap-3 w-full py-3 rounded-xl border-2 border-red-500 text-red-400 font-semibold transition-all duration-300 hover:bg-red-500 hover:text-white hover:shadow-lg">
            <span class="material-symbols-outlined">logout</span>
            Cerrar Sesión
        </a>
    </aside>

    <!-- MODAL NUEVO TICKET -->
    <div id="modalNuevoTicket" class="modal-fondo hidden">
        <div class="bg-white rounded-2xl p-8 w-full max-w-lg mx-4">
            <div class="flex justify-between items-center mb-6">
                <h2 class="text-2xl font-bold text-[#1e1858]">Nuevo Ticket</h2>
                <button onclick="cerrarModal()" class="text-gray-400 hover:text-gray-600">
                    <span class="material-symbols-outlined">close</span>
                </button>
            </div>
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Título</label>
                    <input id="ticketTitulo" type="text" placeholder="Describe brevemente el problema" class="w-full border rounded-xl p-3 outline-none focus:border-[#5750ad]">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Descripción</label>
                    <textarea id="ticketDescripcion" placeholder="Detalla el problema..." class="w-full border rounded-xl p-3 h-28 outline-none focus:border-[#5750ad]"></textarea>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Categoría</label>
                        <select id="ticketCategoria" class="w-full border rounded-xl p-3 outline-none focus:border-[#5750ad]">
                            <option value="">Seleccionar...</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Prioridad</label>
                        <select id="ticketPrioridad" class="w-full border rounded-xl p-3 outline-none focus:border-[#5750ad]">
                            <option value="">Seleccionar...</option>
                        </select>
                    </div>
                </div>
                <button onclick="crearTicket()" class="w-full bg-[#5750ad] text-white py-3 rounded-xl font-bold hover:opacity-90 transition">
                    Crear Ticket
                </button>
            </div>
        </div>
    </div>

    <!-- CONTENIDO PRINCIPAL -->
    <main class="ml-64 pt-24 px-8 pb-10">
        <section class="flex justify-between items-center mb-8">
            <div>
                <h2 class="text-4xl font-bold text-[#1e1858]">Mis Tickets</h2>
                <p class="text-gray-500 mt-2">Hola, <?= $nombreUsuario ?>. Aquí están tus solicitudes.</p>
            </div>
            <button onclick="abrirModal()" class="flex items-center gap-2 bg-[#5750ad] text-white px-5 py-3 rounded-xl font-bold hover:opacity-90 transition">
                <span class="material-symbols-outlined">add</span>Nuevo Ticket
            </button>
        </section>

        <!-- Estadísticas -->
        <section class="grid grid-cols-2 md:grid-cols-4 gap-6 mb-8">
            <div class="tarjeta animar">
                <p class="text-sm text-gray-500 uppercase mb-2">Total</p>
                <h2 class="text-4xl font-bold text-[#5750ad]" id="stat-total">--</h2>
            </div>
            <div class="tarjeta animar">
                <p class="text-sm text-gray-500 uppercase mb-2">Abiertos</p>
                <h2 class="text-4xl font-bold text-yellow-500" id="stat-abiertos">--</h2>
            </div>
            <div class="tarjeta animar">
                <p class="text-sm text-gray-500 uppercase mb-2">Resueltos</p>
                <h2 class="text-4xl font-bold text-green-600" id="stat-resueltos">--</h2>
            </div>
            <div class="tarjeta animar">
                <p class="text-sm text-gray-500 uppercase mb-2">Cancelados</p>
                <h2 class="text-4xl font-bold text-red-500" id="stat-cancelados">--</h2>
            </div>
        </section>

        <!-- Tabla de tickets -->
        <div class="tarjeta animar p-0 overflow-hidden">
            <div class="flex justify-between items-center p-6 border-b">
                <h2 class="text-2xl font-bold">Mis Solicitudes</h2>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50 text-gray-500 uppercase text-sm">
                        <tr>
                            <th class="p-5 text-left">Ticket</th>
                            <th class="p-5 text-left">Título</th>
                            <th class="p-5 text-left">Estado</th>
                            <th class="p-5 text-left">Prioridad</th>
                            <th class="p-5 text-left">Agente</th>
                            <th class="p-5 text-left">Fecha</th>
                            <th class="p-5 text-left">Chat</th>
                        </tr>
                    </thead>
                    <tbody id="tabla-tickets">
                        <tr><td colspan="7" class="p-5 text-center text-gray-400">Cargando...</td></tr>
                    </tbody>
                </table>
            </div>
        </div>

        <footer class="text-center text-gray-500 text-sm border-t mt-10 p-4 bg-white rounded-xl">
            <p>© 2026 ServiceCore Corporation</p>
        </footer>
    </main>

    <script src="../../js/api.js"></script>
    <script>
        const botonUsuario = document.getElementById("botonUsuario");
        const menuUsuario  = document.getElementById("menuUsuario");
        botonUsuario.addEventListener("click", () => menuUsuario.classList.toggle("hidden"));
        document.addEventListener("click", (e) => {
            if (!botonUsuario.contains(e.target) && !menuUsuario.contains(e.target))
                menuUsuario.classList.add("hidden");
        });

        const tarjetas = document.querySelectorAll(".animar");
        tarjetas.forEach(t => { t.style.opacity="0"; t.style.transform="translateY(30px)"; t.style.transition="transform .4s ease, opacity .4s ease"; });
        window.addEventListener("load", () => { tarjetas.forEach((t,i) => setTimeout(() => { t.style.opacity="1"; t.style.transform="translateY(0)"; }, i*120)); });

        const API_BASE = '/api';

        async function parseJsonResponse(res) {
            const text = await res.text();
            if (!text) return null;
            try {
                return JSON.parse(text);
            } catch (error) {
                throw new Error(`Respuesta no JSON (${res.status}): ${text.slice(0, 120)}`);
            }
        }

        function colorEstado(e) {
            const m = {'Pendiente':'bg-yellow-100 text-yellow-700','En proceso':'bg-blue-100 text-blue-700','Cerrado':'bg-green-100 text-green-700','Cancelado':'bg-red-100 text-red-700'};
            return m[e] || 'bg-gray-100 text-gray-700';
        }
        function colorPrioridad(p) {
            const m = {
                'Baja':    'bg-blue-100 text-blue-700',
                'Media':   'bg-yellow-100 text-yellow-700',
                'Alta':    'bg-orange-100 text-orange-700',
                'Crítica': 'bg-red-100 text-red-700',
            };
            return m[p] || 'bg-gray-100 text-gray-700';
        }

        function abrirModal() { document.getElementById('modalNuevoTicket').classList.remove('hidden'); }
        function cerrarModal() { document.getElementById('modalNuevoTicket').classList.add('hidden'); }

        document.getElementById('modalNuevoTicket').addEventListener('click', function(e) {
            if (e.target === this) cerrarModal();
        });

        async function cargarSelectores() {
            try {
                const [resCat, resPrio] = await Promise.all([
                    fetch(`${API_BASE}/categorias`, { credentials: 'include' }),
                    fetch(`${API_BASE}/prioridades`, { credentials: 'include' })
                ]);
                if (!resCat.ok || !resPrio.ok) {
                    const errCat  = await resCat.text();
                    const errPrio = await resPrio.text();
                    throw new Error(`Error cargando selectores: categorias ${resCat.status} ${errCat}, prioridades ${resPrio.status} ${errPrio}`);
                }
                const categorias  = await parseJsonResponse(resCat);
                const prioridades = await parseJsonResponse(resPrio);
                if (!Array.isArray(categorias) || !Array.isArray(prioridades)) {
                    throw new Error('Respuesta inválida de categorías o prioridades');
                }
                const selCat  = document.getElementById('ticketCategoria');
                const selPrio = document.getElementById('ticketPrioridad');
                selCat.innerHTML  = '<option value="">Selecciona una categoría</option>';
                selPrio.innerHTML = '<option value="">Selecciona una prioridad</option>';
                categorias.forEach(c => {
                    const op = document.createElement('option');
                    op.value = c.id_categoria;
                    op.textContent = c.nombre;
                    selCat.appendChild(op);
                });
                prioridades.forEach(p => {
                    const op = document.createElement('option');
                    op.value = p.id_prioridad;
                    op.textContent = p.nombre;
                    selPrio.appendChild(op);
                });
            } catch (error) {
                console.error('Error cargando categorías/prioridades:', error);
                alert('No se pudieron cargar las categorías o prioridades. Recarga la página e intenta de nuevo.');
            }
        }

        async function crearTicket() {
            const titulo      = document.getElementById('ticketTitulo').value.trim();
            const descripcion = document.getElementById('ticketDescripcion').value.trim();
            const idCategoria = document.getElementById('ticketCategoria').value;
            const idPrioridad = document.getElementById('ticketPrioridad').value;
            if (!titulo || !descripcion || !idCategoria || !idPrioridad) {
                alert('Completa todos los campos');
                return;
            }
            try {
                const res = await fetch(`${API_BASE}/tickets`, {
                    method: 'POST',
                    credentials: 'include',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({
                        titulo,
                        descripcion,
                        id_usuario_cliente: <?= $idUsuario ?>,
                        id_usuario_agente: null,
                        id_categoria: parseInt(idCategoria),
                        id_prioridad: parseInt(idPrioridad),
                        id_estado: 1
                    })
                });
                const data = await parseJsonResponse(res);
                if (res.ok) {
                    cerrarModal();
                    document.getElementById('ticketTitulo').value = '';
                    document.getElementById('ticketDescripcion').value = '';
                    cargarTickets();
                    Swal.fire({
                        position: "top-end",
                        icon: "success",
                        title: "Tu ticket ha sido creado correctamente",
                        showConfirmButton: false,
                        timer: 1500
                    });
                } else {
                    alert(data?.error || 'Error creando ticket');
                }
            } catch (err) {
                console.error('Error:', err);
            }
        }

        async function cargarTickets() {
            try {
                const res = await fetch(`${API_BASE}/tickets/cliente/<?= $idUsuario ?>`, { credentials: 'include' });
                const data = await parseJsonResponse(res);
                if (!res.ok) {
                    throw new Error(data?.error || `Error cargando tickets (${res.status})`);
                }
                const lista = Array.isArray(data) ? data : [];
                const abiertos   = lista.filter(t => t.estado === 'Pendiente' || t.estado === 'En proceso').length;
                const resueltos  = lista.filter(t => t.estado === 'Cerrado').length;
                const cancelados = lista.filter(t => t.estado === 'Cancelado').length;
                document.getElementById('stat-total').textContent      = lista.length;
                document.getElementById('stat-abiertos').textContent   = abiertos;
                document.getElementById('stat-resueltos').textContent  = resueltos;
                document.getElementById('stat-cancelados').textContent = cancelados;
                const tbody = document.getElementById('tabla-tickets');
                tbody.innerHTML = lista.length === 0
                    ? '<tr><td colspan="7" class="p-5 text-center text-gray-400">No tienes tickets aún</td></tr>'
                    : lista.map(t => `
                        <tr class="hover:bg-gray-50 transition">
                            <td class="p-5 font-bold">#TK-${t.id_ticket}</td>
                            <td class="p-5 font-medium">${t.titulo}</td>
                            <td class="p-5"><span class="px-3 py-1 rounded-full text-xs font-bold ${colorEstado(t.estado)}">${t.estado}</span></td>
                            <td class="p-5"><span class="px-3 py-1 rounded-full text-xs font-bold ${colorPrioridad(t.prioridad)}">${t.prioridad || '—'}</span></td>
                            <td class="p-5 text-gray-500">${t.agente || 'Sin asignar'}</td>
                            <td class="p-5 text-gray-500 text-sm">${new Date(t.fecha_creacion).toLocaleDateString('es-GT')}</td>
                            <td class="p-5">
                                <button onclick="ChatWidget.abrir(${t.id_ticket})" class="text-[#5750ad] hover:scale-110 transition">
                                    <span class="material-symbols-outlined">chat</span>
                                </button>
                            </td>
                        </tr>
                    `).join('');
            } catch (err) {
                console.error('Error cargando tickets:', err);
            }
        }

        cargarSelectores();
        cargarTickets();
    </script>

    <script>window.ID_USUARIO_ACTUAL = <?= $idUsuario ?>;</script>
    <script src="../../js/chat_widget.js"></script>
</body>
</html>