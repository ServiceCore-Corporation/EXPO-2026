<?php
define('ROL_REQUERIDO', 4);
require_once '../../seguridad.php';
require_once '../../conexion.php';
$nombreUsuario = htmlspecialchars($_SESSION['nombre']);
$idUsuario     = (int)$_SESSION['usuario_id'];

// Categorías reales de la base de datos (catálogo compartido por toda la empresa)
$categoriasCatalogo = [];
$resCat = $conn->query("SELECT id_categoria, nombre FROM categoria ORDER BY nombre");
while ($row = $resCat->fetch_assoc()) {
    $categoriasCatalogo[] = [
        'key'  => (string)$row['id_categoria'],
        'icon' => 'category',
        'name' => $row['nombre'],
        'desc' => 'Tickets de la categoría ' . $row['nombre'] . '.',
    ];
}
// El esquema actual no relaciona categorías con supervisores, por lo que un
// supervisor gestiona todas las categorías existentes.
$misCategorias = $categoriasCatalogo;

// Tickets reales, con su agente y cliente ya resueltos por nombre
$ticketsVisibles = [];
$sqlTickets = "
    SELECT t.id_ticket, t.titulo, t.id_categoria, cat.nombre AS categoria,
           t.id_usuario_agente, ag.nombre AS agente,
           t.id_usuario_cliente, cl.nombre AS cliente,
           e.nombre AS estado, p.nombre AS prioridad, t.fecha_creacion
    FROM ticket t
    LEFT JOIN categoria cat ON cat.id_categoria = t.id_categoria
    LEFT JOIN usuario ag    ON ag.id_usuario = t.id_usuario_agente
    LEFT JOIN usuario cl    ON cl.id_usuario = t.id_usuario_cliente
    LEFT JOIN estado e      ON e.id_estado = t.id_estado
    LEFT JOIN prioridad p   ON p.id_prioridad = t.id_prioridad
    ORDER BY t.fecha_creacion DESC
";
$resT = $conn->query($sqlTickets);
while ($row = $resT->fetch_assoc()) {
    $ticketsVisibles[] = [
        'id'         => (int)$row['id_ticket'],
        'idDisplay'  => 'TK-' . str_pad($row['id_ticket'], 4, '0', STR_PAD_LEFT),
        'asunto'     => $row['titulo'],
        'cliente'    => $row['cliente'] ?? 'Sin cliente',
        'clienteId'  => (int)$row['id_usuario_cliente'],
        'catKey'     => (string)$row['id_categoria'],
        'cat'        => $row['categoria'] ?? 'Sin categoría',
        'agenteId'   => $row['id_usuario_agente'] ? (string)$row['id_usuario_agente'] : null,
        'agente'     => $row['agente'],
        'estado'     => $row['estado'] !== null && $row['estado'] !== '' ? $row['estado'] : 'Pendiente',
        'prio'       => $row['prioridad'] ?? 'Media',
        'fecha'      => $row['fecha_creacion'],
    ];
}

// Agentes reales (rol 3) junto con las categorías que atienden (tabla asignar_categoria)
$agentesTodos = [];
$sqlAgentes = "
    SELECT u.id_usuario, u.nombre, u.activo,
           GROUP_CONCAT(ac.id_categoria) AS categorias
    FROM usuario u
    LEFT JOIN asignar_categoria ac ON ac.id_usuario = u.id_usuario
    WHERE u.id_rol = 3
    GROUP BY u.id_usuario, u.nombre, u.activo
    ORDER BY u.nombre
";
$resA = $conn->query($sqlAgentes);
while ($row = $resA->fetch_assoc()) {
    $cats = $row['categorias'] ? explode(',', $row['categorias']) : array_column($categoriasCatalogo, 'key');
    $agentesTodos[] = [
        'id'         => (string)$row['id_usuario'],
        'nombre'     => $row['nombre'],
        'categorias' => $cats,
        'estado'     => ((int)$row['activo'] === 1) ? 'Activo' : 'Inactivo',
    ];
}
$misAgentes = $agentesTodos;

foreach ($misAgentes as &$ag) {
    $ag['carga'] = count(array_filter(
        $ticketsVisibles,
        fn($t) => $t['agenteId'] === $ag['id'] && $t['estado'] !== 'Cerrado'
    ));
}
unset($ag);

$kpiTotal          = count($ticketsVisibles);
$kpiSinAsignar     = count(array_filter($ticketsVisibles, fn($t) => $t['agenteId'] === null));
$kpiEnProceso      = count(array_filter($ticketsVisibles, fn($t) => $t['estado'] === 'En proceso'));
$kpiAgentesActivos = count(array_filter($misAgentes, fn($a) => $a['estado'] === 'Activo'));

function badgeClassSup($text) {
    $map = [
        'Pendiente' => 'badge badge-yellow', 'En proceso' => 'badge badge-blue', 'Aprobado' => 'badge badge-green', 'Rechazado' => 'badge badge-red',
        'Urgente' => 'badge badge-red', 'Alta' => 'badge badge-orange', 'Media' => 'badge badge-yellow', 'Baja' => 'badge badge-green',
        'Activo' => 'badge badge-green', 'Inactivo' => 'badge badge-gray', 'Sin asignar' => 'badge badge-gray',
    ];
    return $map[$text] ?? 'badge badge-gray';
}

function inicialesSup($nombre) {
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
    <title>Asignación de Tickets — ServiceCore</title>
    <link rel="icon" type="image/png" href="../../img/LogoNav.png">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" rel="stylesheet">
    <link rel="stylesheet" href="../../css/asignacion.css">
    <link rel="stylesheet" href="../../css/override.css">
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
    <aside class="fixed left-0 top-0 w-64 h-full bg-[#1e1858] text-white p-6">
        <div class="flex flex-col items-center mb-8">
            <img src="../../img/logoSC.png" alt="Logo" class="w-20 h-20 object-contain mb-4">
            <h6 class="text-lg font-bold text-center leading-6">ServiceCore<br>Corporation</h6>
        </div>
        <nav class="space-y-2">
            <a href="dashboard_supervisor.php" class="menu-item activo">
                <span class="material-symbols-outlined">dashboard</span>Inicio
            </a>
            <a href="asignacion_tickets.php" class="menu-item">
                <span class="material-symbols-outlined">assignment_ind</span>Asignación de Tickets
            </a>
            <a href="usuarios_agentes.php" class="menu-item">
                <span class="material-symbols-outlined">group</span>Mis Agentes
            </a>
            <a href="mis_categorias.php" class="menu-item">
                <span class="material-symbols-outlined">category</span>Mis Categorías
            </a>
            <a href="#" class="menu-item">
                <span class="material-symbols-outlined">history</span>Historial
            </a>
        </nav>
    </aside>

    <main class="contenido ml-64 pt-24 px-8 pb-10">
        <section class="mb-8">
            <h2 class="text-4xl font-bold text-[#1e1858]">Asignación de Tickets</h2>
            <p class="text-gray-500 mt-2">Asigna tickets a tus agentes dentro de las categorías que tienes bajo tu responsabilidad.</p>
        </section>

        <!-- Aviso de alcance -->
        <div class="restriction-banner">
            <span class="material-symbols-outlined">info</span>
            <div>
                <strong>Categorías disponibles</strong>
                <p>
                    Puedes ver y asignar tickets de las siguientes categorías:
                    <?php foreach ($misCategorias as $c): ?>
                        <span class="mini-pill"><?= htmlspecialchars($c['name']) ?></span>
                    <?php endforeach; ?>
                </p>
            </div>
        </div>

        <!-- KPIs -->
        <section class="kpi-grid">
            <article class="card kpi primary">
                <div class="kpi-icon"><span class="material-symbols-outlined">confirmation_number</span></div>
                <div>
                    <p>Tickets en mis categorías</p>
                    <h3><?= $kpiTotal ?></h3>
                    <span>Visibles para tu rol</span>
                </div>
            </article>
            <article class="card kpi yellow">
                <div class="kpi-icon"><span class="material-symbols-outlined">pending_actions</span></div>
                <div>
                    <p>Sin asignar</p>
                    <h3><?= $kpiSinAsignar ?></h3>
                    <span>Requieren un agente</span>
                </div>
            </article>
            <article class="card kpi blue">
                <div class="kpi-icon"><span class="material-symbols-outlined">sync</span></div>
                <div>
                    <p>En proceso</p>
                    <h3><?= $kpiEnProceso ?></h3>
                    <span>Atendidos actualmente</span>
                </div>
            </article>
            <article class="card kpi green">
                <div class="kpi-icon"><span class="material-symbols-outlined">groups</span></div>
                <div>
                    <p>Agentes disponibles</p>
                    <h3><?= $kpiAgentesActivos ?></h3>
                    <span>Activos en tus categorías</span>
                </div>
            </article>
        </section>

        <section class="grid-2">
            <!-- Mis categorías -->
            <article class="card">
                <div class="section-head">
                    <div>
                        <p class="eyebrow">Alcance</p>
                        <h3>Mis Categorías Asignadas</h3>
                    </div>
                </div>
                <div class="category-grid">
                    <?php foreach ($misCategorias as $c):
                        $countCat = count(array_filter($ticketsVisibles, fn($t) => $t['catKey'] === $c['key']));
                    ?>
                        <div class="category-card">
                            <span class="material-symbols-outlined"><?= $c['icon'] ?></span>
                            <strong><?= htmlspecialchars($c['name']) ?></strong>
                            <p><?= htmlspecialchars($c['desc']) ?></p>
                            <small><?= $countCat ?> tickets</small>
                        </div>
                    <?php endforeach; ?>
                </div>
            </article>

            <!-- Mis agentes -->
            <article class="card">
                <div class="section-head">
                    <div>
                        <p class="eyebrow">Equipo</p>
                        <h3>Mis Agentes</h3>
                    </div>
                </div>
                <div class="agent-list" id="agentList">
                    <?php foreach ($misAgentes as $a): ?>
                        <div class="agent-row" data-agent-id="<?= $a['id'] ?>">
                            <div class="avatar small"><?= htmlspecialchars(inicialesSup($a['nombre'])) ?></div>
                            <div class="agent-info">
                                <strong><?= htmlspecialchars($a['nombre']) ?></strong>
                                <span><?= implode(', ', array_map(fn($k) => $categoriasCatalogo[array_search($k, array_column($categoriasCatalogo, 'key'))]['name'], $a['categorias'])) ?></span>
                            </div>
                            <span class="<?= badgeClassSup($a['estado']) ?>"><?= $a['estado'] ?></span>
                            <span class="carga-badge" data-carga-de="<?= $a['id'] ?>"><?= $a['carga'] ?> tickets</span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </article>
        </section>

        <!-- Tabla de tickets restringida -->
        <article class="card" id="ticketsTableCard">
            <div class="section-head">
                <div>
                    <p class="eyebrow">Gestión</p>
                    <h3>Tickets para Asignar</h3>
                </div>
                <div class="inline-actions">
                    <select id="filterCategoria" class="input-small">
                        <option value="">Todas mis categorías</option>
                        <?php foreach ($misCategorias as $c): ?>
                            <option value="<?= htmlspecialchars($c['name']) ?>"><?= htmlspecialchars($c['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <select id="filterEstado" class="input-small">
                        <option value="">Todos los estados</option>
                        <option>Pendiente</option>
                        <option>En proceso</option>
                        <option>Aprobado</option>
                        <option>Rechazado</option>
                    </select>
                    <select id="filterAsignacion" class="input-small">
                        <option value="">Asignado y sin asignar</option>
                        <option value="sin">Solo sin asignar</option>
                        <option value="con">Solo asignados</option>
                    </select>
                </div>
            </div>

            <div class="table-wrap">
                <table class="data-table" id="ticketsTable">
                    <thead>
                        <tr>
                            <th>ID</th><th>Asunto</th><th>Cliente</th><th>Categoría</th>
                            <th>Prioridad</th><th>Estado</th><th>Agente Actual</th><th>Acción</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($ticketsVisibles as $t): ?>
                            <tr
                                data-id="<?= $t['id'] ?>"
                                data-display="<?= htmlspecialchars($t['idDisplay']) ?>"
                                data-cat="<?= htmlspecialchars($t['cat']) ?>"
                                data-catkey="<?= htmlspecialchars($t['catKey']) ?>"
                                data-estado="<?= htmlspecialchars($t['estado']) ?>"
                                data-agente-id="<?= $t['agenteId'] ?? '' ?>"
                                data-agente-nombre="<?= htmlspecialchars($t['agente'] ?? '') ?>"
                                data-asunto="<?= htmlspecialchars($t['asunto']) ?>"
                                data-cliente="<?= htmlspecialchars($t['cliente']) ?>"
                                data-cliente-id="<?= (int)$t['clienteId'] ?>"
                                data-prio="<?= htmlspecialchars($t['prio']) ?>"
                            >
                                <td><strong class="text-primary"><?= htmlspecialchars($t['idDisplay']) ?></strong></td>
                                <td><?= htmlspecialchars($t['asunto']) ?></td>
                                <td><?= htmlspecialchars($t['cliente']) ?></td>
                                <td><?= htmlspecialchars($t['cat']) ?></td>
                                <td><span class="<?= badgeClassSup($t['prio']) ?>"><?= htmlspecialchars($t['prio']) ?></span></td>
                                <td><span class="<?= badgeClassSup($t['estado']) ?>" data-estado-badge><?= htmlspecialchars($t['estado']) ?></span></td>
                                <td data-agente-cell>
                                    <?php if ($t['agente']): ?>
                                        <?= htmlspecialchars($t['agente']) ?>
                                    <?php else: ?>
                                        <span class="<?= badgeClassSup('Sin asignar') ?>">Sin asignar</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <button class="btn btn-primary btn-sm btn-asignar" data-id="<?= $t['id'] ?>">
                                        <span class="material-symbols-outlined">assignment_ind</span>
                                        <?= $t['agente'] ? 'Reasignar' : 'Asignar' ?>
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <p class="empty-state" id="emptyState" style="display:none;">No se encontraron tickets con esos filtros.</p>
            </div>
        </article>
    </main>
    <footer class="text-center text-gray-500 text-sm border-t mt-10 p-4 bg-white rounded-xl">
        <p>© 2026 ServiceCore Corporation — Asignación de Tickets, acceso restringido por categoría.</p>
    </footer>

    <script>
        const botonUsuario = document.getElementById('botonUsuario');
        const menuUsuario = document.getElementById('menuUsuario');
        botonUsuario.addEventListener('click', () => menuUsuario.classList.toggle('hidden'));
        document.addEventListener('click', (e) => {
            if (!botonUsuario.contains(e.target) && !menuUsuario.contains(e.target)) {
                menuUsuario.classList.add('hidden');
            }
        });
    </script>

    <!-- Modal: asignar / reasignar ticket -->
    <div class="modal" id="modalAsignar">
        <div class="modal-content modal-wide">
            <button class="modal-close" id="modalAsignarCerrar">×</button>
            <div class="modal-icon"><span class="material-symbols-outlined">assignment_ind</span></div>
            <h3>Asignar Ticket</h3>
            <p class="modal-ticket-id" id="asignarTicketId">#0000</p>

            <div class="ticket-summary" id="asignarResumen">
                <div><dt>Asunto</dt><dd id="resumenAsunto">—</dd></div>
                <div><dt>Cliente</dt><dd id="resumenCliente">—</dd></div>
                <div><dt>Categoría</dt><dd id="resumenCategoria">—</dd></div>
                <div><dt>Prioridad</dt><dd id="resumenPrioridad">—</dd></div>
                <div id="resumenAgenteActualWrap"><dt>Agente actual</dt><dd id="resumenAgenteActual">—</dd></div>
            </div>

            <form id="formAsignar" novalidate>
                <div class="form-group" style="text-align:left;">
                    <label for="selectAgente">Selecciona un agente</label>
                    <div class="input-icon">
                        <span class="material-symbols-outlined">person_search</span>
                        <select id="selectAgente"></select>
                    </div>
                    <small class="field-error" id="errorAgente"></small>
                </div>

                <div class="form-group" style="text-align:left;">
                    <label for="notaAsignacion">Nota para el agente <span class="optional">(opcional)</span></label>
                    <div class="input-icon">
                        <span class="material-symbols-outlined">edit_note</span>
                        <input type="text" id="notaAsignacion" placeholder="Indicaciones adicionales...">
                    </div>
                </div>

                <div class="modal-actions">
                    <button type="button" class="btn btn-light" id="btnCancelarAsignar">Cancelar</button>
                    <button type="submit" class="btn btn-primary" id="btnConfirmarAsignar">
                        <span class="material-symbols-outlined">check</span> Confirmar Asignación
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal de confirmación reutilizable (reasignación) -->
    <div class="modal" id="modalConfirmar">
        <div class="modal-content">
            <button class="modal-close" id="modalConfirmarCerrar">×</button>
            <div class="modal-icon"><span class="material-symbols-outlined">help</span></div>
            <h3 id="modalConfirmarTitulo">Confirmar acción</h3>
            <p id="modalConfirmarMensaje">¿Deseas continuar con esta acción?</p>
            <div class="modal-actions">
                <button class="btn btn-light" id="modalConfirmarCancelar">Cancelar</button>
                <button class="btn btn-primary" id="modalConfirmarAceptar">Confirmar</button>
            </div>
        </div>
    </div>

    <div class="toast-container" id="toastContainer"></div>
    
    <!-- Datos de agentes disponibles para el front-end (ya pre-filtrados a las categorías del supervisor) -->
    <script>
        window.AGENTES_DISPONIBLES = <?php echo json_encode(array_map(function ($a) {
            return [
                'id' => $a['id'],
                'nombre' => $a['nombre'],
                'categorias' => $a['categorias'],
                'estado' => $a['estado'],
                'carga' => $a['carga'],
            ];
        }, $misAgentes), JSON_UNESCAPED_UNICODE); ?>;
        window.ID_SUPERVISOR_ACTUAL = <?= (int)$supervisor['id'] ?>;
    </script>
    <script src="../../js/api.js"></script>
    <script src="../../js/asignacion.js"></script>
    <script src="../../js/dashboard_supervisor.js"></script>
</body>
</html>

