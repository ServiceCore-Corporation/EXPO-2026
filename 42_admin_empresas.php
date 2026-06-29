<?php
// En producción esto vendría de una consulta a la tabla de usuarios
// filtrada por rol = 'Admin-Empresa'.
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
    <title>Admin-Empresas — ServiceCore</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" rel="stylesheet">
<link rel="stylesheet" href="css/admin_empresas.css">
</head>
<body>

    <aside class="sidebar" id="sidebar">
        <div class="brand">
            <img src="img/logogi.png" alt="ServiceCore Corporation" class="logo">
        </div>

        <nav class="menu">
            <a href="#" class="menu-item"><span class="material-symbols-outlined">insights</span>Dashboard</a>
            <a href="#" class="menu-item"><span class="material-symbols-outlined">confirmation_number</span>Tickets</a>
            <a href="41_usuarios_roles.php" class="menu-item"><span class="material-symbols-outlined">manage_accounts</span>Usuarios por Rol</a>
            <a href="#" class="menu-item active"><span class="material-symbols-outlined">domain</span>Admin-Empresas</a>
            <a href="#" class="menu-item"><span class="material-symbols-outlined">category</span>Categorías</a>
            <a href="#" class="menu-item"><span class="material-symbols-outlined">history</span>Historial</a>
        </nav>

        <div class="sidebar-box">
            <p class="small-title">Resumen rápido</p>
            <span class="mini-tag"><span class="material-symbols-outlined">domain</span>Empresas registradas · <?= $kpiEmpresas ?></span>
            <span class="mini-tag"><span class="material-symbols-outlined">toggle_on</span>Admins activos · <?= $kpiActivos ?></span>
            <span class="mini-tag"><span class="material-symbols-outlined">toggle_off</span>Admins inactivos · <?= $kpiInactivos ?></span>
        </div>
    </aside>

    <header class="topbar">
        <button class="icon-btn mobile-only" id="btnSidebar"><span class="material-symbols-outlined">menu</span></button>
        <div>
            <p class="eyebrow">Panel Super Admin</p>
            <h2>Admin-Empresas</h2>
        </div>
        <div class="top-actions">
            <div class="search-box">
                <span class="material-symbols-outlined">search</span>
                <input type="search" id="buscadorAE" placeholder="Buscar por nombre, correo o empresa...">
            </div>
            <div class="profile" id="profileBtn">
                <div class="avatar">KS</div>
                <div>
                    <strong>Karla Solís</strong>
                    <span>ServiceCore Corporation / Super Admin</span>
                </div>
                <span class="material-symbols-outlined">expand_more</span>
            </div>
        </div>
    </header>

    <main class="content">

        <section class="page-head">
            <nav class="breadcrumb" aria-label="Ubicación actual">
                <a href="#">Panel</a>
                <span class="material-symbols-outlined">chevron_right</span>
                <a href="#">Usuarios</a>
                <span class="material-symbols-outlined">chevron_right</span>
                <span>Admin-Empresas</span>
            </nav>
            <h1>CRUD Admin-Empresas</h1>
            <p>Crea, lista, edita y habilita/deshabilita a los usuarios Admin-Empresa: las cuentas que administran el acceso de cada empresa cliente al sistema.</p>
        </section>

        <!-- KPIs -->
        <section class="kpi-grid" id="kpiGrid">
            <article class="card kpi primary kpi-clickable active" data-filter-estado="">
                <div class="kpi-icon"><span class="material-symbols-outlined">groups</span></div>
                <div>
                    <p>Total Admin-Empresas</p>
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
                <span class="material-symbols-outlined">person_add</span> Nuevo Admin-Empresa
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

        <footer class="footer">© 2026 ServiceCore Corporation — CRUD Admin-Empresas.</footer>
    </main>

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
                        <span class="material-symbols-outlined">save</span> Guardar Admin-Empresa
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

    <script src="admin_empresas.js"></script>
</body>
</html>
