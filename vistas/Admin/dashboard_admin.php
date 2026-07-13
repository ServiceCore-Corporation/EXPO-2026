<?php
define('ROL_REQUERIDO', 1);
require_once '../../seguridad.php';
$nombreUsuario = htmlspecialchars($_SESSION['nombre']);
$correoUsuario = htmlspecialchars($_SESSION['correo']);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel Administrador | ServiceCore</title>
    <link rel="icon" type="image/png" href="../../img/LogoNav.png">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../../css/dashboard_admin.css">
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
                <p class="font-bold"><?= $nombreUsuario ?></p>
                <p class="text-sm text-gray-500">Administrador</p>
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
            <a href="dashboard_admin.php" class="menu-item activo">
                <span class="material-symbols-outlined">dashboard</span>Inicio
            </a>
            <a href="gestion_empresas.php" class="menu-item ">
                <span class="material-symbols-outlined">business</span> Gestion de Empresas
            </a>
            <a href="gestion_carrusel.php" class="menu-item">
                <span class="material-symbols-outlined">view_carousel</span>Gestión de Carrusel
            </a>
            <a href="gestion_galeria.php" class="menu-item">
                <span class="material-symbols-outlined">photo_library</span>Gestión de Galería
            </a>
            <a href="gestion_planes.php" class="menu-item">
                <span class="material-symbols-outlined">workspace_premium</span>Planes
            </a>
            <a href="gestion_pagos.php" class="menu-item">
                <span class="material-symbols-outlined">payments</span>Pagos
            </a>
            <a href="reportes.php" class="menu-item">
                <span class="material-symbols-outlined">insights</span>Reportes
            </a>
            <a href="../historial.php" class="menu-item">
                <span class="material-symbols-outlined">history</span>Historial y Auditoría
            </a>
        </nav>

        <br><br>
        <!-- Cerrar sesión -->
        <a href="../../logout.php"class="mt-auto flex items-center justify-center gap-3 w-full py-3 rounded-xl border-2 border-red-500 text-red-400 font-semibold transition-all duration-300 hover:bg-red-500 hover:text-white hover:shadow-lg">
            <span class="material-symbols-outlined">logout</span>
            Cerrar Sesión
        </a>
    </aside>

    <!-- CONTENIDO PRINCIPAL -->
    <main class="contenido ml-64 pt-24 px-8 pb-10">
        <section class="mb-8">
            <h2 class="text-4xl font-bold text-[#1e1858]">Bienvenido, <?= $nombreUsuario ?></h2>
            <p class="text-gray-500 mt-2">Panel de administración de ServiceCore.</p>
        </section>

        <!-- Tarjetas estadísticas -->
        <section class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8" id="tarjetas-stats">
            <div class="tarjeta animar">
                <p class="text-sm text-gray-500 uppercase mb-2">Total Tickets</p>
                <h2 class="text-4xl font-bold text-[#5750ad]" id="stat-tickets">--</h2>
            </div>
            <div class="tarjeta animar">
                <p class="text-sm text-gray-500 uppercase mb-2">Usuarios</p>
                <h2 class="text-4xl font-bold" id="stat-usuarios">--</h2>
            </div>
            <div class="tarjeta animar">
                <p class="text-sm text-gray-500 uppercase mb-2">Empresas</p>
                <h2 class="text-4xl font-bold" id="stat-empresas">--</h2>
            </div>
            <div class="tarjeta animar">
                <p class="text-sm text-gray-500 uppercase mb-2">Ingresos</p>
                <h2 class="text-4xl font-bold" id="stat-ingresos">--</h2>
            </div>
        </section>

        <!-- Tickets recientes -->
        <div class="grid grid-cols-12 gap-6">
            <div class="col-span-12 lg:col-span-8">
                <div class="tarjeta animar p-0 overflow-hidden">
                    <div class="flex justify-between items-center p-6 border-b">
                        <h2 class="text-2xl font-bold">Tickets Recientes</h2>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead class="bg-gray-50 text-gray-500 uppercase text-sm">
                                <tr>
                                    <th class="p-5 text-left">Ticket</th>
                                    <th class="p-5 text-left">Asunto</th>
                                    <th class="p-5 text-left">Estado</th>
                                    <th class="p-5 text-left">Prioridad</th>
                                </tr>
                            </thead>
                            <tbody id="tabla-tickets">
                                <tr><td colspan="4" class="p-5 text-center text-gray-400">Cargando...</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Panel derecho - tickets por estado -->
            <div class="col-span-12 lg:col-span-4">
                <div class="tarjeta animar">
                    <h2 class="text-xl font-bold mb-5">Estado de Tickets</h2>
                    <div id="estado-tickets" class="space-y-4">
                        <p class="text-gray-400 text-sm">Cargando...</p>
                    </div>
                </div>
            </div>
        </div>
    </main>
    <footer class="text-center text-gray-500 text-sm border-t mt-10 p-4 bg-white rounded-xl">
            <p>© 2026 ServiceCore Corporation</p>
    </footer>
    
    <script src="../../js/dashboard_admin.js"></script>
</body>
</html>
