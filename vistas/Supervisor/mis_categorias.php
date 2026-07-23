<?php
define('ROL_REQUERIDO', 4);
require_once '../../seguridad.php';
require_once '../../conexion.php';
$nombreUsuario = htmlspecialchars($_SESSION['nombre']);
$idUsuario     = (int)$_SESSION['usuario_id'];

$idEmpresaSupervisor = 0;
$stmtEmp = $conn->prepare("SELECT id_empresa FROM usuario WHERE id_usuario = ? LIMIT 1");
$stmtEmp->bind_param('i', $idUsuario);
$stmtEmp->execute();
$rowEmp = $stmtEmp->get_result()->fetch_assoc();
$stmtEmp->close();
if ($rowEmp) $idEmpresaSupervisor = (int)$rowEmp['id_empresa'];

// ---- AJAX ----
if (isset($_GET['ajax'])) {
    header('Content-Type: application/json; charset=utf-8');
    $accion = $_POST['accion'] ?? $_GET['accion'] ?? '';

    function responderCat($ok, $data = []) {
        echo json_encode(array_merge(['ok' => $ok], $data));
        exit;
    }

    switch ($accion) {
        case 'crear':
            $nombre = trim($_POST['nombre'] ?? '');
            if ($nombre === '') responderCat(false, ['mensaje' => 'El nombre de la categoría es obligatorio.']);

            $stmtChk = $conn->prepare("SELECT id_categoria FROM categoria WHERE nombre = ? LIMIT 1");
            $stmtChk->bind_param('s', $nombre);
            $stmtChk->execute();
            if ($stmtChk->get_result()->fetch_assoc()) {
                responderCat(false, ['mensaje' => 'Ya existe una categoría con ese nombre.']);
            }

            $stmt = $conn->prepare("INSERT INTO categoria (nombre) VALUES (?)");
            $stmt->bind_param('s', $nombre);
            $ok = $stmt->execute();
            responderCat($ok, ['mensaje' => 'Categoría creada correctamente.', 'id' => $conn->insert_id]);
            break;

        case 'editar':
            $id     = (int)($_POST['id'] ?? 0);
            $nombre = trim($_POST['nombre'] ?? '');
            if ($id <= 0 || $nombre === '') responderCat(false, ['mensaje' => 'El nombre de la categoría es obligatorio.']);

            $stmtChk = $conn->prepare("SELECT id_categoria FROM categoria WHERE nombre = ? AND id_categoria != ? LIMIT 1");
            $stmtChk->bind_param('si', $nombre, $id);
            $stmtChk->execute();
            if ($stmtChk->get_result()->fetch_assoc()) {
                responderCat(false, ['mensaje' => 'Ya existe otra categoría con ese nombre.']);
            }

            $stmt = $conn->prepare("UPDATE categoria SET nombre = ? WHERE id_categoria = ?");
            $stmt->bind_param('si', $nombre, $id);
            $ok = $stmt->execute();
            responderCat($ok, ['mensaje' => 'Categoría actualizada correctamente.']);
            break;

        case 'eliminar':
            $id = (int)($_POST['id'] ?? 0);
            if ($id <= 0) responderCat(false, ['mensaje' => 'ID inválido.']);

            $stmtChk = $conn->prepare("SELECT COUNT(*) AS c FROM ticket WHERE id_categoria = ?");
            $stmtChk->bind_param('i', $id);
            $stmtChk->execute();
            $enUso = (int)$stmtChk->get_result()->fetch_assoc()['c'];
            if ($enUso > 0) {
                responderCat(false, ['mensaje' => "No se puede eliminar: tiene $enUso ticket(s) asociados a esta categoría."]);
            }

            $stmt = $conn->prepare("DELETE FROM categoria WHERE id_categoria = ?");
            $stmt->bind_param('i', $id);
            $ok = $stmt->execute();
            responderCat($ok, ['mensaje' => 'Categoría eliminada correctamente.']);
            break;

        case 'asignar':
            $idCategoria = (int)($_POST['id_categoria'] ?? 0);
            $idAgente    = (int)($_POST['id_agente'] ?? 0);
            if ($idCategoria <= 0 || $idAgente <= 0) {
                responderCat(false, ['mensaje' => 'Selecciona una categoría y un agente.']);
            }
            $stmtChk = $conn->prepare("SELECT id FROM asignar_categoria WHERE id_categoria = ? AND id_usuario = ? LIMIT 1");
            $stmtChk->bind_param('ii', $idCategoria, $idAgente);
            $stmtChk->execute();
            if ($stmtChk->get_result()->fetch_assoc()) {
                responderCat(false, ['mensaje' => 'Este agente ya tiene asignada esta categoría.']);
            }
            $stmt = $conn->prepare("INSERT INTO asignar_categoria (id_categoria, id_usuario) VALUES (?, ?)");
            $stmt->bind_param('ii', $idCategoria, $idAgente);
            $ok = $stmt->execute();
            responderCat($ok, ['mensaje' => 'Categoría asignada al agente correctamente.']);
            break;

        case 'desasignar':
            $idCategoria = (int)($_POST['id_categoria'] ?? 0);
            $idAgente    = (int)($_POST['id_agente'] ?? 0);
            if ($idCategoria <= 0 || $idAgente <= 0) responderCat(false, ['mensaje' => 'Datos inválidos.']);
            $stmt = $conn->prepare("DELETE FROM asignar_categoria WHERE id_categoria = ? AND id_usuario = ?");
            $stmt->bind_param('ii', $idCategoria, $idAgente);
            $ok = $stmt->execute();
            responderCat($ok, ['mensaje' => 'Categoría desasignada del agente.']);
            break;

        default:
            responderCat(false, ['mensaje' => 'Acción no reconocida.']);
    }
}

// ---- Categorías + estadísticas reales ----
$categorias = [];
$resCat = $conn->query("
    SELECT c.id_categoria, c.nombre,
           (SELECT COUNT(*) FROM ticket t WHERE t.id_categoria = c.id_categoria) AS tickets,
           (SELECT COUNT(*) FROM asignar_categoria ac WHERE ac.id_categoria = c.id_categoria) AS agentes
    FROM categoria c
    ORDER BY c.nombre ASC
");
while ($row = $resCat->fetch_assoc()) {
    $categorias[] = [
        'id'       => (int)$row['id_categoria'],
        'nombre'   => $row['nombre'],
        'tickets'  => (int)$row['tickets'],
        'agentes'  => (int)$row['agentes'],
    ];
}

// ---- Agentes de la empresa del supervisor, con sus categorías asignadas ----
$agentesConCategorias = [];
$stmtAg = $conn->prepare("
    SELECT u.id_usuario, u.nombre, GROUP_CONCAT(ac.id_categoria) AS cats
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
    $agentesConCategorias[] = [
        'id'     => (int)$row['id_usuario'],
        'nombre' => $row['nombre'],
        'cats'   => $row['cats'] ? array_map('intval', explode(',', $row['cats'])) : [],
    ];
}
$stmtAg->close();

$kpiTotalCat = count($categorias);
$kpiConTickets = count(array_filter($categorias, fn($c) => $c['tickets'] > 0));
$kpiSinAgente = count(array_filter($categorias, fn($c) => $c['agentes'] === 0));
$kpiTicketsTotal = array_sum(array_column($categorias, 'tickets'));
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mis Categorías — ServiceCore</title>
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
            <a href="usuarios_agentes.php" class="menu-item">
                <span class="material-symbols-outlined">group</span>Mis Agentes
            </a>
            <a href="mis_categorias.php" class="menu-item activo">
                <span class="material-symbols-outlined">category</span>Mis Categorías
            </a>
            <a href="../historial.php" class="menu-item">
                <span class="material-symbols-outlined">history</span>Historial
            </a>
        </nav>
    </aside>

    <main class="contenido ml-64 pt-24 px-8 pb-10">
        <section class="mb-8">
            <h2 class="text-4xl font-bold text-[#1e1858]">Mis Categorías</h2>
            <p class="text-gray-500 mt-2">Crea, edita y asigna las categorías de tickets a tus agentes.</p>
        </section>

        <!-- KPIs -->
        <section class="kpi-grid">
            <article class="card kpi primary">
                <div class="kpi-icon"><span class="material-symbols-outlined">category</span></div>
                <div>
                    <p>Total Categorías</p>
                    <h3 data-kpi-cat="total"><?= $kpiTotalCat ?></h3>
                    <span>En el sistema</span>
                </div>
            </article>
            <article class="card kpi blue">
                <div class="kpi-icon"><span class="material-symbols-outlined">confirmation_number</span></div>
                <div>
                    <p>Tickets categorizados</p>
                    <h3 data-kpi-cat="tickets"><?= $kpiTicketsTotal ?></h3>
                    <span>En todas las categorías</span>
                </div>
            </article>
            <article class="card kpi yellow">
                <div class="kpi-icon"><span class="material-symbols-outlined">person_off</span></div>
                <div>
                    <p>Sin agente asignado</p>
                    <h3 data-kpi-cat="sinagente"><?= $kpiSinAgente ?></h3>
                    <span>Requieren asignación</span>
                </div>
            </article>
            <article class="card kpi green">
                <div class="kpi-icon"><span class="material-symbols-outlined">trending_up</span></div>
                <div>
                    <p>Con actividad</p>
                    <h3 data-kpi-cat="activas"><?= $kpiConTickets ?></h3>
                    <span>Con al menos 1 ticket</span>
                </div>
            </article>
        </section>

        <!-- Barra de controles -->
        <section class="controls-bar">
            <div class="controls-left">
                <div class="search-box">
                    <span class="material-symbols-outlined">search</span>
                    <input type="search" id="buscadorCat" placeholder="Buscar categoría...">
                </div>
            </div>
            <div style="display:flex; gap:12px;">
                <button class="btn btn-light" id="btnAsignarCat">
                    <span class="material-symbols-outlined">sell</span> Asignar a Agente
                </button>
                <button class="btn btn-primary" id="btnNuevaCat">
                    <span class="material-symbols-outlined">add</span> Nueva Categoría
                </button>
            </div>
        </section>

        <!-- Listado -->
        <section class="table-wrap card">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Categoría</th>
                        <th>Tickets</th>
                        <th>Agentes asignados</th>
                        <th class="th-actions">Acciones</th>
                    </tr>
                </thead>
                <tbody id="tablaCat">
                    <?php foreach ($categorias as $c): ?>
                        <tr class="cat-row" data-id="<?= $c['id'] ?>" data-nombre="<?= htmlspecialchars($c['nombre']) ?>" data-tickets="<?= $c['tickets'] ?>">
                            <td>
                                <span class="chip-cat" style="background:var(--primary-soft);">
                                    <span class="material-symbols-outlined" style="font-size:16px;">sell</span>
                                    <strong data-row-nombre><?= htmlspecialchars($c['nombre']) ?></strong>
                                </span>
                            </td>
                            <td><span data-row-tickets><?= $c['tickets'] ?></span> ticket(s)</td>
                            <td><span data-row-agentes><?= $c['agentes'] ?></span> agente(s)</td>
                            <td>
                                <div class="row-actions">
                                    <button class="row-icon-btn" data-action="editar" title="Editar categoría">
                                        <span class="material-symbols-outlined">edit</span>
                                    </button>
                                    <button class="row-icon-btn danger" data-action="eliminar" title="Eliminar categoría">
                                        <span class="material-symbols-outlined">delete</span>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <p class="empty-table" id="emptyCat" style="display:<?= empty($categorias) ? 'flex' : 'none' ?>;">
                <span class="material-symbols-outlined">search_off</span>
                No hay categorías registradas todavía.
            </p>
        </section>
    </main>

    <footer class="text-center text-gray-500 text-sm border-t mt-10 p-4 bg-white rounded-xl">
        <p>© 2026 ServiceCore Corporation</p>
    </footer>

    <!-- Modal: crear / editar categoría -->
    <div class="modal" id="modalCat">
        <div class="modal-content">
            <button class="modal-close" id="modalCatCerrar">×</button>
            <div class="modal-icon"><span class="material-symbols-outlined">category</span></div>
            <h3 id="modalCatTitulo">Nueva Categoría</h3>
            <p id="modalCatSub">Completa el nombre de la nueva categoría.</p>

            <form id="formCat" novalidate>
                <input type="hidden" id="catId">
                <div class="form-group" style="text-align:left;">
                    <label for="catNombre">Nombre de la categoría</label>
                    <div class="input-icon">
                        <span class="material-symbols-outlined">sell</span>
                        <input type="text" id="catNombre" placeholder="Ej. Hardware, Software, Redes...">
                    </div>
                    <small class="field-error" id="errorCatNombre"></small>
                </div>
                <div class="modal-actions">
                    <button type="button" class="btn btn-light" id="btnCancelarCat">Cancelar</button>
                    <button type="submit" class="btn btn-primary" id="btnGuardarCat">
                        <span class="material-symbols-outlined">save</span> Guardar Categoría
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal: asignar categoría a agente -->
    <div class="modal" id="modalAsignarCat">
        <div class="modal-content modal-wide">
            <button class="modal-close" id="modalAsignarCatCerrar">×</button>
            <div class="modal-icon"><span class="material-symbols-outlined">sell</span></div>
            <h3>Asignar Categoría a Agente</h3>
            <p>Selecciona un agente y las categorías que atenderá.</p>

            <div class="form-group" style="text-align:left;">
                <label for="selectAgenteCat">Agente</label>
                <div class="input-icon">
                    <span class="material-symbols-outlined">person_search</span>
                    <select id="selectAgenteCat">
                        <option value="">Selecciona un agente</option>
                        <?php foreach ($agentesConCategorias as $a): ?>
                            <option value="<?= $a['id'] ?>" data-cats="<?= htmlspecialchars(implode(',', $a['cats'])) ?>">
                                <?= htmlspecialchars($a['nombre']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="form-group" style="text-align:left;" id="listaCategoriasAsignar">
                <label>Categorías</label>
                <div id="checksCategorias" style="display:flex; flex-wrap:wrap; gap:10px; margin-top:8px;">
                    <?php foreach ($categorias as $c): ?>
                        <label class="chip-cat" style="cursor:pointer; background:#fff; border:1px solid var(--border-color); color:var(--text-dark);">
                            <input type="checkbox" class="chk-categoria" value="<?= $c['id'] ?>" style="margin-right:6px;">
                            <?= htmlspecialchars($c['nombre']) ?>
                        </label>
                    <?php endforeach; ?>
                </div>
                <small class="field-error" id="errorAsignarCat"></small>
            </div>

            <div class="modal-actions">
                <button type="button" class="btn btn-light" id="btnCancelarAsignarCat">Cancelar</button>
                <button type="button" class="btn btn-primary" id="btnGuardarAsignarCat">
                    <span class="material-symbols-outlined">check</span> Guardar Asignación
                </button>
            </div>
        </div>
    </div>

    <!-- Modal de confirmación -->
    <div class="modal" id="modalConfirmarCat">
        <div class="modal-content">
            <button class="modal-close" id="modalConfirmarCatCerrar">×</button>
            <div class="modal-icon"><span class="material-symbols-outlined">help</span></div>
            <h3 id="modalConfirmarCatTitulo">Confirmar acción</h3>
            <p id="modalConfirmarCatMensaje">¿Deseas continuar con esta acción?</p>
            <div class="modal-actions">
                <button class="btn btn-light" id="modalConfirmarCatCancelar">Cancelar</button>
                <button class="btn btn-danger" id="modalConfirmarCatAceptar">Confirmar</button>
            </div>
        </div>
    </div>

    <div class="toast-container" id="toastContainer"></div>

    <script src="../../js/mis_categorias.js"></script>
</body>
</html>
