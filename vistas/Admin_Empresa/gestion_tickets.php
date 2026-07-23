<?php
define('ROL_REQUERIDO', 2);
require_once '../../seguridad.php';
require_once '../../conexion.php';
$nombreUsuario = htmlspecialchars($_SESSION['nombre']);
$correoUsuario = htmlspecialchars($_SESSION['correo']);
$idUsuarioActual = (int)$_SESSION['usuario_id'];

$idEmpresa = 0;
$stmtEmp = $conn->prepare("SELECT id_empresa FROM usuario WHERE id_usuario = ? LIMIT 1");
$stmtEmp->bind_param('i', $idUsuarioActual);
$stmtEmp->execute();
$rowEmp = $stmtEmp->get_result()->fetch_assoc();
$stmtEmp->close();
if ($rowEmp) $idEmpresa = (int)$rowEmp['id_empresa'];

// ---- AJAX: reasignar agente / cambiar estado (limitado a tickets de la propia empresa) ----
if (isset($_GET['ajax'])) {
    header('Content-Type: application/json; charset=utf-8');
    $accion = $_POST['accion'] ?? $_GET['accion'] ?? '';

    function responderTk($ok, $data = []) {
        echo json_encode(array_merge(['ok' => $ok], $data));
        exit;
    }

    // Verifica que el ticket pertenezca a un cliente de esta empresa antes de tocarlo
    function ticketPerteneceAEmpresa($conn, $idTicket, $idEmpresa) {
        $stmt = $conn->prepare("
            SELECT t.id_ticket FROM ticket t
            JOIN usuario cli ON cli.id_usuario = t.id_usuario_cliente
            WHERE t.id_ticket = ? AND cli.id_empresa = ? LIMIT 1
        ");
        $stmt->bind_param('ii', $idTicket, $idEmpresa);
        $stmt->execute();
        $ok = (bool)$stmt->get_result()->fetch_assoc();
        $stmt->close();
        return $ok;
    }

    switch ($accion) {
        case 'asignar-agente':
            $idTicket = (int)($_POST['id_ticket'] ?? 0);
            $idAgente = (int)($_POST['id_agente'] ?? 0);
            if ($idTicket <= 0 || $idAgente <= 0) responderTk(false, ['mensaje' => 'Selecciona un ticket y un agente.']);
            if (!ticketPerteneceAEmpresa($conn, $idTicket, $idEmpresa)) responderTk(false, ['mensaje' => 'Ticket no encontrado.']);

            $stmt = $conn->prepare("UPDATE ticket SET id_usuario_agente = ?, id_estado = IF(id_estado = 1, 2, id_estado) WHERE id_ticket = ?");
            $stmt->bind_param('ii', $idAgente, $idTicket);
            $ok = $stmt->execute();

            // Registro de auditoría en asignar_ticket (id_supervisor queda como quien realizó la asignación)
            $stmtT = $conn->prepare("SELECT id_usuario_cliente FROM ticket WHERE id_ticket = ?");
            $stmtT->bind_param('i', $idTicket);
            $stmtT->execute();
            $cliente = $stmtT->get_result()->fetch_assoc();
            if ($cliente) {
                $idCliente = (int)$cliente['id_usuario_cliente'];
                $stmtIns = $conn->prepare("INSERT INTO asignar_ticket (id_ticket, id_cliente, id_agente, id_supervisor) VALUES (?, ?, ?, ?)");
                $stmtIns->bind_param('iiii', $idTicket, $idCliente, $idAgente, $idUsuarioActual);
                $stmtIns->execute();
            }

            responderTk($ok, ['mensaje' => 'Ticket asignado correctamente.']);
            break;

        case 'cambiar-estado':
            $idTicket = (int)($_POST['id_ticket'] ?? 0);
            $idEstado = (int)($_POST['id_estado'] ?? 0);
            if ($idTicket <= 0 || $idEstado <= 0) responderTk(false, ['mensaje' => 'Datos inválidos.']);
            if (!ticketPerteneceAEmpresa($conn, $idTicket, $idEmpresa)) responderTk(false, ['mensaje' => 'Ticket no encontrado.']);

            if ($idEstado === 4) { // Cerrado
                $fechaCierre = date('Y-m-d H:i:s');
                $stmt = $conn->prepare("UPDATE ticket SET id_estado = ?, fecha_cierre = ? WHERE id_ticket = ?");
                $stmt->bind_param('isi', $idEstado, $fechaCierre, $idTicket);
            } else {
                $stmt = $conn->prepare("UPDATE ticket SET id_estado = ? WHERE id_ticket = ?");
                $stmt->bind_param('ii', $idEstado, $idTicket);
            }
            $ok = $stmt->execute();
            responderTk($ok, ['mensaje' => 'Estado del ticket actualizado.']);
            break;

        default:
            responderTk(false, ['mensaje' => 'Acción no reconocida.']);
    }
}

// ---- Catálogos ----
$categorias = [];
$resCat = $conn->query("SELECT id_categoria, nombre FROM categoria ORDER BY nombre");
while ($row = $resCat->fetch_assoc()) $categorias[] = $row;

$estados = [];
$resEst = $conn->query("SELECT id_estado, nombre FROM estado ORDER BY id_estado");
while ($row = $resEst->fetch_assoc()) $estados[] = $row;

$agentesEmpresa = [];
$stmtAg = $conn->prepare("SELECT id_usuario, nombre FROM usuario WHERE id_empresa = ? AND id_rol = 3 AND activo = 1 ORDER BY nombre");
$stmtAg->bind_param('i', $idEmpresa);
$stmtAg->execute();
$resAg = $stmtAg->get_result();
while ($row = $resAg->fetch_assoc()) $agentesEmpresa[] = $row;
$stmtAg->close();

// ---- Tickets reales de la empresa ----
$tickets = [];
$stmtTk = $conn->prepare("
    SELECT t.id_ticket, t.titulo, t.fecha_creacion, t.id_estado, t.id_categoria, t.id_usuario_agente,
           e.nombre AS estado, cat.nombre AS categoria, p.nombre AS prioridad,
           cli.nombre AS cliente, ag.nombre AS agente
    FROM ticket t
    JOIN usuario cli ON cli.id_usuario = t.id_usuario_cliente
    LEFT JOIN estado e ON e.id_estado = t.id_estado
    LEFT JOIN categoria cat ON cat.id_categoria = t.id_categoria
    LEFT JOIN prioridad p ON p.id_prioridad = t.id_prioridad
    LEFT JOIN usuario ag ON ag.id_usuario = t.id_usuario_agente
    WHERE cli.id_empresa = ?
    ORDER BY t.fecha_creacion DESC
");
$stmtTk->bind_param('i', $idEmpresa);
$stmtTk->execute();
$resTk = $stmtTk->get_result();
while ($row = $resTk->fetch_assoc()) {
    $tickets[] = [
        'id'         => (int)$row['id_ticket'],
        'titulo'     => $row['titulo'],
        'cliente'    => $row['cliente'],
        'categoria'  => $row['categoria'] ?? 'Sin categoría',
        'idCategoria'=> (int)$row['id_categoria'],
        'agente'     => $row['agente'],
        'idAgente'   => $row['id_usuario_agente'] ? (int)$row['id_usuario_agente'] : null,
        'prioridad'  => $row['prioridad'] ?? 'Media',
        'estado'     => $row['estado'] ?? 'Pendiente',
        'idEstado'   => (int)$row['id_estado'],
        'fecha'      => !empty($row['fecha_creacion']) ? date('d M Y, H:i', strtotime($row['fecha_creacion'])) : '—',
    ];
}
$stmtTk->close();

$kpiTotal      = count($tickets);
$kpiPendientes = count(array_filter($tickets, fn($t) => $t['estado'] === 'Pendiente'));
$kpiProceso    = count(array_filter($tickets, fn($t) => $t['estado'] === 'En proceso'));
$kpiCerrados   = count(array_filter($tickets, fn($t) => $t['estado'] === 'Cerrado'));

function badgeClassTk($text) {
    $map = [
        'Pendiente' => 'badge badge-yellow', 'En proceso' => 'badge badge-blue',
        'Cerrado' => 'badge badge-green', 'Cancelado' => 'badge badge-red',
        'Alta' => 'badge badge-orange', 'Media' => 'badge badge-yellow', 'Baja' => 'badge badge-green', 'Crítica' => 'badge badge-red',
    ];
    return $map[$text] ?? 'badge badge-gray';
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Tickets — ServiceCore</title>
    <link rel="icon" type="image/png" href="../../img/LogoNav.png">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" rel="stylesheet">
    <link rel="stylesheet" href="../../css/asignacion.css">
    <link rel="stylesheet" href="../../css/supervisor_equipo.css">
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
            <a href="dashboard_admin_emp.php" class="menu-item">
                <span class="material-symbols-outlined">dashboard</span>Inicio
            </a>
            <a href="gestion_usuarios.php" class="menu-item">
                <span class="material-symbols-outlined">group</span>Gestion de Usuarios
            </a>
            <a href="crear_categorias.php" class="menu-item">
                <span class="material-symbols-outlined">category</span>Gestion de Categorías
            </a>
            <a href="asignar_categoria.php" class="menu-item">
                <span class="material-symbols-outlined">sell</span>Asignar Categoría
            </a>
            <a href="gestion_tickets.php" class="menu-item activo">
                <span class="material-symbols-outlined">confirmation_number</span>Gestión de Tickets
            </a>
            <a href="reportes.php" class="menu-item">
                <span class="material-symbols-outlined">insights</span>Reportes
            </a>
            <a href="../historial.php" class="menu-item">
                <span class="material-symbols-outlined">history</span>Historial
            </a>

            <div class="flex-grow"></div>
            <a href="../../logout.php"class="mt-auto flex items-center justify-center gap-3 w-full py-3 rounded-xl border-2 border-red-500 text-red-400 font-semibold transition-all duration-300 hover:bg-red-500 hover:text-white hover:shadow-lg">
                <span class="material-symbols-outlined">logout</span>
                Cerrar Sesión
            </a>
        </nav>
    </aside>

    <main class="contenido ml-64 pt-24 px-8 pb-10">
        <section class="mb-8">
            <h2 class="text-4xl font-bold text-[#1e1858]">Gestión de Tickets</h2>
            <p class="text-gray-500 mt-2">Supervisa, asigna y da seguimiento a todos los tickets de tu empresa.</p>
        </section>

        <!-- KPIs -->
        <section class="kpi-grid" id="kpiGridTk">
            <article class="card kpi primary kpi-clickable active" data-filter-estado="">
                <div class="kpi-icon"><span class="material-symbols-outlined">confirmation_number</span></div>
                <div><p>Total Tickets</p><h3><?= $kpiTotal ?></h3><span>De tu empresa</span></div>
            </article>
            <article class="card kpi yellow kpi-clickable" data-filter-estado="Pendiente">
                <div class="kpi-icon"><span class="material-symbols-outlined">pending_actions</span></div>
                <div><p>Pendientes</p><h3><?= $kpiPendientes ?></h3><span>Sin iniciar</span></div>
            </article>
            <article class="card kpi blue kpi-clickable" data-filter-estado="En proceso">
                <div class="kpi-icon"><span class="material-symbols-outlined">sync</span></div>
                <div><p>En Proceso</p><h3><?= $kpiProceso ?></h3><span>Siendo atendidos</span></div>
            </article>
            <article class="card kpi green kpi-clickable" data-filter-estado="Cerrado">
                <div class="kpi-icon"><span class="material-symbols-outlined">check_circle</span></div>
                <div><p>Cerrados</p><h3><?= $kpiCerrados ?></h3><span>Resueltos</span></div>
            </article>
        </section>

        <!-- Controles -->
        <section class="controls-bar">
            <div class="controls-left">
                <div class="search-box">
                    <span class="material-symbols-outlined">search</span>
                    <input type="search" id="buscadorTk" placeholder="Buscar por título o cliente...">
                </div>
                <select id="filterCategoriaTk" class="input-small">
                    <option value="">Todas las categorías</option>
                    <?php foreach ($categorias as $c): ?>
                        <option value="<?= htmlspecialchars($c['nombre']) ?>"><?= htmlspecialchars($c['nombre']) ?></option>
                    <?php endforeach; ?>
                </select>
                <select id="filterPrioridadTk" class="input-small">
                    <option value="">Toda prioridad</option>
                    <option>Baja</option><option>Media</option><option>Alta</option><option>Crítica</option>
                </select>
                <button class="btn btn-light" id="btnLimpiarFiltrosTk">
                    <span class="material-symbols-outlined">refresh</span> Limpiar filtros
                </button>
            </div>
        </section>

        <!-- Tabla -->
        <section class="table-wrap card">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Ticket</th><th>Cliente</th><th>Categoría</th><th>Agente</th>
                        <th>Prioridad</th><th>Estado</th><th>Fecha</th><th class="th-actions">Acciones</th>
                    </tr>
                </thead>
                <tbody id="tablaTk">
                    <?php foreach ($tickets as $t): ?>
                        <tr class="tk-row"
                            data-id="<?= $t['id'] ?>"
                            data-titulo="<?= htmlspecialchars($t['titulo']) ?>"
                            data-cliente="<?= htmlspecialchars($t['cliente']) ?>"
                            data-categoria="<?= htmlspecialchars($t['categoria']) ?>"
                            data-prioridad="<?= htmlspecialchars($t['prioridad']) ?>"
                            data-estado="<?= htmlspecialchars($t['estado']) ?>"
                            data-agente-id="<?= $t['idAgente'] ?? '' ?>"
                        >
                            <td><strong class="text-primary">#TK-<?= str_pad($t['id'], 4, '0', STR_PAD_LEFT) ?></strong><br><span style="color:var(--text-muted);font-size:12px;"><?= htmlspecialchars($t['titulo']) ?></span></td>
                            <td><?= htmlspecialchars($t['cliente']) ?></td>
                            <td><?= htmlspecialchars($t['categoria']) ?></td>
                            <td data-cell-agente><?= $t['agente'] ? htmlspecialchars($t['agente']) : '<span class="badge badge-gray">Sin asignar</span>' ?></td>
                            <td><span class="<?= badgeClassTk($t['prioridad']) ?>"><?= htmlspecialchars($t['prioridad']) ?></span></td>
                            <td>
                                <select class="input-small select-estado-tk" data-id-estado="<?= $t['idEstado'] ?>">
                                    <?php foreach ($estados as $e): ?>
                                        <option value="<?= $e['id_estado'] ?>" <?= $e['id_estado'] == $t['idEstado'] ? 'selected' : '' ?>><?= htmlspecialchars($e['nombre']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                            <td style="white-space:nowrap;"><?= htmlspecialchars($t['fecha']) ?></td>
                            <td>
                                <button class="btn btn-light btn-sm btn-asignar-tk" data-id="<?= $t['id'] ?>">
                                    <span class="material-symbols-outlined">assignment_ind</span>
                                    <?= $t['agente'] ? 'Reasignar' : 'Asignar' ?>
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <p class="empty-table" id="emptyTk" style="display:<?= empty($tickets) ? 'flex' : 'none' ?>;">
                <span class="material-symbols-outlined">search_off</span>
                No se encontraron tickets con esos filtros.
            </p>
        </section>
    </main>

    <footer class="text-center text-gray-500 text-sm border-t mt-10 p-4 bg-white rounded-xl">
        <p>© 2026 ServiceCore Corporation</p>
    </footer>

    <!-- Modal: asignar agente -->
    <div class="modal" id="modalAsignarTk">
        <div class="modal-content">
            <button class="modal-close" id="modalAsignarTkCerrar">×</button>
            <div class="modal-icon"><span class="material-symbols-outlined">assignment_ind</span></div>
            <h3>Asignar Ticket</h3>
            <p id="asignarTkTicketId">#0000</p>

            <div class="form-group" style="text-align:left;">
                <label for="selectAgenteTk">Selecciona un agente</label>
                <div class="input-icon">
                    <span class="material-symbols-outlined">person_search</span>
                    <select id="selectAgenteTk">
                        <option value="">Selecciona un agente</option>
                        <?php foreach ($agentesEmpresa as $a): ?>
                            <option value="<?= $a['id_usuario'] ?>"><?= htmlspecialchars($a['nombre']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <small class="field-error" id="errorAgenteTk"></small>
            </div>

            <div class="modal-actions">
                <button type="button" class="btn btn-light" id="btnCancelarAsignarTk">Cancelar</button>
                <button type="button" class="btn btn-primary" id="btnConfirmarAsignarTk">
                    <span class="material-symbols-outlined">check</span> Confirmar
                </button>
            </div>
        </div>
    </div>

    <div class="toast-container" id="toastContainer"></div>

    <script src="../../js/gestion_tickets_emp.js"></script>
</body>
</html>
