<?php
define('ROL_REQUERIDO', 1);
require_once '../../seguridad.php';
require_once '../../conexion.php';
$nombreUsuario = htmlspecialchars($_SESSION['nombre']);
$correoUsuario = htmlspecialchars($_SESSION['correo']);

$adminEmpresas = [];
$sql = "
    SELECT u.id_usuario, u.nombre, u.correo, u.telefono, u.activo, u.fecha_creacion,
           e.id_empresa, e.nombre AS empresa
    FROM usuario u
    LEFT JOIN empresa e ON e.id_empresa = u.id_empresa
    WHERE u.id_rol = 2
    ORDER BY u.fecha_creacion DESC
";
$res = $conn->query($sql);
if ($res) {
    while ($row = $res->fetch_assoc()) {
        $adminEmpresas[] = [
            'id'         => (int)$row['id_usuario'],
            'nombre'     => $row['nombre'],
            'correo'     => $row['correo'],
            'empresa'    => $row['empresa'] ?? 'Sin empresa',
            'idEmpresa'  => $row['id_empresa'] ? (int)$row['id_empresa'] : null,
            'telefono'   => $row['telefono'] ?? '',
            'estado'     => ((int)$row['activo'] === 1) ? 'Activo' : 'Inactivo',
            'fecha'      => !empty($row['fecha_creacion']) ? date('d M Y', strtotime($row['fecha_creacion'])) : '',
        ];
    }
}

$empresasDisponibles = [];
$resEmp = $conn->query("
    SELECT e.id_empresa, e.nombre, e.correo_contacto, e.telefono, e.estado, e.fecha_registro,
           (SELECT COUNT(*) FROM usuario u2 WHERE u2.id_empresa = e.id_empresa) AS total_usuarios,
           (SELECT COUNT(*) FROM pago p WHERE p.id_empresa = e.id_empresa) AS total_pagos
    FROM empresa e
    ORDER BY e.nombre ASC
");
if ($resEmp) {
    while ($row = $resEmp->fetch_assoc()) {
        $empresasDisponibles[] = $row;
    }
}

$empresasCompletas = array_map(function ($e) {
    return [
        'id'            => (int)$e['id_empresa'],
        'nombre'        => $e['nombre'],
        'correo'        => $e['correo_contacto'],
        'telefono'      => $e['telefono'],
        'estado'        => ((int)$e['estado'] === 1) ? 'Activo' : 'Inactivo',
        'fecha'         => !empty($e['fecha_registro']) ? date('d M Y', strtotime($e['fecha_registro'])) : '',
        'totalUsuarios' => (int)$e['total_usuarios'],
        'totalPagos'    => (int)$e['total_pagos'],
    ];
}, $empresasDisponibles);

$kpiEmpTotal    = count($empresasCompletas);
$kpiEmpActivas  = count(array_filter($empresasCompletas, fn($e) => $e['estado'] === 'Activo'));
$kpiEmpInactivas = $kpiEmpTotal - $kpiEmpActivas;

$kpiTotal     = count($adminEmpresas);
$kpiActivos   = count(array_filter($adminEmpresas, fn($u) => $u['estado'] === 'Activo'));
$kpiInactivos = $kpiTotal - $kpiActivos;
$kpiEmpresas  = count(array_unique(array_filter(array_column($adminEmpresas, 'idEmpresa'))));

function badgeClassEstado($estado) {
    return $estado === 'Activo' ? 'badge badge-green' : 'badge badge-gray';
}

function inicialesAE($nombre) {
    $partes = explode(' ', trim($nombre));
    $ini = '';
    foreach (array_slice($partes, 0, 2) as $p) { $ini .= mb_substr($p, 0, 1); }
    return mb_strtoupper($ini);
}

// ---- AJAX: CRUD real de Admin-Empresa (usuario con id_rol = 2) ----
if (isset($_GET['ajax'])) {
    header('Content-Type: application/json; charset=utf-8');
    $accion = $_POST['accion'] ?? $_GET['accion'] ?? '';

    function responderAE($ok, $data = []) {
        echo json_encode(array_merge(['ok' => $ok], $data));
        exit;
    }

    switch ($accion) {
        case 'crear':
            $nombre    = trim($_POST['nombre'] ?? '');
            $correo    = trim($_POST['correo'] ?? '');
            $telefono  = trim($_POST['telefono'] ?? '');
            $idEmpresa = (int)($_POST['id_empresa'] ?? 0);
            $activo    = ($_POST['estado'] ?? 'Activo') === 'Activo' ? 1 : 0;
            $password  = trim($_POST['password'] ?? '');

            if ($nombre === '' || $correo === '' || $idEmpresa <= 0) {
                responderAE(false, ['mensaje' => 'Nombre, correo y empresa son obligatorios.']);
            }
            if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
                responderAE(false, ['mensaje' => 'El correo no es válido.']);
            }
            if ($password === '' || strlen($password) < 8) {
                responderAE(false, ['mensaje' => 'La contraseña debe tener al menos 8 caracteres.']);
            }

            $stmtChk = $conn->prepare("SELECT id_usuario FROM usuario WHERE correo = ? LIMIT 1");
            $stmtChk->bind_param('s', $correo);
            $stmtChk->execute();
            if ($stmtChk->get_result()->fetch_assoc()) {
                responderAE(false, ['mensaje' => 'Ya existe un usuario con ese correo.']);
            }

            $hash = password_hash($password, PASSWORD_BCRYPT);
            $stmt = $conn->prepare("
                INSERT INTO usuario (nombre, telefono, correo, pass, id_rol, activo, fecha_creacion, id_empresa)
                VALUES (?, ?, ?, ?, 2, ?, NOW(), ?)
            ");
            $stmt->bind_param('ssssii', $nombre, $telefono, $correo, $hash, $activo, $idEmpresa);
            $ok = $stmt->execute();
            responderAE($ok, ['mensaje' => 'Admin-Empresa creado correctamente.', 'id' => $conn->insert_id]);
            break;

        case 'editar':
            $id        = (int)($_POST['id'] ?? 0);
            $nombre    = trim($_POST['nombre'] ?? '');
            $correo    = trim($_POST['correo'] ?? '');
            $telefono  = trim($_POST['telefono'] ?? '');
            $idEmpresa = (int)($_POST['id_empresa'] ?? 0);
            $activo    = ($_POST['estado'] ?? 'Activo') === 'Activo' ? 1 : 0;

            if ($id <= 0 || $nombre === '' || $correo === '' || $idEmpresa <= 0) {
                responderAE(false, ['mensaje' => 'Nombre, correo y empresa son obligatorios.']);
            }
            if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
                responderAE(false, ['mensaje' => 'El correo no es válido.']);
            }

            $stmtChk = $conn->prepare("SELECT id_usuario FROM usuario WHERE correo = ? AND id_usuario != ? LIMIT 1");
            $stmtChk->bind_param('si', $correo, $id);
            $stmtChk->execute();
            if ($stmtChk->get_result()->fetch_assoc()) {
                responderAE(false, ['mensaje' => 'Ya existe otro usuario con ese correo.']);
            }

            $stmt = $conn->prepare("
                UPDATE usuario SET nombre = ?, correo = ?, telefono = ?, id_empresa = ?, activo = ?
                WHERE id_usuario = ? AND id_rol = 2
            ");
            $stmt->bind_param('sssiii', $nombre, $correo, $telefono, $idEmpresa, $activo, $id);
            $ok = $stmt->execute();
            responderAE($ok, ['mensaje' => 'Admin-Empresa actualizado correctamente.']);
            break;

        case 'toggle-estado':
            $id     = (int)($_POST['id'] ?? 0);
            $activo = (int)($_POST['activo'] ?? 0);
            if ($id <= 0) responderAE(false, ['mensaje' => 'ID inválido.']);
            $stmt = $conn->prepare("UPDATE usuario SET activo = ? WHERE id_usuario = ? AND id_rol = 2");
            $stmt->bind_param('ii', $activo, $id);
            $ok = $stmt->execute();
            responderAE($ok, ['mensaje' => 'Estado actualizado correctamente.']);
            break;

        // ---- CRUD de empresas (tabla `empresa`) ----
        case 'crear-empresa':
            $nombre   = trim($_POST['nombre'] ?? '');
            $correo   = trim($_POST['correo'] ?? '');
            $telefono = preg_replace('/\D/', '', $_POST['telefono'] ?? '');
            $activo   = ($_POST['estado'] ?? 'Activo') === 'Activo' ? 1 : 0;

            if ($nombre === '' || $correo === '' || $telefono === '') {
                responderAE(false, ['mensaje' => 'Nombre, correo y teléfono son obligatorios.']);
            }
            if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
                responderAE(false, ['mensaje' => 'El correo de contacto no es válido.']);
            }

            $stmtChk = $conn->prepare("SELECT id_empresa FROM empresa WHERE nombre = ? LIMIT 1");
            $stmtChk->bind_param('s', $nombre);
            $stmtChk->execute();
            if ($stmtChk->get_result()->fetch_assoc()) {
                responderAE(false, ['mensaje' => 'Ya existe una empresa con ese nombre.']);
            }

            $stmt = $conn->prepare("
                INSERT INTO empresa (nombre, correo_contacto, telefono, estado, fecha_registro)
                VALUES (?, ?, ?, ?, NOW())
            ");
            $stmt->bind_param('sssi', $nombre, $correo, $telefono, $activo);
            $ok = $stmt->execute();
            responderAE($ok, ['mensaje' => 'Empresa creada correctamente.', 'id' => $conn->insert_id]);
            break;

        case 'editar-empresa':
            $id       = (int)($_POST['id'] ?? 0);
            $nombre   = trim($_POST['nombre'] ?? '');
            $correo   = trim($_POST['correo'] ?? '');
            $telefono = preg_replace('/\D/', '', $_POST['telefono'] ?? '');
            $activo   = ($_POST['estado'] ?? 'Activo') === 'Activo' ? 1 : 0;

            if ($id <= 0 || $nombre === '' || $correo === '' || $telefono === '') {
                responderAE(false, ['mensaje' => 'Nombre, correo y teléfono son obligatorios.']);
            }
            if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
                responderAE(false, ['mensaje' => 'El correo de contacto no es válido.']);
            }

            $stmtChk = $conn->prepare("SELECT id_empresa FROM empresa WHERE nombre = ? AND id_empresa != ? LIMIT 1");
            $stmtChk->bind_param('si', $nombre, $id);
            $stmtChk->execute();
            if ($stmtChk->get_result()->fetch_assoc()) {
                responderAE(false, ['mensaje' => 'Ya existe otra empresa con ese nombre.']);
            }

            $stmt = $conn->prepare("
                UPDATE empresa SET nombre = ?, correo_contacto = ?, telefono = ?, estado = ?
                WHERE id_empresa = ?
            ");
            $stmt->bind_param('sssii', $nombre, $correo, $telefono, $activo, $id);
            $ok = $stmt->execute();
            responderAE($ok, ['mensaje' => 'Empresa actualizada correctamente.']);
            break;

        case 'toggle-estado-empresa':
            $id     = (int)($_POST['id'] ?? 0);
            $activo = (int)($_POST['activo'] ?? 0);
            if ($id <= 0) responderAE(false, ['mensaje' => 'ID inválido.']);
            $stmt = $conn->prepare("UPDATE empresa SET estado = ? WHERE id_empresa = ?");
            $stmt->bind_param('ii', $activo, $id);
            $ok = $stmt->execute();
            responderAE($ok, ['mensaje' => 'Estado de la empresa actualizado correctamente.']);
            break;

        case 'eliminar-empresa':
            $id = (int)($_POST['id'] ?? 0);
            if ($id <= 0) responderAE(false, ['mensaje' => 'ID inválido.']);
            // Al eliminar, las restricciones de la BD (ON DELETE CASCADE) también
            // borran usuarios, cuentas y pagos ligados a esta empresa.
            $stmt = $conn->prepare("DELETE FROM empresa WHERE id_empresa = ?");
            $stmt->bind_param('i', $id);
            $ok = $stmt->execute();
            responderAE($ok, ['mensaje' => 'Empresa eliminada correctamente.']);
            break;

        default:
            responderAE(false, ['mensaje' => 'Acción no reconocida.']);
    }
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
            <a href="reportes.php" class="menu-item">
                <span class="material-symbols-outlined">insights</span>Reportes
            </a>
            <a href="../historial.php" class="menu-item">
                <span class="material-symbols-outlined">history</span>Historial y Auditoría
            </a>
        </nav>
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
                            data-id-empresa="<?= htmlspecialchars((string)$u['idEmpresa']) ?>"
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

        <!-- ================= EMPRESAS (tabla `empresa`) ================= -->
        <section class="mb-8" style="margin-top:48px;">
            <h2 class="text-3xl font-bold text-[#1e1858]">Empresas registradas</h2>
            <p class="text-gray-500 mt-2">Crea y administra las empresas clientes de ServiceCore.</p>
        </section>

        <section class="kpi-grid" id="kpiGridEmp">
            <article class="card kpi primary">
                <div class="kpi-icon"><span class="material-symbols-outlined">domain</span></div>
                <div>
                    <p>Total Empresas</p>
                    <h3 data-kpi-emp="total"><?= $kpiEmpTotal ?></h3>
                    <span>Registradas en el sistema</span>
                </div>
            </article>
            <article class="card kpi green">
                <div class="kpi-icon"><span class="material-symbols-outlined">toggle_on</span></div>
                <div>
                    <p>Activas</p>
                    <h3 data-kpi-emp="activas"><?= $kpiEmpActivas ?></h3>
                    <span>Con acceso habilitado</span>
                </div>
            </article>
            <article class="card kpi gray">
                <div class="kpi-icon"><span class="material-symbols-outlined">toggle_off</span></div>
                <div>
                    <p>Inactivas</p>
                    <h3 data-kpi-emp="inactivas"><?= $kpiEmpInactivas ?></h3>
                    <span>Con acceso deshabilitado</span>
                </div>
            </article>
        </section>

        <section class="controls-bar">
            <div class="controls-left">
                <div class="search-box">
                    <span class="material-symbols-outlined">search</span>
                    <input type="search" id="buscadorEmp" placeholder="Buscar por nombre o correo...">
                </div>
            </div>
            <button class="btn btn-primary" id="btnNuevaEmpresa">
                <span class="material-symbols-outlined">add_business</span> Nueva Empresa
            </button>
        </section>

        <section class="table-wrap card">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Empresa</th>
                        <th>Correo de contacto</th>
                        <th>Teléfono</th>
                        <th>Estado</th>
                        <th>Registrada</th>
                        <th class="th-actions">Acciones</th>
                    </tr>
                </thead>
                <tbody id="tablaEmp">
                    <?php foreach ($empresasCompletas as $e): ?>
                        <tr class="emp-row"
                            data-id="<?= $e['id'] ?>"
                            data-nombre="<?= htmlspecialchars($e['nombre']) ?>"
                            data-correo="<?= htmlspecialchars($e['correo']) ?>"
                            data-telefono="<?= htmlspecialchars($e['telefono']) ?>"
                            data-estado="<?= htmlspecialchars($e['estado']) ?>"
                            data-total-usuarios="<?= $e['totalUsuarios'] ?>"
                            data-total-pagos="<?= $e['totalPagos'] ?>"
                        >
                            <td>
                                <span class="empresa-chip">
                                    <span class="material-symbols-outlined">domain</span>
                                    <strong data-row-nombre><?= htmlspecialchars($e['nombre']) ?></strong>
                                </span>
                            </td>
                            <td><span data-row-correo><?= htmlspecialchars($e['correo']) ?></span></td>
                            <td><span data-row-telefono><?= htmlspecialchars($e['telefono']) ?></span></td>
                            <td><span class="<?= badgeClassEstado($e['estado']) ?>" data-row-estado-badge><?= htmlspecialchars($e['estado']) ?></span></td>
                            <td><span data-row-fecha><?= htmlspecialchars($e['fecha']) ?></span></td>
                            <td>
                                <div class="row-actions">
                                    <button class="row-icon-btn" data-action="editar-empresa" title="Editar empresa">
                                        <span class="material-symbols-outlined">edit</span>
                                    </button>
                                    <button class="row-icon-btn toggle-btn" data-action="toggle-estado-empresa" title="Activar / desactivar">
                                        <span class="material-symbols-outlined" data-row-toggle-icon><?= $e['estado'] === 'Activo' ? 'toggle_on' : 'toggle_off' ?></span>
                                    </button>
                                    <button class="row-icon-btn" data-action="eliminar-empresa" title="Eliminar empresa">
                                        <span class="material-symbols-outlined">delete</span>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <p class="empty-table" id="emptyEmp" style="display:<?= empty($empresasCompletas) ? 'flex' : 'none' ?>;">
                <span class="material-symbols-outlined">search_off</span>
                No se encontraron empresas que coincidan con tu búsqueda.
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

                <div class="form-group" id="grupoAEPassword" style="text-align:left;">
                    <label for="aePassword">Contraseña</label>
                    <div class="input-icon">
                        <span class="material-symbols-outlined">lock</span>
                        <input type="password" id="aePassword" placeholder="Mínimo 8 caracteres" autocomplete="new-password">
                    </div>
                    <small class="field-error" id="errorAEPassword"></small>
                </div>

                <div class="form-group" style="text-align:left;">
                    <label for="aeEmpresa">Empresa asignada</label>
                    <div class="input-icon">
                        <span class="material-symbols-outlined">domain</span>
                        <select id="aeEmpresa">
                            <option value="">Selecciona una empresa</option>
                            <?php foreach ($empresasDisponibles as $emp): ?>
                                <option value="<?= (int)$emp['id_empresa'] ?>"><?= htmlspecialchars($emp['nombre']) ?></option>
                            <?php endforeach; ?>
                        </select>
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

    <!-- Modal: crear / editar Empresa -->
    <div class="modal" id="modalEmpresa">
        <div class="modal-content modal-wide">
            <button class="modal-close" id="modalEmpresaCerrar">×</button>
            <div class="modal-icon"><span class="material-symbols-outlined">domain</span></div>
            <h3 id="modalEmpresaTitulo">Nueva Empresa</h3>
            <p id="modalEmpresaSub">Completa los datos para registrar una nueva empresa cliente.</p>

            <form id="formEmpresa" novalidate>
                <input type="hidden" id="empId">

                <div class="form-group" style="text-align:left;">
                    <label for="empNombre">Nombre de la empresa</label>
                    <div class="input-icon">
                        <span class="material-symbols-outlined">domain</span>
                        <input type="text" id="empNombre" placeholder="Ej. Grupo Andino">
                    </div>
                    <small class="field-error" id="errorEmpNombre"></small>
                </div>

                <div class="form-group" style="text-align:left;">
                    <label for="empCorreo">Correo de contacto</label>
                    <div class="input-icon">
                        <span class="material-symbols-outlined">mail</span>
                        <input type="email" id="empCorreo" placeholder="contacto@empresa.com">
                    </div>
                    <small class="field-error" id="errorEmpCorreo"></small>
                </div>

                <div class="form-row">
                    <div class="form-group" style="text-align:left;">
                        <label for="empTelefono">Teléfono</label>
                        <div class="input-icon">
                            <span class="material-symbols-outlined">call</span>
                            <input type="tel" id="empTelefono" placeholder="Solo números, ej. 41234567">
                        </div>
                        <small class="field-error" id="errorEmpTelefono"></small>
                    </div>

                    <div class="form-group" style="text-align:left;">
                        <label for="empEstado">Estado</label>
                        <div class="input-icon">
                            <span class="material-symbols-outlined">toggle_on</span>
                            <select id="empEstado">
                                <option value="Activo">Activo</option>
                                <option value="Inactivo">Inactivo</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="modal-actions">
                    <button type="button" class="btn btn-light" id="btnCancelarEmpresa">Cancelar</button>
                    <button type="submit" class="btn btn-primary" id="btnGuardarEmpresa">
                        <span class="material-symbols-outlined">save</span> Guardar Empresa
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal de confirmación reutilizable (empresas) -->
    <div class="modal" id="modalConfirmarEmp">
        <div class="modal-content">
            <button class="modal-close" id="modalConfirmarEmpCerrar">×</button>
            <div class="modal-icon"><span class="material-symbols-outlined">help</span></div>
            <h3 id="modalConfirmarEmpTitulo">Confirmar acción</h3>
            <p id="modalConfirmarEmpMensaje">¿Deseas continuar con esta acción?</p>
            <div class="modal-actions">
                <button class="btn btn-light" id="modalConfirmarEmpCancelar">Cancelar</button>
                <button class="btn btn-danger" id="modalConfirmarEmpAceptar">Confirmar</button>
            </div>
        </div>
    </div>

    <div class="toast-container" id="toastContainer"></div>

    <script src="../../js/admin_empresas.js"></script>
    <script src="../../js/dashboard_admin.js"></script>
</body>
</html>
