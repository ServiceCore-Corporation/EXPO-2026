<?php
define('ROL_REQUERIDO', 2);
require_once '../../seguridad.php';
$nombreUsuario = htmlspecialchars($_SESSION['nombre']);
$correoUsuario = htmlspecialchars($_SESSION['correo']);

$rolesConfig = [
    'Administrador' => ['icon' => 'shield_person',      'badge' => 'badge-purple', 'accent' => 'purple'],
    'Supervisor'    => ['icon' => 'supervisor_account',  'badge' => 'badge-blue',   'accent' => 'blue'],
    'Agente'        => ['icon' => 'support_agent',       'badge' => 'badge-green',  'accent' => 'green'],
    'Cliente'       => ['icon' => 'person',               'badge' => 'badge-gray',   'accent' => 'gray'],
];

$usuariosTodos = [
    ['id' => 'USR-001', 'nombre' => 'Alex Rivera',          'correo' => 'alex.rivera@servicecore.com',      'rol' => 'Administrador', 'estado' => 'Activo',   'detalle' => 'Tecnología',                  'fecha' => '14 feb 2024'],
    ['id' => 'USR-002', 'nombre' => 'Daniela Castillo',      'correo' => 'daniela.castillo@servicecore.com', 'rol' => 'Administrador', 'estado' => 'Activo',   'detalle' => 'Operaciones',                 'fecha' => '02 ene 2023'],
    ['id' => 'USR-010', 'nombre' => 'Alejandro Méndez',      'correo' => 'alejandro.m@servicecore.com',      'rol' => 'Supervisor',    'estado' => 'Activo',   'detalle' => 'Infraestructura, Accesos',    'fecha' => '19 may 2023'],
    ['id' => 'USR-011', 'nombre' => 'Patricia Domínguez',    'correo' => 'patricia.d@servicecore.com',       'rol' => 'Supervisor',    'estado' => 'Activo',   'detalle' => 'Software, Soporte General',   'fecha' => '30 ago 2023'],
    ['id' => 'USR-012', 'nombre' => 'Jorge Salazar',         'correo' => 'jorge.salazar@servicecore.com',    'rol' => 'Supervisor',    'estado' => 'Inactivo', 'detalle' => 'Hardware',                    'fecha' => '11 nov 2022'],
    ['id' => 'USR-020', 'nombre' => 'Luis García',           'correo' => 'luis.garcia@servicecore.com',      'rol' => 'Agente',        'estado' => 'Activo',   'detalle' => 'Infraestructura',             'fecha' => '05 mar 2024'],
    ['id' => 'USR-021', 'nombre' => 'María López',           'correo' => 'maria.lopez@servicecore.com',      'rol' => 'Agente',        'estado' => 'Activo',   'detalle' => 'Accesos, Infraestructura',    'fecha' => '18 abr 2024'],
    ['id' => 'USR-022', 'nombre' => 'Carlos Ruiz',           'correo' => 'carlos.ruiz@servicecore.com',      'rol' => 'Agente',        'estado' => 'Activo',   'detalle' => 'Software',                    'fecha' => '22 jun 2024'],
    ['id' => 'USR-023', 'nombre' => 'Sofía Ramírez',         'correo' => 'sofia.ramirez@servicecore.com',    'rol' => 'Agente',        'estado' => 'Inactivo', 'detalle' => 'Accesos',                     'fecha' => '09 sep 2023'],
    ['id' => 'USR-024', 'nombre' => 'Pedro Sánchez',         'correo' => 'pedro.sanchez@servicecore.com',    'rol' => 'Agente',        'estado' => 'Activo',   'detalle' => 'Soporte General',             'fecha' => '14 oct 2024'],
    ['id' => 'USR-030', 'nombre' => 'Roberto Sánchez',       'correo' => 'roberto.sanchez@clientecorp.com',  'rol' => 'Cliente',       'estado' => 'Activo',   'detalle' => 'Cliente Corp',                'fecha' => '01 feb 2025'],
    ['id' => 'USR-031', 'nombre' => 'Ana Gómez',             'correo' => 'ana.gomez@bancacentral.com',       'rol' => 'Cliente',       'estado' => 'Activo',   'detalle' => 'Banca Central',               'fecha' => '15 mar 2025'],
    ['id' => 'USR-032', 'nombre' => 'Elena Rodríguez',       'correo' => 'elena.rodriguez@xyz.com',          'rol' => 'Cliente',       'estado' => 'Inactivo', 'detalle' => 'Empresa XYZ',                 'fecha' => '20 dic 2024'],
];

$usuariosPorRol = [];
foreach (array_keys($rolesConfig) as $rolNombre) {
    $usuariosPorRol[$rolNombre] = array_values(array_filter(
        $usuariosTodos,
        fn($u) => $u['rol'] === $rolNombre
    ));
}

$kpiTotal    = count($usuariosTodos);
$kpiActivos  = count(array_filter($usuariosTodos, fn($u) => $u['estado'] === 'Activo'));
$kpiInactivos = $kpiTotal - $kpiActivos;

function badgeClassRoles($text) {
    $map = [
        'Activo' => 'badge badge-green', 'Inactivo' => 'badge badge-gray',
        'Administrador' => 'badge badge-purple', 'Supervisor' => 'badge badge-blue',
        'Agente' => 'badge badge-green', 'Cliente' => 'badge badge-gray',
    ];
    return $map[$text] ?? 'badge badge-gray';
}

function inicialesRoles($nombre) {
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
    <title>Usuarios por Rol — ServiceCore</title>
    <link rel="icon" type="image/png" href="../../img/LogoNav.png">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" rel="stylesheet">
    <link rel="stylesheet" href="../../css/usuarios_roles.css">
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
            <a href="dashboard_admin_emp.php" class="menu-item ">
                <span class="material-symbols-outlined">dashboard</span>Inicio
            </a>
            <a href="gestion_usuarios.php" class="menu-item activo">
                <span class="material-symbols-outlined">group</span>Gestion de Usuarios
            </a>
            <a href="crear_categorias.php" class="menu-item">
                <span class="material-symbols-outlined">category</span>Gestion de Categorías
            </a>
            <a href="asignar_categoria.php" class="menu-item">
                <span class="material-symbols-outlined">sell</span>Asignar Categoría
            </a>
            <a href="../historial.php" class="menu-item">
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

    <main class="contenido ml-64 pt-24 px-8 pb-10" id="content">

        <!-- Page heading -->
        <section class="mb-6">
        <h2 class="text-4xl font-bold text-[#1e1858]">Gestión de Categorías</h2>
        <p class="text-gray-500 mt-2">
            Visualiza y administra las categorías de tu empresa que se asignan a los tickets.
            Las categorías son exclusivas de cada organización.
        </p>
        </section>

        <!-- KPIs / filtros rápidos por rol -->
        <section class="kpi-grid" id="kpiGrid">
            <article class="card kpi primary kpi-clickable active" data-filter-rol="">
                <div class="kpi-icon"><span class="material-symbols-outlined">groups</span></div>
                <div>
                    <p>Total de usuarios</p>
                    <h3><?= $kpiTotal ?></h3>
                    <span><?= $kpiActivos ?> activos · <?= $kpiInactivos ?> inactivos</span>
                </div>
            </article>
            <?php foreach ($rolesConfig as $rolNombre => $cfg): ?>
                <article class="card kpi <?= $cfg['accent'] ?> kpi-clickable" data-filter-rol="<?= htmlspecialchars($rolNombre) ?>">
                    <div class="kpi-icon"><span class="material-symbols-outlined"><?= $cfg['icon'] ?></span></div>
                    <div>
                        <p><?= htmlspecialchars($rolNombre) ?>s</p>
                        <h3><?= count($usuariosPorRol[$rolNombre]) ?></h3>
                        <span>Ver solo esta columna</span>
                    </div>
                </article>
            <?php endforeach; ?>
        </section>

        <!-- Barra de controles -->
        <section class="controls-bar">
            <div class="controls-left">
                <div class="search-box">
                    <span class="material-symbols-outlined">search</span>
                    <input type="search" id="buscadorUsuarios" placeholder="Buscar por nombre o correo...">
                </div>
                <select id="filterEstado" class="input-small">
                    <option value="">Todos los estados</option>
                    <option value="Activo">Solo activos</option>
                    <option value="Inactivo">Solo inactivos</option>
                </select>
                <button class="btn btn-light" id="btnLimpiarFiltros">
                    <span class="material-symbols-outlined">refresh</span> Limpiar filtros
                </button>
            </div>
            <button class="btn btn-primary" id="btnNuevoUsuario">
                <span class="material-symbols-outlined">person_add</span> Nuevo Usuario
            </button>
        </section>

        <!-- Tablero segmentado por rol -->
        <section class="role-board" id="roleBoard">
            <?php foreach ($rolesConfig as $rolNombre => $cfg): ?>
                <div class="role-column" data-role-column="<?= htmlspecialchars($rolNombre) ?>">
                    <div class="role-column-head accent-<?= $cfg['accent'] ?>">
                        <div class="role-column-title">
                            <span class="material-symbols-outlined"><?= $cfg['icon'] ?></span>
                            <strong><?= htmlspecialchars($rolNombre) ?>s</strong>
                        </div>
                        <span class="role-count" data-role-count="<?= htmlspecialchars($rolNombre) ?>"><?= count($usuariosPorRol[$rolNombre]) ?></span>
                    </div>

                    <div class="role-column-body" data-role-body="<?= htmlspecialchars($rolNombre) ?>">
                        <?php foreach ($usuariosPorRol[$rolNombre] as $u): ?>
                            <article class="user-card"
                                data-id="<?= htmlspecialchars($u['id']) ?>"
                                data-nombre="<?= htmlspecialchars($u['nombre']) ?>"
                                data-correo="<?= htmlspecialchars($u['correo']) ?>"
                                data-rol="<?= htmlspecialchars($u['rol']) ?>"
                                data-estado="<?= htmlspecialchars($u['estado']) ?>"
                                data-detalle="<?= htmlspecialchars($u['detalle']) ?>"
                            >
                                <div class="user-card-top">
                                    <div class="avatar small accent-<?= $cfg['accent'] ?>"><?= htmlspecialchars(inicialesRoles($u['nombre'])) ?></div>
                                    <div class="user-card-id">
                                        <strong data-card-nombre><?= htmlspecialchars($u['nombre']) ?></strong>
                                        <span data-card-correo><?= htmlspecialchars($u['correo']) ?></span>
                                    </div>
                                </div>
                                <p class="user-card-detalle" data-card-detalle><?= htmlspecialchars($u['detalle']) ?></p>
                                <div class="user-card-badges">
                                    <span class="<?= badgeClassRoles($u['estado']) ?>" data-card-estado-badge><?= htmlspecialchars($u['estado']) ?></span>
                                    <small data-card-fecha>Desde <?= htmlspecialchars($u['fecha']) ?></small>
                                </div>
                                <div class="user-card-actions">
                                    <button class="card-icon-btn" data-action="editar" title="Editar usuario">
                                        <span class="material-symbols-outlined">edit</span>
                                    </button>
                                    <button class="card-icon-btn" data-action="toggle-estado" title="Activar / desactivar">
                                        <span class="material-symbols-outlined">toggle_on</span>
                                    </button>
                                    <button class="card-icon-btn danger" data-action="eliminar" title="Eliminar usuario">
                                        <span class="material-symbols-outlined">delete</span>
                                    </button>
                                </div>
                            </article>
                        <?php endforeach; ?>

                        <p class="empty-column" data-empty-column="<?= htmlspecialchars($rolNombre) ?>" style="display:none;">
                            Sin usuarios <?= $rolNombre === 'Cliente' ? 'clientes' : 'con este rol' ?> que coincidan.
                        </p>
                    </div>
                </div>
            <?php endforeach; ?>
        </section>
    </main>

    <!-- Modal: crear / editar usuario -->
    <div class="modal" id="modalUsuario">
        <div class="modal-content modal-wide">
            <button class="modal-close" id="modalUsuarioCerrar">×</button>
            <div class="modal-icon"><span class="material-symbols-outlined">person_add</span></div>
            <h3 id="modalUsuarioTitulo">Nuevo Usuario</h3>
            <p id="modalUsuarioSub">Completa los datos para registrar un nuevo usuario en el sistema.</p>

            <form id="formUsuario" novalidate>
                <input type="hidden" id="usuarioId">

                <div class="form-group" style="text-align:left;">
                    <label for="usuarioNombre">Nombre completo</label>
                    <div class="input-icon">
                        <span class="material-symbols-outlined">person</span>
                        <input type="text" id="usuarioNombre" placeholder="Nombre y apellidos">
                    </div>
                    <small class="field-error" id="errorUsuarioNombre"></small>
                </div>

                <div class="form-group" style="text-align:left;">
                    <label for="usuarioCorreo">Correo electrónico</label>
                    <div class="input-icon">
                        <span class="material-symbols-outlined">mail</span>
                        <input type="email" id="usuarioCorreo" placeholder="correo@empresa.com">
                    </div>
                    <small class="field-error" id="errorUsuarioCorreo"></small>
                </div>

                <div class="form-group" style="text-align:left;">
                    <label for="usuarioDetalle">Departamento / Categorías / Empresa</label>
                    <div class="input-icon">
                        <span class="material-symbols-outlined">apartment</span>
                        <input type="text" id="usuarioDetalle" placeholder="Ej. Tecnología, Soporte, Empresa XYZ...">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group" style="text-align:left;">
                        <label for="usuarioRol">Rol</label>
                        <div class="input-icon">
                            <span class="material-symbols-outlined">badge</span>
                            <select id="usuarioRol">
                                <option value="Administrador">Administrador</option>
                                <option value="Supervisor">Supervisor</option>
                                <option value="Agente">Agente</option>
                                <option value="Cliente">Cliente</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-group" style="text-align:left;">
                        <label for="usuarioEstado">Estado</label>
                        <div class="input-icon">
                            <span class="material-symbols-outlined">toggle_on</span>
                            <select id="usuarioEstado">
                                <option value="Activo">Activo</option>
                                <option value="Inactivo">Inactivo</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="modal-actions">
                    <button type="button" class="btn btn-light" id="btnCancelarUsuario">Cancelar</button>
                    <button type="submit" class="btn btn-primary" id="btnGuardarUsuario">
                        <span class="material-symbols-outlined">save</span> Guardar Usuario
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal de confirmación reutilizable -->
    <div class="modal" id="modalConfirmar">
        <div class="modal-content">
            <button class="modal-close" id="modalConfirmarCerrar">×</button>
            <div class="modal-icon"><span class="material-symbols-outlined">help</span></div>
            <h3 id="modalConfirmarTitulo">Confirmar acción</h3>
            <p id="modalConfirmarMensaje">¿Deseas continuar con esta acción?</p>
            <div class="modal-actions">
                <button class="btn btn-light" id="modalConfirmarCancelar">Cancelar</button>
                <button class="btn btn-danger" id="modalConfirmarAceptar">Confirmar</button>
            </div>
        </div>
    </div>

    <div class="toast-container" id="toastContainer"></div>

    <footer class="text-center text-gray-500 text-sm border-t mt-10 p-4 bg-white rounded-xl">
            <p>© 2026 ServiceCore Corporation</p>
    </footer>

    <script src="../../js/usuarios_roles.js"></script>
    <script src="../../js/dashboard_admin.js"></script>
</body>
</html>
