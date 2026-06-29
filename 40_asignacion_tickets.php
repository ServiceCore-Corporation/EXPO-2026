<?php
// En producción esto vendría de la sesión autenticada del supervisor.
$supervisor = [
    'id'     => 'USR-00219',
    'nombre' => 'Alejandro Méndez',
    'rol'    => 'Supervisor',
];

// Catálogo completo de categorías del sistema.
$categoriasCatalogo = [
    ['key' => 'infraestructura', 'icon' => 'dns',  'name' => 'Infraestructura',     'desc' => 'Red, servidores y hardware crítico.'],
    ['key' => 'accesos',         'icon' => 'lock', 'name' => 'Gestión de Accesos',  'desc' => 'Permisos, contraseñas y roles.'],
    ['key' => 'software',        'icon' => 'code', 'name' => 'Software',            'desc' => 'Instalación, errores y actualizaciones.'],
    ['key' => 'soporte',         'icon' => 'help', 'name' => 'Soporte General',     'desc' => 'Consultas internas y solicitudes varias.'],
];

// Categorías ASIGNADAS a este supervisor (vendría de la tabla supervisor_categorias).
$categoriasAsignadasKeys = ['infraestructura', 'accesos'];

$misCategorias = array_values(array_filter(
    $categoriasCatalogo,
    fn($c) => in_array($c['key'], $categoriasAsignadasKeys, true)
));

// Pool completo de tickets del sistema (incluye categorías fuera del alcance del supervisor a propósito,
// para demostrar que la restricción realmente los excluye de esta vista).
$ticketsTodos = [
    ['id' => '#5921', 'asunto' => 'Caída del servidor de producción',        'cliente' => 'Roberto Sánchez',  'catKey' => 'infraestructura', 'cat' => 'Infraestructura',    'agenteId' => null,    'agente' => null,            'estado' => 'Pendiente',  'prio' => 'Urgente', 'fecha' => 'Hoy — 08:10 AM'],
    ['id' => '#5919', 'asunto' => 'Lentitud en red interna',                 'cliente' => 'Banca Central',    'catKey' => 'infraestructura', 'cat' => 'Infraestructura',    'agenteId' => 'AG-01', 'agente' => 'Luis García',   'estado' => 'En proceso', 'prio' => 'Alta',    'fecha' => 'Hoy — 07:40 AM'],
    ['id' => '#5918', 'asunto' => 'Acceso bloqueado a base de datos',        'cliente' => 'Ana Gómez',        'catKey' => 'accesos',         'cat' => 'Gestión de Accesos', 'agenteId' => null,    'agente' => null,            'estado' => 'Pendiente',  'prio' => 'Urgente', 'fecha' => 'Ayer — 05:55 PM'],
    ['id' => '#5912', 'asunto' => 'Restablecer permisos de carpeta compartida','cliente' => 'Recursos Humanos','catKey' => 'accesos',        'cat' => 'Gestión de Accesos', 'agenteId' => 'AG-02', 'agente' => 'María López',   'estado' => 'En proceso', 'prio' => 'Media',   'fecha' => 'Ayer — 02:20 PM'],
    ['id' => '#5908', 'asunto' => 'Renovar certificado de VPN',              'cliente' => 'Sucursal Norte',   'catKey' => 'infraestructura', 'cat' => 'Infraestructura',    'agenteId' => null,    'agente' => null,            'estado' => 'Pendiente',  'prio' => 'Alta',    'fecha' => 'Hace 2 días'],
    ['id' => '#5903', 'asunto' => 'Reseteo de contraseña',                   'cliente' => 'Elena Rodríguez',  'catKey' => 'accesos',         'cat' => 'Gestión de Accesos', 'agenteId' => 'AG-02', 'agente' => 'María López',   'estado' => 'Aprobado',   'prio' => 'Baja',    'fecha' => 'Hace 3 días'],
    // Las dos siguientes pertenecen a categorías que el supervisor NO tiene asignadas.
    ['id' => '#5915', 'asunto' => 'Error en módulo de facturación',         'cliente' => 'Empresa XYZ',      'catKey' => 'software',        'cat' => 'Software',           'agenteId' => 'AG-03', 'agente' => 'Carlos Ruiz',   'estado' => 'En proceso', 'prio' => 'Alta',    'fecha' => 'Hoy — 09:00 AM'],
    ['id' => '#5910', 'asunto' => 'Solicitud de nuevo hardware',             'cliente' => 'Contabilidad',     'catKey' => 'soporte',         'cat' => 'Soporte General',    'agenteId' => 'AG-05', 'agente' => 'Pedro Sánchez', 'estado' => 'Aprobado',   'prio' => 'Media',   'fecha' => 'Hace 4 días'],
];

// === Aplicación real de la restricción ===
$ticketsVisibles = array_values(array_filter(
    $ticketsTodos,
    fn($t) => in_array($t['catKey'], $categoriasAsignadasKeys, true)
));

// Agentes del sistema, cada uno con las categorías que puede atender.
$agentesTodos = [
    ['id' => 'AG-01', 'nombre' => 'Luis García',    'categorias' => ['infraestructura'],            'estado' => 'Activo'],
    ['id' => 'AG-02', 'nombre' => 'María López',    'categorias' => ['accesos', 'infraestructura'], 'estado' => 'Activo'],
    ['id' => 'AG-03', 'nombre' => 'Carlos Ruiz',    'categorias' => ['software'],                   'estado' => 'Activo'],
    ['id' => 'AG-04', 'nombre' => 'Sofía Ramírez',  'categorias' => ['accesos'],                    'estado' => 'Inactivo'],
    ['id' => 'AG-05', 'nombre' => 'Pedro Sánchez',  'categorias' => ['soporte'],                     'estado' => 'Activo'],
];

// Solo interesan al supervisor los agentes que cubren al menos una de sus categorías asignadas.
$misAgentes = array_values(array_filter(
    $agentesTodos,
    fn($a) => count(array_intersect($a['categorias'], $categoriasAsignadasKeys)) > 0
));

// Carga actual de cada agente, contada únicamente sobre los tickets visibles para el supervisor.
foreach ($misAgentes as &$ag) {
    $ag['carga'] = count(array_filter($ticketsVisibles, fn($t) => $t['agenteId'] === $ag['id']));
}
unset($ag);

// KPIs calculados exclusivamente sobre el set ya restringido.
$kpiTotal       = count($ticketsVisibles);
$kpiSinAsignar  = count(array_filter($ticketsVisibles, fn($t) => $t['agenteId'] === null));
$kpiEnProceso   = count(array_filter($ticketsVisibles, fn($t) => $t['estado'] === 'En proceso'));
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
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" rel="stylesheet">
    <link rel="stylesheet" href="asignacion.css">
    <link rel="stylesheet" href="css/override.css">
</head>
<body>

    <aside class="sidebar" id="sidebar">
        <div class="brand">
            <img src="img/logogi.png" alt="ServiceCore Corporation" class="logo">
            <h1>ServiceCore<br>Corporation</h1>
            <p><?= htmlspecialchars($supervisor['rol']) ?></p>
        </div>

        <nav class="menu">
            <a href="40_asignacion_tickets.php" class="menu-item active"><span class="material-symbols-outlined">assignment_ind</span>Asignación Tickets</a>
            <a href="39_mi_perfil.php" class="menu-item"><span class="material-symbols-outlined">account_circle</span>Mi Perfil</a>
            <a href="#categorias" class="menu-item"><span class="material-symbols-outlined">category</span>Mis Categorías</a>
            <a href="#agentes" class="menu-item"><span class="material-symbols-outlined">groups</span>Mis Agentes</a>
            <a href="#historial" class="menu-item"><span class="material-symbols-outlined">history</span>Historial</a>
        </nav>

        <div class="sidebar-box">
            <p class="small-title">Categorías asignadas</p>
            <?php foreach ($misCategorias as $c): ?>
                <span class="mini-tag"><span class="material-symbols-outlined"><?= $c['icon'] ?></span><?= htmlspecialchars($c['name']) ?></span>
            <?php endforeach; ?>
        </div>
    </aside>

    <header class="topbar">
        <button class="icon-btn mobile-only" id="btnSidebar"><span class="material-symbols-outlined">menu</span></button>
        <div>
            <p class="eyebrow">Panel del supervisor</p>
            <h2>Asignación de Tickets</h2>
        </div>
        <div class="top-actions">
            <div class="search-box">
                <span class="material-symbols-outlined">search</span>
                <input type="search" id="buscadorTickets" placeholder="Buscar por ID, asunto o cliente...">
            </div>
           
            <div class="profile" id="profileBtn">
                <div class="avatar"><?= htmlspecialchars(inicialesSup($supervisor['nombre'])) ?></div>
                <div>
                    <strong><?= htmlspecialchars($supervisor['nombre']) ?></strong>
                    <span>ServiceCore Corporation / <?= htmlspecialchars($supervisor['rol']) ?></span>
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
                <span>Asignación de Tickets</span>
            </nav>
            <h1>Asignación de Tickets</h1>
            <p>Asigna tickets a tus agentes dentro de las categorías que tienes bajo tu responsabilidad.</p>
        </section>

        <!-- Aviso de restricción -->
        <div class="restriction-banner">
            <span class="material-symbols-outlined">lock</span>
            <div>
                <strong>Acceso restringido por categoría</strong>
                <p>
                    Solo puedes ver y asignar tickets de tus categorías asignadas:
                    <?php foreach ($misCategorias as $i => $c): ?>
                        <span class="mini-pill"><?= htmlspecialchars($c['name']) ?></span><?= $i < count($misCategorias) - 1 ? '' : '' ?>
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
                                data-cat="<?= htmlspecialchars($t['cat']) ?>"
                                data-catkey="<?= $t['catKey'] ?>"
                                data-estado="<?= $t['estado'] ?>"
                                data-agente-id="<?= $t['agenteId'] ?? '' ?>"
                                data-agente-nombre="<?= htmlspecialchars($t['agente'] ?? '') ?>"
                                data-asunto="<?= htmlspecialchars($t['asunto']) ?>"
                                data-cliente="<?= htmlspecialchars($t['cliente']) ?>"
                                data-prio="<?= $t['prio'] ?>"
                            >
                                <td><strong class="text-primary"><?= $t['id'] ?></strong></td>
                                <td><?= htmlspecialchars($t['asunto']) ?></td>
                                <td><?= htmlspecialchars($t['cliente']) ?></td>
                                <td><?= htmlspecialchars($t['cat']) ?></td>
                                <td><span class="<?= badgeClassSup($t['prio']) ?>"><?= $t['prio'] ?></span></td>
                                <td><span class="<?= badgeClassSup($t['estado']) ?>" data-estado-badge><?= $t['estado'] ?></span></td>
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

        <footer class="footer">© 2026 ServiceCore Corporation — Asignación de Tickets, acceso restringido por categoría.</footer>
    </main>

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
    </script>
    <script src="asignacion.js"></script>
</body>
</html>
