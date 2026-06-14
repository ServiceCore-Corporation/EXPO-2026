<?php
define('ROL_REQUERIDO', 2);
require_once 'seguridad.php';
$nombreUsuario = htmlspecialchars($_SESSION['nombre']);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel Empresa | ServiceCore</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; background: #f4f7ff; }
        .tarjeta { background: white; border-radius: 14px; border: 1px solid #dbe3f5; box-shadow: 0 2px 6px rgba(0,0,0,0.05); padding: 24px; transition: 0.3s; }
        .menu-item { display: flex; align-items: center; gap: 10px; padding: 12px; border-radius: 10px; transition: 0.3s; color: #d1d5db; }
        .menu-item:hover { background: rgba(255,255,255,0.1); color: white; }
        .activo { background: #5750ad; color: white; }
        .boton { background: #5750ad; color: white; padding: 10px 18px; border-radius: 10px; transition: 0.3s; }
        .boton:hover { background: #433f7f; }
    </style>
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
                <a href="#" class="flex items-center gap-3 px-4 py-3 hover:bg-gray-100 transition">
                    <span class="material-symbols-outlined text-gray-600">person</span>Perfil
                </a>
                <a href="logout.php" class="flex items-center gap-3 px-4 py-3 hover:bg-red-50 text-red-600 transition">
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
            <a href="dashboard_admin_emp.php" class="menu-item activo">
                <span class="material-symbols-outlined">insights</span>Estadísticas
            </a>
            <a href="#" class="menu-item">
                <span class="material-symbols-outlined">confirmation_number</span>Tickets
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
    <main class="ml-64 pt-24 p-8">
        <section class="flex justify-between items-center mb-8">
            <div>
                <h2 class="text-4xl font-bold text-[#1e1858]">Resumen General</h2>
                <p class="text-gray-500 mt-2">Métricas y estadísticas del sistema</p>
            </div>
            <button class="boton flex items-center gap-2">
                <span class="material-symbols-outlined">download</span>Exportar Reporte
            </button>
        </section>

        <!-- Tarjetas estadísticas -->
        <section class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
            <div class="tarjeta animar md:col-span-2">
                <p class="text-gray-500 uppercase text-sm">Total Tickets</p>
                <h3 class="text-5xl font-black mt-2" id="stat-total">--</h3>
            </div>
            <div class="tarjeta animar border-l-4 border-green-500">
                <p class="text-gray-500">Cerrados</p>
                <h3 class="text-3xl font-bold mt-2" id="stat-cerrados">--</h3>
            </div>
            <div class="tarjeta animar border-l-4 border-yellow-500">
                <p class="text-gray-500">Pendientes</p>
                <h3 class="text-3xl font-bold mt-2" id="stat-pendientes">--</h3>
            </div>
        </section>

        <!-- Actividad reciente y alertas -->
        <section class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
            <div class="tarjeta animar lg:col-span-2">
                <div class="flex justify-between items-center mb-6">
                    <h3 class="text-2xl font-bold">Tickets Recientes</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-50 text-gray-500 uppercase text-sm">
                            <tr>
                                <th class="p-4 text-left">Ticket</th>
                                <th class="p-4 text-left">Título</th>
                                <th class="p-4 text-left">Estado</th>
                                <th class="p-4 text-left">Prioridad</th>
                            </tr>
                        </thead>
                        <tbody id="tabla-tickets">
                            <tr><td colspan="4" class="p-4 text-center text-gray-400">Cargando...</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="tarjeta animar">
                <h3 class="text-xl font-bold mb-4">Usuarios del Sistema</h3>
                <div id="lista-usuarios" class="space-y-3">
                    <p class="text-gray-400 text-sm">Cargando...</p>
                </div>
            </div>
        </section>

        <footer class="text-center text-gray-500 text-sm border-t mt-10 p-4 bg-white rounded-xl">
            <p>© 2026 ServiceCore Corporation</p>
        </footer>
    </main>

    <script>
        const botonUsuario = document.getElementById("botonUsuario");
        const menuUsuario  = document.getElementById("menuUsuario");
        botonUsuario.addEventListener("click", () => menuUsuario.classList.toggle("hidden"));
        document.addEventListener("click", (e) => {
            if (!botonUsuario.contains(e.target) && !menuUsuario.contains(e.target))
                menuUsuario.classList.add("hidden");
        });

        const tarjetas = document.querySelectorAll(".animar");
        tarjetas.forEach(t => { t.style.opacity = "0"; t.style.transform = "translateY(30px)"; t.style.transition = "transform .4s ease, opacity .4s ease"; });
        window.addEventListener("load", () => { tarjetas.forEach((t, i) => setTimeout(() => { t.style.opacity="1"; t.style.transform="translateY(0)"; }, i*120)); });

        function colorEstado(e) {
            const m = { 'Pendiente':'bg-yellow-100 text-yellow-700','En proceso':'bg-blue-100 text-blue-700','Cerrado':'bg-green-100 text-green-700','Cancelado':'bg-red-100 text-red-700' };
            return m[e] || 'bg-gray-100 text-gray-700';
        }
        function colorPrioridad(p) {
            const m = { 'Alta':'text-red-600','Media':'text-orange-500','Baja':'text-blue-500' };
            return m[p] || 'text-gray-600';
        }

        async function cargarDatos() {
            try {
                const [resTickets, resUsuarios] = await Promise.all([
                    fetch('/api/dashboard/tickets'),
                    fetch('/api/dashboard/usuarios')
                ]);
                const tickets  = await resTickets.json();
                const usuarios = await resUsuarios.json();

                document.getElementById('stat-total').textContent     = tickets.pendientes + tickets.en_proceso + tickets.cerrados;
                document.getElementById('stat-cerrados').textContent  = tickets.cerrados;
                document.getElementById('stat-pendientes').textContent = tickets.pendientes;

                const tbody = document.getElementById('tabla-tickets');
                tbody.innerHTML = (tickets.recientes || []).map(t => `
                    <tr class="hover:bg-gray-50 transition">
                        <td class="p-4 font-bold">#TK-${t.id_ticket}</td>
                        <td class="p-4 font-medium">${t.titulo}</td>
                        <td class="p-4"><span class="px-3 py-1 rounded-full text-xs font-bold ${colorEstado(t.estado)}">${t.estado}</span></td>
                        <td class="p-4 font-bold ${colorPrioridad(t.prioridad)}">${t.prioridad}</td>
                    </tr>
                `).join('') || '<tr><td colspan="4" class="p-4 text-center text-gray-400">Sin tickets</td></tr>';

                const listaUsuarios = document.getElementById('lista-usuarios');
                listaUsuarios.innerHTML = (usuarios.por_rol || []).map(r => `
                    <div class="flex justify-between items-center py-2 border-b last:border-0">
                        <span class="text-sm font-medium">${r.rol}</span>
                        <span class="bg-[#5750ad]/10 text-[#5750ad] px-3 py-1 rounded-full text-xs font-bold">${r.total}</span>
                    </div>
                `).join('');
            } catch (err) {
                console.error('Error cargando datos:', err);
            }
        }

        cargarDatos();
    </script>
</body>
</html>
