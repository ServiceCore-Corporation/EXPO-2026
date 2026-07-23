<?php
define('ROL_REQUERIDO', 4);
require_once '../../seguridad.php';
require_once '../../conexion.php';
$nombreUsuario = htmlspecialchars($_SESSION['nombre']);
$idUsuario     = (int)$_SESSION['usuario_id'];

// Empresa del supervisor autenticado: los agentes que administra son
// siempre de su misma empresa (multi-tenant, igual que el resto del sistema).
$idEmpresaSupervisor = 0;
$stmtEmp = $conn->prepare("SELECT id_empresa FROM usuario WHERE id_usuario = ? LIMIT 1");
$stmtEmp->bind_param('i', $idUsuario);
$stmtEmp->execute();
$rowEmp = $stmtEmp->get_result()->fetch_assoc();
$stmtEmp->close();
if ($rowEmp) $idEmpresaSupervisor = (int)$rowEmp['id_empresa'];

function inicialesAg($nombre) {
    $partes = explode(' ', trim($nombre));
    $ini = '';
    foreach (array_slice($partes, 0, 2) as $p) { $ini .= mb_substr($p, 0, 1); }
    return mb_strtoupper($ini);
}

// ---- AJAX: CRUD real de Agentes (usuario con id_rol = 3) ----
if (isset($_GET['ajax'])) {
    header('Content-Type: application/json; charset=utf-8');
    $accion = $_POST['accion'] ?? $_GET['accion'] ?? '';

    function responderAg($ok, $data = []) {
        echo json_encode(array_merge(['ok' => $ok], $data));
        exit;
    }

    switch ($accion) {
        case 'crear':
            $nombre   = trim($_POST['nombre'] ?? '');
            $correo   = trim($_POST['correo'] ?? '');
            $telefono = trim($_POST['telefono'] ?? '');
            $password = trim($_POST['password'] ?? '');
            $activo   = ($_POST['estado'] ?? 'Activo') === 'Activo' ? 1 : 0;

            if ($nombre === '' || $correo === '') {
                responderAg(false, ['mensaje' => 'Nombre y correo son obligatorios.']);
            }
            if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
                responderAg(false, ['mensaje' => 'El correo no es válido.']);
            }
            if ($password === '' || strlen($password) < 8) {
                responderAg(false, ['mensaje' => 'La contraseña debe tener al menos 8 caracteres.']);
            }

            $stmtChk = $conn->prepare("SELECT id_usuario FROM usuario WHERE correo = ? LIMIT 1");
            $stmtChk->bind_param('s', $correo);
            $stmtChk->execute();
            if ($stmtChk->get_result()->fetch_assoc()) {
                responderAg(false, ['mensaje' => 'Ya existe un usuario con ese correo.']);
            }

            $hash = password_hash($password, PASSWORD_BCRYPT);
            $stmt = $conn->prepare("
                INSERT INTO usuario (nombre, telefono, correo, pass, id_rol, activo, fecha_creacion, id_empresa)
                VALUES (?, ?, ?, ?, 3, ?, NOW(), ?)
            ");
            $stmt->bind_param('ssssii', $nombre, $telefono, $correo, $hash, $activo, $idEmpresaSupervisor);
            $ok = $stmt->execute();
            responderAg($ok, ['mensaje' => 'Agente creado correctamente.', 'id' => $conn->insert_id]);
            break;

        case 'editar':
            $id       = (int)($_POST['id'] ?? 0);
            $nombre   = trim($_POST['nombre'] ?? '');
            $correo   = trim($_POST['correo'] ?? '');
            $telefono = trim($_POST['telefono'] ?? '');
            $activo   = ($_POST['estado'] ?? 'Activo') === 'Activo' ? 1 : 0;

            if ($id <= 0 || $nombre === '' || $correo === '') {
                responderAg(false, ['mensaje' => 'Nombre y correo son obligatorios.']);
            }
            if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
                responderAg(false, ['mensaje' => 'El correo no es válido.']);
            }

            $stmtChk = $conn->prepare("SELECT id_usuario FROM usuario WHERE correo = ? AND id_usuario != ? LIMIT 1");
            $stmtChk->bind_param('si', $correo, $id);
            $stmtChk->execute();
            if ($stmtChk->get_result()->fetch_assoc()) {
                responderAg(false, ['mensaje' => 'Ya existe otro usuario con ese correo.']);
            }

            $stmt = $conn->prepare("
                UPDATE usuario SET nombre = ?, correo = ?, telefono = ?, activo = ?
                WHERE id_usuario = ? AND id_rol = 3 AND id_empresa = ?
            ");
            $stmt->bind_param('sssiii', $nombre, $correo, $telefono, $activo, $id, $idEmpresaSupervisor);
            $ok = $stmt->execute();
            responderAg($ok, ['mensaje' => 'Agente actualizado correctamente.']);
            break;

        case 'toggle-estado':
            $id     = (int)($_POST['id'] ?? 0);
            $activo = (int)($_POST['activo'] ?? 0);
            if ($id <= 0) responderAg(false, ['mensaje' => 'ID inválido.']);
            $stmt = $conn->prepare("UPDATE usuario SET activo = ? WHERE id_usuario = ? AND id_rol = 3 AND id_empresa = ?");
            $stmt->bind_param('iii', $activo, $id, $idEmpresaSupervisor);
            $ok = $stmt->execute();
            responderAg($ok, ['mensaje' => $activo ? 'Agente reincorporado al equipo.' : 'Agente removido del equipo activo.']);
            break;

        default:
            responderAg(false, ['mensaje' => 'Acción no reconocida.']);
    }
}

// ---- Catálogo de categorías (compartido, según el esquema actual) ----
$categorias = [];
$resCat = $conn->query("SELECT id_categoria, nombre FROM categoria ORDER BY nombre");
while ($row = $resCat->fetch_assoc()) {
    $categorias[$row['id_categoria']] = $row['nombre'];
}

// ---- Agentes reales de la empresa del supervisor ----
$agentes = [];
$stmtAg = $conn->prepare("
    SELECT u.id_usuario, u.nombre, u.correo, u.telefono, u.activo, u.fecha_creacion,
           GROUP_CONCAT(DISTINCT ac.id_categoria) AS categorias
    FROM usuario u
    LEFT JOIN asignar_categoria ac ON ac.id_usuario = u.id_usuario
    WHERE u.id_rol = 3 AND u.id_empresa = ?
    GROUP BY u.id_usuario
    ORDER BY u.nombre ASC
");
$stmtAg->bind_param('i', $idEmpresaSupervisor);
$stmtAg->execute();
$resAg = $stmtAg->get_result();
while ($row = $resAg->fetch_assoc()) {
    $catIds = $row['categorias'] ? explode(',', $row['categorias']) : [];
    $catNombres = array_map(fn($cid) => $categorias[$cid] ?? '—', $catIds);

    // Carga actual: tickets abiertos (no cerrados) asignados a este agente
    $stmtCarga = $conn->prepare("
        SELECT COUNT(*) AS c FROM ticket t
        JOIN estado e ON e.id_estado = t.id_estado
        WHERE t.id_usuario_agente = ? AND e.nombre <> 'Cerrado'
    ");
    $idAgenteActual = (int)$row['id_usuario'];
    $stmtCarga->bind_param('i', $idAgenteActual);
    $stmtCarga->execute();
    $carga = (int)$stmtCarga->get_result()->fetch_assoc()['c'];
    $stmtCarga->close();

    $agentes[] = [
        'id'         => $idAgenteActual,
        'nombre'     => $row['nombre'],
        'correo'     => $row['correo'],
        'telefono'   => $row['telefono'] ?? '',
        'estado'     => ((int)$row['activo'] === 1) ? 'Activo' : 'Inactivo',
        'categorias' => $catNombres,
        'carga'      => $carga,
        'fecha'      => !empty($row['fecha_creacion']) && $row['fecha_creacion'] !== '0000-00-00 00:00:00'
                            ? date('d M Y', strtotime($row['fecha_creacion'])) : '—',
    ];
}
$stmtAg->close();

$kpiTotal     = count($agentes);
$kpiActivos   = count(array_filter($agentes, fn($a) => $a['estado'] === 'Activo'));
$kpiInactivos = $kpiTotal - $kpiActivos;
$kpiCarga     = array_sum(array_column($agentes, 'carga'));

function badgeClassAg($estado) {
    return $estado === 'Activo' ? 'badge badge-green' : 'badge badge-gray';
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mis Agentes — ServiceCore</title>
    <link rel="icon" type="image/png" href="../../img/LogoNav.png">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" rel="stylesheet">
    <link rel="stylesheet" href="../../css/asignacion.css">
    <link rel="stylesheet" href="../../css/override.css">
    <link rel="stylesheet" href="../../css/supervisor_equipo.css">
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
    <aside class="fixed left-0 top-0 w-64 h-full bg-[#1e1858] text-white p-6">
        <div class="flex flex-col items-center mb-8">
            <img src="../../img/logoSC.png" alt="Logo" class="w-20 h-20 object-contain mb-4">
            <h6 class="text-lg font-bold text-center leading-6">ServiceCore<br>Corporation</h6>
        </div>
        <nav class="space-y-2">
            <a href="dashboard_supervisor.php" class="menu-item">
                <span class="material-symbols-outlined">dashboard</span>Inicio
            </a>
            <a href="asignacion_tickets.php" class="menu-item">
                <span class="material-symbols-outlined">assignment_ind</span>Asignación de Tickets
            </a>
            <a href="usuarios_agentes.php" class="menu-item activo">
                <span class="material-symbols-outlined">group</span>Mis Agentes
            </a>
            <a href="mis_categorias.php" class="menu-item">
                <span class="material-symbols-outlined">category</span>Mis Categorías
            </a>
            <a href="../historial.php" class="menu-item">
                <span class="material-symbols-outlined">history</span>Historial
            </a>
        </nav>
    </aside>

    <main class="contenido ml-64 pt-24 px-8 pb-10">
        <section class="mb-8">
            <h2 class="text-4xl font-bold text-[#1e1858]">Mis Agentes</h2>
            <p class="text-gray-500 mt-2">Gestiona el equipo de agentes de tu empresa: agrega, edita, busca y filtra.</p>
        </section>

        <!-- KPIs -->
        <section class="kpi-grid" id="kpiGrid">
            <article class="card kpi primary kpi-clickable active" data-filter-estado="">
                <div class="kpi-icon"><span class="material-symbols-outlined">groups</span></div>
                <div>
                    <p>Total Agentes</p>
                    <h3 data-kpi="total"><?= $kpiTotal ?></h3>
                    <span>En tu empresa</span>
                </div>
            </article>
            <article class="card kpi green kpi-clickable" data-filter-estado="Activo">
                <div class="kpi-icon"><span class="material-symbols-outlined">toggle_on</span></div>
                <div>
                    <p>Activos</p>
                    <h3 data-kpi="activos"><?= $kpiActivos ?></h3>
                    <span>Disponibles para asignar</span>
                </div>
            </article>
            <article class="card kpi gray kpi-clickable" data-filter-estado="Inactivo">
                <div class="kpi-icon"><span class="material-symbols-outlined">toggle_off</span></div>
                <div>
                    <p>Fuera del equipo</p>
                    <h3 data-kpi="inactivos"><?= $kpiInactivos ?></h3>
                    <span>Removidos o deshabilitados</span>
                </div>
            </article>
            <article class="card kpi blue">
                <div class="kpi-icon"><span class="material-symbols-outlined">assignment</span></div>
                <div>
                    <p>Carga total</p>
                    <h3 data-kpi="carga"><?= $kpiCarga ?></h3>
                    <span>Tickets abiertos en el equipo</span>
                </div>
            </article>
        </section>

        <!-- Barra de controles -->
        <section class="controls-bar">
            <div class="controls-left">
                <div class="search-box">
                    <span class="material-symbols-outlined">search</span>
                    <input type="search" id="buscadorAg" placeholder="Buscar por nombre o correo...">
                </div>
                <select id="filterEstadoAg" class="input-small">
                    <option value="">Todos los estados</option>
                    <option value="Activo">Solo activos</option>
                    <option value="Inactivo">Solo inactivos</option>
                </select>
                <button class="btn btn-light" id="btnLimpiarFiltrosAg">
                    <span class="material-symbols-outlined">refresh</span> Limpiar filtros
                </button>
            </div>
            <button class="btn btn-primary" id="btnNuevoAg">
                <span class="material-symbols-outlined">person_add</span> Agregar Agente
            </button>
        </section>

        <!-- Listado -->
        <section class="table-wrap card">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Agente</th>
                        <th>Categorías</th>
                        <th>Carga actual</th>
                        <th>Estado</th>
                        <th>Alta</th>
                        <th class="th-actions">Acciones</th>
                    </tr>
                </thead>
                <tbody id="tablaAg">
                    <?php foreach ($agentes as $a): ?>
                        <tr class="ag-row"
                            data-id="<?= $a['id'] ?>"
                            data-nombre="<?= htmlspecialchars($a['nombre']) ?>"
                            data-correo="<?= htmlspecialchars($a['correo']) ?>"
                            data-telefono="<?= htmlspecialchars($a['telefono']) ?>"
                            data-estado="<?= htmlspecialchars($a['estado']) ?>"
                            data-fecha="<?= htmlspecialchars($a['fecha']) ?>"
                        >
                            <td>
                                <div class="ae-user" style="display:flex;align-items:center;gap:10px;">
                                    <div class="avatar small accent-purple"><?= htmlspecialchars(inicialesAg($a['nombre'])) ?></div>
                                    <div class="ae-user-id">
                                        <strong data-row-nombre><?= htmlspecialchars($a['nombre']) ?></strong><br>
                                        <span data-row-correo style="color:var(--text-muted);font-size:12px;"><?= htmlspecialchars($a['correo']) ?></span>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <?php if ($a['categorias']): foreach ($a['categorias'] as $c): ?>
                                    <span class="chip-cat"><?= htmlspecialchars($c) ?></span>
                                <?php endforeach; else: ?>
                                    <span style="color:var(--text-muted);">Todas</span>
                                <?php endif; ?>
                            </td>
                            <td><span class="carga-badge"><?= $a['carga'] ?> tickets</span></td>
                            <td><span class="<?= badgeClassAg($a['estado']) ?>" data-row-estado-badge><?= htmlspecialchars($a['estado']) ?></span></td>
                            <td><span data-row-fecha><?= htmlspecialchars($a['fecha']) ?></span></td>
                            <td>
                                <div class="row-actions">
                                    <button class="row-icon-btn" data-action="editar" title="Editar agente">
                                        <span class="material-symbols-outlined">edit</span>
                                    </button>
                                    <button class="row-icon-btn toggle-btn" data-action="toggle-estado" title="Agregar / quitar del equipo activo">
                                        <span class="material-symbols-outlined" data-row-toggle-icon><?= $a['estado'] === 'Activo' ? 'toggle_on' : 'toggle_off' ?></span>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <p class="empty-table" id="emptyAg" style="display:<?= empty($agentes) ? 'flex' : 'none' ?>;">
                <span class="material-symbols-outlined">search_off</span>
                No se encontraron agentes que coincidan con tu búsqueda.
            </p>
        </section>
    </main>

    <footer class="text-center text-gray-500 text-sm border-t mt-10 p-4 bg-white rounded-xl">
        <p>© 2026 ServiceCore Corporation</p>
    </footer>

    <!-- Modal: crear / editar Agente -->
    <div class="modal" id="modalAg">
        <div class="modal-content modal-wide">
            <button class="modal-close" id="modalAgCerrar">×</button>
            <div class="modal-icon"><span class="material-symbols-outlined">person_add</span></div>
            <h3 id="modalAgTitulo">Nuevo Agente</h3>
            <p id="modalAgSub">Completa los datos para agregar un nuevo agente a tu equipo.</p>

            <form id="formAg" novalidate>
                <input type="hidden" id="agId">

                <div class="form-group" style="text-align:left;">
                    <label for="agNombre">Nombre completo</label>
                    <div class="input-icon">
                        <span class="material-symbols-outlined">person</span>
                        <input type="text" id="agNombre" placeholder="Nombre y apellidos">
                    </div>
                    <small class="field-error" id="errorAgNombre"></small>
                </div>

                <div class="form-group" style="text-align:left;">
                    <label for="agCorreo">Correo electrónico</label>
                    <div class="input-icon">
                        <span class="material-symbols-outlined">mail</span>
                        <input type="email" id="agCorreo" placeholder="correo@empresa.com">
                    </div>
                    <small class="field-error" id="errorAgCorreo"></small>
                </div>

                <div class="form-group" id="grupoAgPassword" style="text-align:left;">
                    <label for="agPassword">Contraseña</label>
                    <div class="input-icon">
                        <span class="material-symbols-outlined">lock</span>
                        <input type="password" id="agPassword" placeholder="Mínimo 8 caracteres" autocomplete="new-password">
                    </div>
                    <small class="field-error" id="errorAgPassword"></small>
                </div>

                <div class="form-row" style="display:flex; gap:16px;">
                    <div class="form-group" style="text-align:left; flex:1;">
                        <label for="agTelefono">Teléfono (opcional)</label>
                        <div class="input-icon">
                            <span class="material-symbols-outlined">call</span>
                            <input type="text" id="agTelefono" placeholder="+502 0000-0000">
                        </div>
                    </div>

                    <div class="form-group" style="text-align:left; flex:1;">
                        <label for="agEstado">Estado</label>
                        <div class="input-icon">
                            <span class="material-symbols-outlined">toggle_on</span>
                            <select id="agEstado">
                                <option value="Activo">Activo</option>
                                <option value="Inactivo">Inactivo</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="modal-actions">
                    <button type="button" class="btn btn-light" id="btnCancelarAg">Cancelar</button>
                    <button type="submit" class="btn btn-primary" id="btnGuardarAg">
                        <span class="material-symbols-outlined">save</span> Guardar Agente
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal de confirmación reutilizable -->
    <div class="modal" id="modalConfirmarAg">
        <div class="modal-content">
            <button class="modal-close" id="modalConfirmarAgCerrar">×</button>
            <div class="modal-icon"><span class="material-symbols-outlined">help</span></div>
            <h3 id="modalConfirmarAgTitulo">Confirmar acción</h3>
            <p id="modalConfirmarAgMensaje">¿Deseas continuar con esta acción?</p>
            <div class="modal-actions">
                <button class="btn btn-light" id="modalConfirmarAgCancelar">Cancelar</button>
                <button class="btn btn-danger" id="modalConfirmarAgAceptar">Confirmar</button>
            </div>
        </div>
    </div>

    <div class="toast-container" id="toastContainer"></div>

    <script src="../../js/usuarios_agentes.js"></script>
</body>
</html>
