<?php
define('ROL_REQUERIDO', 4);
require_once '../../seguridad.php';
$nombreUsuario = htmlspecialchars($_SESSION['nombre']);
$idUsuario     = (int)$_SESSION['usuario_id'];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel Supervisor | ServiceCore</title>
    <link rel="icon" type="image/png" href="../../img/LogoNav.png">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;900&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" rel="stylesheet">
    <link rel="stylesheet" href="../../css/dashboard_aprovador.css">
</head>
<body>

    <!-- ENCABEZADO -->
    <header class="fixed top-0 left-64 right-0 h-16 bg-white shadow flex items-center justify-between px-8 z-50">
        <div class="flex items-center gap-4">
            <span class="material-symbols-outlined text-[#5750ad]">menu</span>
            <h1 class="text-xl font-bold text-[#1e1858]">Panel Supervisor</h1>
        </div>
        <div class="relative flex items-center gap-4">
            <span class="material-symbols-outlined cursor-pointer">notifications</span>
            <div class="text-right">
                <p class="font-bold"><?= $nombreUsuario ?></p>
                <p class="text-sm text-gray-500">Supervisor</p>
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
    <aside class="fixed left-0 top-0 w-64 h-full bg-[#1e1858] text-white p-6">
        <div class="flex flex-col items-center mb-8">
            <img src="../../img/logoSC.png" alt="Logo" class="w-20 h-20 object-contain mb-4">
            <h6 class="text-lg font-bold text-center leading-6">ServiceCore<br>Corporation</h6>
        </div>
        <nav class="space-y-2">
            <a href="dashboard_aprovador.php" class="menu-item activo">
                <span class="material-symbols-outlined">insights</span>Estadísticas
            </a>
            <a href="dashboard_aprovador.php" class="menu-item">
                <span class="material-symbols-outlined">confirmation_number</span>Tickets
            </a>
            <a href="asignacion_tickets.php" class="menu-item">
                <span class="material-symbols-outlined">assignment_ind</span>Asignación de Tickets
            </a>
            <a href="#" class="menu-item">
                <span class="material-symbols-outlined">group</span>Usuarios
            </a>
            <a href="#" class="menu-item">
                <span class="material-symbols-outlined">history</span>Historial
            </a>
            <a href="#" class="menu-item">
                <span class="material-symbols-outlined">category</span>Categorías
            </a>
        </nav>
    </aside>

    <!-- CONTENIDO PRINCIPAL -->
    <main class="ml-64 pt-24 px-8 pb-10">
        <section class="mb-8">
            <h2 class="text-4xl font-bold text-[#1e1858]">Panel de Aprobación</h2>
            <p class="text-gray-500 mt-2">Gestión y validación de tickets técnicos.</p>
        </section>

        <!-- Estadísticas -->
        <section class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
            <div class="tarjeta animar">
                <p class="text-sm text-gray-500 uppercase mb-2">Pendientes</p>
                <h2 class="text-4xl font-bold text-[#5750ad]" id="stat-pendientes">--</h2>
            </div>
            <div class="tarjeta animar">
                <p class="text-sm text-gray-500 uppercase mb-2">En Proceso</p>
                <h2 class="text-4xl font-bold" id="stat-proceso">--</h2>
            </div>
            <div class="tarjeta animar">
                <p class="text-sm text-gray-500 uppercase mb-2">Cerrados</p>
                <h2 class="text-4xl font-bold text-green-600" id="stat-cerrados">--</h2>
            </div>
            <div class="tarjeta animar">
                <p class="text-sm text-gray-500 uppercase mb-2">Total</p>
                <h2 class="text-4xl font-bold" id="stat-total">--</h2>
            </div>
        </section>

        <!-- Tickets y asignaciones -->
        <div class="grid grid-cols-12 gap-6">
            <div class="col-span-12 lg:col-span-8">
                <div class="tarjeta animar p-0 overflow-hidden">
                    <div class="flex justify-between items-center p-6 border-b">
                        <h2 class="text-2xl font-bold">Tickets por Aprobar</h2>
                        <button class="boton text-sm">Ver Todos</button>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead class="bg-gray-50 text-gray-500 uppercase text-sm">
                                <tr>
                                    <th class="p-5 text-left">Ticket</th>
                                    <th class="p-5 text-left">Asunto</th>
                                    <th class="p-5 text-left">Estado</th>
                                    <th class="p-5 text-left">Prioridad</th>
                                    <th class="p-5 text-left">Acción</th>
                                </tr>
                            </thead>
                            <tbody id="tabla-tickets">
                                <tr><td colspan="5" class="p-5 text-center text-gray-400">Cargando...</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Panel asignaciones -->
            <div class="col-span-12 lg:col-span-4 flex flex-col gap-6">
                <div class="tarjeta animar">
                    <h2 class="text-xl font-bold mb-5 flex items-center gap-2">
                        <span class="material-symbols-outlined text-[#5750ad]">assignment_ind</span>
                        Mis Asignaciones
                    </h2>
                    <div id="lista-asignaciones" class="space-y-3">
                        <p class="text-gray-400 text-sm">Cargando...</p>
                    </div>
                </div>
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

        function colorEstado(e) {
            const m = {'Pendiente':'bg-yellow-100 text-yellow-700','En proceso':'bg-blue-100 text-blue-700','Cerrado':'bg-green-100 text-green-700','Cancelado':'bg-red-100 text-red-700'};
            return m[e] || 'bg-gray-100 text-gray-700';
        }
        function colorPrioridad(p) {
            const m = {'Alta':'text-red-600','Media':'text-orange-500','Baja':'text-blue-500'};
            return m[p] || 'text-gray-600';
        }

        async function cargarDatos() {
            try {
                const [resTickets, resAsig] = await Promise.all([
                    fetch('/api/dashboard/tickets'),
                    fetch('/api/asignaciones/supervisor/<?= $idUsuario ?>')
                ]);
                const tickets = await resTickets.json();
                const asig    = await resAsig.json();

                document.getElementById('stat-pendientes').textContent = tickets.pendientes;
                document.getElementById('stat-proceso').textContent    = tickets.en_proceso;
                document.getElementById('stat-cerrados').textContent   = tickets.cerrados;
                document.getElementById('stat-total').textContent      = tickets.pendientes + tickets.en_proceso + tickets.cerrados;

                const tbody = document.getElementById('tabla-tickets');
                tbody.innerHTML = (tickets.recientes || []).map(t => `
                    <tr class="hover:bg-gray-50 transition">
                        <td class="p-5 font-bold">#TK-${t.id_ticket}</td>
                        <td class="p-5"><p class="font-bold">${t.titulo}</p><p class="text-sm text-gray-500">${t.cliente || '—'}</p></td>
                        <td class="p-5"><span class="px-3 py-1 rounded-full text-xs font-bold ${colorEstado(t.estado)}">${t.estado}</span></td>
                        <td class="p-5 font-bold ${colorPrioridad(t.prioridad)}">${t.prioridad}</td>
                        <td class="p-5">
                            <button class="hover:scale-110 transition" onclick="window.ChatWidgetAprovador && window.ChatWidgetAprovador.abrir(${t.id_ticket})">
                                <span class="material-symbols-outlined text-[#5750ad]">chat</span>
                            </button>
                        </td>
                    </tr>
                `).join('') || '<tr><td colspan="5" class="p-5 text-center text-gray-400">Sin tickets</td></tr>';

                const listaAsig = document.getElementById('lista-asignaciones');
                listaAsig.innerHTML = (Array.isArray(asig) ? asig : []).slice(0,5).map(a => `
                    <div class="flex justify-between items-center py-2 border-b last:border-0">
                        <div>
                            <p class="font-medium text-sm">${a.ticket || '—'}</p>
                            <p class="text-xs text-gray-500">Agente: ${a.agente || '—'}</p>
                        </div>
                    </div>
                `).join('') || '<p class="text-gray-400 text-sm">Sin asignaciones</p>';
            } catch (err) {
                console.error('Error:', err);
            }
        }

        cargarDatos();

    </script>
    <script>window.ID_USUARIO_ACTUAL = <?= $idUsuario ?>;</script>
    <script src="../../js/chat_widget_aprovador.js"></script>
</body>
</html>
