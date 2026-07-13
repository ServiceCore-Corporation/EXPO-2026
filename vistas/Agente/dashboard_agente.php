<?php
define('ROL_REQUERIDO', 3);
require_once '../../seguridad.php';
$nombreUsuario = htmlspecialchars($_SESSION['nombre']);
$idUsuario     = (int)$_SESSION['usuario_id'];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Agente | ServiceCore</title>
    <link rel="icon" type="image/png" href="../../img/LogoNav.png">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../../css/dashboard_agente.css">
</head>
<body>

    <!-- ENCABEZADO -->
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
            <a href="dashboard_agente.php" class="menu-item activo">
                <span class="material-symbols-outlined">dashboard</span>Inicio
            </a>
            <a href="dashboard_agente.php" class="menu-item">
                <span class="material-symbols-outlined">confirmation_number</span>Tickets
            </a>
            <a href="../historial.php" class="menu-item">
                <span class="material-symbols-outlined">history</span>Historial
            </a>
            <a href="#" class="menu-item">
                <span class="material-symbols-outlined">insights</span>Estadísticas
            </a>
        </nav>
        <div class="flex-grow"></div>
        <!-- Cerrar sesión -->
        <a href="../../logout.php"class="mt-auto flex items-center justify-center gap-3 w-full py-3 rounded-xl border-2 border-red-500 text-red-400 font-semibold transition-all duration-300 hover:bg-red-500 hover:text-white hover:shadow-lg">
            <span class="material-symbols-outlined">logout</span>
            Cerrar Sesión
        </a>
    </aside>

    <!-- El chat se maneja con el widget flotante de js/chat_widget_agente.js -->

    <!-- CONTENIDO PRINCIPAL -->
    <main class="contenido ml-64 pt-24 px-8 pb-10">
        <section class="mb-8">
            <h2 class="text-4xl font-bold text-[#1e1858]">Panel Principal</h2>
            <p class="text-gray-500 mt-2">Gestión y validación de tickets técnicos.</p>
        </section>

        <!-- Estadísticas -->
        <section class="p-10">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
                <div class="tarjeta animar">
                    <p class="text-sm text-gray-500 uppercase mb-2">Mis Tickets</p>
                    <div class="flex items-center justify-between">
                        <h2 class="text-4xl font-bold text-[#5750ad]" id="stat-total">--</h2>
                    </div>
                </div>
                <div class="tarjeta animar">
                    <p class="text-sm text-gray-500 uppercase mb-2">Pendientes</p>
                    <h2 class="text-4xl font-bold text-yellow-500" id="stat-pendientes">--</h2>
                </div>
                <div class="tarjeta animar">
                    <p class="text-sm text-gray-500 uppercase mb-2">En Proceso</p>
                    <h2 class="text-4xl font-bold text-blue-500" id="stat-proceso">--</h2>
                </div>
                <div class="tarjeta animar">
                    <p class="text-sm text-gray-500 uppercase mb-2">Cerrados</p>
                    <h2 class="text-4xl font-bold text-green-600" id="stat-cerrados">--</h2>
                </div>
            </div>

            <!-- Tabla + panel resolución -->
            <div class="grid grid-cols-12 gap-6">
                <div class="col-span-12 lg:col-span-8">
                    <div class="tarjeta animar p-0 overflow-hidden">
                        <div class="flex justify-between items-center p-6 border-b">
                            <h2 class="text-2xl font-bold">Panel de Tickets</h2>
                            <div class="flex gap-2">
                                <button onclick="filtrarTickets('todos')" id="btn-todos" class="bg-gray-100 px-4 py-2 rounded-lg text-sm">Todos</button>
                                <button onclick="filtrarTickets('asignados')" id="btn-asignados" class="bg-[#5750ad] text-white px-4 py-2 rounded-lg text-sm">Asignados</button>
                            </div>
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

                <!-- Panel resolución -->
                <div class="col-span-12 lg:col-span-4 flex flex-col gap-6">
                    <div class="tarjeta animar">
                        <h2 class="text-2xl font-bold mb-5 flex items-center gap-2">
                            <span class="material-symbols-outlined text-[#5750ad]">task_alt</span>Resolución
                        </h2>
                        <div class="border-2 border-dashed rounded-xl p-8 text-center bg-gray-50 mb-6 cursor-pointer" id="zonaSubida">
                            <span class="material-symbols-outlined text-5xl text-gray-400">cloud_upload</span>
                            <p class="font-bold mt-3">Subir Evidencias</p>
                            <p class="text-sm text-gray-500">PNG, JPG o PDF</p>
                            <input type="file" id="archivoEvidencia" class="hidden" accept=".png,.jpg,.jpeg,.pdf">
                        </div>
                        <textarea id="descripcionSolucion" placeholder="Describe la solución..." class="w-full border rounded-xl p-4 h-32 outline-none focus:border-[#5750ad] mb-5"></textarea>
                        <div class="flex gap-3">
                            <button onclick="resolverTicket()" class="bg-[#5750ad] text-white px-5 py-3 rounded-xl w-full hover:opacity-90 transition">Resolver Ticket</button>
                            <button class="border px-5 py-3 rounded-xl hover:bg-gray-100 transition">Guardar</button>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <footer class="text-center text-gray-500 text-sm border-t mt-10 p-4 bg-white rounded-xl">
            <p>© 2026 ServiceCore Corporation</p>
    </footer>

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
        tarjetas.forEach(t => { t.style.opacity="0"; t.style.transform="translateY(30px)"; t.style.transition="transform .4s ease, opacity .4s ease, box-shadow .3s ease"; });
        window.addEventListener("load", () => { tarjetas.forEach((t,i) => setTimeout(() => { t.style.opacity="1"; t.style.transform="translateY(0)"; }, i*120)); });
        tarjetas.forEach(t => {
            t.addEventListener("mouseenter", () => { t.style.transform="translateY(-6px)"; t.style.boxShadow="0 12px 25px rgba(0,0,0,0.08)"; });
            t.addEventListener("mouseleave", () => { t.style.transform="translateY(0)"; t.style.boxShadow="0 2px 10px rgba(0,0,0,0.05)"; });
        });

        document.getElementById('zonaSubida').addEventListener('click', () => document.getElementById('archivoEvidencia').click());

        let todosLosTickets  = [];
        let ticketSeleccionado = null;

        function colorEstado(e) {
            const m = {'Pendiente':'bg-yellow-100 text-yellow-700','En proceso':'bg-blue-100 text-blue-700','Cerrado':'bg-green-100 text-green-700','Cancelado':'bg-red-100 text-red-700'};
            return m[e] || 'bg-gray-100 text-gray-700';
        }
        function colorBorde(p) {
            const m = {'Alta':'border-red-500','Media':'border-orange-400','Baja':'border-blue-400'};
            return m[p] || 'border-gray-300';
        }
        function colorPrioridad(p) {
            const m = {'Alta':'text-red-600','Media':'text-orange-600','Baja':'text-blue-600'};
            return m[p] || 'text-gray-600';
        }

        function renderTickets(lista) {
            const tbody = document.getElementById('tabla-tickets');
            if (!lista || lista.length === 0) {
                tbody.innerHTML = '<tr><td colspan="5" class="p-5 text-center text-gray-400">Sin tickets asignados</td></tr>';
                return;
            }
            tbody.innerHTML = lista.map(t => `
                <tr class="fila hover:bg-gray-50 transition cursor-pointer" onclick="seleccionarTicket(${t.id_ticket})">
                    <td class="p-5 border-l-4 ${colorBorde(t.prioridad)} font-bold">#TK-${t.id_ticket}</td>
                    <td class="p-5">
                        <p class="font-bold">${t.titulo}</p>
                        <p class="text-sm text-gray-500">${t.cliente || '—'}</p>
                    </td>
                    <td class="p-5"><span class="px-3 py-1 rounded-full text-xs font-bold ${colorEstado(t.estado)}">${t.estado}</span></td>
                    <td class="p-5 font-bold ${colorPrioridad(t.prioridad)}">${t.prioridad}</td>
                    <td class="p-5">
                        <button class="hover:scale-110 transition" onclick="event.stopPropagation(); window.ChatWidgetAgente && window.ChatWidgetAgente.abrir(${t.id_ticket})">
                            <span class="material-symbols-outlined text-[#5750ad]">chat</span>
                        </button>
                    </td>
                </tr>
            `).join('');
        }

        function filtrarTickets(tipo) {
            document.getElementById('btn-todos').className     = tipo === 'todos'    ? 'bg-[#5750ad] text-white px-4 py-2 rounded-lg text-sm' : 'bg-gray-100 px-4 py-2 rounded-lg text-sm';
            document.getElementById('btn-asignados').className = tipo === 'asignados' ? 'bg-[#5750ad] text-white px-4 py-2 rounded-lg text-sm' : 'bg-gray-100 px-4 py-2 rounded-lg text-sm';
            renderTickets(todosLosTickets);
        }

        function seleccionarTicket(idTicket) {
            ticketSeleccionado = idTicket;
            document.querySelectorAll('.fila').forEach(f => f.classList.remove('bg-purple-50'));
            const fila = document.querySelector(`.fila[onclick="seleccionarTicket(${idTicket})"]`);
            if (fila) fila.classList.add('bg-purple-50');
        }

        async function resolverTicket() {
            if (!ticketSeleccionado) { alert('Selecciona un ticket primero'); return; }
            const descripcion = document.getElementById('descripcionSolucion').value.trim();
            if (!descripcion) { alert('Escribe la solución'); return; }
            try {
                await fetch(`/api/tickets/${ticketSeleccionado}/cerrar`, { method: 'PATCH' });
                await fetch('/api/historial', {
                    method: 'POST',
                    headers: {'Content-Type':'application/json'},
                    body: JSON.stringify({ id_ticket: ticketSeleccionado, accion: 'Ticket resuelto', campo_modificado: 'estado', valor_anterior: 'En proceso', valor_nuevo: 'Cerrado' })
                });
                document.getElementById('descripcionSolucion').value = '';
                ticketSeleccionado = null;
                cargarTickets();
                alert('Ticket resuelto correctamente');
            } catch (err) {
                console.error('Error:', err);
            }
        }

        async function cargarTickets() {
            try {
                const res  = await fetch('/api/tickets/agente/<?= $idUsuario ?>');
                const data = await res.json();
                todosLosTickets = Array.isArray(data) ? data : [];

                const pendientes = todosLosTickets.filter(t => t.estado === 'Pendiente').length;
                const proceso    = todosLosTickets.filter(t => t.estado === 'En proceso').length;
                const cerrados   = todosLosTickets.filter(t => t.estado === 'Cerrado').length;

                document.getElementById('stat-total').textContent     = todosLosTickets.length;
                document.getElementById('stat-pendientes').textContent = pendientes;
                document.getElementById('stat-proceso').textContent    = proceso;
                document.getElementById('stat-cerrados').textContent   = cerrados;

                renderTickets(todosLosTickets);
            } catch (err) {
                console.error('Error cargando tickets:', err);
            }
        }

        cargarTickets();
    </script>

    <script>window.ID_USUARIO_ACTUAL = <?= $idUsuario ?>;</script>
    <script src="../../js/chat_widget_agente.js"></script>
</body>
</html>
