<?php
define('ROL_REQUERIDO', 1);
require_once '../../seguridad.php';

$nombreUsuario = $_SESSION['nombre'] ?? 'Administrador';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reportes — ServiceCore</title>
    <link rel="icon" type="image/png" href="../../img/LogoNav.png">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4"></script>
    <link rel="stylesheet" href="../../css/admin_empresas.css">
    <link rel="stylesheet" href="../../css/admin_reportes.css">
</head>
<body>
    <!-- ENCABEZADO -->
    <header class="fixed top-0 left-64 right-0 h-16 bg-white shadow flex items-center justify-between px-8 z-50">
        <div class="flex items-center gap-4">
            <span class="material-symbols-outlined text-[#5750ad]">menu</span>
            <h1 class="text-xl font-bold text-[#1e1858]">Panel Administrador</h1>
        </div>
        <div class="relative flex items-center gap-4">
            <span class="material-symbols-outlined cursor-pointer">notifications</span>
            <div class="text-right">
                <p class="font-bold"><?= htmlspecialchars($nombreUsuario) ?></p>
                <p class="text-sm text-gray-500">Administrador</p>
            </div>
            <div id="botonUsuario" class="w-10 h-10 rounded-full cursor-pointer border-2 border-[#5750ad] bg-[#5750ad] flex items-center justify-center text-white font-bold">
                <?= mb_strtoupper(mb_substr($nombreUsuario, 0, 1)) ?>
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
            <a href="dashboard_admin.php" class="menu-item">
                <span class="material-symbols-outlined">dashboard</span>Inicio
            </a>
            <a href="gestion_empresas.php" class="menu-item">
                <span class="material-symbols-outlined">business</span> Gestion de Empresas
            </a>
            <a href="carrusel.php" class="menu-item">
                <span class="material-symbols-outlined">view_carousel</span>Gestión de Carrusel
            </a>
            <a href="galeria.php" class="menu-item">
                <span class="material-symbols-outlined">photo_library</span>Gestión de Galería
            </a>
            <a href="#" class="menu-item">
                <span class="material-symbols-outlined">workspace_premium</span>Planes
            </a>
            <a href="#" class="menu-item">
                <span class="material-symbols-outlined">payments</span>Pagos
            </a>
            <a href="reportes.php" class="menu-item activo">
                <span class="material-symbols-outlined">insights</span>Reportes
            </a>
        </nav>

        <div class="flex-grow"></div>
        <a href="../../logout.php" class="mt-auto flex items-center justify-center gap-3 w-full py-3 rounded-xl border-2 border-red-500 text-red-400 font-semibold transition-all duration-300 hover:bg-red-500 hover:text-white hover:shadow-lg">
            <span class="material-symbols-outlined">logout</span>
            Cerrar Sesión
        </a>
    </aside>

    <!-- CONTENIDO -->
    <main class="contenido ml-64 pt-16 min-h-screen">
        <div class="p-8">

            <!-- Encabezado de página + botón de descarga -->
            <div class="flex flex-wrap items-center justify-between gap-4 mb-6">
                <div>
                    <h2 class="text-2xl font-bold text-[#1e1858]">Reportes de Mesa de Servicio</h2>
                    <p class="text-gray-500 text-sm mt-1">Métricas, gráficas y resumen de tickets del periodo seleccionado.</p>
                </div>
                <button id="btnDescargarPDF" class="flex items-center gap-2 bg-[#5750ad] hover:bg-[#1e1858] text-white font-semibold px-5 py-3 rounded-xl transition shadow">
                    <span class="material-symbols-outlined">picture_as_pdf</span>
                    Descargar PDF
                </button>
            </div>

            <!-- ===================== FILTROS ===================== -->
            <div class="bg-white rounded-2xl shadow p-5 mb-6">
                <form id="formFiltros" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4 items-end">
                    <div>
                        <label class="campo-label">Desde</label>
                        <input type="date" id="filtroFechaInicio" class="campo-input">
                    </div>
                    <div>
                        <label class="campo-label">Hasta</label>
                        <input type="date" id="filtroFechaFin" class="campo-input">
                    </div>
                    <div>
                        <label class="campo-label">Estado</label>
                        <select id="filtroEstado" class="campo-input">
                            <option value="todos">Todos</option>
                            <option value="Pendiente">Pendiente</option>
                            <option value="En proceso">En proceso</option>
                            <option value="Cerrado">Cerrado</option>
                            <option value="Cancelado">Cancelado</option>
                        </select>
                    </div>
                    <div>
                        <label class="campo-label">Prioridad</label>
                        <select id="filtroPrioridad" class="campo-input">
                            <option value="todos">Todas</option>
                            <option value="Baja">Baja</option>
                            <option value="Media">Media</option>
                            <option value="Alta">Alta</option>
                        </select>
                    </div>
                    <button type="submit" class="btn-primario">Aplicar filtros</button>
                </form>
            </div>

            <!-- ===================== TARJETAS DE MÉTRICAS ===================== -->
            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4 mb-6" id="contenedorMetricas">
                <!-- Generadas por JS (renderMetricas) para poder actualizarse sin recargar la página -->
                <div class="tarjeta-metrica"><span class="valor" data-metrica="total">—</span><span class="etiqueta">Tickets totales</span></div>
                <div class="tarjeta-metrica"><span class="valor" data-metrica="resueltos">—</span><span class="etiqueta">Resueltos</span></div>
                <div class="tarjeta-metrica"><span class="valor" data-metrica="abiertos">—</span><span class="etiqueta">Abiertos</span></div>
                <div class="tarjeta-metrica"><span class="valor" data-metrica="en_progreso">—</span><span class="etiqueta">En progreso</span></div>
                <div class="tarjeta-metrica"><span class="valor" data-metrica="tiempo_promedio_horas">—</span><span class="etiqueta">Tiempo prom. (h)</span></div>
                <div class="tarjeta-metrica"><span class="valor" data-metrica="cumplimiento_sla_pct">—</span><span class="etiqueta">SLA cumplido</span></div>
            </div>

            <!-- ===================== GRÁFICAS ===================== -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
                <div class="bg-white rounded-2xl shadow p-5">
                    <h3 class="titulo-grafica">Tickets por estado</h3>
                    <div class="contenedor-canvas"><canvas id="graficaEstado"></canvas></div>
                </div>
                <div class="bg-white rounded-2xl shadow p-5">
                    <h3 class="titulo-grafica">Tickets por prioridad</h3>
                    <div class="contenedor-canvas"><canvas id="graficaPrioridad"></canvas></div>
                </div>
                <div class="bg-white rounded-2xl shadow p-5 lg:col-span-2">
                    <h3 class="titulo-grafica">Tendencia: creados vs. resueltos</h3>
                    <div class="contenedor-canvas contenedor-canvas--ancho"><canvas id="graficaTendencia"></canvas></div>
                </div>
                <div class="bg-white rounded-2xl shadow p-5 lg:col-span-2">
                    <h3 class="titulo-grafica">Tickets resueltos por agente</h3>
                    <div class="contenedor-canvas contenedor-canvas--ancho"><canvas id="graficaAgentes"></canvas></div>
                </div>
            </div>

            <!-- ===================== TABLA RESUMEN DE TICKETS ===================== -->
            <div class="bg-white rounded-2xl shadow p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-bold text-[#1e1858]">Tickets recientes del periodo</h3>
                    <span class="text-xs text-gray-400">Vista previa — el PDF incluye hasta 50 registros</span>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm" id="tablaTickets">
                        <thead>
                            <tr class="text-left text-gray-500 border-b border-gray-100">
                                <th class="py-3 pr-4 font-semibold">Folio</th>
                                <th class="py-3 pr-4 font-semibold">Asunto</th>
                                <th class="py-3 pr-4 font-semibold">Cliente</th>
                                <th class="py-3 pr-4 font-semibold">Agente</th>
                                <th class="py-3 pr-4 font-semibold">Prioridad</th>
                                <th class="py-3 pr-4 font-semibold">Estado</th>
                                <th class="py-3 pr-4 font-semibold">Creado</th>
                            </tr>
                        </thead>
                        <tbody id="cuerpoTablaTickets">
                            <!-- Filas generadas por JS -->
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </main>

    <script src="../../js/admin_reportes.js"></script>
</body>
</html>
