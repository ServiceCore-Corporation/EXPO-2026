<?php
define('ROL_REQUERIDO', 2); // 2 = Admin de Empresa
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

// ---- AJAX: asignar / desasignar categoría a un agente ----
if (isset($_GET['ajax'])) {
    header('Content-Type: application/json; charset=utf-8');
    $accion = $_POST['accion'] ?? $_GET['accion'] ?? '';

    function responderAsig($ok, $data = []) {
        echo json_encode(array_merge(['ok' => $ok], $data));
        exit;
    }

    switch ($accion) {
        case 'asignar':
            $idCategoria = (int)($_POST['id_categoria'] ?? 0);
            $idAgente    = (int)($_POST['id_agente'] ?? 0);
            if ($idCategoria <= 0 || $idAgente <= 0) {
                responderAsig(false, ['mensaje' => 'Selecciona una categoría y un agente.']);
            }
            $stmtChk = $conn->prepare("SELECT id FROM asignar_categoria WHERE id_categoria = ? AND id_usuario = ? LIMIT 1");
            $stmtChk->bind_param('ii', $idCategoria, $idAgente);
            $stmtChk->execute();
            if ($stmtChk->get_result()->fetch_assoc()) {
                responderAsig(false, ['mensaje' => 'Este agente ya tiene asignada esta categoría.']);
            }
            $stmt = $conn->prepare("INSERT INTO asignar_categoria (id_categoria, id_usuario) VALUES (?, ?)");
            $stmt->bind_param('ii', $idCategoria, $idAgente);
            $ok = $stmt->execute();
            responderAsig($ok, ['mensaje' => 'Categoría asignada correctamente.']);
            break;

        case 'desasignar':
            $idCategoria = (int)($_POST['id_categoria'] ?? 0);
            $idAgente    = (int)($_POST['id_agente'] ?? 0);
            if ($idCategoria <= 0 || $idAgente <= 0) responderAsig(false, ['mensaje' => 'Datos inválidos.']);
            $stmt = $conn->prepare("DELETE FROM asignar_categoria WHERE id_categoria = ? AND id_usuario = ?");
            $stmt->bind_param('ii', $idCategoria, $idAgente);
            $ok = $stmt->execute();
            responderAsig($ok, ['mensaje' => 'Categoría desasignada correctamente.']);
            break;

        default:
            responderAsig(false, ['mensaje' => 'Acción no reconocida.']);
    }
}

// ---- Catálogo real de categorías ----
$categorias = [];
$resCat = $conn->query("SELECT id_categoria, nombre FROM categoria ORDER BY nombre");
while ($row = $resCat->fetch_assoc()) {
    $categorias[] = ['id' => (int)$row['id_categoria'], 'nombre' => $row['nombre']];
}

// ---- Agentes y supervisores reales de la empresa, con sus categorías asignadas ----
$colaboradores = [];
$stmtC = $conn->prepare("
    SELECT u.id_usuario, u.nombre, u.correo, u.activo, r.nombre AS rol,
           GROUP_CONCAT(ac.id_categoria) AS cats
    FROM usuario u
    JOIN rol r ON r.id_rol = u.id_rol
    LEFT JOIN asignar_categoria ac ON ac.id_usuario = u.id_usuario
    WHERE u.id_empresa = ? AND u.id_rol IN (3, 4)
    GROUP BY u.id_usuario
    ORDER BY r.nombre ASC, u.nombre ASC
");
$stmtC->bind_param('i', $idEmpresa);
$stmtC->execute();
$resC = $stmtC->get_result();
while ($row = $resC->fetch_assoc()) {
    $catIds = $row['cats'] ? array_map('intval', explode(',', $row['cats'])) : [];
    $colaboradores[] = [
        'id'     => (int)$row['id_usuario'],
        'nombre' => $row['nombre'],
        'correo' => $row['correo'],
        'rol'    => $row['rol'],
        'activo' => (int)$row['activo'] === 1,
        'cats'   => $catIds,
    ];
}
$stmtC->close();

$kpiTotalColab   = count($colaboradores);
$kpiTotalCat     = count($categorias);
$kpiAsignados    = count(array_filter($colaboradores, fn($c) => count($c['cats']) > 0));
$kpiSinAsignar   = $kpiTotalColab - $kpiAsignados;

function inicialesAsig($nombre) {
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
    <title>Asignar Categoría — ServiceCore</title>
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
            <a href="asignar_categoria.php" class="menu-item activo">
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
            <a href="../../logout.php"class="mt-auto flex items-center justify-center gap-3 w-full py-3 rounded-xl border-2 border-red-500 text-red-400 font-semibold transition-all duration-300 hover:bg-red-500 hover:text-white hover:shadow-lg">
                <span class="material-symbols-outlined">logout</span>
                Cerrar Sesión
            </a>
        </nav>
    </aside>

    <main class="contenido ml-64 pt-24 px-8 pb-10">
        <section class="mb-8">
            <h2 class="text-4xl font-bold text-[#1e1858]">Asignar Categoría</h2>
            <p class="text-gray-500 mt-2">Define qué categorías atiende cada agente o supervisor de tu empresa.</p>
        </section>

        <!-- KPIs -->
        <section class="kpi-grid">
            <article class="card kpi primary">
                <div class="kpi-icon"><span class="material-symbols-outlined">groups</span></div>
                <div>
                    <p>Total Colaboradores</p>
                    <h3><?= $kpiTotalColab ?></h3>
                    <span>Agentes y supervisores</span>
                </div>
            </article>
            <article class="card kpi blue">
                <div class="kpi-icon"><span class="material-symbols-outlined">category</span></div>
                <div>
                    <p>Categorías disponibles</p>
                    <h3><?= $kpiTotalCat ?></h3>
                    <span>En el catálogo</span>
                </div>
            </article>
            <article class="card kpi green">
                <div class="kpi-icon"><span class="material-symbols-outlined">check_circle</span></div>
                <div>
                    <p>Con categoría asignada</p>
                    <h3><?= $kpiAsignados ?></h3>
                    <span>Ya configurados</span>
                </div>
            </article>
            <article class="card kpi yellow">
                <div class="kpi-icon"><span class="material-symbols-outlined">pending_actions</span></div>
                <div>
                    <p>Sin asignar</p>
                    <h3><?= $kpiSinAsignar ?></h3>
                    <span>Cubren todas por defecto</span>
                </div>
            </article>
        </section>

        <!-- Listado de colaboradores -->
        <section class="table-wrap card">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Colaborador</th>
                        <th>Rol</th>
                        <th>Categorías asignadas</th>
                        <th>Estado</th>
                        <th class="th-actions">Acciones</th>
                    </tr>
                </thead>
                <tbody id="tablaAsig">
                    <?php foreach ($colaboradores as $c): ?>
                        <tr class="asig-row" data-id="<?= $c['id'] ?>" data-nombre="<?= htmlspecialchars($c['nombre']) ?>" data-cats="<?= htmlspecialchars(implode(',', $c['cats'])) ?>">
                            <td>
                                <div style="display:flex;align-items:center;gap:10px;">
                                    <div class="avatar small accent-purple"><?= htmlspecialchars(inicialesAsig($c['nombre'])) ?></div>
                                    <div>
                                        <strong><?= htmlspecialchars($c['nombre']) ?></strong><br>
                                        <span style="color:var(--text-muted);font-size:12px;"><?= htmlspecialchars($c['correo']) ?></span>
                                    </div>
                                </div>
                            </td>
                            <td><span class="badge <?= $c['rol'] === 'Supervisor' ? 'badge-blue' : 'badge-green' ?>"><?= htmlspecialchars($c['rol']) ?></span></td>
                            <td data-cell-cats>
                                <?php if ($c['cats']): foreach ($c['cats'] as $cid):
                                    $nombreCat = current(array_column(array_filter($categorias, fn($cc) => $cc['id'] === $cid), 'nombre')) ?: '—';
                                ?>
                                    <span class="chip-cat"><?= htmlspecialchars($nombreCat) ?></span>
                                <?php endforeach; else: ?>
                                    <span style="color:var(--text-muted);">Todas (sin restricción)</span>
                                <?php endif; ?>
                            </td>
                            <td><span class="badge <?= $c['activo'] ? 'badge-green' : 'badge-gray' ?>"><?= $c['activo'] ? 'Activo' : 'Inactivo' ?></span></td>
                            <td>
                                <div class="row-actions">
                                    <button class="row-icon-btn" data-action="editar-asignacion" title="Editar categorías">
                                        <span class="material-symbols-outlined">edit</span>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <p class="empty-table" id="emptyAsig" style="display:<?= empty($colaboradores) ? 'flex' : 'none' ?>;">
                <span class="material-symbols-outlined">search_off</span>
                Todavía no tienes agentes ni supervisores registrados.
            </p>
        </section>
    </main>

    <footer class="text-center text-gray-500 text-sm border-t mt-10 p-4 bg-white rounded-xl">
        <p>© 2026 ServiceCore Corporation</p>
    </footer>

    <!-- Modal: editar categorías de un colaborador -->
    <div class="modal" id="modalAsig">
        <div class="modal-content modal-wide">
            <button class="modal-close" id="modalAsigCerrar">×</button>
            <div class="modal-icon"><span class="material-symbols-outlined">sell</span></div>
            <h3 id="modalAsigTitulo">Editar Categorías</h3>
            <p id="modalAsigSub">Selecciona las categorías que este colaborador atenderá.</p>

            <div style="display:flex; flex-wrap:wrap; gap:10px; margin-top:8px;" id="checksCategoriasAsig">
                <?php foreach ($categorias as $c): ?>
                    <label class="chip-cat" style="cursor:pointer; background:#fff; border:1px solid var(--border-color); color:var(--text-dark);">
                        <input type="checkbox" class="chk-categoria-asig" value="<?= $c['id'] ?>" style="margin-right:6px;">
                        <?= htmlspecialchars($c['nombre']) ?>
                    </label>
                <?php endforeach; ?>
            </div>
            <?php if (empty($categorias)): ?>
                <p style="color:var(--text-muted); margin-top:8px;">
                    No hay categorías creadas todavía. <a href="crear_categorias.php" style="color:var(--primary);">Crea una primero</a>.
                </p>
            <?php endif; ?>

            <div class="modal-actions">
                <button type="button" class="btn btn-light" id="btnCancelarAsig">Cancelar</button>
                <button type="button" class="btn btn-primary" id="btnGuardarAsig">
                    <span class="material-symbols-outlined">check</span> Guardar Asignación
                </button>
            </div>
        </div>
    </div>

    <div class="toast-container" id="toastContainer"></div>

    <script src="../../js/asignar_categoria_emp.js"></script>
</body>
</html>
