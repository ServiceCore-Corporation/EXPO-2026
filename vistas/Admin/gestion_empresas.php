<?php
define('ROL_REQUERIDO', 1);
require_once '../../seguridad.php';
$nombreUsuario = htmlspecialchars($_SESSION['nombre']);
$correoUsuario = htmlspecialchars($_SESSION['correo']);

$adminEmpresas = [
    ['id' => 'AE-001', 'nombre' => 'Roberto Sánchez',   'correo' => 'roberto.sanchez@clientecorp.com',        'empresa' => 'Cliente Corp',          'telefono' => '+502 4012-3344', 'estado' => 'Activo',   'fecha' => '01 feb 2025'],
    ['id' => 'AE-002', 'nombre' => 'Ana Gómez',          'correo' => 'ana.gomez@bancacentral.com',             'empresa' => 'Banca Central',         'telefono' => '+502 5588-2210', 'estado' => 'Activo',   'fecha' => '15 mar 2025'],
    ['id' => 'AE-003', 'nombre' => 'Elena Rodríguez',    'correo' => 'elena.rodriguez@xyz.com',                'empresa' => 'Empresa XYZ',           'telefono' => '+502 4477-9981', 'estado' => 'Inactivo', 'fecha' => '20 dic 2024'],
    ['id' => 'AE-004', 'nombre' => 'Mauricio Pineda',    'correo' => 'mauricio.pineda@grupoandino.com',        'empresa' => 'Grupo Andino',          'telefono' => '+502 3322-1190', 'estado' => 'Activo',   'fecha' => '09 ene 2025'],
    ['id' => 'AE-005', 'nombre' => 'Lucía Fernández',    'correo' => 'lucia.fernandez@constructoradelta.com',  'empresa' => 'Constructora Delta',    'telefono' => '+502 5566-7788', 'estado' => 'Activo',   'fecha' => '28 abr 2025'],
    ['id' => 'AE-006', 'nombre' => 'Diego Castellanos',  'correo' => 'diego.castellanos@polaris-inv.com',      'empresa' => 'Inversiones Polaris',   'telefono' => '+502 4499-0021', 'estado' => 'Inactivo', 'fecha' => '12 jul 2024'],
    ['id' => 'AE-007', 'nombre' => 'Valeria Ortiz',      'correo' => 'valeria.ortiz@logisticameridiano.com',   'empresa' => 'Logística Meridiano',   'telefono' => '+502 5511-3340', 'estado' => 'Activo',   'fecha' => '03 oct 2025'],
    ['id' => 'AE-008', 'nombre' => 'Hugo Martínez',      'correo' => 'hugo.martinez@comercialatlas.com',       'empresa' => 'Comercial Atlas',       'telefono' => '+502 4400-8821', 'estado' => 'Activo',   'fecha' => '17 nov 2025'],
];

$kpiTotal     = count($adminEmpresas);
$kpiActivos   = count(array_filter($adminEmpresas, fn($u) => $u['estado'] === 'Activo'));
$kpiInactivos = $kpiTotal - $kpiActivos;
$kpiEmpresas  = count(array_unique(array_column($adminEmpresas, 'empresa')));

function badgeClassEstado($estado) {
    return $estado === 'Activo' ? 'badge badge-green' : 'badge badge-gray';
}

function inicialesAE($nombre) {
    $partes = explode(' ', trim($nombre));
    $ini = '';
    foreach (array_slice($partes, 0, 2) as $p) { $ini .= mb_substr($p, 0, 1); }
    return mb_strtoupper($ini);
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion de Empresas — ServiceCore</title>
    <link rel="icon" type="image/png" href="../../img/LogoNav.png">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" rel="stylesheet">
    <link rel="stylesheet" href="../../css/admin_empresas.css">
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
            <a href="dashboard_admin.php" class="menu-item">
                <span class="material-symbols-outlined">dashboard</span>Inicio
            </a>
            <a href="gestion_empresas.php" class="menu-item activo">
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
            <a href="../historial.php" class="menu-item">
                <span class="material-symbols-outlined">history</span>Historial y Auditoría
            </a>
            <a href="reportes.php" class="menu-item">
                <span class="material-symbols-outlined">insights</span>Reportes
            </a>
        </nav>

        <div class="flex-grow"></div>
        <!-- Cerrar sesión -->
        <a href="../../logout.php"class="mt-auto flex items-center justify-center gap-3 w-full py-3 rounded-xl border-2 border-red-500 text-red-400 font-semibold transition-all duration-300 hover:bg-red-500 hover:text-white hover:shadow-lg">
            <span class="material-symbols-outlined">logout</span>
            Cerrar Sesión
        </a>

    </aside>

    <main class="contenido ml-64 pt-24 px-8 pb-10">
        <section class="mb-8">
            <h2 class="text-4xl font-bold text-[#1e1858]">Gestion de Empresas</h2>
            <p class="text-gray-500 mt-2">Agrega, edita y visualiza tus empresas asociadas.</p>
        </section>

        <!-- KPIs -->
        <section class="kpi-grid" id="kpiGrid">
            <article class="card kpi primary kpi-clickable active" data-filter-estado="">
                <div class="kpi-icon"><span class="material-symbols-outlined">groups</span></div>
                <div>
                    <p>Total Empresas</p>
                    <h3 data-kpi="total"><?= $kpiTotal ?></h3>
                    <span>En todas las empresas</span>
                </div>
            </article>
            <article class="card kpi green kpi-clickable" data-filter-estado="Activo">
                <div class="kpi-icon"><span class="material-symbols-outlined">toggle_on</span></div>
                <div>
                    <p>Activos</p>
                    <h3 data-kpi="activos"><?= $kpiActivos ?></h3>
                    <span>Con acceso habilitado</span>
                </div>
            </article>
            <article class="card kpi gray kpi-clickable" data-filter-estado="Inactivo">
                <div class="kpi-icon"><span class="material-symbols-outlined">toggle_off</span></div>
                <div>
                    <p>Inactivos</p>
                    <h3 data-kpi="inactivos"><?= $kpiInactivos ?></h3>
                    <span>Con acceso deshabilitado</span>
                </div>
            </article>
            <article class="card kpi purple">
                <div class="kpi-icon"><span class="material-symbols-outlined">domain</span></div>
                <div>
                    <p>Empresas registradas</p>
                    <h3 data-kpi="empresas"><?= $kpiEmpresas ?></h3>
                    <span>Con administrador asignado</span>
                </div>
            </article>
        </section>

        <!-- Barra de controles -->
        <section class="controls-bar">
            <div class="controls-left">
                <div class="search-box">
                    <span class="material-symbols-outlined">search</span>
                    <input type="search" id="buscadorAE" placeholder="Buscar por nombre, correo o empresa...">
                </div>
                <select id="filterEstadoAE" class="input-small">
                    <option value="">Todos los estados</option>
                    <option value="Activo">Solo activos</option>
                    <option value="Inactivo">Solo inactivos</option>
                </select>
                <button class="btn btn-light" id="btnLimpiarFiltrosAE">
                    <span class="material-symbols-outlined">refresh</span> Limpiar filtros
                </button>
            </div>
            <button class="btn btn-primary" id="btnNuevoAE">
                <span class="material-symbols-outlined">person_add</span> Agregar Empresa
            </button>
        </section>

        <!-- Listado -->
        <section class="table-wrap card">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Admin-Empresa</th>
                        <th>Empresa asignada</th>
                        <th>Teléfono</th>
                        <th>Estado</th>
                        <th>Alta</th>
                        <th class="th-actions">Acciones</th>
                    </tr>
                </thead>
                <tbody id="tablaAE">
                    <?php foreach ($adminEmpresas as $u): ?>
                        <tr class="ae-row"
                            data-id="<?= htmlspecialchars($u['id']) ?>"
                            data-nombre="<?= htmlspecialchars($u['nombre']) ?>"
                            data-correo="<?= htmlspecialchars($u['correo']) ?>"
                            data-empresa="<?= htmlspecialchars($u['empresa']) ?>"
                            data-telefono="<?= htmlspecialchars($u['telefono']) ?>"
                            data-estado="<?= htmlspecialchars($u['estado']) ?>"
                            data-fecha="<?= htmlspecialchars($u['fecha']) ?>"
                        >
                            <td>
                                <div class="ae-user">
                                    <div class="avatar small accent-purple"><?= htmlspecialchars(inicialesAE($u['nombre'])) ?></div>
                                    <div class="ae-user-id">
                                        <strong data-row-nombre><?= htmlspecialchars($u['nombre']) ?></strong>
                                        <span data-row-correo><?= htmlspecialchars($u['correo']) ?></span>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <span class="empresa-chip">
                                    <span class="material-symbols-outlined">domain</span>
                                    <span data-row-empresa><?= htmlspecialchars($u['empresa']) ?></span>
                                </span>
                            </td>
                            <td><span data-row-telefono><?= htmlspecialchars($u['telefono'] ?: '—') ?></span></td>
                            <td><span class="<?= badgeClassEstado($u['estado']) ?>" data-row-estado-badge><?= htmlspecialchars($u['estado']) ?></span></td>
                            <td><span data-row-fecha><?= htmlspecialchars($u['fecha']) ?></span></td>
                            <td>
                                <div class="row-actions">
                                    <button class="row-icon-btn" data-action="editar" title="Editar Admin-Empresa">
                                        <span class="material-symbols-outlined">edit</span>
                                    </button>
                                    <button class="row-icon-btn toggle-btn" data-action="toggle-estado" title="Habilitar / deshabilitar">
                                        <span class="material-symbols-outlined" data-row-toggle-icon><?= $u['estado'] === 'Activo' ? 'toggle_on' : 'toggle_off' ?></span>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <p class="empty-table" id="emptyAE" style="display:none;">
                <span class="material-symbols-outlined">search_off</span>
                No se encontraron Admin-Empresas que coincidan con tu búsqueda.
            </p>
        </section>
        
    </main>
    
    <footer class="text-center text-gray-500 text-sm border-t mt-10 p-4 bg-white rounded-xl">
            <p>© 2026 ServiceCore Corporation</p>
    </footer>

    <!-- Modal: crear / editar Admin-Empresa -->
    <div class="modal" id="modalAE">
        <div class="modal-content modal-wide">
            <button class="modal-close" id="modalAECerrar">×</button>
            <div class="modal-icon"><span class="material-symbols-outlined">person_add</span></div>
            <h3 id="modalAETitulo">Nuevo Admin-Empresa</h3>
            <p id="modalAESub">Completa los datos para registrar un nuevo administrador de empresa.</p>

            <form id="formAE" novalidate>
                <input type="hidden" id="aeId">

                <div class="form-group" style="text-align:left;">
                    <label for="aeNombre">Nombre completo</label>
                    <div class="input-icon">
                        <span class="material-symbols-outlined">person</span>
                        <input type="text" id="aeNombre" placeholder="Nombre y apellidos">
                    </div>
                    <small class="field-error" id="errorAENombre"></small>
                </div>

                <div class="form-group" style="text-align:left;">
                    <label for="aeCorreo">Correo electrónico</label>
                    <div class="input-icon">
                        <span class="material-symbols-outlined">mail</span>
                        <input type="email" id="aeCorreo" placeholder="correo@empresa.com">
                    </div>
                    <small class="field-error" id="errorAECorreo"></small>
                </div>

                <div class="form-group" style="text-align:left;">
                    <label for="aeEmpresa">Empresa asignada</label>
                    <div class="input-icon">
                        <span class="material-symbols-outlined">domain</span>
                        <input type="text" id="aeEmpresa" placeholder="Nombre de la empresa">
                    </div>
                    <small class="field-error" id="errorAEEmpresa"></small>
                </div>

                <div class="form-row">
                    <div class="form-group" style="text-align:left;">
                        <label for="aeTelefono">Teléfono (opcional)</label>
                        <div class="input-icon">
                            <span class="material-symbols-outlined">call</span>
                            <input type="text" id="aeTelefono" placeholder="+502 0000-0000">
                        </div>
                    </div>

                    <div class="form-group" style="text-align:left;">
                        <label for="aeEstado">Estado</label>
                        <div class="input-icon">
                            <span class="material-symbols-outlined">toggle_on</span>
                            <select id="aeEstado">
                                <option value="Activo">Activo</option>
                                <option value="Inactivo">Inactivo</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="modal-actions">
                    <button type="button" class="btn btn-light" id="btnCancelarAE">Cancelar</button>
                    <button type="submit" class="btn btn-primary" id="btnGuardarAE">
                        <span class="material-symbols-outlined">save</span> Guardar Empresa
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal de confirmación reutilizable -->
    <div class="modal" id="modalConfirmarAE">
        <div class="modal-content">
            <button class="modal-close" id="modalConfirmarAECerrar">×</button>
            <div class="modal-icon"><span class="material-symbols-outlined">help</span></div>
            <h3 id="modalConfirmarAETitulo">Confirmar acción</h3>
            <p id="modalConfirmarAEMensaje">¿Deseas continuar con esta acción?</p>
            <div class="modal-actions">
                <button class="btn btn-light" id="modalConfirmarAECancelar">Cancelar</button>
                <button class="btn btn-danger" id="modalConfirmarAEAceptar">Confirmar</button>
            </div>
        </div>
    </div>

    <div class="toast-container" id="toastContainer"></div>

    <script src="../../js/admin_empresas.js"></script>
    <script src="../../js/dashboard_admin.js"></script>
</body>
</html>
