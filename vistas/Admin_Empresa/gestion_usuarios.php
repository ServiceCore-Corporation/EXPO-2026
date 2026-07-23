<?php
define('ROL_REQUERIDO', 2);
require_once '../../seguridad.php';
require_once '../../conexion.php';
$nombreUsuario = htmlspecialchars($_SESSION['nombre']);
$correoUsuario = htmlspecialchars($_SESSION['correo']);
$idUsuarioActual = (int)$_SESSION['usuario_id'];

// Empresa del Admin de Empresa autenticado: todo lo que administra esta
// vista pertenece siempre a su propia empresa (multi-tenant).
$idEmpresa = 0;
$stmtEmp = $conn->prepare("SELECT id_empresa FROM usuario WHERE id_usuario = ? LIMIT 1");
$stmtEmp->bind_param('i', $idUsuarioActual);
$stmtEmp->execute();
$rowEmp = $stmtEmp->get_result()->fetch_assoc();
$stmtEmp->close();
if ($rowEmp) $idEmpresa = (int)$rowEmp['id_empresa'];

// Roles administrables por un Admin de Empresa dentro de su propia empresa.
// El rol 1 (Admin ServiceCore) es a nivel de sistema y no se gestiona aquí.
$rolesConfig = [
    'Admin Empresa' => ['id_rol' => 2, 'icon' => 'shield_person',      'badge' => 'badge-purple', 'accent' => 'purple'],
    'Supervisor'     => ['id_rol' => 4, 'icon' => 'supervisor_account', 'badge' => 'badge-blue',   'accent' => 'blue'],
    'Agente'         => ['id_rol' => 3, 'icon' => 'support_agent',      'badge' => 'badge-green',  'accent' => 'green'],
    'Cliente'        => ['id_rol' => 5, 'icon' => 'person',             'badge' => 'badge-gray',   'accent' => 'gray'],
];

function inicialesRoles($nombre) {
    $partes = explode(' ', trim($nombre));
    $ini = '';
    foreach (array_slice($partes, 0, 2) as $p) { $ini .= mb_substr($p, 0, 1); }
    return mb_strtoupper($ini);
}

function badgeClassRoles($text) {
    $map = [
        'Activo' => 'badge badge-green', 'Inactivo' => 'badge badge-gray',
        'Admin Empresa' => 'badge badge-purple', 'Supervisor' => 'badge badge-blue',
        'Agente' => 'badge badge-green', 'Cliente' => 'badge badge-gray',
    ];
    return $map[$text] ?? 'badge badge-gray';
}

function plural($rolNombre) {
    $map = [
        'Admin Empresa' => 'Admins de Empresa',
        'Supervisor'    => 'Supervisores',
        'Agente'        => 'Agentes',
        'Cliente'       => 'Clientes',
    ];
    return $map[$rolNombre] ?? ($rolNombre . 's');
}

// ---- AJAX: CRUD real de usuarios de la empresa ----
if (isset($_GET['ajax'])) {
    header('Content-Type: application/json; charset=utf-8');
    $accion = $_POST['accion'] ?? $_GET['accion'] ?? '';
    $rolesPermitidos = array_column($rolesConfig, 'id_rol');

    function responderUR($ok, $data = []) {
        echo json_encode(array_merge(['ok' => $ok], $data));
        exit;
    }

    switch ($accion) {
        case 'crear':
            $nombre   = trim($_POST['nombre'] ?? '');
            $correo   = trim($_POST['correo'] ?? '');
            $telefono = trim($_POST['telefono'] ?? '');
            $detalle  = trim($_POST['detalle'] ?? '');
            $idRol    = (int)($_POST['id_rol'] ?? 0);
            $password = trim($_POST['password'] ?? '');
            $activo   = ($_POST['estado'] ?? 'Activo') === 'Activo' ? 1 : 0;

            if ($nombre === '' || $correo === '' || !in_array($idRol, $rolesPermitidos, true)) {
                responderUR(false, ['mensaje' => 'Nombre, correo y rol son obligatorios.']);
            }
            if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
                responderUR(false, ['mensaje' => 'El correo no es válido.']);
            }
            if ($password === '' || strlen($password) < 8) {
                responderUR(false, ['mensaje' => 'La contraseña debe tener al menos 8 caracteres.']);
            }

            $stmtChk = $conn->prepare("SELECT id_usuario FROM usuario WHERE correo = ? LIMIT 1");
            $stmtChk->bind_param('s', $correo);
            $stmtChk->execute();
            if ($stmtChk->get_result()->fetch_assoc()) {
                responderUR(false, ['mensaje' => 'Ya existe un usuario con ese correo.']);
            }

            $hash = password_hash($password, PASSWORD_BCRYPT);
            $stmt = $conn->prepare("
                INSERT INTO usuario (nombre, telefono, departamento, correo, pass, id_rol, activo, fecha_creacion, id_empresa)
                VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), ?)
            ");
            $stmt->bind_param('sssssiii', $nombre, $telefono, $detalle, $correo, $hash, $idRol, $activo, $idEmpresa);
            $ok = $stmt->execute();
            responderUR($ok, ['mensaje' => 'Usuario creado correctamente.', 'id' => $conn->insert_id]);
            break;

        case 'editar':
            $id       = (int)($_POST['id'] ?? 0);
            $nombre   = trim($_POST['nombre'] ?? '');
            $correo   = trim($_POST['correo'] ?? '');
            $telefono = trim($_POST['telefono'] ?? '');
            $detalle  = trim($_POST['detalle'] ?? '');
            $idRol    = (int)($_POST['id_rol'] ?? 0);
            $activo   = ($_POST['estado'] ?? 'Activo') === 'Activo' ? 1 : 0;

            if ($id <= 0 || $nombre === '' || $correo === '' || !in_array($idRol, $rolesPermitidos, true)) {
                responderUR(false, ['mensaje' => 'Nombre, correo y rol son obligatorios.']);
            }
            if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
                responderUR(false, ['mensaje' => 'El correo no es válido.']);
            }

            $stmtChk = $conn->prepare("SELECT id_usuario FROM usuario WHERE correo = ? AND id_usuario != ? LIMIT 1");
            $stmtChk->bind_param('si', $correo, $id);
            $stmtChk->execute();
            if ($stmtChk->get_result()->fetch_assoc()) {
                responderUR(false, ['mensaje' => 'Ya existe otro usuario con ese correo.']);
            }

            $stmt = $conn->prepare("
                UPDATE usuario SET nombre = ?, correo = ?, telefono = ?, departamento = ?, id_rol = ?, activo = ?
                WHERE id_usuario = ? AND id_empresa = ? AND id_rol IN (2,3,4,5)
            ");
            $stmt->bind_param('ssssiiii', $nombre, $correo, $telefono, $detalle, $idRol, $activo, $id, $idEmpresa);
            $ok = $stmt->execute();
            responderUR($ok, ['mensaje' => 'Usuario actualizado correctamente.']);
            break;

        case 'toggle-estado':
            $id     = (int)($_POST['id'] ?? 0);
            $activo = (int)($_POST['activo'] ?? 0);
            if ($id <= 0) responderUR(false, ['mensaje' => 'ID inválido.']);
            if ($id === $idUsuarioActual) responderUR(false, ['mensaje' => 'No puedes desactivar tu propia cuenta.']);
            $stmt = $conn->prepare("UPDATE usuario SET activo = ? WHERE id_usuario = ? AND id_empresa = ?");
            $stmt->bind_param('iii', $activo, $id, $idEmpresa);
            $ok = $stmt->execute();
            responderUR($ok, ['mensaje' => 'Estado actualizado correctamente.']);
            break;

        case 'eliminar':
            $id = (int)($_POST['id'] ?? 0);
            if ($id <= 0) responderUR(false, ['mensaje' => 'ID inválido.']);
            if ($id === $idUsuarioActual) responderUR(false, ['mensaje' => 'No puedes eliminar tu propia cuenta.']);

            $stmtChk = $conn->prepare("SELECT COUNT(*) AS c FROM ticket WHERE id_usuario_cliente = ? OR id_usuario_agente = ?");
            $stmtChk->bind_param('ii', $id, $id);
            $stmtChk->execute();
            $enUso = (int)$stmtChk->get_result()->fetch_assoc()['c'];
            if ($enUso > 0) {
                responderUR(false, ['mensaje' => "No se puede eliminar: tiene $enUso ticket(s) asociados. Desactívalo en su lugar."]);
            }

            $stmt = $conn->prepare("DELETE FROM usuario WHERE id_usuario = ? AND id_empresa = ? AND id_rol IN (2,3,4,5)");
            $stmt->bind_param('ii', $id, $idEmpresa);
            $ok = $stmt->execute();
            responderUR($ok, ['mensaje' => 'Usuario eliminado correctamente.']);
            break;

        default:
            responderUR(false, ['mensaje' => 'Acción no reconocida.']);
    }
}

// ---- Carga real de usuarios de la empresa, agrupados por rol ----
$usuariosPorRol = array_fill_keys(array_keys($rolesConfig), []);
$stmt = $conn->prepare("
    SELECT u.id_usuario, u.nombre, u.correo, u.telefono, u.departamento, u.cargo, u.activo, u.fecha_creacion, r.nombre AS rol
    FROM usuario u
    JOIN rol r ON r.id_rol = u.id_rol
    WHERE u.id_empresa = ? AND u.id_rol IN (2,3,4,5)
    ORDER BY u.fecha_creacion DESC
");
$stmt->bind_param('i', $idEmpresa);
$stmt->execute();
$res = $stmt->get_result();
while ($row = $res->fetch_assoc()) {
    $detalle = trim(($row['departamento'] ?? '') . (!empty($row['cargo']) ? ' · ' . $row['cargo'] : ''));
    $u = [
        'id'      => (int)$row['id_usuario'],
        'nombre'  => $row['nombre'],
        'correo'  => $row['correo'],
        'rol'     => $row['rol'],
        'estado'  => ((int)$row['activo'] === 1) ? 'Activo' : 'Inactivo',
        'detalle' => $detalle !== '' ? $detalle : ($row['telefono'] ?: '—'),
        'telefono'=> $row['telefono'] ?? '',
        'fecha'   => !empty($row['fecha_creacion']) && $row['fecha_creacion'] !== '0000-00-00 00:00:00'
                        ? date('d M Y', strtotime($row['fecha_creacion'])) : '—',
    ];
    if (isset($usuariosPorRol[$row['rol']])) {
        $usuariosPorRol[$row['rol']][] = $u;
    }
}
$stmt->close();

$usuariosTodos = array_merge(...array_values($usuariosPorRol));
$kpiTotal    = count($usuariosTodos);
$kpiActivos  = count(array_filter($usuariosTodos, fn($u) => $u['estado'] === 'Activo'));
$kpiInactivos = $kpiTotal - $kpiActivos;
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
            <div id="botonUsuario" class="w-10 h-10 rounded-full cursor-pointer border-2 border-[#5750ad] bg-[#5750ad] flex items-center justify-center text-white font-bold overflow-hidden">
                <?php if (!empty($_SESSION['foto'])): ?>
                    <img src="../<?= htmlspecialchars($_SESSION['foto']) ?>" alt="Foto de perfil" class="w-full h-full object-cover">
                <?php else: ?>
                    <?= mb_strtoupper(mb_substr($_SESSION['nombre'], 0, 1)) ?>
                <?php endif; ?>
            </div>
            <div id="menuUsuario" class="hidden absolute right-0 top-14 w-52 bg-white rounded-xl shadow-lg border border-gray-200 overflow-hidden z-50">
                <a href="perfil.php#tarjetaPreferencias" class="flex items-center gap-3 px-4 py-3 hover:bg-gray-100 transition">
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
            <a href="gestion_tickets.php" class="menu-item">
                <span class="material-symbols-outlined">confirmation_number</span>Gestión de Tickets
            </a>
            <a href="reportes.php" class="menu-item">
                <span class="material-symbols-outlined">insights</span>Reportes
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
                        <p><?= htmlspecialchars(plural($rolNombre)) ?></p>
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
                            <strong><?= htmlspecialchars(plural($rolNombre)) ?></strong>
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

                <div class="form-group" id="grupoUsuarioPassword" style="text-align:left;">
                    <label for="usuarioPassword">Contraseña</label>
                    <div class="input-icon">
                        <span class="material-symbols-outlined">lock</span>
                        <input type="password" id="usuarioPassword" placeholder="Mínimo 8 caracteres" autocomplete="new-password">
                    </div>
                    <small class="field-error" id="errorUsuarioPassword"></small>
                </div>

                <div class="form-group" style="text-align:left;">
                    <label for="usuarioDetalle">Departamento / Cargo</label>
                    <div class="input-icon">
                        <span class="material-symbols-outlined">apartment</span>
                        <input type="text" id="usuarioDetalle" placeholder="Ej. Tecnología, Soporte, Coordinador...">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group" style="text-align:left;">
                        <label for="usuarioRol">Rol</label>
                        <div class="input-icon">
                            <span class="material-symbols-outlined">badge</span>
                            <select id="usuarioRol">
                                <option value="2">Admin Empresa</option>
                                <option value="4">Supervisor</option>
                                <option value="3">Agente</option>
                                <option value="5">Cliente</option>
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
